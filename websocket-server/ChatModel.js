// FILE: GODIFA/websocket-server/ChatModel.js

const mysql = require('mysql2/promise');

// [QUAN TRỌNG: KẾT NỐI CSDL] Sửa thông tin kết nối CSDL Node.js của bạn
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'godifa1',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

class ChatModel {
    constructor() {
        this.pool = mysql.createPool(dbConfig);
    }
    
    /**
     * TÌM HOẶC TẠO Conversation dựa trên customerID. (Khắc phục lỗi lặp khách hàng)
     */
    async createConversation(customerID) {
        // Kiểm tra customerID phải là số nguyên (hoặc string số)
        if (!customerID || customerID === 'guest' || isNaN(parseInt(customerID))) {
            return null; 
        }
        const conn = await this.pool.getConnection();
        try {
            await conn.beginTransaction();

            // 1. Kiểm tra Conversation đã tồn tại chưa
            const [existing] = await conn.execute(
                'SELECT conversationID FROM conversation WHERE customerID = ? LIMIT 1', 
                [customerID]
            );

            if (existing.length > 0) {
                await conn.commit();
                return existing[0].conversationID;
            }

            // 2. Nếu chưa có, tạo mới
            const [result] = await conn.execute(
                'INSERT INTO conversation (customerID, user_unread_count, customer_unread_count, status, last_message_at) VALUES (?, 0, 0, "open", NOW())', 
                [customerID]
            );

            await conn.commit();
            return result.insertId;
        } catch (error) {
            await conn.rollback();
            console.error("Lỗi khi tạo Conversation:", error.message);
            return null; // Trả về null nếu tạo thất bại
        } finally {
            if (conn) conn.release();
        }
    }
    
    async saveMessage(data) {
        const { conversation_ID, content, senderID, senderType } = data;
        
        if (!senderID || !conversation_ID) {
            console.error("Lỗi: senderID hoặc conversation_ID không hợp lệ. Không thể lưu tin nhắn.");
            return null;
        }
        
        // [ĐÃ SỬA LỖI CỘT] Sử dụng sender_ID
        const sql = `
            INSERT INTO chat (conversation_ID, chatContent, sender_ID, senderType, date, isRead) 
            VALUES (?, ?, ?, ?, NOW(), 0)
        `;
        
        try {
            const [result] = await this.pool.execute(sql, [
                conversation_ID, 
                content, 
                senderID, 
                senderType
            ]);

            // Cập nhật last_message_at và unread count
            // user gửi -> tăng customer_unread_count
            // customer gửi -> tăng user_unread_count
            const receiver_field = (senderType === 'user') ? 'customer_unread_count' : 'user_unread_count';
            
            await this.pool.execute(
                `UPDATE conversation SET last_message_at = NOW(), ${receiver_field} = ${receiver_field} + 1 WHERE conversationID = ?`,
                [conversation_ID]
            );

            return result.insertId;
        } catch (error) {
            console.error("Lỗi nghiêm trọng khi lưu tin nhắn vào CSDL:", { 
                error: error.message, 
                sql: error.sql 
            });
            return null; // Trả về null nếu lưu thất bại
        }
    }
}

module.exports = ChatModel;