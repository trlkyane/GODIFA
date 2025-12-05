<?php
/**
 * API: Check Payment Status
 * File: api/check_payment_status.php
 * Kiểm tra trạng thái thanh toán (polling từ frontend)
 * Updated: Dùng bảng order trực tiếp, không cần bảng payment
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../model/database.php';

// Lấy orderID từ query string
$orderID = isset($_GET['orderID']) ? intval($_GET['orderID']) : 0;

if (!$orderID) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing orderID'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->connect();
    
    // Lấy thông tin đơn hàng
    $stmt = mysqli_prepare($conn, "SELECT paymentStatus, paymentDate FROM `order` WHERE orderID = ?");
    mysqli_stmt_bind_param($stmt, "i", $orderID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Order not found'
        ]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'status' => $order['paymentStatus'],
        'paymentDate' => $order['paymentDate']
    ]);
    
} catch (Exception $e) {
    error_log("Check Payment Status Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
