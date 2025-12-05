<?php
/**
 * Script: Thêm thông tin ngân hàng vào bảng return_requests
 * File: add_bank_info_to_return_requests.php
 * Chạy 1 lần để update database structure
 */

require_once __DIR__ . '/model/database.php';

echo "<h2>Thêm thông tin ngân hàng vào bảng return_requests</h2>";

try {
    $conn = Database::getInstance()->getConnection();
    
    // Kiểm tra và thêm column bankName
    $sql1 = "ALTER TABLE return_requests 
             ADD COLUMN IF NOT EXISTS bankName VARCHAR(100) NOT NULL COMMENT 'Tên ngân hàng nhận tiền hoàn' AFTER images";
    
    // Nếu MySQL không hỗ trợ IF NOT EXISTS, dùng cách kiểm tra trước
    $checkColumns = "SHOW COLUMNS FROM return_requests LIKE 'bankName'";
    $result = mysqli_query($conn, $checkColumns);
    
    if (mysqli_num_rows($result) == 0) {
        // Column chưa tồn tại, thêm mới
        $alterQueries = [
            "ALTER TABLE return_requests ADD COLUMN bankName VARCHAR(100) DEFAULT '' AFTER images",
            "ALTER TABLE return_requests ADD COLUMN bankAccount VARCHAR(20) DEFAULT '' AFTER bankName",
            "ALTER TABLE return_requests ADD COLUMN accountHolder VARCHAR(100) DEFAULT '' AFTER bankAccount"
        ];
        
        foreach ($alterQueries as $query) {
            if (mysqli_query($conn, $query)) {
                echo "<p style='color: green;'>✓ Đã thêm column thành công</p>";
            } else {
                $error = mysqli_error($conn);
                if (strpos($error, 'Duplicate column') !== false) {
                    echo "<p style='color: orange;'>⚠ Column đã tồn tại, bỏ qua</p>";
                } else {
                    echo "<p style='color: red;'>✗ Lỗi: $error</p>";
                }
            }
        }
        
        // Update các record cũ để không bị NULL
        $updateOld = "UPDATE return_requests 
                     SET bankName = 'Chưa cập nhật', 
                         bankAccount = '', 
                         accountHolder = 'Chưa cập nhật'
                     WHERE bankName = '' OR bankName IS NULL";
        
        if (mysqli_query($conn, $updateOld)) {
            $affected = mysqli_affected_rows($conn);
            echo "<p style='color: blue;'>ℹ Đã cập nhật $affected record cũ</p>";
        }
        
        echo "<h3 style='color: green;'>✅ Hoàn thành! Database đã được cập nhật.</h3>";
        
    } else {
        echo "<p style='color: blue;'>ℹ Các column ngân hàng đã tồn tại, không cần cập nhật.</p>";
    }
    
    // Hiển thị cấu trúc bảng hiện tại
    echo "<h3>Cấu trúc bảng return_requests hiện tại:</h3>";
    echo "<pre>";
    $showColumns = mysqli_query($conn, "SHOW COLUMNS FROM return_requests");
    while ($col = mysqli_fetch_assoc($showColumns)) {
        echo "{$col['Field']}\t{$col['Type']}\t{$col['Null']}\t{$col['Key']}\t{$col['Default']}\n";
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
?>
