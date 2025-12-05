<?php
require_once __DIR__ . '/model/database.php';
$conn = Database::getInstance()->getConnection();

echo "<h2>Kiểm tra bảng return_requests</h2>";

// 1. Kiểm tra bảng có tồn tại không
$sql = "SHOW TABLES LIKE 'return_requests'";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green'>✅ Bảng return_requests đã tồn tại</p>";
    
    // 2. Kiểm tra cấu trúc
    echo "<h3>Cấu trúc bảng:</h3>";
    $sql = "SHOW COLUMNS FROM return_requests";
    $result = mysqli_query($conn, $sql);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Test INSERT
    echo "<h3>Test INSERT:</h3>";
    $testOrderID = 1;
    $testCustomerID = 1;
    $testReason = "Test lý do hoàn trả";
    $testImages = json_encode(['test.jpg']);
    
    $sqlInsert = "INSERT INTO return_requests (orderID, customerID, reason, images) 
                  VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sqlInsert);
    mysqli_stmt_bind_param($stmt, "iiss", $testOrderID, $testCustomerID, $testReason, $testImages);
    
    if (mysqli_stmt_execute($stmt)) {
        $insertID = mysqli_insert_id($conn);
        echo "<p style='color: green'>✅ Test INSERT thành công! ID: $insertID</p>";
        
        // Xóa record test
        mysqli_query($conn, "DELETE FROM return_requests WHERE returnID = $insertID");
        echo "<p style='color: blue'>ℹ️ Đã xóa record test</p>";
    } else {
        echo "<p style='color: red'>❌ Test INSERT thất bại: " . mysqli_error($conn) . "</p>";
    }
    
} else {
    echo "<p style='color: red'>❌ Bảng return_requests CHƯA TỒN TẠI! Cần chạy migration.</p>";
    echo "<p>Hãy truy cập: <a href='fix_return_order_db.php'>fix_return_order_db.php</a></p>";
}
?>