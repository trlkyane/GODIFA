<?php
/**
 * Auto-cleanup database - Remove unused columns
 */
require_once __DIR__ . '/includes/database_simple.php';

$db = Database::getInstance();
$conn = $db->connect();

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
table { border-collapse: collapse; margin: 20px 0; width: 100%; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background-color: #4CAF50; color: white; }
tr:nth-child(even) { background-color: #f2f2f2; }
.warning { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; border-radius: 4px; }
.success { background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0; border-radius: 4px; }
.error { background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; border-radius: 4px; }
.info { background-color: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 15px 0; border-radius: 4px; }
.btn { display: inline-block; padding: 10px 20px; margin: 10px 5px 10px 0; background: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
.btn-danger { background: #dc3545; }
.btn-success { background: #28a745; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";

echo "<div class='container'>";
echo "<h1>🧹 Database Cleanup Tool</h1>";

// Step 1: Analyze order table
echo "<h2>📋 Step 1: Analyzing `order` table...</h2>";

$result = $conn->query("DESCRIBE `order`");
$columns = [];
$column_types = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
    $column_types[$row['Field']] = [
        'type' => $row['Type'],
        'null' => $row['Null'],
        'default' => $row['Default']
    ];
}

echo "<div class='info'>✅ Found " . count($columns) . " columns in `order` table</div>";

// Step 2: Check which columns are always NULL
echo "<h2>🔍 Step 2: Checking for NULL/Empty columns...</h2>";

$empty_columns = [];
$total_orders = $conn->query("SELECT COUNT(*) as total FROM `order`")->fetch_assoc()['total'];

echo "<p>Total orders in database: <strong>$total_orders</strong></p>";

foreach ($columns as $col) {
    // Skip important columns
    if (in_array($col, ['orderID', 'orderDate', 'customerID', 'totalAmount', 'paymentStatus', 'deliveryStatus', 'paymentMethod', 'voucherID', 'userID', 'shippingFee', 'note'])) {
        continue;
    }
    
    // Handle DATETIME columns differently
    $colType = $column_types[$col]['type'];
    if (strpos($colType, 'datetime') !== false || strpos($colType, 'timestamp') !== false) {
        $result = $conn->query("SELECT COUNT(*) as non_null FROM `order` WHERE `$col` IS NOT NULL");
    } else {
        $result = $conn->query("SELECT COUNT(*) as non_null FROM `order` WHERE `$col` IS NOT NULL AND `$col` != ''");
    }
    
    if ($result) {
        $non_null_count = $result->fetch_assoc()['non_null'];
        if ($non_null_count == 0) {
            $empty_columns[] = $col;
        }
    }
}

if (!empty($empty_columns)) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Found " . count($empty_columns) . " columns that are ALWAYS NULL/Empty:</h3>";
    echo "<ul>";
    foreach ($empty_columns as $col) {
        echo "<li><code>$col</code> (" . $column_types[$col]['type'] . ")</li>";
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='success'>✅ No completely empty columns found</div>";
}

// Step 3: Check for duplicate columns (should be in order_delivery)
echo "<h2>🔄 Step 3: Checking for duplicate columns...</h2>";

$should_be_in_delivery = [
    'recipientName' => 'Duplicate - exists in order_delivery',
    'recipientEmail' => 'Duplicate - exists in order_delivery', 
    'recipientPhone' => 'Duplicate - exists in order_delivery',
    'deliveryAddress' => 'Duplicate - exists in order_delivery',
    'deliveryNotes' => 'Duplicate - exists in order_delivery',
    'address' => 'Duplicate - exists in order_delivery',
    'ward' => 'Duplicate - exists in order_delivery',
    'district' => 'Duplicate - exists in order_delivery',
    'city' => 'Duplicate - exists in order_delivery'
];

$duplicate_columns = [];
foreach ($should_be_in_delivery as $col => $reason) {
    if (in_array($col, $columns)) {
        $duplicate_columns[$col] = $reason;
    }
}

if (!empty($duplicate_columns)) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Found " . count($duplicate_columns) . " duplicate columns:</h3>";
    echo "<table>";
    echo "<tr><th>Column</th><th>Reason</th></tr>";
    foreach ($duplicate_columns as $col => $reason) {
        echo "<tr><td><code>$col</code></td><td>$reason</td></tr>";
    }
    echo "</table>";
    echo "</div>";
} else {
    echo "<div class='success'>✅ No duplicate columns found</div>";
}

// Step 4: Check for old GHN columns
echo "<h2>📦 Step 4: Checking for old GHN columns...</h2>";

$old_ghn_columns = [
    'transactionCode' => 'Old payment transaction code',
    'bankTransactionId' => 'Old bank transaction ID',
    'qrImagePath' => 'Old QR payment image',
    'cancelReason' => 'Better to keep this one'
    // 'shippingCode' => Removed - feature simplified
];

$found_ghn_columns = [];
foreach ($old_ghn_columns as $col => $desc) {
    if (in_array($col, $columns)) {
        $result = $conn->query("SELECT COUNT(*) as non_null FROM `order` WHERE `$col` IS NOT NULL AND `$col` != ''");
        $usage = $result->fetch_assoc()['non_null'];
        $found_ghn_columns[$col] = [
            'desc' => $desc,
            'usage' => $usage,
            'keep' => ($col === 'cancelReason' || $col === 'transactionCode')
        ];
    }
}

if (!empty($found_ghn_columns)) {
    echo "<table>";
    echo "<tr><th>Column</th><th>Description</th><th>Used in</th><th>Action</th></tr>";
    foreach ($found_ghn_columns as $col => $info) {
        $action = $info['keep'] ? '✅ Keep' : '❌ Remove';
        $rowClass = $info['keep'] ? '' : " style='background-color: #fff3cd;'";
        echo "<tr$rowClass>";
        echo "<td><code>$col</code></td>";
        echo "<td>" . $info['desc'] . "</td>";
        echo "<td>" . $info['usage'] . " / $total_orders orders</td>";
        echo "<td><strong>$action</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 5: Generate SQL
echo "<h2>🔧 Step 5: Generated SQL Commands</h2>";

$columns_to_remove = array_merge($empty_columns, array_keys($duplicate_columns));
// Remove columns we should keep
$columns_to_remove = array_filter($columns_to_remove, function($col) use ($found_ghn_columns) {
    if (isset($found_ghn_columns[$col]) && $found_ghn_columns[$col]['keep']) {
        return false;
    }
    return true;
});

// Remove qrImagePath and bankTransactionId if they exist
foreach ($found_ghn_columns as $col => $info) {
    if (!$info['keep'] && !in_array($col, $columns_to_remove)) {
        $columns_to_remove[] = $col;
    }
}

$columns_to_remove = array_unique($columns_to_remove);

if (!empty($columns_to_remove)) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Will remove " . count($columns_to_remove) . " columns</h3>";
    echo "<p><strong>Columns to be removed:</strong></p>";
    echo "<ul>";
    foreach ($columns_to_remove as $col) {
        echo "<li><code>$col</code></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📝 SQL Commands (Copy to phpMyAdmin):</h3>";
    echo "<pre>";
    echo "-- Backup first!\n";
    echo "-- CREATE TABLE order_backup AS SELECT * FROM `order`;\n\n";
    echo "-- Remove unused columns from order table\n";
    foreach ($columns_to_remove as $col) {
        echo "ALTER TABLE `order` DROP COLUMN `$col`;\n";
    }
    echo "\n-- Verify changes\n";
    echo "DESCRIBE `order`;\n";
    echo "</pre>";
    echo "</div>";
    
    // Auto-run option
    if (isset($_GET['auto_run']) && $_GET['auto_run'] === 'yes') {
        echo "<div class='error'>";
        echo "<h3>⚡ AUTO-RUN MODE</h3>";
        echo "<p>Executing SQL commands...</p>";
        
        foreach ($columns_to_remove as $col) {
            $sql = "ALTER TABLE `order` DROP COLUMN `$col`";
            if ($conn->query($sql)) {
                echo "<p>✅ Removed column: <code>$col</code></p>";
            } else {
                echo "<p>❌ Error removing <code>$col</code>: " . $conn->error . "</p>";
            }
        }
        
        echo "<div class='success'><strong>✅ Cleanup completed!</strong></div>";
        echo "<p><a href='inspect_database.php' class='btn btn-success'>View Updated Structure</a></p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Ready to clean?</h3>";
        echo "<p><strong>Option 1:</strong> Copy SQL above and run in phpMyAdmin (Safer)</p>";
        echo "<p><strong>Option 2:</strong> <a href='?auto_run=yes' class='btn btn-danger' onclick='return confirm(\"This will remove " . count($columns_to_remove) . " columns permanently. Are you sure?\")'>Auto-Run Cleanup Now</a></p>";
        echo "</div>";
    }
} else {
    echo "<div class='success'>";
    echo "<h3>✅ Database is clean!</h3>";
    echo "<p>No unnecessary columns found to remove.</p>";
    echo "</div>";
}

// Step 6: Update old statuses
echo "<h2>🔄 Step 6: Delivery Status Migration</h2>";

$result = $conn->query("
    SELECT deliveryStatus, COUNT(*) as total 
    FROM `order` 
    GROUP BY deliveryStatus
");

$old_statuses_found = [];
while ($row = $result->fetch_assoc()) {
    if (in_array($row['deliveryStatus'], ['Chờ xử lý', 'Đang xử lý', 'Đang giao', 'Đã giao'])) {
        $old_statuses_found[$row['deliveryStatus']] = $row['total'];
    }
}

if (!empty($old_statuses_found)) {
    echo "<div class='error'>";
    echo "<h3>❌ Found old delivery statuses:</h3>";
    echo "<table>";
    echo "<tr><th>Old Status</th><th>Count</th><th>New Status</th></tr>";
    foreach ($old_statuses_found as $old => $count) {
        $new = '';
        if ($old === 'Chờ xử lý') $new = 'Chờ xác nhận';
        if ($old === 'Đang xử lý' || $old === 'Đang giao') $new = 'Đang tiến hành vận chuyển';
        if ($old === 'Đã giao') $new = 'Hoàn thành';
        echo "<tr>";
        echo "<td><code>$old</code></td>";
        echo "<td>$count</td>";
        echo "<td><strong>$new</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📝 SQL to update statuses:</h3>";
    echo "<pre>";
    echo "-- Update delivery statuses\n";
    echo "UPDATE `order` SET deliveryStatus = 'Chờ xác nhận' WHERE deliveryStatus = 'Chờ xử lý';\n";
    echo "UPDATE `order` SET deliveryStatus = 'Đang tiến hành vận chuyển' WHERE deliveryStatus IN ('Đang xử lý', 'Đang giao');\n";
    echo "UPDATE `order` SET deliveryStatus = 'Hoàn thành' WHERE deliveryStatus = 'Đã giao';\n\n";
    echo "-- Verify\n";
    echo "SELECT deliveryStatus, COUNT(*) as total FROM `order` GROUP BY deliveryStatus;\n";
    echo "</pre>";
    echo "</div>";
    
    if (isset($_GET['auto_run']) && $_GET['auto_run'] === 'yes') {
        echo "<p><strong>Updating statuses...</strong></p>";
        $conn->query("UPDATE `order` SET deliveryStatus = 'Chờ xác nhận' WHERE deliveryStatus = 'Chờ xử lý'");
        $conn->query("UPDATE `order` SET deliveryStatus = 'Đang tiến hành vận chuyển' WHERE deliveryStatus IN ('Đang xử lý', 'Đang giao')");
        $conn->query("UPDATE `order` SET deliveryStatus = 'Hoàn thành' WHERE deliveryStatus = 'Đã giao'");
        echo "<p>✅ Statuses updated!</p>";
    }
} else {
    echo "<div class='success'>✅ All statuses are up to date!</div>";
}

echo "<hr>";
echo "<p><a href='inspect_database.php' class='btn'>View Database Structure</a></p>";
echo "<p><a href='check_order_status.php' class='btn'>Check Order Statuses</a></p>";

echo "</div>";
?>
