<?php
/**
 * Debug: Test API create_return_request.php
 */

// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_name('GODIFA_USER_SESSION');
session_start();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Create Return Request</title></head><body>";
echo "<h2>🔍 Debug Create Return Request API</h2>";

// 1. Kiểm tra session
echo "<h3>1. Session:</h3>";
if (!isset($_SESSION['customer_id'])) {
    echo "<p style='color:red;'>❌ Chưa đăng nhập! <a href='view/auth/customer-login.php'>Đăng nhập</a></p>";
    echo "</body></html>";
    exit;
}
echo "<p style='color:green;'>✅ customer_id: " . $_SESSION['customer_id'] . "</p>";

// 2. Kiểm tra cấu trúc bảng return_requests
require_once __DIR__ . '/model/database.php';
$conn = Database::getInstance()->getConnection();

echo "<h3>2. Cấu trúc bảng return_requests:</h3>";
$sqlDesc = "DESCRIBE return_requests";
$result = mysqli_query($conn, $sqlDesc);
echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr style='background:#f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
$hasBank = false;
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
    
    if (in_array($row['Field'], ['bankName', 'bankAccount', 'accountHolder'])) {
        $hasBank = true;
    }
}
echo "</table>";

if ($hasBank) {
    echo "<p style='color:green;'>✅ Bảng có cột ngân hàng</p>";
} else {
    echo "<p style='color:red;'>❌ Bảng CHƯA có cột ngân hàng! Cần chạy migration.</p>";
}

// 3. Test gọi API
echo "<h3>3. Test gọi API:</h3>";
echo "<form method='POST' action='api/create_return_request.php' target='_blank'>";
echo "<p><label>Order ID: <input type='number' name='orderID' value='201' required></label></p>";
echo "<p><label>Lý do: <textarea name='reason' required>Test yêu cầu hoàn trả</textarea></label></p>";
echo "<p><label>Ngân hàng: <input type='text' name='bankName' value='Vietcombank' required></label></p>";
echo "<p><label>Số TK: <input type='text' name='bankAccount' value='1234567890' required></label></p>";
echo "<p><label>Chủ TK: <input type='text' name='accountHolder' value='NGUYEN VAN A' required></label></p>";
echo "<p><button type='submit'>Gửi yêu cầu</button></p>";
echo "</form>";

// 4. Kiểm tra đơn hàng có thể return không
echo "<h3>4. Các đơn hàng có thể Return:</h3>";
$customerID = $_SESSION['customer_id'];
$sqlOrders = "SELECT o.orderID, o.deliveryStatus, o.paymentStatus 
              FROM `order` o 
              WHERE o.customerID = ? 
              AND o.deliveryStatus = 'Hoàn thành'
              ORDER BY o.orderID DESC
              LIMIT 5";
$stmt = mysqli_prepare($conn, $sqlOrders);
mysqli_stmt_bind_param($stmt, "i", $customerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>Đơn #" . $row['orderID'] . " - " . $row['deliveryStatus'] . " - " . $row['paymentStatus'] . "</li>";
}
echo "</ul>";

// 5. Test trực tiếp INSERT
echo "<h3>5. Test INSERT trực tiếp:</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_insert'])) {
    $testOrderID = intval($_POST['test_orderID']);
    $testReason = "Test từ debug script";
    $testBank = "Vietcombank";
    $testAccount = "9876543210";
    $testHolder = "NGUYEN VAN B";
    
    $sqlInsert = "INSERT INTO return_requests (orderID, customerID, reason, images, bankName, bankAccount, accountHolder) 
                  VALUES (?, ?, ?, '[]', ?, ?, ?)";
    $stmtInsert = mysqli_prepare($conn, $sqlInsert);
    mysqli_stmt_bind_param($stmtInsert, "iissss", $testOrderID, $customerID, $testReason, $testBank, $testAccount, $testHolder);
    
    if (mysqli_stmt_execute($stmtInsert)) {
        echo "<p style='color:green;'>✅ INSERT thành công! ID: " . mysqli_insert_id($conn) . "</p>";
    } else {
        echo "<p style='color:red;'>❌ INSERT thất bại: " . mysqli_error($conn) . "</p>";
    }
}

echo "<form method='POST'>";
echo "<p><label>Test INSERT với Order ID: <input type='number' name='test_orderID' value='201' required></label></p>";
echo "<p><button type='submit' name='test_insert'>Test INSERT</button></p>";
echo "</form>";

echo "</body></html>";
