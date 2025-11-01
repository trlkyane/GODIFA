<?php
// FILE: GODIFA/controller/ChatController.php

// Đảm bảo đường dẫn này đúng
require_once(__DIR__ . "/../model/ChatModel.php");

class ChatController {
    protected $model;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ChatModel(); 
    }
    
    // Hàm này phải tồn tại để admin/pages/chat.php gọi (khắc phục lỗi Call to undefined method)
    public function getAllConversations($currentRoleID) {
        // Lấy danh sách hội thoại từ Model. 'all' cho admin/user.
        $conversations = $this->model->getConversations('all', 'user'); 
        
        return [
            'success' => true,
            'data' => $conversations
        ];
    }
    
    /**
     * Lấy lịch sử chat và đánh dấu đã đọc (Sử dụng AJAX)
     */
    public function getChatHistory($convID) {
        if (!is_numeric($convID) || $convID <= 0) {
            return ['success' => false, 'data' => [], 'message' => 'Conversation ID không hợp lệ'];
        }
        
        // 1. Lấy lịch sử tin nhắn
        $messages = $this->model->getMessagesByConversationID($convID);
        
        // 2. Đánh dấu tất cả tin nhắn khách hàng gửi đã đọc (cho Staff/Admin)
        $this->model->markAsReadForUser($convID);
        
        return [
            'success' => true,
            'data' => $messages,
            'currentUserID' => $_SESSION['user_id'] ?? 0 
        ];
    }

    public function countUnreadMessages() {
        return $this->model->countUnreadConversations('user');
    }
    
    public function countMessages() {
        return $this->model->countMessages();
    }
    
    // Các hàm khác giữ nguyên
}

// Xử lý request AJAX để tải lịch sử chat
if (isset($_GET['action']) && $_GET['action'] === 'getChatHistory') {
    $controller = new ChatController();
    $convID = $_GET['convID'] ?? 0;
    
    header('Content-Type: application/json');
    echo json_encode($controller->getChatHistory($convID));
    exit;
}
?>