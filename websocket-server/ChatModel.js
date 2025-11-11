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
        console.log("MySQL Connection Pool đã được khởi tạo.");
    }
    
    // ==========================================================
    // PHƯƠNG THỨC CHATBOT (FAQ)
    // ==========================================================

    /**
     * Tải tất cả các cặp từ khóa-phản hồi từ bảng chatbot (FAQ cố định).
     * SỬ DỤNG CỘT 'keywords' và 'response_text' (Đã xác nhận).
     * @returns {Object} Một object map: { keyword: response, ... }
     */
    async loadFAQs() {
        try {
            // Lấy keyword và response_text từ bảng chatbot
            const [rows] = await this.pool.execute('SELECT keywords, response_text FROM chatbot');
            const faqsMap = {};
            
            rows.forEach(row => {
                // Đảm bảo chỉ dùng các cặp có cả từ khóa và phản hồi
                if (row.keywords && row.response_text) {
                    // Chuyển từ khóa thành chữ thường và loại bỏ khoảng trắng thừa
                    faqsMap[row.keywords.toLowerCase().trim()] = row.response_text;
                }
            });

            return faqsMap;
        } catch (error) {
            console.error("Lỗi khi tải FAQ từ bảng 'chatbot' (Kiểm tra tên bảng/cột!):", error.message);
            // Trả về object rỗng nếu có lỗi để tránh crash server
            return {}; 
        }
    }

    // ==========================================================
    // PHƯƠNG THỨC CONVERSATION
    // ==========================================================
    
    /**
     * TÌM HOẶC TẠO Conversation dựa trên customerID. (Khắc phục lỗi lặp khách hàng)
     */
    async createConversation(customerID) {
        const parsedCustomerID = parseInt(customerID);

        if (isNaN(parsedCustomerID) || parsedCustomerID <= 0) {
            console.error("LỖI CONV: customerID không hợp lệ hoặc không phải số dương. Không thể tạo Conversation.");
            return null; 
        }

        let conn;
        try {
            conn = await this.pool.getConnection();
            await conn.beginTransaction();

            // 1. Kiểm tra Conversation đã tồn tại chưa và còn MỞ không
            const [existing] = await conn.execute(
                'SELECT conversationID FROM conversation WHERE customerID = ? AND status = "open" LIMIT 1', 
                [parsedCustomerID]
            );

            if (existing.length > 0) {
                await conn.commit();
                return existing[0].conversationID;
            }

            // 2. Nếu chưa có, tạo mới
            const [result] = await conn.execute(
                'INSERT INTO conversation (customerID, user_unread_count, customer_unread_count, status, last_message_at) VALUES (?, 0, 0, "open", NOW())', 
                [parsedCustomerID]
            );

            await conn.commit();
            return result.insertId;
        } catch (error) {
            if (conn) await conn.rollback();
            console.error("Lỗi nghiêm trọng khi tạo Conversation:", error.message);
            return null; 
        } finally {
            if (conn) conn.release();
        }
    }
    
    // ==========================================================
    // PHƯƠNG THỨC TIN NHẮN
    // ==========================================================

    async saveMessage(data) {
        // [QUAN TRỌNG] Destructuring sử dụng tên cột CSDL (snake_case)
        const { conversation_ID, content, sender_ID, senderType } = data; 
        
        // Chuyển đổi và kiểm tra tính hợp lệ của ID
        const conversationID_int = parseInt(conversation_ID);
        const sender_ID_int = parseInt(sender_ID); 

        // KIỂM TRA CHẶT CHẼ DỮ LIỆU ĐẦU VÀO
        if (isNaN(conversationID_int) || conversationID_int <= 0 || 
            isNaN(sender_ID_int) || sender_ID_int < 0 || 
            !content || content.trim() === "") {
            
            console.error("LỖI DỮ LIỆU CHATMODEL: Dữ liệu gửi tin nhắn không hợp lệ.", { 
                conversationID: conversation_ID, 
                senderID: sender_ID, 
                senderType: senderType, 
                content: content 
            });
            return null;
        }
        
        // CỘT SQL: conversation_ID, chatContent, sender_ID, senderType
        const sql = `
            INSERT INTO chat (conversation_ID, chatContent, sender_ID, senderType, date, isRead) 
            VALUES (?, ?, ?, ?, NOW(), 0)
        `;
        
        let conn;
        try {
            conn = await this.pool.getConnection();
            await conn.beginTransaction();

            const [result] = await conn.execute(sql, [
                conversationID_int, 
                content, 
                sender_ID_int, // Binds sender_ID_int
                senderType
            ]);

            // Cập nhật last_message_at và unread count
            // Giả định 'customer' gửi -> tăng 'user_unread_count' (admin)
            // Ngược lại, 'user'/'bot' gửi -> tăng 'customer_unread_count'
            const receiver_field = (senderType === 'customer') ? 'user_unread_count' : 'customer_unread_count';
            
            await conn.execute(
                `UPDATE conversation SET last_message_at = NOW(), ${receiver_field} = ${receiver_field} + 1 WHERE conversationID = ?`,
                [conversationID_int]
            );

            await conn.commit();
            return result.insertId;
        } catch (error) {
            if (conn) await conn.rollback();
            console.error("Lỗi nghiêm trọng khi lưu tin nhắn vào CSDL:", { 
                error: error.message, 
                sql: error.sql 
            });
            return null; 
        } finally {
             if (conn) conn.release();
        }
    }
}

module.exports = ChatModel;