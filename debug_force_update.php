<?php
require_once __DIR__ . '/model/database.php';
$db = Database::getInstance();
$conn = $db->connect();

echo "<h2>Force Update deliveryStatus</h2>";

// Bước 1: Xem giá trị hiện tại (dạng hex để xem ký tự ẩn)
echo "<h3>1. Giá trị hiện tại (HEX):</h3>";
$result = mysqli_query($conn, "SELECT orderID, deliveryStatus, HEX(deliveryStatus) as hex_value, LENGTH(deliveryStatus) as len FROM `order` WHERE orderID = 191");
$row = mysqli_fetch_assoc($result);
echo "<pre>";
print_r($row);
echo "</pre>";

// Bước 2: Set NULL trước
echo "<h3>2. Set NULL trước:</h3>";
mysqli_query($conn, "UPDATE `order` SET deliveryStatus = NULL WHERE orderID = 191");
echo "Affected: " . mysqli_affected_rows($conn) . "<br>";

// Bước 3: Set giá trị mới
echo "<h3>3. Set giá trị 'Đã hoàn tiền':</h3>";
mysqli_query($conn, "UPDATE `order` SET deliveryStatus = 'Đã hoàn tiền' WHERE orderID = 191");
echo "Affected: " . mysqli_affected_rows($conn) . "<br>";

// Bước 4: Kiểm tra kết quả
echo "<h3>4. Kết quả:</h3>";
$result2 = mysqli_query($conn, "SELECT orderID, deliveryStatus, paymentStatus FROM `order` WHERE orderID = 191");
$row2 = mysqli_fetch_assoc($result2);
echo "<pre>";
print_r($row2);
echo "</pre>";

// Bước 5: UPDATE cả 2 cùng lúc
echo "<h3>5. UPDATE cả deliveryStatus và paymentStatus cùng lúc:</h3>";
$sql = "UPDATE `order` SET deliveryStatus = 'Đã hoàn tiền', paymentStatus = 'Đã hoàn tiền' WHERE orderID = 191";
mysqli_query($conn, $sql);
echo "Query: <code>$sql</code><br>";
echo "Affected: " . mysqli_affected_rows($conn) . "<br>";

// Final check
$result3 = mysqli_query($conn, "SELECT * FROM `order` WHERE orderID = 191");
$row3 = mysqli_fetch_assoc($result3);
echo "<h3>Final:</h3>";
echo "<pre>";
print_r($row3);
echo "</pre>";
?>
