<?php
// Include database connection BEFORE any output
require_once __DIR__ . '/includes/database_simple.php';

// Get database connection
$db = Database::getInstance();
$conn = $db->connect();

// Get action parameter
$action = $_GET['action'] ?? 'show';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Fix - GODIFA</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .warning {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            border-left: 5px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .error {
            background: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info {
            background: #d1ecf1;
            border-left: 5px solid #17a2b8;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }
        .btn {
            padding: 15px 30px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,53,69,0.3);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .checklist {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .checklist h3 {
            margin-top: 0;
            color: #333;
        }
        .checklist ul {
            list-style: none;
            padding: 0;
        }
        .checklist li {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }
        .step {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
        }
        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            font-weight: bold;
            font-size: 20px;
            margin-right: 15px;
        }
        .step h3 {
            display: inline-block;
            margin: 0;
            color: #333;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
        }
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .result-table th,
        .result-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .result-table th {
            background: #007bff;
            color: white;
        }
        .result-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .progress {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            width: 0%;
            transition: width 0.5s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Structure Fix</h1>
        <p class="subtitle">Chuẩn hóa database GODIFA theo cấu trúc MVC</p>

<?php
// Database already connected at the top of the file

function showCurrentStatus($conn) {
    echo "<div class='step'>";
    echo "<span class='step-number'>📊</span>";
    echo "<h3>Tình trạng hiện tại</h3>";
    
    // Check shipping_history
    $result = $conn->query("SHOW TABLES LIKE 'shipping_history'");
    $has_shipping_history = $result->num_rows > 0;
    
    // Check order structure
    $result = $conn->query("DESCRIBE `order`");
    $order_columns = [];
    while ($row = $result->fetch_assoc()) {
        $order_columns[] = $row['Field'];
    }
    
    // Check delivery status values
    $result = $conn->query("SHOW COLUMNS FROM `order` LIKE 'deliveryStatus'");
    $delivery_status_info = $result->fetch_assoc();
    
    echo "<div class='checklist'>";
    echo "<h3>Phát hiện vấn đề:</h3>";
    echo "<ul>";
    
    $issues = 0;
    
    if ($has_shipping_history) {
        echo "<li style='border-left-color: #ffc107;'>⚠️ Bảng <code>shipping_history</code> tồn tại (không dùng)</li>";
        $issues++;
    }
    
    $unused_columns = ['shippingMetadata', 'actualDeliveryTime', 'qrExpiredAt', 'qrUrl'];
    foreach ($unused_columns as $col) {
        if (in_array($col, $order_columns)) {
            echo "<li style='border-left-color: #ffc107;'>⚠️ Column <code>$col</code> không dùng</li>";
            $issues++;
        }
    }
    
    if (strpos($delivery_status_info['Type'], 'Đang xử lý') !== false) {
        echo "<li style='border-left-color: #dc3545;'>❌ Delivery status chưa migrate (còn old values)</li>";
        $issues++;
    }
    
    // Check old statuses in data
    $result = $conn->query("SELECT COUNT(*) as total FROM `order` WHERE deliveryStatus IN ('Chờ xử lý', 'Đang xử lý', 'Đang giao', 'Đã giao')");
    $old_status_count = $result->fetch_assoc()['total'];
    if ($old_status_count > 0) {
        echo "<li style='border-left-color: #dc3545;'>❌ Có $old_status_count đơn hàng dùng old status</li>";
        $issues++;
    }
    
    // Check COD payment
    $result = $conn->query("SELECT COUNT(*) as total FROM `order` WHERE paymentMethod = 'COD' AND deliveryStatus = 'Hoàn thành' AND paymentStatus != 'Đã thanh toán'");
    $cod_need_payment = $result->fetch_assoc()['total'];
    if ($cod_need_payment > 0) {
        echo "<li style='border-left-color: #ffc107;'>⚠️ Có $cod_need_payment đơn COD hoàn thành chưa auto-payment</li>";
        $issues++;
    }
    
    echo "</ul>";
    
    if ($issues == 0) {
        echo "<div class='success'>";
        echo "<strong>✅ Database đã được chuẩn hóa!</strong>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<strong>Phát hiện $issues vấn đề cần fix</strong>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    
    return $issues;
}

function createBackup($conn) {
    echo "<div class='step'>";
    echo "<span class='step-number'>💾</span>";
    echo "<h3>Tạo Backup</h3>";
    
    try {
        $result = $conn->query("CREATE TABLE IF NOT EXISTS order_backup_" . date('Ymd_His') . " AS SELECT * FROM `order`");
        
        if ($result) {
            echo "<div class='success'>";
            echo "✅ Đã backup bảng <code>order</code> thành công!";
            echo "</div>";
            return true;
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "❌ Lỗi backup: " . $e->getMessage();
        echo "</div>";
        return false;
    }
    
    echo "</div>";
}

function dropUnusedColumns($conn) {
    $columns_to_drop = ['shippingMetadata', 'actualDeliveryTime', 'qrExpiredAt', 'qrUrl'];
    $sql_statements = '';
    
    // Check which columns exist
    $result = $conn->query("SHOW COLUMNS FROM `order`");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    // Generate DROP statements for existing columns
    foreach ($columns_to_drop as $col) {
        if (in_array($col, $existing_columns)) {
            $sql_statements .= "ALTER TABLE `order` DROP COLUMN `$col`;\n";
        }
    }
    
    return $sql_statements;
}

function executeFix($conn) {
    echo "<div class='step'>";
    echo "<span class='step-number'>🔧</span>";
    echo "<h3>Thực hiện Fix</h3>";
    
    $sql_file = __DIR__ . '/../migrations/fix_database_structure.sql';
    
    if (!file_exists($sql_file)) {
        echo "<div class='error'>";
        echo "❌ Không tìm thấy file: $sql_file";
        echo "</div>";
        echo "</div>";
        return false;
    }
    
    $sql = file_get_contents($sql_file);
    
    // Handle PHP-specific markers
    if (strpos($sql, '__PHP_DROP_COLUMNS__') !== false) {
        // Handle column drops in PHP
        $drop_columns_sql = dropUnusedColumns($conn);
        $sql = str_replace('-- __PHP_DROP_COLUMNS__', $drop_columns_sql, $sql);
    }
    
    // Remove comments
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/^#.*$/m', '', $sql);
    
    // Handle DELIMITER commands - remove them for PHP execution
    $sql = preg_replace('/DELIMITER\s+\$\$/i', '', $sql);
    $sql = preg_replace('/DELIMITER\s+;/i', '', $sql);
    $sql = str_replace('$$', ';', $sql);
    
    // Split by semicolon but handle multi-statement blocks
    $queries = [];
    $buffer = '';
    $in_procedure = false;
    
    foreach (explode(';', $sql) as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        // Check if entering procedure/trigger
        if (preg_match('/(CREATE\s+(PROCEDURE|TRIGGER|FUNCTION))/i', $statement)) {
            $in_procedure = true;
            $buffer = $statement;
            continue;
        }
        
        if ($in_procedure) {
            $buffer .= ';' . $statement;
            if (preg_match('/\bEND\s*$/i', $statement)) {
                $in_procedure = false;
                $queries[] = $buffer;
                $buffer = '';
            }
        } else {
            if (!empty($statement)) {
                $queries[] = $statement;
            }
        }
    }
    
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    echo "<div class='progress'>";
    echo "<div class='progress-bar' id='progressBar'>0%</div>";
    echo "</div>";
    
    $total = count($queries);
    
    foreach ($queries as $index => $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        // Skip verification SELECT queries
        if (preg_match('/^SELECT.*FROM.*GROUP BY/is', $query)) continue;
        
        try {
            if ($conn->multi_query($query)) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->next_result());
            }
            
            if ($conn->errno) {
                throw new Exception($conn->error);
            }
            
            $success_count++;
        } catch (Exception $e) {
            // Ignore "doesn't exist" errors for DROP commands
            if (stripos($e->getMessage(), "doesn't exist") === false && 
                stripos($e->getMessage(), "Unknown column") === false) {
                $error_count++;
                $errors[] = [
                    'query' => substr($query, 0, 150) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Update progress
        $progress = round((($index + 1) / $total) * 100);
        echo "<script>document.getElementById('progressBar').style.width = '$progress%'; document.getElementById('progressBar').innerText = '$progress%';</script>";
        flush();
    }
    
    echo "<div class='info'>";
    echo "<strong>Kết quả:</strong><br>";
    echo "✅ Thành công: $success_count queries<br>";
    if ($error_count > 0) {
        echo "❌ Lỗi: $error_count queries<br>";
        echo "<details><summary>Xem chi tiết lỗi</summary>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li><code>" . htmlspecialchars($error['query']) . "</code><br>";
            echo "Error: " . htmlspecialchars($error['error']) . "</li>";
        }
        echo "</ul>";
        echo "</details>";
    }
    echo "</div>";
    
    echo "</div>";
    
    return $error_count == 0;
}

function showVerification($conn) {
    echo "<div class='step'>";
    echo "<span class='step-number'>✅</span>";
    echo "<h3>Verification</h3>";
    
    // Check delivery status distribution
    echo "<h4>Phân bố Delivery Status:</h4>";
    $result = $conn->query("
        SELECT 
            deliveryStatus, 
            COUNT(*) as total,
            SUM(CASE WHEN paymentMethod = 'COD' THEN 1 ELSE 0 END) as cod_orders
        FROM `order`
        GROUP BY deliveryStatus
        ORDER BY 
            CASE deliveryStatus
                WHEN 'Chờ xác nhận' THEN 1
                WHEN 'Đang tiến hành vận chuyển' THEN 2
                WHEN 'Hoàn thành' THEN 3
                WHEN 'Đã hủy' THEN 4
            END
    ");
    
    echo "<table class='result-table'>";
    echo "<tr><th>Delivery Status</th><th>Tổng đơn</th><th>COD Orders</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['deliveryStatus'] . "</strong></td>";
        echo "<td>" . $row['total'] . "</td>";
        echo "<td>" . $row['cod_orders'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check payment status
    echo "<h4>Phân bố Payment Status:</h4>";
    $result = $conn->query("SELECT paymentStatus, COUNT(*) as total FROM `order` GROUP BY paymentStatus");
    
    echo "<table class='result-table'>";
    echo "<tr><th>Payment Status</th><th>Tổng đơn</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['paymentStatus'] . "</td>";
        echo "<td>" . $row['total'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check tables
    echo "<h4>Kiểm tra Tables:</h4>";
    $result = $conn->query("SHOW TABLES LIKE 'shipping_history'");
    if ($result->num_rows == 0) {
        echo "<div class='success'>✅ Bảng <code>shipping_history</code> đã xóa</div>";
    } else {
        echo "<div class='warning'>⚠️ Bảng <code>shipping_history</code> vẫn còn</div>";
    }
    
    echo "</div>";
}

// Main execution (conn already initialized)
if ($action === 'show') {
    $issues = showCurrentStatus($conn);
    
    if ($issues > 0) {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Cần fix database!</h3>";
        echo "<p>Click nút bên dưới để tự động fix database structure.</p>";
        echo "<p><strong>Lưu ý:</strong> Script sẽ tự động backup trước khi fix.</p>";
        echo "</div>";
        
        echo "<div class='btn-group'>";
        echo "<a href='?action=fix' class='btn btn-primary'>🔧 Fix Database Now</a>";
        echo "<a href='../migrations/README_DATABASE_FIX.md' class='btn btn-success' target='_blank'>📖 Đọc hướng dẫn</a>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Database đã OK!</h3>";
        echo "<p>Không cần fix gì thêm.</p>";
        echo "</div>";
    }
    
} elseif ($action === 'fix') {
    echo "<h2>🚀 Đang fix database...</h2>";
    
    // Step 1: Backup
    $backup_ok = createBackup($conn);
    
    if (!$backup_ok) {
        echo "<div class='error'>";
        echo "<strong>❌ Backup failed! Không thể tiếp tục.</strong>";
        echo "</div>";
        echo "<div class='btn-group'>";
        echo "<a href='?action=show' class='btn btn-primary'>← Quay lại</a>";
        echo "</div>";
        exit;
    }
    
    // Step 2: Execute fix
    $fix_ok = executeFix($conn);
    
    // Step 3: Verification
    showVerification($conn);
    
    if ($fix_ok) {
        echo "<div class='success' style='text-align: center; padding: 30px;'>";
        echo "<h2>✅ Fix database thành công!</h2>";
        echo "<p>Database đã được chuẩn hóa theo cấu trúc MVC.</p>";
        echo "</div>";
    } else {
        echo "<div class='warning' style='text-align: center; padding: 30px;'>";
        echo "<h2>⚠️ Fix hoàn tất với một số lỗi</h2>";
        echo "<p>Vui lòng check chi tiết bên trên.</p>";
        echo "</div>";
    }
    
    echo "<div class='btn-group'>";
    echo "<a href='../admin/pages/orders.php' class='btn btn-primary'>📦 Xem Orders</a>";
    echo "<a href='?action=show' class='btn btn-success'>🔄 Check lại Status</a>";
    echo "</div>";
}
?>

    </div>
</body>
</html>
