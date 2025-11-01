// FILE: GODIFA/websocket-server/server.js

const http = require('http');
const express = require('express');
const { Server } = require('socket.io');
const ChatModel = require('./ChatModel');

const app = express();
const server = http.createServer(app);
const chatModel = new ChatModel();

// Cấu hình CORS
const io = new Server(server, {
    cors: {
        origin: "http://localhost", 
        methods: ["GET", "POST"]
    }
});

io.on('connection', (socket) => {
    console.log(`Socket ID: ${socket.id} Connected.`);
    
    // Hàm xử lý việc lưu và broadcast tin nhắn
    const handleMessage = async (msg) => {
        try {
            const conversation_ID = msg.conversationID; // Sử dụng tên conversationID từ client
            
            if (!conversation_ID) {
                console.error("Lỗi: Không có Conversation ID. Bỏ qua lưu/broadcast.");
                return null;
            }
            
            // LƯU TIN NHẮN VÀO CSDL
            // Model vẫn dùng conversation_ID và content, nên phải ánh xạ dữ liệu
            const chatID = await chatModel.saveMessage({
                conversation_ID: conversation_ID,
                content: msg.chatContent, // Sử dụng chatContent từ client
                senderID: msg.senderID,
                senderType: msg.senderType
            });
            
            if (!chatID) {
                 console.error("Lỗi: Lưu CSDL thất bại (ChatID null/undefined). Bỏ qua broadcast.");
                 return null;
            }

            // Dữ liệu Broadcast cuối cùng
            const finalMessage = {
                chatID: chatID,
                conversationID: conversation_ID, 
                chatContent: msg.chatContent,
                senderID: msg.senderID,
                senderType: msg.senderType,
                date: new Date().toISOString()
            };
            
            // Phát tin nhắn tới tất cả client (Admin và Khách hàng)
            io.emit('new_message', finalMessage); 
            
            return finalMessage;

        } catch (error) {
            console.error('Lỗi xử lý tin nhắn:', error);
            return null;
        }
    }
    
    // -------------------------------------------------------------------
    // SỰ KIỆN 1: KHÁCH HÀNG GỬI TIN NHẮN ĐẦU TIÊN (YÊU CẦU TẠO CONV)
    // -------------------------------------------------------------------
    socket.on('create_new_conversation', async (msg) => {
        try {
            const customerID = msg.senderID; // Khách hàng gửi senderID = customerID
            const conversation_ID = await chatModel.createConversation(customerID);
            
            if (!conversation_ID) {
                console.error("KHÔNG THỂ TẠO CONVERSATION ID. Bỏ qua lưu/broadcast.");
                return;
            }
            
            msg.conversationID = conversation_ID; // Cập nhật ConvID cho payload tin nhắn
            
            // Xử lý lưu và broadcast tin nhắn đầu tiên
            const finalMessage = await handleMessage(msg);
            
            if (finalMessage) {
                // THÔNG BÁO TẠO CONVERSATION THÀNH CÔNG CHO KHÁCH HÀNG
                socket.emit('conversation_created', { conversationID: conversation_ID });

                // THÔNG BÁO CHO ADMIN VỀ HỘI THOẠI MỚI (Tùy chọn)
                io.emit('new conversation', { conversationID: conversation_ID, customerID: customerID });
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

    // ... (các sự kiện khác giữ nguyên) ...
    socket.on('disconnect', () => {
        console.log(`Socket ID: ${socket.id} Disconnected.`);
    });
});

const PORT = 3000;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`Node.js Socket.IO Server đang lắng nghe ở Port ${PORT}`);
}).on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
        console.error(`ERROR: EADDRINUSE: address already in use :::${PORT}. Vui lòng tắt server cũ.`);
    } else {
        console.error('Lỗi khi khởi động server:', err.message);
    }
});