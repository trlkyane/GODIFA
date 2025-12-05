<?php
/**
 * Debug: Kiểm tra và test update trạng thái đơn hàng khi hoàn tiền
 */

require_once __DIR__ . '/model/database.php';

$db = Database::getInstance();
$conn = $db->connect();

echo "<h2>Debug Update Refund Status</h2>";

// Kiểm tra đơn hàng #191
$orderID = 191;

echo "<h3>1. Trạng thái hiện tại của đơn hàng #$orderID:</h3>";
$sql = "SELECT orderID, paymentStatus, deliveryStatus, paymentDate FROM `order` WHERE orderID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $orderID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if ($order) {
    echo "<pre>";
    print_r($order);
    echo "</pre>";
} else {
    echo "<p style='color:red'>Không tìm thấy đơn hàng #$orderID</p>";
}

// Kiểm tra refund request
echo "<h3>2. Thông tin yêu cầu hoàn tiền:</h3>";
$sql2 = "SELECT * FROM refund_requests WHERE orderID = ?";
$stmt2 = mysqli_prepare($conn, $sql2);
mysqli_stmt_bind_param($stmt2, "i", $orderID);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
$refund = mysqli_fetch_assoc($result2);

if ($refund) {
    echo "<pre>";
    print_r($refund);
    echo "</pre>";
} else {
    echo "<p>Không có yêu cầu hoàn tiền cho đơn này</p>";
}

// Test UPDATE
echo "<h3>3. Test UPDATE trạng thái (DRY RUN - không thực thi):</h3>";
$testSQL = "UPDATE `order` 
            SET deliveryStatus = 'Đã hoàn tiền',
                paymentStatus = 'Đã hoàn tiền'
            WHERE orderID = $orderID";
echo "<code>$testSQL</code>";

// Thực thi UPDATE để test
echo "<h3>4. Thực thi UPDATE:</h3>";
$updateSQL = "UPDATE `order` 
              SET deliveryStatus = 'Đã hoàn tiền',
                  paymentStatus = 'Đã hoàn tiền'
              WHERE orderID = ?";
$stmtUpdate = mysqli_prepare($conn, $updateSQL);
mysqli_stmt_bind_param($stmtUpdate, "i", $orderID);

if (mysqli_stmt_execute($stmtUpdate)) {
    $affected = mysqli_stmt_affected_rows($stmtUpdate);
    echo "<p style='color:green'>✅ UPDATE thành công! Số dòng bị ảnh hưởng: $affected</p>";
    
    // Kiểm tra lại sau khi update
    echo "<h3>5. Trạng thái SAU khi update:</h3>";
    mysqli_stmt_execute($stmt);
    $result3 = mysqli_stmt_get_result($stmt);
    $orderAfter = mysqli_fetch_assoc($result3);
    echo "<pre>";
    print_r($orderAfter);
    echo "</pre>";
} else {
    echo "<p style='color:red'>❌ UPDATE thất bại: " . mysqli_error($conn) . "</p>";
}
?>
