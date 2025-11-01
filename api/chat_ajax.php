<?php
/**
 * AJAX Endpoint cho Chat (Admin Client)
 * File: GODIFA/api/chat_ajax.php
 */

// Đảm bảo đường dẫn này trỏ đến ChatController.php đúng. 
// Giả sử 'api' và 'controller' nằm cùng cấp trong thư mục GODIFA
require_once __DIR__ . '/../controller/ChatController.php'; 
// Bảo mật: Đảm bảo người dùng đã đăng nhập (Staff)
// require_once __DIR__ . '/../middleware/auth.php';
// requireStaff(); 

header('Content-Type: application/json');

// Khởi tạo Controller MỚI
$chatController = new ChatController();

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];

switch ($action) {
    case 'load_messages_by_conv':
        $convID = intval($_GET['convID'] ?? 0);
        if ($convID > 0) {
            // Gọi hàm từ Controller
            $messages = $chatController->getMessagesByConversationID($convID); 
            $response = ['success' => true, 'messages' => $messages];
        } else {
            $response['message'] = 'Missing conversation ID.';
        }
        break;

    case 'mark_as_read':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $convID = intval($data['convID'] ?? 0);
            
            // Lấy viewerType từ Client (là 'user' trong Admin Client)
            $viewerType = $data['viewerType'] ?? 'user'; 
            
            // Xử lý bảo mật: Đảm bảo chỉ staff mới được dùng viewerType='user'
            if ($viewerType !== 'user') {
                 $response['message'] = 'Unauthorized viewer type.';
                 break;
            }

            if ($convID > 0) {
                // Truyền viewerType='user' vào hàm Model
                if ($chatController->markConversationAsRead($convID, $viewerType)) { 
                    $response = ['success' => true, 'message' => 'Conversation marked as read.'];
                } else {
                    $response['message'] = 'Failed to mark as read.';
                }
            } else {
                $response['message'] = 'Missing conversation ID.';
            }
        }
        break;

    case 'search_conversations':
        $keyword = $_GET['keyword'] ?? '';
        if (!empty($keyword)) {
            // Thêm hàm searchConversations vào ChatController.php nếu cần
            $conversations = $chatController->searchConversations($keyword);
            $response = ['success' => true, 'conversations' => $conversations];
        } else {
            $response['message'] = 'Missing search keyword.';
        }
        break;
        
    default:
        break;
}

echo json_encode($response);
exit;
?>