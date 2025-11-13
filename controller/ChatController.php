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
     * 🚀 HÀM MỚI: Tìm ID Conversation gần nhất của Khách hàng
     * Cần thiết cho GODIFA/view/chat/index.php để load lịch sử
     */
    public function getLatestConversationIDByCustomerID($customerID) {
        // Giả định ChatModel có hàm findLatestConversationIDByCustomerID($customerID)
        // để truy vấn database và tìm Conversation ID gần nhất
        $convID = $this->model->findLatestConversationIDByCustomerID($customerID); 
        
        // Trả về kết quả, đảm bảo convID là một số hoặc 0
        return [
            'conversationID' => (int)$convID 
        ];
    }
    
    /**
     * Lấy lịch sử chat và đánh dấu đã đọc (Sử dụng AJAX)
     * ĐÃ FIX LỖI: Đảm bảo senderType cho tin nhắn bot
     */
    public function getChatHistory($convID) {
        if (!is_numeric($convID) || $convID <= 0) {
            return ['success' => false, 'data' => [], 'message' => 'Conversation ID không hợp lệ'];
        }
        
        // 1. Lấy lịch sử tin nhắn
        $messages = $this->model->getMessagesByConversationID($convID);
        
        // 🚀 BẮT ĐẦU FIX LỖI: Đảm bảo tin nhắn bot có senderType = 'bot'
        if (!empty($messages)) {
            $BOT_SENDER_ID = 0; // ID bot đã xác nhận là 0
            
            // Dùng tham chiếu (&) để sửa đổi trực tiếp mảng $messages
            foreach ($messages as &$msg) {
                // Kiểm tra nếu senderType bị thiếu hoặc không đúng
                if (!isset($msg['senderType']) || $msg['senderType'] !== 'bot') {
                    
                    // So sánh senderID là 0 (hoặc '0')
                    if (isset($msg['senderID']) && $msg['senderID'] == $BOT_SENDER_ID) { 
                        $msg['senderType'] = 'bot';
                    }
                    
                    // Logic dự phòng để gán các senderType khác (nếu cần thiết)
                    else if (isset($msg['senderID']) && $msg['senderID'] > 0) {
                         // Giả định các ID khác 0 là Staff/User (trừ khi có ID Khách hàng riêng)
                         $msg['senderType'] = 'user';
                    }
                    else {
                        // Nếu senderID = NULL hoặc không xác định và không phải bot, giả định là customer
                        $msg['senderType'] = 'customer';
                    }
                }
            }
            unset($msg); // Bỏ tham chiếu để tránh lỗi
        }
        // 🛑 KẾT THÚC FIX LỖI
        
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
// ⬅️ ĐÃ SỬA LỖI: Đổi action từ 'getChatHistory' thành 'getMessages' để khớp với JS
if (isset($_GET['action']) && $_GET['action'] === 'getMessages') {
    $controller = new ChatController();
    
    // ⬅️ ĐÃ SỬA LỖI: Đổi tham số GET từ 'convID' thành 'conv_id' để khớp với JS
    $convID = $_GET['conv_id'] ?? 0;
    
    $result = $controller->getChatHistory($convID);
    
    // ⬅️ ĐÃ SỬA LỖI: Tái cấu trúc output để khớp với JavaScript mong muốn: {success: true, messages: [...]}
    $output = [
        'success' => $result['success'],
        'messages' => $result['data'] // Đổi key 'data' thành 'messages'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($output);
    exit;
}
?>