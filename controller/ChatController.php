<?php
// FILE: GODIFA/controller/ChatController.php

// Äáº£m báº£o Ä‘Æ°á»ng dáº«n nÃ y Ä‘Ãºng
require_once(__DIR__ . "/../model/ChatModel.php");

class ChatController {
    protected $model;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ChatModel(); 
    }
    
    // HÃ m nÃ y pháº£i tá»“n táº¡i Ä‘á»ƒ admin/pages/chat.php gá»i (kháº¯c phá»¥c lá»—i Call to undefined method)
    public function getAllConversations($currentRoleID) {
        // Láº¥y danh sÃ¡ch há»™i thoáº¡i tá»« Model. 'all' cho admin/user.
        $conversations = $this->model->getConversations('all', 'user'); 
        
        return [
            'success' => true,
            'data' => $conversations
        ];
    }
    
    /**
     * ðŸš€ HÃ€M Má»šI: TÃ¬m ID Conversation gáº§n nháº¥t cá»§a KhÃ¡ch hÃ ng
     * Cáº§n thiáº¿t cho GODIFA/view/chat/index.php Ä‘á»ƒ load lá»‹ch sá»­
     */
    public function getLatestConversationIDByCustomerID($customerID) {
        // Giáº£ Ä‘á»‹nh ChatModel cÃ³ hÃ m findLatestConversationIDByCustomerID($customerID)
        // Ä‘á»ƒ truy váº¥n database vÃ  tÃ¬m Conversation ID gáº§n nháº¥t
        $convID = $this->model->findLatestConversationIDByCustomerID($customerID); 
        
        // Tráº£ vá» káº¿t quáº£, Ä‘áº£m báº£o convID lÃ  má»™t sá»‘ hoáº·c 0
        return [
            'conversationID' => (int)$convID 
        ];
    }
    
    /**
     * Láº¥y lá»‹ch sá»­ chat vÃ  Ä‘Ã¡nh dáº¥u Ä‘Ã£ Ä‘á»c (Sá»­ dá»¥ng AJAX)
     * ÄÃƒ FIX Lá»–I: Äáº£m báº£o senderType cho tin nháº¯n bot
     */
    public function getChatHistory($convID) {
        if (!is_numeric($convID) || $convID <= 0) {
            return ['success' => false, 'data' => [], 'message' => 'Conversation ID không hợp lệ'];
        }
        
        // 1. Lấy lịch sử tin nhắn
        $messages = $this->model->getMessagesByConversationID($convID);
        
        // ðŸš€ Báº®T Äáº¦U FIX Lá»–I: Äáº£m báº£o tin nháº¯n bot cÃ³ senderType = 'bot'
        if (!empty($messages)) {
            $BOT_SENDER_ID = 0; // ID bot Ä‘Ã£ xÃ¡c nháº­n lÃ  0
            
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
        // ðŸ›‘ Káº¾T THÃšC FIX Lá»–I
        
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
    
    // CÃ¡c hÃ m khÃ¡c giá»¯ nguyÃªn
}

// Xá»­ lÃ½ request AJAX Ä‘á»ƒ táº£i lá»‹ch sá»­ chat
// â¬…ï¸ ÄÃƒ Sá»¬A Lá»–I: Äá»•i action tá»« 'getChatHistory' thÃ nh 'getMessages' Ä‘á»ƒ khá»›p vá»›i JS
if (isset($_GET['action']) && $_GET['action'] === 'getMessages') {
    $controller = new ChatController();
    
    // â¬…ï¸ ÄÃƒ Sá»¬A Lá»–I: Äá»•i tham sá»‘ GET tá»« 'convID' thÃ nh 'conv_id' Ä‘á»ƒ khá»›p vá»›i JS
    $convID = $_GET['conv_id'] ?? 0;
    
    $result = $controller->getChatHistory($convID);
    
    // â¬…ï¸ ÄÃƒ Sá»¬A Lá»–I: TÃ¡i cáº¥u trÃºc output Ä‘á»ƒ khá»›p vá»›i JavaScript mong muá»‘n: {success: true, messages: [...]}
    $output = [
        'success' => $result['success'],
        'messages' => $result['data'] // Đổi key 'data' thành 'messages'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($output);
    exit;
}
?>