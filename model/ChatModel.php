<?php
// FILE: GODIFA/model/ChatModel.php

class ChatModel {
    private $db;

    public function __construct() {
        try {
            $this->db = new PDO('mysql:host=localhost;dbname=godifa1', 'root', '');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->exec("set names utf8"); 
        } catch (\PDOException $e) {
            die("Lỗi kết nối CSDL trong ChatModel: " . $e->getMessage()); 
        }
    }
    
    /**
     * Lấy danh sách hội thoại (Đảm bảo hàm này tồn tại để Controller gọi)
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
     * Lấy lịch sử chat dựa trên Conversation ID
     */
    public function getMessagesByConversationID($convID) {
        $sql = "
            SELECT 
                chatID, chatContent, sender_ID, senderType, date
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