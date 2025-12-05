<?php
/**
 * AJAX Endpoint cho Chat (Admin Client)
 * File: GODIFA/api/chat_ajax.php
 */

// Äáº£m báº£o Ä‘Æ°á»ng dáº«n nÃ y trá» Ä‘áº¿n ChatController.php Ä‘Ãºng. 
// Giáº£ sá»­ 'api' vÃ  'controller' náº±m cÃ¹ng cáº¥p trong thÆ° má»¥c GODIFA
require_once __DIR__ . '/../controller/ChatController.php'; 
// Báº£o máº­t: Äáº£m báº£o ngÆ°á»i dÃ¹ng Ä‘Ã£ Ä‘Äƒng nháº­p (Staff)
// require_once __DIR__ . '/../middleware/auth.php';
// requireStaff(); 

header('Content-Type: application/json');

// Khá»Ÿi táº¡o Controller Má»šI
$chatController = new ChatController();

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];

switch ($action) {
    case 'load_messages_by_conv':
        $convID = intval($_GET['convID'] ?? 0);
        if ($convID > 0) {
            // Gá»i hÃ m tá»« Controller
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
            
            // Láº¥y viewerType tá»« Client (lÃ  'user' trong Admin Client)
            $viewerType = $data['viewerType'] ?? 'user'; 
            
            // Xá»­ lÃ½ báº£o máº­t: Äáº£m báº£o chá»‰ staff má»›i Ä‘Æ°á»£c dÃ¹ng viewerType='user'
            if ($viewerType !== 'user') {
                 $response['message'] = 'Unauthorized viewer type.';
                 break;
            }

            if ($convID > 0) {
                // Truyá»n viewerType='user' vÃ o hÃ m Model
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
            // ThÃªm hÃ m searchConversations vÃ o ChatController.php náº¿u cáº§n
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