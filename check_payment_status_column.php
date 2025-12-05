<?php
/**
 * Debug: Kiểm tra cột paymentStatus trong bảng order
 */
require_once __DIR__ . '/model/database.php';

echo "<h2>Kiểm tra cấu trúc bảng ORDER</h2>";

$conn = Database::getInstance()->getConnection();

// Xem cấu trúc cột paymentStatus
echo "<h3>1. Cấu trúc cột paymentStatus:</h3>";
$sql = "SHOW COLUMNS FROM `order` LIKE 'paymentStatus'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $column = mysqli_fetch_assoc($result);
    echo "<pre>";
    print_r($column);
    echo "</pre>";
    
    // Kiểm tra xem có giá trị 'Đã hoàn tiền' trong ENUM chưa
    if (strpos($column['Type'], 'Đã hoàn tiền') !== false) {
        echo "<p style='color:green;'>✅ Cột đã có giá trị 'Đã hoàn tiền'</p>";
    } else {
        echo "<p style='color:red;'>❌ Cột CHƯA có giá trị 'Đã hoàn tiền'</p>";
        echo "<p>Type hiện tại: <strong>" . htmlspecialchars($column['Type']) . "</strong></p>";
        
        // Tạo ALTER query để thêm
        echo "<h3>2. Cần chạy query sau:</h3>";
        echo "<textarea style='width:100%; height:100px;'>";
        echo "ALTER TABLE `order` MODIFY COLUMN paymentStatus ENUM('Chưa thanh toán','Đã thanh toán','Đã hoàn tiền') DEFAULT 'Chưa thanh toán';";
        echo "</textarea>";
    }
} else {
    echo "<p style='color:red;'>❌ Không tìm thấy cột paymentStatus</p>";
}

// Xem các giá trị hiện có
echo "<h3>3. Các giá trị paymentStatus đang dùng:</h3>";
$sql2 = "SELECT DISTINCT paymentStatus, COUNT(*) as count FROM `order` GROUP BY paymentStatus";
$result2 = mysqli_query($conn, $sql2);
echo "<ul>";
while ($row = mysqli_fetch_assoc($result2)) {
    echo "<li><strong>" . htmlspecialchars($row['paymentStatus']) . "</strong>: " . $row['count'] . " đơn</li>";
}
echo "</ul>";
