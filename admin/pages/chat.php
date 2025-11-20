<?php
/**
 * Quản lý Chat - Đã chuyển sang Socket.IO để chat Real-time
 * File: admin/pages/chat.php
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission
if (!hasPermission('view_chat')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập Chat!</div></div>');
}

// Load Controller
require_once __DIR__ . '/../../controller/ChatController.php'; 
$chatController = new ChatController(); 

$success = '';
$error = '';
$currentUserID = $_SESSION['user_id'] ?? 0;
$currentRoleID = $_SESSION['role_id'] ?? 0; 

// --- Xử lý DELETE MESSAGE / DELETE CONVERSATION (giữ nguyên) ---

// Xử lý XÓA TIN NHẮN (Chỉ Chủ DN và NVCSKH)
if (isset($_GET['delete_message']) && ($currentRoleID == 1 || $currentRoleID == 4)) {
    $chatID = intval($_GET['delete_message']);
    // Hàm này phải được định nghĩa trong ChatController.php của bạn
    $result = $chatController->deleteMessage($chatID, $currentRoleID); 
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Xử lý XÓA HỘI THOẠI (Chỉ Chủ DN và NVCSKH)
if (isset($_GET['delete_conversation']) && ($currentRoleID == 1 || $currentRoleID == 4)) {
    $customerID = intval($_GET['delete_conversation']);
    // Hàm này phải được định nghĩa trong ChatController.php của bạn (Ví dụ: deleteConversationByCustomerID)
    $result = $chatController->deleteConversation($customerID, $currentRoleID); 
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Lấy danh sách hội thoại
// LƯU Ý: Nếu ChatController::getAllConversations() vẫn chưa tồn tại, lỗi Fatal Error sẽ xảy ra ở đây.
$conversationsResult = $chatController->getAllConversations($currentRoleID); 
$conversations = $conversationsResult['success'] ? $conversationsResult['data'] : [];

// Chọn Conversation đầu tiên nếu có để hiển thị ngay (Tùy chọn)
$currentConversation = null;
$currentConversationID = 'null';
$currentCustomerID = 'null';
$currentCustomerName = 'Khách hàng';

if (!empty($conversations)) {
    // Lấy Conversation mới nhất để load mặc định
    $currentConversation = $conversations[0];
    $currentConversationID = $currentConversation['conversationID'];
    $currentCustomerID = $currentConversation['customerID'];
    $currentCustomerName = htmlspecialchars($currentConversation['customerName'] ?? 'Khách #'.$currentCustomerID); // Xử lý trường hợp tên NULL
}

// Thống kê
$totalMessages = $chatController->countMessages();
$unreadMessages = $chatController->countUnreadMessages();

$pageTitle = 'Quản lý Chat';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="flex-1 overflow-y-auto ml-64">
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-4 md:px-6 py-4 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                        <i class="fas fa-comments text-blue-500 mr-2"></i>
                        Quản lý Chat
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng số: <strong><?php echo $totalMessages; ?></strong> tin nhắn
                        <?php if ($unreadMessages > 0): ?>
                        <span class="mx-2">|</span>
                        Chưa đọc: <span class="text-red-600 font-semibold"><?php echo $unreadMessages; ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="text-sm">
                    <?php if ($currentRoleID == 1): ?>
                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full">
                            <i class="fas fa-crown mr-1"></i>Chủ Doanh nghiệp
                        </span>
                    <?php elseif ($currentRoleID == 2): ?>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full">
                            <i class="fas fa-user-shield mr-1"></i>Nhân viên
                        </span>
                    <?php elseif ($currentRoleID == 4): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full">
                            <i class="fas fa-headset mr-1"></i>CSKH
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="p-4 md:p-6">
            <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?php echo $success; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="bg-blue-600 text-white px-4 py-3 font-bold">
                            <i class="fas fa-users mr-2"></i>
                            Danh sách hội thoại (<span id="convCount"><?php echo count($conversations); ?></span>)
                        </div>
                        
                        <div class="p-3 border-b">
                            <input type="text" id="searchConversation" placeholder="Tìm khách hàng..." 
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div id="conversationList" class="divide-y max-h-[600px] overflow-y-auto">
                            <?php if (empty($conversations)): ?>
                            <div class="p-8 text-center text-gray-500" id="noConversationMessage">
                                <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                                <p>Chưa có hội thoại nào</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                <?php
                                $customerID = $conv['customerID'];
                                $convID = $conv['conversationID'];
                                // Xử lý trường hợp customerName NULL (nếu chưa có trong bảng customer)
                                $customerName = htmlspecialchars($conv['customerName'] ?? 'Khách #'.$customerID);
                                $phone = htmlspecialchars($conv['phone'] ?? '');
                                $totalMessages = $conv['totalMessages'];
                                // ĐÃ SỬA LỖI: Kiểm tra sự tồn tại của lastMessage
                                $lastMessage = htmlspecialchars($conv['lastMessage'] ?? 'Bắt đầu hội thoại');
                                
                                // ĐÃ SỬA LỖI: Luôn sử dụng 'last_message_at' và kiểm tra sự tồn tại
                                $lastMessageTime = $conv['last_message_at'] ?? date('Y-m-d H:i:s');
                                
                                // Lấy số tin chưa đọc cho Staff (User)
                                $unreadCount = $conv['customer_unread_count'] ?? 0; 
                                
                                $isActive = ($convID == $currentConversationID) ? 'bg-blue-50 border-blue-500' : 'border-transparent';
                                ?>
                                <div class="conversation-item p-3 hover:bg-blue-50 cursor-pointer border-l-4 hover:border-blue-500 transition-all <?php echo $isActive; ?>"
                                    data-conv-id="<?php echo $convID; ?>"
                                    data-customer-id="<?php echo $customerID; ?>"
                                    data-customer-name="<?php echo $customerName; ?>"
                                    onclick="loadConversation(this)">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-gray-800 mb-1 flex items-center">
                                                <i class="fas fa-user-circle text-blue-500 mr-1"></i>
                                                <span class="truncate"><?php echo $customerName; ?></span>
                                            </div>
                                            <div class="text-sm text-gray-500 truncate last-message-content" 
                                                     id="last-message-<?php echo $convID; ?>">
                                                <?php echo mb_substr($lastMessage, 0, 40) . (mb_strlen($lastMessage) > 40 ? '...' : ''); ?>
                                            </div>
                                        </div>
                                        <div class="text-right ml-2 flex-shrink-0">
                                            <div class="text-xs text-gray-500 mb-1 last-message-time" id="last-time-<?php echo $convID; ?>">
                                                <?php 
                                                // Format thời gian từ last_message_at
                                                echo date('H:i', strtotime($lastMessageTime)); 
                                                ?>
                                            </div>
                                            <?php if ($unreadCount > 0): ?>
                                            <span class="unread-badge px-2 py-1 bg-red-500 text-white text-xs rounded-full" id="badge-<?php echo $convID; ?>">
                                                <?php echo $unreadCount; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="lg:col-span-2">
                    <div id="chatBox" class="bg-white rounded-lg shadow overflow-hidden h-[700px] flex flex-col">
                        
                        <div id="chatHeader" class="bg-blue-600 text-white px-4 py-3 flex justify-between items-center flex-shrink-0">
                            <?php if ($currentConversationID != 'null'): ?>
                                <div class="font-bold" id="current-customer-name-display"><i class="fas fa-user-circle mr-2"></i><?php echo $currentCustomerName; ?></div>
                                <?php if ($currentRoleID == 1 || $currentRoleID == 4): ?>
                                <button onclick="deleteConversation(<?php echo $currentCustomerID; ?>)" class="text-white hover:text-red-200 text-sm">
                                    <i class="fas fa-trash mr-1"></i>Xóa hội thoại
                                </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="font-bold">Chọn một hội thoại để bắt đầu</div>
                            <?php endif; ?>
                        </div>

                        <div id="messagesList" class="flex-1 p-4 overflow-y-auto bg-gray-50 flex flex-col">
                            <?php if ($currentConversationID == 'null'): ?>
                                <div class="w-full text-center py-10 text-gray-400">
                                    <i class="fas fa-comments text-6xl mb-4"></i>
                                    <p class="text-lg">Chọn một hội thoại để bắt đầu</p>
                                </div>
                            <?php else: ?>
                                <div class="w-full text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i> Đang tải lịch sử chat...</div>
                            <?php endif; ?>
                        </div>
                        
                        <div id="chatInputArea" class="border-t p-4 bg-white flex-shrink-0 <?php echo ($currentConversationID == 'null' ? 'hidden' : ''); ?>"> 
                            <form id="userMessageForm" class="flex gap-2">
                                <input type="text" id="userMessageInput" placeholder="Nhập tin nhắn..." required
                                        class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="submit" id="userSendButton"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-paper-plane mr-1"></i>Gửi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="admin-metadata" style="display: none;" 
    data-user-id="<?php echo $currentUserID; ?>"
    data-user-type="user"
    data-init-conv-id="<?php echo $currentConversationID; ?>"
    data-init-customer-id="<?php echo $currentCustomerID; ?>">
</div>

<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script>
    // Định nghĩa biến để JS client có thể sử dụng
    const CURRENT_USER_ID = '<?php echo $currentUserID; ?>';
    const CURRENT_ROLE_ID = '<?php echo $currentRoleID; ?>';
    
    /**
     * Tự động cuộn xuống dưới cùng của danh sách tin nhắn
     */
    function scrollToBottom(elementId = 'messagesList') {
        const messagesList = document.getElementById(elementId);
        if (messagesList) {
            // Sử dụng setTimeout để đảm bảo cuộn sau khi DOM đã render tin nhắn mới
            setTimeout(() => {
                 messagesList.scrollTop = messagesList.scrollHeight;
            }, 100);
        }
    }

    // Hàm deleteConversation cần được định nghĩa trong admin_chat_client.js
    function deleteConversation(customerID) {
        // Thay window.confirm bằng một modal hoặc pop-up tùy chỉnh theo yêu cầu của dự án.
        if (confirm('Bạn có chắc chắn muốn xóa hội thoại này và tất cả tin nhắn liên quan?')) {
            window.location.href = '?delete_conversation=' + customerID;
        }
    }
</script>
<script src="/GODIFA/public/js/admin_chat_client.js"></script> 

<?php include __DIR__ . '/../includes/footer.php'; ?>

<style>
/* 1. KHUNG CHỨA TIN NHẮN (MESSAGES LIST) */
#messagesList { 
    display: flex;
    flex-direction: column; 
    padding: 1rem;
    
    /* FIX QUAN TRỌNG: Đảm bảo khả năng cuộn */
    flex-grow: 1; /* Đã có từ class flex-1 */
    min-height: 0; /* Ngăn nó giãn nở vô hạn, cho phép cuộn */
    
    overflow-y: auto; /* Kích hoạt thanh cuộn (Đã có) */ 
    
    align-items: stretch; 
}

/* 2. ĐỊNH DẠNG CHUNG CHO MỖI TIN NHẮN (ROW) */
.message-row {
    display: flex; 
    max-width: 100%; 
    margin-bottom: 0.5rem;
    align-items: flex-end; 
}

/* 3. CĂN CHỈNH TIN NHẮN GỬI ĐI (BÊN PHẢI) - staff/admin */
.message-row.sent {
    /* Quan trọng: Đẩy toàn bộ hàng tin nhắn sang phải */
    align-self: flex-end; 
    justify-content: flex-end;
}

/* 4. CĂN CHỈNH TIN NHẮN NHẬN ĐƯỢC (BÊN TRÁI) - customer */
.message-row.received {
    /* Quan trọng: Đảm bảo toàn bộ hàng tin nhắn nằm bên trái */
    align-self: flex-start; 
    justify-content: flex-start;
}

/* 5. KHUNG BONG BÓNG TIN NHẮN */
.message-bubble {
    padding: 10px 14px;
    border-radius: 18px;
    word-wrap: break-word;
    max-width: 85%; 
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    text-align: left;
}

/* Tùy chỉnh màu và góc bo cho TIN GỬI ĐI */
.message-row.sent .message-bubble {
    background-color: #3b82f6; /* Blue-500 */
    color: white;
    /* Loại bỏ bo góc dưới phải (tạo kiểu "chat") */
    border-bottom-right-radius: 4px; 
}

/* Tùy chỉnh màu và góc bo cho TIN NHẬN ĐƯỢC */
.message-row.received .message-bubble {
    background-color: #ffffff; /* White */
    color: #1f2937; /* Gray-800 */
    /* Loại bỏ bo góc dưới trái (tạo kiểu "chat") */
    border-bottom-left-radius: 4px; 
}

/* 6. TIMESTAMP */
.timestamp {
    display: block;
    font-size: 10px;
    margin-top: 3px;
    opacity: 0.9;
}

/* Timestamp CĂN PHẢI cho tin nhắn GỬI ĐI */
.message-row.sent .timestamp {
    text-align: right; 
    color: rgba(255, 255, 255, 0.7); /* Xám nhạt trên nền xanh */
}

/* Timestamp CĂN TRÁI cho tin nhắn NHẬN ĐƯỢC */
.message-row.received .timestamp {
    text-align: left; 
    color: #6b7280; /* Gray-500 trên nền trắng */
}

/* 🚀 FIX LỖI CUỘN: Xóa thuộc tính justify-content: flex-end; */
#messagesList:not(:empty) {
    display: flex;
    flex-direction: column;
    /* justify-content: flex-end; <--- Đã loại bỏ */
}
</style>