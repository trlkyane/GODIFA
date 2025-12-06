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

// 🆕 MAP THEO DÕI TRẠNG THÁI BOT CHO MỖI CUỘC HỘI THOẠI
// Key: conversationID, Value: { hasStaffJoined: boolean }
const conversationStates = new Map();

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
    conversationStates.clear();
    console.log(`[BOT] Đã xóa toàn bộ trạng thái cũ của bot (conversationStates cleared).`);
}).catch(e => {
    console.error("Lỗi khi tải FAQ ban đầu:", e);
});

// ==========================================================
// HÀM KIỂM TRA GIỜ HÀNH CHÍNH
// ==========================================================
/**
 * Kiểm tra xem hiện tại có phải giờ hành chính không
 * Giờ hành chính: 8:00 - 17:00 (Thứ 2 - Chủ nhật)
 * @returns {boolean} true nếu đang trong giờ hành chính
 */
function isBusinessHours() {
    const now = new Date();
    const hour = now.getHours();
    
    // 🔍 DEBUG: Log giờ hiện tại
    console.log(`[TIME CHECK] Giờ hiện tại: ${hour}:${now.getMinutes()}`);
    
    // Giờ hành chính: 8h sáng đến 5h chiều (17h)
    const isWithinHours = hour >= 8 && hour < 17;
    console.log(`[TIME CHECK] Trong giờ hành chính? ${isWithinHours ? 'CÓ ✅' : 'KHÔNG ❌'}`);
    
    return isWithinHours;
}

// ==========================================================
// HÀM XỬ LÝ PHẢN HỒI BOT (CHỈ DÙNG FAQ)
// LOGIC MỚI: Chỉ dựa vào hasStaffJoined
// ==========================================================
/**
 * Xử lý phản hồi của Bot theo logic mới:
 * 
 * 🔹 KHÔNG CÓ ADMIN ONLINE (hasStaffJoined = false):
 *    - Có từ khóa FAQ: Bot trả lời ✅
 *    - Ngoài FAQ: Bot luôn phản hồi "Chờ nhân viên..." (lặp lại mỗi lần hỏi) ⚠️
 * 
 * 🔹 CÓ ADMIN ONLINE (hasStaffJoined = true):
 *    - Bot im lặng HOÀN TOÀN (cả FAQ lẫn ngoài FAQ) 🔇
 */
async function handleBotResponse(io, conversationID, message) {
    // 🔍 KIỂM TRA TRẠNG THÁI CUỘC HỘI THOẠI
    const state = conversationStates.get(conversationID) || { hasStaffJoined: false };
    
    console.log(`[BOT DEBUG] ConvID ${conversationID}: hasStaffJoined = ${state.hasStaffJoined}, Message: "${message}"`);

    // ❌ NẾU ADMIN ĐÃ ONLINE → Bot im lặng hoàn toàn (không phản hồi gì cả)
    if (state.hasStaffJoined) {
        console.log(`[BOT] ConvID ${conversationID}: Admin đã online, Bot im lặng HOÀN TOÀN.`);
        return;
    }

    let botResponse = null;
    let foundKeyword = false;

    // 1. TÌM KIẾM FAQ CỐ ĐỊNH TRƯỚC
    const normalizedMessage = message.toLowerCase().trim();
    
    for (const keyword of sortedKeywords) {
        // Kiểm tra xem tin nhắn có chứa từ khóa nào không
        if (normalizedMessage.includes(keyword)) {
            botResponse = faqs[keyword];
            foundKeyword = true;
            console.log(`[BOT] Phản hồi FAQ cố định (Keyword: ${keyword}) cho ConvID: ${conversationID}`);
            // Đã tìm thấy từ khóa khớp dài nhất, thoát vòng lặp
            break; 
        }
    }
    
    // 2. NẾU TÌM THẤY FAQ → Trả lời
    if (foundKeyword) {
        console.log(`[BOT] ConvID ${conversationID}: Tìm thấy FAQ, trả lời.`);
        // (Lưu và broadcast sẽ ở cuối hàm)
    }
    // 3. NẾU KHÔNG TÌM THẤY FAQ → Gửi "Chờ nhân viên..." (lặp lại mỗi lần)
    else {
        console.log(`[BOT] ConvID ${conversationID}: Chưa có admin, gửi thông báo chờ nhân viên.`);
        botResponse = "Xin lỗi, tôi chưa được đào tạo để trả lời câu hỏi này. Vui lòng chờ nhân viên hỗ trợ sẽ phản hồi bạn sớm nhất có thể! 😊";
    }

    // 3. Lưu và gửi phản hồi của Bot (nếu có)
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
    } else {
        // Bot đã gửi thông báo chờ trước đó, giờ im lặng
        console.log(`[BOT] ConvID ${conversationID}: Đã gửi thông báo chờ trước đó, bỏ qua tin nhắn này.`);
    }
}


io.on('connection', (socket) => {
    console.log(`Socket ID: ${socket.id} Connected.`);
    
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
            
            // 🆕 Đánh dấu nhân viên đã tham gia khi admin/user gửi tin nhắn
            if (msg.senderType === 'user') {
                const state = conversationStates.get(conversationID_int) || { hasStaffJoined: false };
                if (!state.hasStaffJoined) {
                    state.hasStaffJoined = true;
                    conversationStates.set(conversationID_int, state);
                    console.log(`[STAFF] ConvID ${conversationID_int}: Nhân viên đã tham gia, Bot sẽ im lặng với câu ngoài FAQ.`);
                }
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
        // Xử lý lưu và broadcast tin nhắn
        await handleMessage(msg);
    });
    
    // -------------------------------------------------------------------
    // SỰ KIỆN 3: ADMIN/USER THAM GIA PHÒNG CHAT CÓ SẴN (Cho phép nhận tin nhắn)
    // 🆕 Đánh dấu nhân viên đã tham gia khi join room
    // -------------------------------------------------------------------
    socket.on('join_room', (data) => {
        const conversationID_int = parseInt(data.conversationID);
        if (isNaN(conversationID_int) || conversationID_int <= 0) {
            console.error("LỖI JOIN ROOM: ConversationID không hợp lệ.", data);
            return;
        }
        const roomName = `conv:${conversationID_int}`;
        socket.join(roomName);
        console.log(`Socket ID: ${socket.id} tham gia phòng ${roomName} (Quản lý/Khách cũ)`);
        
        // 🆕 Nếu là admin/user join vào -> Đánh dấu nhân viên đã tham gia
        // (Giả sử client gửi thêm trường userType: 'admin' hoặc 'user')
        if (data.userType === 'admin' || data.userType === 'user') {
            const state = conversationStates.get(conversationID_int) || { hasStaffJoined: false };
            if (!state.hasStaffJoined) {
                state.hasStaffJoined = true;
                conversationStates.set(conversationID_int, state);
                console.log(`[STAFF] ConvID ${conversationID_int}: Nhân viên join room, Bot sẽ im lặng với câu ngoài FAQ.`);
            }
        }
    });
    
    // -------------------------------------------------------------------
    // SỰ KIỆN 4: 🆕 NHÂN VIÊN RỜI/ĐÓNG CONVERSATION
    // -------------------------------------------------------------------
    socket.on('staff_leave_conversation', (data) => {
        const conversationID_int = parseInt(data.conversationID);
        if (isNaN(conversationID_int) || conversationID_int <= 0) {
            console.error("LỖI STAFF LEAVE: ConversationID không hợp lệ.", data);
            return;
        }
        
        const state = conversationStates.get(conversationID_int);
        if (state) {
            // Reset cờ để bot hoạt động lại từ đầu
            state.hasStaffJoined = false;
            conversationStates.set(conversationID_int, state);
            console.log(`[STAFF] ConvID ${conversationID_int}: Nhân viên đã rời, Bot hoạt động trở lại. State reset: ${JSON.stringify(state)}`);
        }
        
        // Rời khỏi phòng (optional)
        const roomName = `conv:${conversationID_int}`;
        socket.leave(roomName);
        console.log(`Socket ID: ${socket.id} đã rời phòng ${roomName}`);
    });

    // --- Xử lý ngắt kết nối ---
    socket.on('disconnect', () => {
        console.log(`Socket ID: ${socket.id} Disconnected.`);
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