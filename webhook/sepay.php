<?php
/**
 * SePay Webhook Handler
 * File: webhook/sepay.php
 * Nhận thông báo thanh toán từ SePay
 * 
 * URL webhook: https://yourdomain.com/webhook/sepay.php
 * Updated: 2025-11-07 - Xử lý trực tiếp bảng order
 */

require_once __DIR__ . '/../model/database.php';

// Log file
$logFile = __DIR__ . '/../logs/sepay_webhook.log';

// Hàm log
function logWebhook($message) {
    global $logFile;
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Lấy raw input
$input = file_get_contents('php://input');
logWebhook("Received webhook: $input");

// Parse JSON
$data = json_decode($input, true);

if (!$data) {
    logWebhook("ERROR: Invalid JSON");
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid JSON']));
}

// ===== 1. PARSE TRANSACTION CODE =====
$transactionCode = null;

if (!empty($data['code'])) {
    $transactionCode = $data['code'];
} elseif (!empty($data['content'])) {
    // Extract GODIFA{YmdHHmm} từ content
    // Format: "106587008893-0978848500-SEVQR TKP155 GODIFA202511070121"
    if (preg_match('/GODIFA\d+/', $data['content'], $matches)) {
        $transactionCode = $matches[0];
    }
}

logWebhook("Parsed transactionCode: " . ($transactionCode ?? 'NULL'));

if (!$transactionCode) {
    logWebhook("ERROR: Cannot parse transaction code");
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Missing transaction code']));
}

// ===== 2. LẤY THÔNG TIN =====
$transferAmount = floatval($data['transferAmount'] ?? $data['amount'] ?? 0);
$bankTransactionId = $data['id'] ?? null;
$gateway = $data['gateway'] ?? 'Unknown';

logWebhook("Amount: $transferAmount | Bank: $gateway | ID: $bankTransactionId");

// ===== 3. XỬ LÝ DATABASE =====
try {
    $db = Database::getInstance();
    $conn = $db->connect();
    
    // Tìm order theo transactionCode
    $stmt = $conn->prepare("
        SELECT orderID, totalAmount, paymentStatus 
        FROM `order` 
        WHERE transactionCode = ?
    ");
    $stmt->bind_param("s", $transactionCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        logWebhook("ERROR: Order not found - $transactionCode");
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Order not found']));
    }
    
    // Kiểm tra đã thanh toán chưa
    if ($order['paymentStatus'] === 'Đã thanh toán') {
        logWebhook("WARNING: Order #{$order['orderID']} already paid");
        http_response_code(200);
        die(json_encode(['success' => true, 'message' => 'Already paid']));
    }
    
    // Kiểm tra số tiền (cho phép sai lệch 1000đ do làm tròn)
    if ($transferAmount < ($order['totalAmount'] - 1000)) {
        logWebhook("ERROR: Amount mismatch - Expected: {$order['totalAmount']}, Got: $transferAmount");
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Amount mismatch']));
    }
    
    // ===== 4. CẬP NHẬT ORDER =====
    $stmt = $conn->prepare("
        UPDATE `order` 
        SET paymentStatus = 'Đã thanh toán',
            deliveryStatus = 'Đang xử lý',
            bankTransactionId = ?
        WHERE orderID = ?
    ");
    $stmt->bind_param("si", $bankTransactionId, $order['orderID']);
    
    if ($stmt->execute()) {
        logWebhook("SUCCESS: Order #{$order['orderID']} paid successfully");
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Payment confirmed',
            'orderID' => $order['orderID']
        ]);
    } else {
        throw new Exception("Database update failed");
    }
    
} catch (Exception $e) {
    logWebhook("EXCEPTION: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
