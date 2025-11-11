<?php
/**
 * Cronjob: Auto Cancel Expired Orders
 * File: cron/cancel_expired_orders.php
 * 
 * Chức năng: Tự động hủy các đơn hàng có QR code đã hết hạn
 * 
 * Cách setup:
 * 1. Windows Task Scheduler:
 *    - Action: Start a program
 *    - Program: C:\wamp64\bin\php\php8.3.14\php.exe
 *    - Arguments: C:\wamp64\www\GODIFA\cron\cancel_expired_orders.php
 *    - Trigger: Every 30 minutes
 * 
 * 2. Linux Crontab:
 *    "star/30 * * * * /usr/bin/php /var/www/GODIFA/cron/cancel_expired_orders.php"
 * 
 * 3. Test manually: php C:\wamp64\www\GODIFA\cron\cancel_expired_orders.php
 */

require_once __DIR__ . '/../model/database.php';

// Logging
function logMessage($message) {
    $logFile = __DIR__ . '/cancel_orders.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

try {
    logMessage("=== Starting auto-cancel cronjob ===");
    
    $db = Database::getInstance();
    $conn = $db->connect();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Tìm các đơn hàng:
    // - paymentStatus = 'Chờ thanh toán'
    // - qrExpiredAt < NOW() (đã hết hạn)
    $sql = "
        SELECT orderID, transactionCode, qrExpiredAt 
        FROM `order` 
        WHERE paymentStatus = 'Chờ thanh toán' 
        AND qrExpiredAt IS NOT NULL 
        AND qrExpiredAt < NOW()
    ";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $expiredOrders = $result->fetch_all(MYSQLI_ASSOC);
    $count = count($expiredOrders);
    
    if ($count === 0) {
        logMessage("No expired orders found. Everything is up to date.");
        exit(0);
    }
    
    logMessage("Found $count expired orders to cancel");
    
    // Chuẩn bị statement để update
    $updateStmt = $conn->prepare("
        UPDATE `order` 
        SET paymentStatus = 'Đã hủy', 
            cancelReason = 'QR code hết hạn - Tự động hủy' 
        WHERE orderID = ?
    ");
    
    if (!$updateStmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $successCount = 0;
    $failCount = 0;
    
    // Hủy từng đơn
    foreach ($expiredOrders as $order) {
        $updateStmt->bind_param("i", $order['orderID']);
        
        if ($updateStmt->execute()) {
            $successCount++;
            logMessage("✅ Cancelled order #{$order['orderID']} - {$order['transactionCode']}");
        } else {
            $failCount++;
            logMessage("❌ Failed to cancel order #{$order['orderID']}: " . $updateStmt->error);
        }
    }
    
    logMessage("=== Cronjob completed: $successCount succeeded, $failCount failed ===");
    
    $updateStmt->close();
    $conn->close();
    
    exit(0);
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}
?>
