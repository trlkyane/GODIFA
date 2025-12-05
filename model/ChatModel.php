<?php
// FILE: GODIFA/model/ChatModel.php

class ChatModel {
    private $db;

    public function __construct() {
        try {
            // Sử dụng Database class chung để tương thích VPS
            require_once __DIR__ . '/database.php';
            $dbInstance = Database::getInstance();
            
            // Lấy thông tin kết nối từ Database class
            $reflection = new ReflectionClass('Database');
            $dbHost = $reflection->getConstant('DB_HOST') ?: 'localhost';
            $dbName = $reflection->getConstant('DB_NAME') ?: 'godifa1';
            $dbUser = $reflection->getConstant('DB_USER') ?: 'root';
            $dbPass = $reflection->getConstant('DB_PASS') ?: '';
            
            $this->db = new PDO(
                "mysql:host={$dbHost};dbname={$dbName}",
                $dbUser,
                $dbPass
            );
            
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec("set names utf8"); 
        } catch (\PDOException $e) {
            die("Lỗi kết nối CSDL trong ChatModel: " . $e->getMessage()); 
        }
    }
    
    /**
     * Láº¥y danh sÃ¡ch há»™i thoáº¡i (Äáº£m báº£o hÃ m nÃ y tá»“n táº¡i Ä‘á»ƒ Controller gá»i)
     */
    public function getConversations($userID, $userType) {
        $params = [];
        $whereClause = "";
        
        if ($userID !== 'all') {
            $whereClause = "WHERE c.customerID = ?";
            $params[] = $userID;
        }

        $sql = "
            SELECT 
                c.conversationID, c.customerID, 
                c.last_message_at, 
                c.user_unread_count, c.customer_unread_count,
                cu.customerName, cu.phone,
                (SELECT chatContent FROM chat WHERE conversation_ID = c.conversationID ORDER BY date DESC LIMIT 1) AS lastMessage,
                (SELECT COUNT(*) FROM chat WHERE conversation_ID = c.conversationID) AS totalMessages
            FROM conversation c
            LEFT JOIN customer cu ON c.customerID = cu.customerID 
            {$whereClause}
            ORDER BY c.last_message_at DESC
        ";
        
        try {
            if ($userID !== 'all') { 
                $stmt = $this->db->prepare($sql); 
                $stmt->execute($params); 
            } else { 
                $stmt = $this->db->query($sql); 
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC); 
        } catch (\Exception $e) {
            error_log("Lỗi lấy danh sách hội thoại: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 🚀 HÀM MỚI BỔ SUNG: Tìm Conversation ID gần nhất của Khách hàng
     * Dùng cho việc load lịch sử chat của Khách hàng khi truy cập
     */
    public function findLatestConversationIDByCustomerID($customerID) {
        // Truy vấn tìm Conversation ID (ID hội thoại) có 'last_message_at' mới nhất
        $sql = "
            SELECT 
                conversationID 
            FROM conversation 
            WHERE customerID = ? 
            ORDER BY last_message_at DESC 
            LIMIT 1
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$customerID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Trả về Conversation ID hoặc 0 nếu không tìm thấy
            return $result ? (int)$result['conversationID'] : 0;
        } catch (\Exception $e) {
            error_log("Lỗi tìm Conversation ID gần nhất: " . $e->getMessage());
            return 0;
        }
    }


    /**
     * Lấy lịch sử chat dựa trên Conversation ID
     */
    public function getMessagesByConversationID($convID) {
        $sql = "
            SELECT 
                chatID, chatContent, sender_ID AS senderID, senderType, date
            FROM chat 
            WHERE conversation_ID = ? 
            ORDER BY date ASC
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$convID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Lỗi lấy lịch sử chat: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cập nhật tin nhắn đã đọc (cho nhân viên/admin)
     */
    public function markAsReadForUser($convID) {
        try {
            // Đánh dấu tin nhắn do customer gửi là đã đọc, và reset count cho User
            $stmt = $this->db->prepare("UPDATE conversation SET customer_unread_count = 0 WHERE conversationID = ?");
            $stmt->execute([$convID]);
            return true;
        } catch (\Exception $e) {
            error_log("Lỗi đánh dấu đã đọc: " . $e->getMessage());
            return false;
        }
    }

    public function countUnreadConversations($viewerType) {
        $countField = ($viewerType === 'user') ? 'customer_unread_count' : 'user_unread_count';
        $sql = "SELECT SUM({$countField}) FROM conversation"; 
        
        try {
            $stmt = $this->db->query($sql);
            return (int)$stmt->fetchColumn(); 
        } catch (\Exception $e) {
            error_log("Lỗi đếm tin nhắn chưa đọc: " . $e->getMessage());
            return 0; 
        }
    }
    
    public function countMessages() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM chat");
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            error_log("Lỗi đếm tổng tin nhắn: " . $e->getMessage());
            return 0;
        }
    }
}
?>