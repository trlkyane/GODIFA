<?php
// Tệp: GODIFA/api/review_action.php

// 🌟 ĐÃ SỬA: Đường dẫn relative từ 'api' ra 'GODIFA/' rồi vào 'controller/admin/' 🌟
require_once __DIR__ . '/../controller/admin/cReview.php'; 

// Thiết lập header để trả về JSON
header('Content-Type: application/json');

// Chỉ chấp nhận yêu cầu POST
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
    if ($action === 'updateStatus' && ($status === 1 || $status === 2)) {
        // Duyệt (status 1) hoặc Từ chối/Ẩn (status 2)
        $result = $reviewController->updateStatus($reviewID, $status);
    } elseif ($action === 'delete') {
        // Xóa vĩnh viễn
        $result = $reviewController->deleteReview($reviewID);
    } else {
        $result['message'] = 'Hành động không hợp lệ hoặc thiếu trạng thái.';
    }
} else {
    $result['message'] = 'ID Đánh giá không hợp lệ.';
}

echo json_encode($result);
exit;