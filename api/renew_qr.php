<?php
/**
 * API: Renew QR Code
 * File: api/renew_qr.php
 * Tạo lại QR code mới khi QR cũ đã hết hạn
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../model/database.php';

// Lấy orderID từ query string
$orderID = isset($_GET['orderID']) ? intval($_GET['orderID']) : 0;

if (!$orderID) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing orderID'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->connect();
    
    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("SELECT orderID, totalAmount, paymentStatus, transactionCode FROM `order` WHERE orderID = ?");
    $stmt->bind_param("i", $orderID);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đơn hàng'
        ]);
        exit;
    }
    
    // Kiểm tra đã thanh toán chưa
    if ($order['paymentStatus'] === 'Đã thanh toán') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Đơn hàng đã được thanh toán'
        ]);
        exit;
    }
    
    // Kiểm tra đã hủy chưa
    if ($order['paymentStatus'] === 'Đã hủy') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Đơn hàng đã bị hủy'
        ]);
        exit;
    }
    
    // Tạo QR mới với expiry time mới (15 phút)
    $newExpiredAt = date('Y-m-d H:i:s', time() + 15 * 60);
    
    // Thông tin SePay (giống checkout.php)
    $account = '105875539922'; // STK VietinBank
    $bank = 'VietinBank'; // Tên ngân hàng đầy đủ
    $amount = $order['totalAmount'];
    $transactionCode = $order['transactionCode'];
    $description = 'SEVQR TKP155 ' . $transactionCode; // Format: SEVQR TKP{mã VA} {mã giao dịch}
    
    // Tạo QR URL theo chuẩn SePay
    $qrUrl = "https://qr.sepay.vn/img?acc=$account&bank=$bank&amount=$amount&des=" . urlencode($description);
    
    // Update vào database
    $stmt = $conn->prepare("UPDATE `order` SET qrExpiredAt = ?, qrUrl = ? WHERE orderID = ?");
    $stmt->bind_param("ssi", $newExpiredAt, $qrUrl, $orderID);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update order");
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'QR code đã được tạo mới',
        'data' => [
            'qrUrl' => $qrUrl,
            'expiredAt' => $newExpiredAt,
            'remainingSeconds' => 15 * 60
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Renew QR Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
?>
