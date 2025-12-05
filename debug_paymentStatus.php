<?php
/**
 * DEBUG: Kiểm tra ENUM của paymentStatus
 */
require_once __DIR__ . '/model/database.php';
$conn = Database::getInstance()->getConnection();

echo "<h2>DEBUG: Kiểm tra paymentStatus column</h2>";

// 1. Kiểm tra cấu trúc column paymentStatus
echo "<h3>1. Cấu trúc column paymentStatus:</h3>";
$sql = "SHOW FULL COLUMNS FROM `order` LIKE 'paymentStatus'";
$result = mysqli_query($conn, $sql);
$column = mysqli_fetch_assoc($result);
echo "<pre>";
print_r($column);
echo "</pre>";

// 2. Kiểm tra trạng thái hiện tại của orderID 191
echo "<h3>2. Trạng thái hiện tại orderID 191:</h3>";
$sql = "SELECT orderID, deliveryStatus, paymentStatus FROM `order` WHERE orderID = 191";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
echo "<pre>";
print_r($row);
echo "</pre>";

// 3. Test UPDATE paymentStatus
echo "<h3>3. Test UPDATE paymentStatus:</h3>";
$sql = "UPDATE `order` SET paymentStatus = 'Đã hoàn tiền' WHERE orderID = 191";
echo "Query: $sql<br>";
if (mysqli_query($conn, $sql)) {
    echo "Result: SUCCESS<br>";
    echo "Affected rows: " . mysqli_affected_rows($conn) . "<br>";
    
    // Kiểm tra lại
    $sql = "SELECT orderID, deliveryStatus, paymentStatus FROM `order` WHERE orderID = 191";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    echo "Sau UPDATE:<br>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
    
    if ($row['paymentStatus'] === 'Đã hoàn tiền') {
        echo "<p style='color: green; font-weight: bold;'>✅ paymentStatus ĐÃ CẬP NHẬT THÀNH CÔNG!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ paymentStatus KHÔNG thay đổi! Cần fix ENUM.</p>";
    }
} else {
    echo "Result: FAILED - " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
?>
