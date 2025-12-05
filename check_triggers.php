<?php
/**
 * Kiểm tra và xóa trigger liên quan đến returnStatus
 */
require_once __DIR__ . '/model/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Check Triggers</title></head><body>";
echo "<h2>🔍 Kiểm tra Database Triggers</h2>";

$conn = Database::getInstance()->getConnection();

// 1. Xem tất cả triggers
echo "<h3>1. Danh sách Triggers:</h3>";
$sqlShowTriggers = "SHOW TRIGGERS FROM godifa1";
$result = mysqli_query($conn, $sqlShowTriggers);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>Trigger</th><th>Event</th><th>Table</th><th>Statement</th><th>Action</th></tr>";
    
    $triggersToDelete = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $hasReturnStatus = stripos($row['Statement'], 'returnStatus') !== false;
        $bgColor = $hasReturnStatus ? 'background:#ffcccc;' : '';
        
        echo "<tr style='$bgColor'>";
        echo "<td><strong>" . htmlspecialchars($row['Trigger']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Event']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Table']) . "</td>";
        echo "<td><pre style='max-width:500px;overflow:auto;'>" . htmlspecialchars($row['Statement']) . "</pre></td>";
        
        if ($hasReturnStatus) {
            $triggersToDelete[] = $row['Trigger'];
            echo "<td><span style='color:red;'>❌ Có returnStatus - CẦN XÓA</span></td>";
        } else {
            echo "<td><span style='color:green;'>✅ OK</span></td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Form xóa triggers
    if (!empty($triggersToDelete)) {
        echo "<hr>";
        echo "<h3>2. Xóa Triggers có vấn đề:</h3>";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_triggers'])) {
            foreach ($triggersToDelete as $triggerName) {
                $sqlDrop = "DROP TRIGGER IF EXISTS " . mysqli_real_escape_string($conn, $triggerName);
                if (mysqli_query($conn, $sqlDrop)) {
                    echo "<p style='color:green;'>✅ Đã xóa trigger: <strong>$triggerName</strong></p>";
                } else {
                    echo "<p style='color:red;'>❌ Lỗi khi xóa trigger: <strong>$triggerName</strong> - " . mysqli_error($conn) . "</p>";
                }
            }
            echo "<p><strong>✅ Hoàn tất! Reload trang để kiểm tra.</strong></p>";
        } else {
            echo "<p style='color:orange;'>⚠️ Phát hiện <strong>" . count($triggersToDelete) . "</strong> trigger(s) có vấn đề:</p>";
            echo "<ul>";
            foreach ($triggersToDelete as $triggerName) {
                echo "<li><code>$triggerName</code></li>";
            }
            echo "</ul>";
            
            echo "<form method='POST'>";
            echo "<button type='submit' name='delete_triggers' style='padding:10px 20px; background:red; color:white; border:none; cursor:pointer; font-size:16px;'>";
            echo "🗑️ XÓA TẤT CẢ TRIGGERS CÓ VẤN ĐỀ";
            echo "</button>";
            echo "</form>";
        }
    } else {
        echo "<p style='color:green;'><strong>✅ Không có trigger nào có vấn đề!</strong></p>";
    }
    
} else {
    echo "<p>Không có trigger nào trong database.</p>";
}

// 3. Kiểm tra cấu trúc bảng order
echo "<hr>";
echo "<h3>3. Cấu trúc bảng ORDER:</h3>";
$sqlDescOrder = "DESCRIBE `order`";
$resultOrder = mysqli_query($conn, $sqlDescOrder);

$hasReturnStatusColumn = false;
echo "<ul>";
while ($row = mysqli_fetch_assoc($resultOrder)) {
    if ($row['Field'] === 'returnStatus') {
        $hasReturnStatusColumn = true;
        echo "<li style='color:red;'><strong>" . $row['Field'] . "</strong> - ⚠️ CỘT NÀY ĐANG TỒN TẠI!</li>";
    } else {
        echo "<li>" . $row['Field'] . "</li>";
    }
}
echo "</ul>";

if ($hasReturnStatusColumn) {
    echo "<p style='color:orange;'>⚠️ Bảng ORDER có cột <code>returnStatus</code>. Cột này không cần thiết nữa!</p>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drop_column'])) {
        $sqlDropColumn = "ALTER TABLE `order` DROP COLUMN returnStatus";
        if (mysqli_query($conn, $sqlDropColumn)) {
            echo "<p style='color:green;'>✅ Đã xóa cột returnStatus!</p>";
        } else {
            echo "<p style='color:red;'>❌ Lỗi: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<form method='POST'>";
        echo "<button type='submit' name='drop_column' style='padding:10px 20px; background:orange; color:white; border:none; cursor:pointer;'>";
        echo "🗑️ XÓA CỘT returnStatus";
        echo "</button>";
        echo "</form>";
    }
}

echo "</body></html>";
