<?php
/**
 * FIX: Thêm giá trị 'Đã hoàn tiền' và 'Chờ xử lý hoàn tiền' vào ENUM của deliveryStatus
 */
require_once __DIR__ . '/model/database.php';
$conn = Database::getInstance()->getConnection();

echo "<h2>FIX: Cập nhật ENUM deliveryStatus</h2>";

// ALTER TABLE để thêm các giá trị mới vào ENUM
$sql = "ALTER TABLE `order` 
        MODIFY COLUMN deliveryStatus ENUM(
            'Chờ xác nhận',
            'Đang tiến hành vận chuyển',
            'Hoàn thành',
            'Đã hủy',
            'Chờ xử lý hoàn tiền',
            'Đã hoàn tiền'
        ) 
        COLLATE utf8mb3_unicode_520_ci 
        DEFAULT 'Chờ xác nhận'";

echo "<p>Đang chạy query:</p>";
echo "<pre>$sql</pre>";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green; font-weight: bold;'>✅ ĐÃ CẬP NHẬT THÀNH CÔNG!</p>";
    
    // Kiểm tra lại cấu trúc
    echo "<h3>Kiểm tra cấu trúc mới:</h3>";
    $checkSql = "SHOW COLUMNS FROM `order` LIKE 'deliveryStatus'";
    $result = mysqli_query($conn, $checkSql);
    $column = mysqli_fetch_assoc($result);
    echo "<pre>";
    print_r($column);
    echo "</pre>";
    
    // Bây giờ test UPDATE lại
    echo "<h3>Test UPDATE sau khi fix:</h3>";
    $testSql = "UPDATE `order` SET deliveryStatus = 'Đã hoàn tiền' WHERE orderID = 191";
    if (mysqli_query($conn, $testSql)) {
        echo "<p>Query thành công! Affected rows: " . mysqli_affected_rows($conn) . "</p>";
        
        // Kiểm tra giá trị
        $verifySql = "SELECT orderID, deliveryStatus, paymentStatus FROM `order` WHERE orderID = 191";
        $result = mysqli_query($conn, $verifySql);
        $row = mysqli_fetch_assoc($result);
        echo "<p>Kết quả:</p>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
        
        if ($row['deliveryStatus'] === 'Đã hoàn tiền') {
            echo "<p style='color: green; font-weight: bold; font-size: 20px;'>🎉 THÀNH CÔNG! deliveryStatus đã được cập nhật!</p>";
        } else {
            echo "<p style='color: red;'>⚠️ Vẫn chưa cập nhật được...</p>";
        }
    } else {
        echo "<p style='color: red;'>Lỗi UPDATE: " . mysqli_error($conn) . "</p>";
    }
    
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ LỖI: " . mysqli_error($conn) . "</p>";
}

mysqli_close($conn);
?>
