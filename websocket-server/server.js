const http = require('http');
const express = require('express');
const { Server } = require('socket.io');
const ChatModel = require('./ChatModel');
const dotenv = require('dotenv');
dotenv.config({ path: './.env' });

const app = express();
const server = http.createServer(app);
const chatModel = new ChatModel();
let faqs = {}; 
let sortedKeywords = []; // Danh sách từ khóa đã sắp xếp

// 🆕 MAP THEO DÕI NHÂN VIÊN ONLINE CHO MỖI CONVERSATION
// Key: conversationID, Value: Set of staff socket IDs
const staffOnlineByConversation = new Map();

// Cấu hình CORS
const io = new Server(server, {
    cors: {
        origin: "*", 
        methods: ["GET", "POST"]
    }
});

// Load FAQ khi Server khởi động (và sắp xếp)
chatModel.loadFAQs().then(loadedFaqs => {
    faqs = loadedFaqs;
    // Sắp xếp từ khóa theo độ dài GIẢM DẦN để ưu tiên từ khóa dài/cụ thể hơn
    sortedKeywords = Object.keys(faqs).sort((a, b) => b.length - a.length); 
    console.log(`[BOT] Đã tải ${sortedKeywords.length} mục FAQ từ CSDL.`);
    
    // 🆕 Xóa toàn bộ state cũ khi restart server
    staffOnlineByConversation.clear();
    console.log(`[BOT] Đã xóa toàn bộ trạng thái cũ của bot (staffOnlineByConversation cleared).`);
}).catch(e => {
    console.error("Lỗi khi tải FAQ ban đầu:", e);
});

// ==========================================================
// HÀM XỬ LÝ PHẢN HỒI BOT
// LOGIC MỚI: 
// - CÓ NHÂN VIÊN ONLINE → Bot im lặng hoàn toàn
// - KHÔNG CÓ NHÂN VIÊN ONLINE:
//   + Trúng keyword FAQ → Bot trả lời
//   + Không trúng keyword → Thông báo chờ nhân viên
// ==========================================================
/**
 * Xử lý phản hồi của Bot theo logic mới:
 * 
 * 🔹 CÓ NHÂN VIÊN ONLINE (staffOnlineByConversation has entries):
 *    - Bot im lặng HOÀN TOÀN ✅
 * 
 * 🔹 KHÔNG CÓ NHÂN VIÊN ONLINE:
 *    - Có từ khóa FAQ → Bot trả lời ✅
 *    - Không trúng keyword → Bot thông báo "Xin lỗi, tôi chưa được đào tạo..." ⚠️
 */
async function handleBotResponse(io, conversationID, message) {
    // 🔍 KIỂM TRA CÓ NHÂN VIÊN ONLINE KHÔNG
    const staffSockets = staffOnlineByConversation.get(conversationID) || new Set();
    const hasStaffOnline = staffSockets.size > 0;
    
    console.log(`[BOT DEBUG] ConvID ${conversationID}: staffOnline = ${hasStaffOnline} (${staffSockets.size} nhân viên), Message: "${message}"`);

    // ❌ NẾU CÓ NHÂN VIÊN ONLINE → Bot im lặng hoàn toàn
    if (hasStaffOnline) {
        console.log(`[BOT] ConvID ${conversationID}: Có ${staffSockets.size} nhân viên online, Bot im lặng HOÀN TOÀN.`);
        return;
    }

    let botResponse = null;
    let foundKeyword = false;

    // 1. TÌM KIẾM FAQ
    const normalizedMessage = message.toLowerCase().trim();
    
    for (const keyword of sortedKeywords) {
        // Kiểm tra xem tin nhắn có chứa từ khóa nào không
        if (normalizedMessage.includes(keyword)) {
            botResponse = faqs[keyword];
            foundKeyword = true;
            console.log(`[BOT] Phản hồi FAQ (Keyword: "${keyword}") cho ConvID: ${conversationID}`);
            // Đã tìm thấy từ khóa khớp dài nhất, thoát vòng lặp
            break; 
        }
    }
    
    // 2. NẾU TÌM THẤY FAQ → Trả lời
    if (foundKeyword) {
        console.log(`[BOT] ConvID ${conversationID}: Tìm thấy FAQ, trả lời.`);
        // (Lưu và broadcast sẽ ở cuối hàm)
    }
    // 3. NẾU KHÔNG TÌM THẤY FAQ → Thông báo chờ nhân viên
    else {
        console.log(`[BOT] ConvID ${conversationID}: Không trúng keyword, gửi thông báo chờ nhân viên.`);
        botResponse = "Xin lỗi, tôi chưa được đào tạo để trả lời câu hỏi này. Nhân viên sẽ phản hồi bạn sớm nhất có thể (Giờ làm việc là 8h-17h)!";
    }

    // Lưu và gửi phản hồi của Bot (nếu có)
    if (botResponse) { 
        const chatID = await chatModel.saveMessage({
            conversation_ID: conversationID, 
            content: botResponse,
            sender_ID: 0, // ID của Bot
            senderType: 'bot'
        });

        if (chatID) {
            // Dữ liệu gửi qua Socket (dùng camelCase cho client)
            const botMessageData = {
                chatID: chatID,
                conversationID: conversationID,
                chatContent: botResponse, 
                senderID: 0,
                senderType: 'bot',
                date: new Date().toISOString()
            };
            
            // Phát tin nhắn Bot tới phòng chat
            io.to(`conv:${conversationID}`).emit('receive_message', botMessageData);
            console.log(`<<< BROADCAST LOG >>> Gửi tin nhắn BOT (ChatID: ${chatID}) đến phòng conv:${conversationID}`);
        } else {
             console.error("Lỗi: Lưu tin nhắn Bot thất bại (ChatID null/undefined). Bỏ qua broadcast.");
        }
    }
}


io.on('connection', (socket) => {
    console.log(`Socket ID: ${socket.id} Connected. Waiting for authentication...`);
    
    // 🔐 BIẾN THEO DÕI XÁC THỰC
    let isAuthenticated = false;
    let userRole = null;
    let userId = null;
    let userType = null; // 'customer' hoặc 'staff'
    
    // 🔐 SỰ KIỆN XÁC THỰC (BẮT BUỘC)
    socket.on('authenticate', (authData) => {
        console.log('[AUTH] Nhận yêu cầu xác thực:', authData);
        
        const { role_id, user_id, type } = authData;
        
        // Kiểm tra type: chỉ cho phép 'customer' hoặc 'staff'
        if (type !== 'customer' && type !== 'staff') {
            console.error('[AUTH] FAILED: Invalid type:', type);
            socket.emit('auth_failed', { message: 'Invalid user type' });
            socket.disconnect(true);
            return;
        }
        
        // Nếu là staff, PHẢI là CSKH (role_id = 4)
        if (type === 'staff') {
            if (parseInt(role_id) !== 4) {
                console.error(`[AUTH] FAILED: Staff role ${role_id} is not CSKH (4)`);
                socket.emit('auth_failed', { message: 'Chỉ nhân viên CSKH mới có quyền chat!' });
                socket.disconnect(true);
                return;
            }
        }
        
        // Xác thực thành công
        isAuthenticated = true;
        userRole = parseInt(role_id);
        userId = parseInt(user_id);
        userType = type;
        
        console.log(`[AUTH] SUCCESS: User ${userId} (${type}, role: ${userRole}) authenticated`);
        socket.emit('auth_success', { message: 'Authenticated successfully' });
    });
    
    // 🔐 HÀM KIỂM TRA XÁC THỰC TRƯỚC MỖI HÀNH ĐỘNG
    const requireAuth = (action) => {
        if (!isAuthenticated) {
            console.error(`[SECURITY] Unauthorized attempt to ${action} from socket ${socket.id}`);
            socket.emit('auth_required', { message: 'Please authenticate first' });
            return false;
        }
        return true;
    };
    
    // 🔐 HÀM KIỂM TRA CHỈ CSKH (cho một số hành động đặc biệt)
    const requireCSKH = (action) => {
        if (!requireAuth(action)) return false;
        if (userType !== 'staff' || userRole !== 4) {
            console.error(`[SECURITY] Non-CSKH attempt to ${action} from socket ${socket.id}`);
            socket.emit('permission_denied', { message: 'Chỉ CSKH mới có quyền thực hiện hành động này' });
            return false;
        }
        return true;
    };
    
    // Hàm xử lý việc lưu và broadcast tin nhắn
    const handleMessage = async (msg) => {
        // msg nhận từ client thường dùng camelCase: conversationID, senderID, chatContent
        console.log("<<< DIAGNOSTIC LOG >>> Nhận được yêu cầu xử lý tin nhắn với dữ liệu:", msg);

        try {
            // Ép kiểu ID và kiểm tra tính hợp lệ
            const conversationID_int = parseInt(msg.conversationID);
            const senderID_int = parseInt(msg.senderID); 
            const roomName = `conv:${conversationID_int}`;
            
            // Kiểm tra dữ liệu đầu vào nghiêm ngặt
            if (isNaN(conversationID_int) || conversationID_int <= 0 || isNaN(senderID_int) || senderID_int < 0 || !msg.chatContent || msg.chatContent.trim() === "") {
                console.error("LỖI SEND: Dữ liệu gửi tin nhắn không hợp lệ sau khi ép kiểu.", msg);
                return null;
            }

            // LƯU TIN NHẮN VÀO CSDL 
            const chatID = await chatModel.saveMessage({
                conversation_ID: conversationID_int, 
                content: msg.chatContent,
                sender_ID: senderID_int, 
                senderType: msg.senderType // Có thể là 'customer' hoặc 'user'
            });
            
            if (!chatID) {
                console.error("Lỗi: Lưu CSDL thất bại (ChatID null/undefined). Bỏ qua broadcast.");
                return null;
            }

            // Dữ liệu Broadcast cuối cùng (Dùng camelCase cho đồng bộ Client)
            const finalMessage = {
                chatID: chatID,
                conversationID: conversationID_int, 
                chatContent: msg.chatContent,
                senderID: senderID_int,
                senderType: msg.senderType,
                date: new Date().toISOString()
            };
            
            // PHÁT TIN NHẮN TỚI PHÒNG CHAT CỤ THỂ
            io.to(roomName).emit('receive_message', finalMessage); 
            console.log(`<<< BROADCAST LOG >>> Gửi tin nhắn User/Admin (ChatID: ${chatID}) đến phòng ${roomName}`);
            
            // Xử lý phản hồi của Bot (Chỉ khi tin nhắn từ khách hàng)
            if (msg.senderType === 'customer') {
                await handleBotResponse(io, conversationID_int, msg.chatContent);
            }

            return finalMessage;

        } catch (error) {
            console.error('Lỗi xử lý tin nhắn handleMessage:', error);
            return null;
        }
    }
    
    // -------------------------------------------------------------------
    // SỰ KIỆN 1: KHÁCH HÀNG GỬI TIN NHẮN ĐẦU TIÊN (YÊU CẦU TẠO CONV)
    // -------------------------------------------------------------------
    socket.on('create_new_conversation', async (msg) => {
        // 🔐 Kiểm tra xác thực
        if (!requireAuth('create_new_conversation')) return;
        
        // 🔐 Chỉ customer mới được tạo conversation mới
        if (userType !== 'customer') {
            console.error('[SECURITY] Non-customer attempt to create conversation');
            socket.emit('permission_denied', { message: 'Chỉ khách hàng mới có thể tạo hội thoại mới' });
            return;
        }
        
        console.log("--- Nhận sự kiện create_new_conversation ---", msg);
        try {
            const customerID = parseInt(msg.senderID); 

            if (isNaN(customerID) || customerID <= 0) {
                 console.error("LỖI CONV: customerID không hợp lệ hoặc không phải số dương. Bỏ qua.", msg);
                 return;
            }

            // 1. TẠO HOẶC TÌM CONVERSATION
            const conversation_ID = await chatModel.createConversation(customerID);
            
            if (!conversation_ID) {
                console.error("KHÔNG THỂ TẠO CONVERSATION ID. Kiểm tra CSDL/Model.");
                return;
            }
            
            msg.conversationID = conversation_ID; // Cập nhật ConvID cho payload tin nhắn
            
            // 2. THAM GIA PHÒNG CHAT
            const roomName = `conv:${conversation_ID}`;
            socket.join(roomName);
            console.log(`Socket ID: ${socket.id} đã tham gia phòng ${roomName}`);
            
            // 3. Xử lý lưu và broadcast tin nhắn đầu tiên
            const finalMessage = await handleMessage(msg);
            
            if (finalMessage) {
                // THÔNG BÁO TẠO CONVERSATION THÀNH CÔNG CHO KHÁCH HÀNG (Dùng event khác)
                socket.emit('conversation_created', { conversationID: conversation_ID });

                // THÔNG BÁO CHO ADMIN VỀ HỘI THOẠI MỚI 
                // Có thể thêm logic để chỉ broadcast cho các sockets admin đang online
                socket.broadcast.emit('new_conversation_available', { conversationID: conversation_ID, customerID: customerID });
            }

        } catch (error) {
            console.error('Lỗi xử lý create_new_conversation:', error);
        }
    });
    
    // -------------------------------------------------------------------
    // SỰ KIỆN 2: CẢ KHÁCH HÀNG VÀ ADMIN GỬI TIN NHẮN BÌNH THƯỜNG
    // -------------------------------------------------------------------
    socket.on('send_message', async (msg) => {
        // 🔐 Kiểm tra xác thực
        if (!requireAuth('send_message')) return;
        
        // 🔐 Validate senderType
        // - Customer phải dùng 'customer'
        // - Staff có thể dùng 'user' (để lưu vào DB) hoặc 'staff'
        const isValidSenderType = (
            (userType === 'customer' && msg.senderType === 'customer') ||
            (userType === 'staff' && (msg.senderType === 'user' || msg.senderType === 'staff'))
        );
        
        if (!isValidSenderType) {
            console.error(`[SECURITY] senderType mismatch: claimed ${msg.senderType}, actual userType ${userType}`);
            socket.emit('permission_denied', { message: 'Invalid sender type' });
            return;
        }
        
        // Xử lý lưu và broadcast tin nhắn
        await handleMessage(msg);
    });
    
    // -------------------------------------------------------------------
    // SỰ KIỆN 3: ADMIN/USER THAM GIA PHÒNG CHAT (JOIN ROOM)
    // 🆕 Theo dõi nhân viên online khi join vào conversation
    // -------------------------------------------------------------------
    socket.on('join_room', (data) => {
        // 🔐 Kiểm tra xác thực
        if (!requireAuth('join_room')) return;
        
        const conversationID_int = parseInt(data.conversationID);
        if (isNaN(conversationID_int) || conversationID_int <= 0) {
            console.error("LỖI JOIN ROOM: ConversationID không hợp lệ.", data);
            return;
        }
        const roomName = `conv:${conversationID_int}`;
        socket.join(roomName);
        console.log(`Socket ID: ${socket.id} (${userType}, role: ${userRole}) tham gia phòng ${roomName}`);
        
        // 🆕 Nếu là staff (CSKH) join vào -> Thêm vào danh sách online
        if (userType === 'staff') {
            if (!staffOnlineByConversation.has(conversationID_int)) {
                staffOnlineByConversation.set(conversationID_int, new Set());
            }
            staffOnlineByConversation.get(conversationID_int).add(socket.id);
            console.log(`[STAFF] ConvID ${conversationID_int}: CSKH (socket ${socket.id}) join room, Bot sẽ im lặng. Total staff online: ${staffOnlineByConversation.get(conversationID_int).size}`);
        }
    });
    
    // -------------------------------------------------------------------
    // SỰ KIỆN 4: 🆕 NHÂN VIÊN RỜI/ĐÓNG CONVERSATION
    // -------------------------------------------------------------------
    socket.on('staff_leave_conversation', (data) => {
        // 🔐 Kiểm tra xác thực
        if (!requireAuth('staff_leave_conversation')) return;
        
        const conversationID_int = parseInt(data.conversationID);
        if (isNaN(conversationID_int) || conversationID_int <= 0) {
            console.error("LỖI STAFF LEAVE: ConversationID không hợp lệ.", data);
            return;
        }
        
        // Xóa staff khỏi danh sách online
        if (staffOnlineByConversation.has(conversationID_int)) {
            staffOnlineByConversation.get(conversationID_int).delete(socket.id);
            const remainingStaff = staffOnlineByConversation.get(conversationID_int).size;
            
            // Nếu không còn staff nào, xóa entry
            if (remainingStaff === 0) {
                staffOnlineByConversation.delete(conversationID_int);
                console.log(`[STAFF] ConvID ${conversationID_int}: Không còn nhân viên online, Bot sẽ hoạt động trở lại.`);
            } else {
                console.log(`[STAFF] ConvID ${conversationID_int}: Còn ${remainingStaff} nhân viên online, Bot vẫn im lặng.`);
            }
        }
        
        // Rời khỏi phòng
        const roomName = `conv:${conversationID_int}`;
        socket.leave(roomName);
        console.log(`Socket ID: ${socket.id} đã rời phòng ${roomName}`);
    });

    // --- Xử lý ngắt kết nối ---
    socket.on('disconnect', () => {
        console.log(`Socket ID: ${socket.id} Disconnected.`);
        
        // 🆕 Xóa staff khỏi tất cả conversation khi disconnect
        if (userType === 'staff') {
            staffOnlineByConversation.forEach((staffSet, conversationID) => {
                if (staffSet.has(socket.id)) {
                    staffSet.delete(socket.id);
                    console.log(`[STAFF] Socket ${socket.id} removed from ConvID ${conversationID} due to disconnect`);
                    
                    // Xóa entry nếu không còn staff
                    if (staffSet.size === 0) {
                        staffOnlineByConversation.delete(conversationID);
                        console.log(`[STAFF] ConvID ${conversationID}: Không còn nhân viên online, Bot hoạt động trở lại.`);
                    }
                }
            });
        }
    });
});

// Đọc PORT từ biến môi trường để tương thích VPS
const PORT = process.env.PORT || 3000;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`Node.js Socket.IO Server đang lắng nghe ở Port ${PORT}`);
}).on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
        console.error(`ERROR: EADDRINUSE: address already in use :::${PORT}. Vui lòng tắt server cũ.`);
    } else {
        console.error('Lỗi khi khởi động server:', err.message);
    }
});