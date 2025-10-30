<?php
/**
 * Model: Chat
 * File: model/mChat.php
 * Quản lý tin nhắn chat giữa khách hàng và nhân viên
 */

require_once __DIR__ . '/database.php';

class Chat {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    /**
     * Lấy tất cả hội thoại (group by customer)
     */
    public function getAllConversations() {
        $sql = "SELECT 
                    c.customerID,
                    c.customerName,
                    c.email,
                    c.phone,
                    COUNT(ch.chatID) as totalMessages,
                    MAX(ch.date) as lastMessageDate,
                    (SELECT chatContent FROM chat WHERE fromCustomerID = c.customerID ORDER BY date DESC LIMIT 1) as lastMessage
                FROM customer c
                LEFT JOIN chat ch ON c.customerID = ch.fromCustomerID
                GROUP BY c.customerID
                HAVING totalMessages > 0
                ORDER BY lastMessageDate DESC";
        
        $result = mysqli_query($this->conn, $sql);
        $conversations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $conversations[] = $row;
        }
        return $conversations;
    }
    
    /**
     * Lấy tin nhắn của 1 khách hàng
     * @param int $customerID
     * @return array
     */
    public function getMessagesByCustomer($customerID) {
        $sql = "SELECT 
                    ch.*,
                    c.customerName,
                    u.userName as staffName
                FROM chat ch
                LEFT JOIN customer c ON ch.fromCustomerID = c.customerID
                LEFT JOIN user u ON ch.toUserID = u.userID
                WHERE ch.fromCustomerID = ?
                ORDER BY ch.date ASC";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $messages = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
        return $messages;
    }
    
    /**
     * Gửi tin nhắn từ nhân viên đến khách hàng
     * @param int $customerID
     * @param int $userID
     * @param string $content
     * @return bool
     */
    public function sendMessage($customerID, $userID, $content) {
        $sql = "INSERT INTO chat (chatContent, date, fromCustomerID, toUserID) 
                VALUES (?, NOW(), ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $content, $customerID, $userID);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Xóa tin nhắn
     * @param int $chatID
     * @return bool
     */
    public function deleteMessage($chatID) {
        $sql = "DELETE FROM chat WHERE chatID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $chatID);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Xóa tất cả tin nhắn của 1 khách hàng
     * @param int $customerID
     * @return bool
     */
    public function deleteConversation($customerID) {
        $sql = "DELETE FROM chat WHERE fromCustomerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerID);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Đếm tổng số tin nhắn
     * @return int
     */
    public function countMessages() {
        $sql = "SELECT COUNT(*) as total FROM chat";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    /**
     * Đếm tin nhắn chưa đọc (giả sử tin nhắn mới nhất từ customer là chưa đọc)
     * @return int
     */
    public function countUnreadMessages() {
        $sql = "SELECT COUNT(DISTINCT fromCustomerID) as total 
                FROM chat 
                WHERE toUserID = 0 OR toUserID IS NULL";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    /**
     * Tìm kiếm hội thoại
     * @param string $keyword - Tên khách hàng, email, SĐT
     * @return array
     */
    public function searchConversations($keyword) {
        $keyword = "%$keyword%";
        $sql = "SELECT 
                    c.customerID,
                    c.customerName,
                    c.email,
                    c.phone,
                    COUNT(ch.chatID) as totalMessages,
                    MAX(ch.date) as lastMessageDate
                FROM customer c
                LEFT JOIN chat ch ON c.customerID = ch.fromCustomerID
                WHERE c.customerName LIKE ? 
                   OR c.email LIKE ? 
                   OR c.phone LIKE ?
                GROUP BY c.customerID
                HAVING totalMessages > 0
                ORDER BY lastMessageDate DESC";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $keyword, $keyword, $keyword);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $conversations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $conversations[] = $row;
        }
        return $conversations;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
