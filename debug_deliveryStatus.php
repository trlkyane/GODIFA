<?php
// DEBUG: Kiểm tra tại sao deliveryStatus không update được
require_once __DIR__ . '/model/database.php';
$conn = Database::getInstance()->getConnection();

echo "<h2>DEBUG: Kiểm tra deliveryStatus column</h2>";

$orderID = 191;

// 1. Kiểm tra trạng thái hiện tại
echo "<h3>1. Trạng thái hiện tại orderID $orderID:</h3>";
$sql = "SELECT orderID, deliveryStatus, paymentStatus, LENGTH(deliveryStatus) as ds_length 
        FROM `order` WHERE orderID = $orderID";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
echo "<pre>";
print_r($row);
echo "</pre>";
echo "deliveryStatus = '" . $row['deliveryStatus'] . "' (length: " . $row['ds_length'] . ")<br>";
echo "deliveryStatus (hex): " . bin2hex($row['deliveryStatus']) . "<br>";

// 2. Kiểm tra cấu trúc column
echo "<h3>2. Cấu trúc column deliveryStatus:</h3>";
$sql = "SHOW FULL COLUMNS FROM `order` LIKE 'deliveryStatus'";
$result = mysqli_query($conn, $sql);
$column = mysqli_fetch_assoc($result);
echo "<pre>";
print_r($column);
echo "</pre>";

// 3. Kiểm tra có trigger nào không
echo "<h3>3. Triggers trên bảng order:</h3>";
$sql = "SHOW TRIGGERS WHERE `Table` = 'order'";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) > 0) {
    while ($trigger = mysqli_fetch_assoc($result)) {
        echo "<pre>";
        print_r($trigger);
        echo "</pre>";
    }
} else {
    echo "Không có trigger nào.<br>";
}

// 4. Test UPDATE với giá trị khác
echo "<h3>4. Test UPDATE deliveryStatus:</h3>";

// Test 1: Update với 'TEST'
$sql = "UPDATE `order` SET deliveryStatus = 'TEST' WHERE orderID = $orderID";
$result = mysqli_query($conn, $sql);
echo "Query 1: $sql<br>";
echo "Result: " . ($result ? 'SUCCESS' : 'FAILED: ' . mysqli_error($conn)) . "<br>";
echo "Affected rows: " . mysqli_affected_rows($conn) . "<br>";

// Kiểm tra giá trị sau update
$sql = "SELECT deliveryStatus FROM `order` WHERE orderID = $orderID";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
echo "deliveryStatus sau UPDATE 'TEST': '" . $row['deliveryStatus'] . "'<br><br>";

// Test 2: Update với 'Đã hoàn tiền'
$sql = "UPDATE `order` SET deliveryStatus = 'Đã hoàn tiền' WHERE orderID = $orderID";
$result = mysqli_query($conn, $sql);
echo "Query 2: $sql<br>";
echo "Result: " . ($result ? 'SUCCESS' : 'FAILED: ' . mysqli_error($conn)) . "<br>";
echo "Affected rows: " . mysqli_affected_rows($conn) . "<br>";

// Kiểm tra giá trị sau update
$sql = "SELECT deliveryStatus FROM `order` WHERE orderID = $orderID";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
echo "deliveryStatus sau UPDATE 'Đã hoàn tiền': '" . $row['deliveryStatus'] . "'<br><br>";

// Test 3: Update cả 2 column cùng lúc
$sql = "UPDATE `order` SET deliveryStatus = 'Đã hoàn tiền', paymentStatus = 'Đã hoàn tiền' WHERE orderID = $orderID";
$result = mysqli_query($conn, $sql);
echo "Query 3: $sql<br>";
echo "Result: " . ($result ? 'SUCCESS' : 'FAILED: ' . mysqli_error($conn)) . "<br>";
echo "Affected rows: " . mysqli_affected_rows($conn) . "<br>";

// Kiểm tra giá trị sau update
$sql = "SELECT deliveryStatus, paymentStatus FROM `order` WHERE orderID = $orderID";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
echo "deliveryStatus sau UPDATE cả 2: '" . $row['deliveryStatus'] . "'<br>";
echo "paymentStatus sau UPDATE cả 2: '" . $row['paymentStatus'] . "'<br>";

echo "<h3>5. Kiểm tra toàn bộ record:</h3>";
$sql = "SELECT * FROM `order` WHERE orderID = $orderID";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
echo "<pre>";
print_r($row);
echo "</pre>";

mysqli_close($conn);
?>
