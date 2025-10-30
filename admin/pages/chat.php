<?php
/**
 * Quản lý Chat
 * File: admin/pages/chat.php
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission - Nhân viên Bán Hàng KHÔNG được truy cập
$currentRoleID = $_SESSION['roleID'] ?? $_SESSION['role_id'] ?? null;
if ($currentRoleID == 3) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Nhân viên Bán Hàng không có quyền truy cập Chat!</div></div>');
}

// Load Controller
require_once __DIR__ . '/../../controller/admin/cChat.php';
$chatController = new cChat();

$success = '';
$error = '';

// Xử lý GỬI TIN NHẮN
if (isset($_POST['send_message'])) {
    $data = [
        'customerID' => intval($_POST['customerID']),
        'content' => trim($_POST['content'])
    ];
    
    $currentUserID = $_SESSION['userID'] ?? $_SESSION['user_id'] ?? 0;
    $result = $chatController->sendMessage($data, $currentUserID, $currentRoleID);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = isset($result['errors']) ? implode('<br>', $result['errors']) : $result['message'];
    }
}

// Xử lý XÓA TIN NHẮN (Chỉ Chủ DN và NVCSKH)
if (isset($_GET['delete_message']) && ($currentRoleID == 1 || $currentRoleID == 4)) {
    $chatID = intval($_GET['delete_message']);
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
    $result = $chatController->deleteConversation($customerID, $currentRoleID);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Lấy danh sách hội thoại
$conversationsResult = $chatController->getAllConversations($currentRoleID);
$conversations = $conversationsResult['success'] ? $conversationsResult['data'] : [];

// Thống kê
$totalMessages = $chatController->countMessages();
$unreadMessages = $chatController->countUnreadMessages();

$pageTitle = 'Quản lý Chat';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto ml-64">
        <!-- Header -->
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
                
                <!-- Quyền hạn hiển thị -->
                <div class="text-sm">
                    <?php if ($currentRoleID == 1): ?>
                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full">
                            <i class="fas fa-crown mr-1"></i>Theo dõi tất cả hội thoại
                        </span>
                    <?php elseif ($currentRoleID == 2): ?>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full">
                            <i class="fas fa-user-shield mr-1"></i>Xem và hỗ trợ chat
                        </span>
                    <?php elseif ($currentRoleID == 4): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full">
                            <i class="fas fa-headset mr-1"></i>Toàn quyền quản lý chat
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-4 md:p-6">
            <!-- Alerts -->
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
            
            <!-- Chat Layout: 2 cột -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Danh sách hội thoại -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="bg-blue-600 text-white px-4 py-3 font-bold">
                            <i class="fas fa-users mr-2"></i>
                            Danh sách hội thoại (<?php echo count($conversations); ?>)
                        </div>
                        
                        <!-- Search -->
                        <div class="p-3 border-b">
                            <input type="text" id="searchConversation" placeholder="Tìm khách hàng..." 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <!-- List -->
                        <div class="divide-y max-h-[600px] overflow-y-auto">
                            <?php if (empty($conversations)): ?>
                            <div class="p-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                                <p>Chưa có hội thoại nào</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                <?php
                                $customerID = $conv['customerID'];
                                $customerName = htmlspecialchars($conv['customerName']);
                                $phone = htmlspecialchars($conv['phone'] ?? '');
                                $totalMessages = $conv['totalMessages'];
                                $lastMessage = htmlspecialchars($conv['lastMessage'] ?? '');
                                $lastMessageDate = $conv['lastMessageDate'];
                                ?>
                                <div class="conversation-item p-3 hover:bg-blue-50 cursor-pointer border-l-4 border-transparent hover:border-blue-500 transition-all"
                                     onclick="loadConversation(<?php echo $customerID; ?>, '<?php echo $customerName; ?>')">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="font-semibold text-gray-800 mb-1">
                                                <i class="fas fa-user-circle text-blue-500 mr-1"></i>
                                                <?php echo $customerName; ?>
                                            </div>
                                            <div class="text-xs text-gray-600 mb-1">
                                                <i class="fas fa-phone mr-1"></i><?php echo $phone; ?>
                                            </div>
                                            <div class="text-sm text-gray-500 truncate">
                                                <?php echo mb_substr($lastMessage, 0, 50) . (mb_strlen($lastMessage) > 50 ? '...' : ''); ?>
                                            </div>
                                        </div>
                                        <div class="text-right ml-2">
                                            <div class="text-xs text-gray-500 mb-1">
                                                <?php echo date('d/m H:i', strtotime($lastMessageDate)); ?>
                                            </div>
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                                <?php echo $totalMessages; ?> tin
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Khung chat -->
                <div class="lg:col-span-2">
                    <div id="chatBox" class="bg-white rounded-lg shadow overflow-hidden h-[700px] flex flex-col">
                        <div class="bg-gray-100 p-8 flex-1 flex items-center justify-center">
                            <div class="text-center text-gray-400">
                                <i class="fas fa-comments text-6xl mb-4"></i>
                                <p class="text-lg">Chọn một hội thoại để bắt đầu</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentCustomerID = null;
let currentCustomerName = '';

// Load hội thoại
function loadConversation(customerID, customerName) {
    currentCustomerID = customerID;
    currentCustomerName = customerName;
    
    // Highlight active conversation
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('bg-blue-50', 'border-blue-500');
    });
    event.currentTarget.classList.add('bg-blue-50', 'border-blue-500');
    
    // Load messages via AJAX
    fetch(`chat_ajax.php?action=load_messages&customerID=${customerID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderChatBox(data.messages);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Lỗi khi tải tin nhắn!');
        });
}

// Render chat box
function renderChatBox(messages) {
    let html = `
        <div class="bg-blue-600 text-white px-4 py-3 flex justify-between items-center">
            <div class="font-bold">
                <i class="fas fa-user-circle mr-2"></i>${currentCustomerName}
            </div>
            <?php if ($currentRoleID == 1 || $currentRoleID == 4): ?>
            <button onclick="deleteConversation(${currentCustomerID})" class="text-white hover:text-red-200">
                <i class="fas fa-trash mr-1"></i>Xóa hội thoại
            </button>
            <?php endif; ?>
        </div>
        
        <div id="messagesList" class="flex-1 p-4 overflow-y-auto bg-gray-50" style="max-height: 500px;">
    `;
    
    if (messages.length === 0) {
        html += `
            <div class="text-center text-gray-400 py-8">
                <i class="fas fa-comment-slash text-4xl mb-2"></i>
                <p>Chưa có tin nhắn</p>
            </div>
        `;
    } else {
        messages.forEach(msg => {
            const isCustomer = msg.toUserID == 0 || msg.toUserID == null;
            const alignClass = isCustomer ? 'justify-start' : 'justify-end';
            const bgClass = isCustomer ? 'bg-white' : 'bg-blue-100';
            const name = isCustomer ? currentCustomerName : (msg.staffName || 'Nhân viên');
            const icon = isCustomer ? 'fa-user' : 'fa-user-tie';
            
            html += `
                <div class="flex ${alignClass} mb-3">
                    <div class="${bgClass} rounded-lg p-3 max-w-md shadow">
                        <div class="text-xs text-gray-600 mb-1">
                            <i class="fas ${icon} mr-1"></i>${name}
                            <span class="ml-2">${msg.date}</span>
                        </div>
                        <div class="text-sm text-gray-800">${msg.chatContent}</div>
                    </div>
                </div>
            `;
        });
    }
    
    html += `
        </div>
        
        <div class="border-t p-4 bg-white">
            <form method="POST" class="flex gap-2">
                <input type="hidden" name="customerID" value="${currentCustomerID}">
                <input type="text" name="content" placeholder="Nhập tin nhắn..." required
                       class="flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" name="send_message"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-paper-plane mr-1"></i>Gửi
                </button>
            </form>
        </div>
    `;
    
    document.getElementById('chatBox').innerHTML = html;
    
    // Scroll to bottom
    setTimeout(() => {
        const messagesList = document.getElementById('messagesList');
        if (messagesList) {
            messagesList.scrollTop = messagesList.scrollHeight;
        }
    }, 100);
}

// Xóa hội thoại
function deleteConversation(customerID) {
    if (confirm('Bạn có chắc muốn xóa toàn bộ hội thoại với khách hàng này?')) {
        window.location.href = `?delete_conversation=${customerID}`;
    }
}

// Tìm kiếm
document.getElementById('searchConversation')?.addEventListener('input', function(e) {
    const keyword = e.target.value.toLowerCase();
    document.querySelectorAll('.conversation-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(keyword) ? 'block' : 'none';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
