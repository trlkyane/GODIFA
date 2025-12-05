<?php
/**
 * Script: Thêm cột ngân hàng vào bảng return_requests
 * File: run_bank_migration.php
 * Chạy script này 1 lần để update database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/model/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Migration</title>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style></head><body>";
echo "<h2>🔧 Thêm thông tin ngân hàng vào bảng return_requests</h2>";

try {
    $conn = Database::getInstance()->getConnection();
    
    echo "<p class='info'>📊 Đang kiểm tra cấu trúc bảng...</p>";
    
    // Kiểm tra từng column
    $columnsToAdd = [
        'bankName' => "VARCHAR(100) DEFAULT '' COMMENT 'Tên ngân hàng'",
        'bankAccount' => "VARCHAR(20) DEFAULT '' COMMENT 'Số tài khoản'",
        'accountHolder' => "VARCHAR(100) DEFAULT '' COMMENT 'Tên chủ tài khoản'"
    ];
    
    $addedCount = 0;
    $existingCount = 0;
    
    foreach ($columnsToAdd as $columnName => $columnDef) {
        // Kiểm tra column có tồn tại chưa
        $checkQuery = "SHOW COLUMNS FROM return_requests LIKE '$columnName'";
        $result = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($result) == 0) {
            // Column chưa tồn tại, thêm mới
            $alterQuery = "ALTER TABLE return_requests ADD COLUMN $columnName $columnDef AFTER images";
            
            if (mysqli_query($conn, $alterQuery)) {
                echo "<p class='success'>✅ Đã thêm column <strong>$columnName</strong></p>";
                $addedCount++;
            } else {
                echo "<p class='error'>❌ Lỗi khi thêm column <strong>$columnName</strong>: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p class='info'>ℹ️ Column <strong>$columnName</strong> đã tồn tại, bỏ qua</p>";
            $existingCount++;
        }
    }
    
    echo "<hr>";
    echo "<h3>📋 Kết quả:</h3>";
    echo "<ul>";
    echo "<li>Đã thêm mới: <strong>$addedCount</strong> column</li>";
    echo "<li>Đã tồn tại: <strong>$existingCount</strong> column</li>";
    echo "</ul>";
    
    if ($addedCount > 0) {
        echo "<p class='success'><strong>✅ Migration hoàn tất! Bây giờ bạn có thể test lại chức năng Return Order.</strong></p>";
    } else {
        echo "<p class='info'><strong>ℹ️ Không có thay đổi nào được thực hiện. Database đã được cập nhật trước đó.</strong></p>";
    }
    
    // Hiển thị cấu trúc bảng hiện tại
    echo "<hr>";
    echo "<h3>🗄️ Cấu trúc bảng return_requests:</h3>";
    echo "<pre>";
    $descQuery = "DESCRIBE return_requests";
    $descResult = mysqli_query($conn, $descQuery);
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($descResult)) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['Field'] ?? '') . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Null'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Key'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Lỗi: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='view/account/order_history.php'>← Quay lại Order History</a></p>";
echo "</body></html>";
