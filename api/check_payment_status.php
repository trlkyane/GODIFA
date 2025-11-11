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
    $stmt = $conn->prepare("SELECT paymentStatus, qrExpiredAt, bankTransactionId FROM `order` WHERE orderID = ?");
    $stmt->bind_param("i", $orderID);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Order not found'
        ]);
        exit;
    }
    
    // Kiểm tra QR đã hết hạn chưa
    $isExpired = $order['qrExpiredAt'] && strtotime($order['qrExpiredAt']) < time();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'status' => $order['paymentStatus'],
        'isExpired' => $isExpired,
        'bankTransactionId' => $order['bankTransactionId']
    ]);
    
} catch (Exception $e) {
    error_log("Check Payment Status Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
