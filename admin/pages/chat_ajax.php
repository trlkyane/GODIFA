<?php
/**
 * AJAX handler cho Chat
 * File: admin/pages/chat_ajax.php
 */

session_start();
require_once __DIR__ . '/../../controller/admin/cChat.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['userID']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập!']);
    exit;
}

$chatController = new cChat();
$currentRoleID = $_SESSION['roleID'] ?? $_SESSION['role_id'] ?? null;

// Kiểm tra quyền truy cập (Nhân viên Bán Hàng không được truy cập)
if ($currentRoleID == 3) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập Chat!']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'load_messages':
        $customerID = intval($_GET['customerID'] ?? 0);
        
        if ($customerID <= 0) {
            echo json_encode(['success' => false, 'message' => 'Customer ID không hợp lệ!']);
            exit;
        }
        
        $result = $chatController->getMessagesByCustomer($customerID, $currentRoleID);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'messages' => $result['data']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
        break;
        
    case 'search_conversations':
        $keyword = $_GET['keyword'] ?? '';
        
        if (empty($keyword)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập từ khóa!']);
            exit;
        }
        
        $result = $chatController->searchConversations($keyword, $currentRoleID);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'conversations' => $result['data']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ!']);
        break;
}
?>
