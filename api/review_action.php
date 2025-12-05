<?php
// Tá»‡p: GODIFA/api/review_action.php

// ðŸŒŸ ÄÃƒ Sá»¬A: ÄÆ°á»ng dáº«n relative tá»« 'api' ra 'GODIFA/' rá»“i vÃ o 'controller/admin/' ðŸŒŸ
require_once __DIR__ . '/../controller/admin/cReview.php'; 

// Thiáº¿t láº­p header Ä‘á»ƒ tráº£ vá» JSON
header('Content-Type: application/json');

// Chá»‰ cháº¥p nháº­n yÃªu cáº§u POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Lỗi: Phương thức yêu cầu không hợp lệ.']);
    exit;
}

$reviewController = new cReview();
// Đọc dữ liệu JSON từ yêu cầu Fetch API
$data = json_decode(file_get_contents('php://input'), true);

$reviewID = intval($data['reviewID'] ?? 0);
$action = $data['action'] ?? ''; 
$status = intval($data['status'] ?? -1);

$result = ['success' => false, 'message' => 'Lỗi không xác định.'];

// --- Gọi Controller dựa trên Action ---
if ($reviewID > 0) {
    if ($action === 'toggle') {
        // Ẩn/Hiện đánh giá
        $result = $reviewController->toggleVisibility($reviewID);
    } elseif ($action === 'delete') {
        // Xóa vĩnh viễn
        $result = $reviewController->deleteReview($reviewID);
    } else {
        $result['message'] = 'Hành động không hợp lệ.';
    }
} else {
    $result['message'] = 'ID Đánh giá không hợp lệ.';
}

echo json_encode($result);
exit;