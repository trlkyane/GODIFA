<?php
/**
 * Database Inspector - Check order table structure and data
 */
require_once __DIR__ . '/includes/database_simple.php';

$db = Database::getInstance();
$conn = $db->connect();

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 20px 0; width: 100%; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background-color: #4CAF50; color: white; }
tr:nth-child(even) { background-color: #f2f2f2; }
.warning { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
.info { background-color: #d1ecf1; padding: 10px; border-left: 4px solid #17a2b8; margin: 10px 0; }
.error { background-color: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin: 10px 0; }
</style>";

echo "<h1>🔍 Database Inspector - Order Table</h1>";

// 1. Check table structure
echo "<h2>📋 Table Structure: `order`</h2>";
$result = $conn->query("DESCRIBE `order`");
echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
    echo "<tr>";
    echo "<td><strong>" . $row['Field'] . "</strong></td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check for duplicate columns
echo "<div class='info'><strong>ℹ️ Total columns:</strong> " . count($columns) . "</div>";

// 2. Check for potential duplicate columns
$duplicates = [
    'recipientName' => 'Should be in order_delivery table only',
    'recipientEmail' => 'Should be in order_delivery table only',
    'recipientPhone' => 'Should be in order_delivery table only',
    'deliveryAddress' => 'Should be in order_delivery table only',
    'deliveryNotes' => 'Should be in order_delivery table only'
];

echo "<h2>⚠️ Checking for Duplicate/Unnecessary Columns</h2>";
$found_duplicates = [];
foreach ($duplicates as $col => $note) {
    if (in_array($col, $columns)) {
        $found_duplicates[] = $col;
        echo "<div class='warning'>⚠️ Found: <strong>$col</strong> - $note</div>";
    }
}

if (empty($found_duplicates)) {
    echo "<div class='info'>✅ No duplicate columns found in `order` table</div>";
}

// 3. Check delivery statuses
echo "<h2>📊 Current Delivery Statuses in Database</h2>";
$result = $conn->query("
    SELECT 
        deliveryStatus,
        COUNT(*) as total
    FROM `order`
    GROUP BY deliveryStatus
    ORDER BY total DESC
");

echo "<table><tr><th>Delivery Status</th><th>Count</th><th>Status Type</th></tr>";
$statuses = [];
while ($row = $result->fetch_assoc()) {
    $statuses[] = $row['deliveryStatus'];
    $statusClass = '';
    $statusType = '✅ New';
    if (in_array($row['deliveryStatus'], ['Chờ xử lý', 'Đang xử lý', 'Đang giao', 'Đã giao'])) {
        $statusClass = " style='background-color: #fff3cd;'";
        $statusType = '⚠️ OLD - Need Update';
    }
    echo "<tr$statusClass>";
    echo "<td><strong>" . htmlspecialchars($row['deliveryStatus']) . "</strong></td>";
    echo "<td>" . $row['total'] . "</td>";
    echo "<td>" . $statusType . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check for old statuses
$old_statuses = array_intersect($statuses, ['Chờ xử lý', 'Đang xử lý', 'Đang giao', 'Đã giao']);
if (!empty($old_statuses)) {
    echo "<div class='error'><strong>❌ Found OLD statuses:</strong> " . implode(', ', $old_statuses) . "</div>";
    echo "<div class='warning'><strong>Action needed:</strong> Run migration to update these statuses</div>";
}

// 4. Check payment statuses
echo "<h2>💰 Current Payment Statuses</h2>";
$result = $conn->query("
    SELECT 
        paymentStatus,
        COUNT(*) as total
    FROM `order`
    GROUP BY paymentStatus
    ORDER BY total DESC
");

echo "<table><tr><th>Payment Status</th><th>Count</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['paymentStatus']) . "</td>";
    echo "<td>" . $row['total'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 5. Sample recent orders
echo "<h2>🔍 Last 10 Orders - Full Details</h2>";
$result = $conn->query("
    SELECT 
        orderID,
        orderDate,
        deliveryStatus,
        paymentStatus,
        paymentMethod,
        totalAmount,
        customerID
    FROM `order`
    ORDER BY orderDate DESC
    LIMIT 10
");

echo "<table>";
echo "<tr><th>Order ID</th><th>Date</th><th>Delivery Status</th><th>Payment Status</th><th>Method</th><th>Amount</th></tr>";
while ($row = $result->fetch_assoc()) {
    $statusClass = '';
    if (in_array($row['deliveryStatus'], ['Chờ xử lý', 'Đang xử lý', 'Đang giao', 'Đã giao'])) {
        $statusClass = " style='background-color: #fff3cd;'";
    }
    echo "<tr$statusClass>";
    echo "<td><strong>#" . $row['orderID'] . "</strong></td>";
    echo "<td>" . date('d/m/Y H:i', strtotime($row['orderDate'])) . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['deliveryStatus']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['paymentStatus']) . "</td>";
    echo "<td>" . $row['paymentMethod'] . "</td>";
    echo "<td>" . number_format($row['totalAmount']) . " đ</td>";
    echo "</tr>";
}
echo "</table>";

// 6. Check order_delivery table
echo "<h2>🚚 Order Delivery Table Structure</h2>";
$result = $conn->query("DESCRIBE order_delivery");
if ($result) {
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ order_delivery table not found!</div>";
}

// 7. Generate SQL to remove duplicate columns
if (!empty($found_duplicates)) {
    echo "<h2>🔧 SQL to Remove Duplicate Columns</h2>";
    echo "<div class='warning'>";
    echo "<p><strong>⚠️ Run these SQL commands in phpMyAdmin:</strong></p>";
    echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>";
    foreach ($found_duplicates as $col) {
        echo "ALTER TABLE `order` DROP COLUMN `$col`;\n";
    }
    echo "</pre>";
    echo "</div>";
}

// 8. Generate SQL to update old statuses
if (!empty($old_statuses)) {
    echo "<h2>🔄 SQL to Update Old Statuses</h2>";
    echo "<div class='warning'>";
    echo "<p><strong>📝 Copy and run in phpMyAdmin:</strong></p>";
    echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px; font-family: monospace;'>";
    echo "-- Update old delivery statuses to new ones\n";
    echo "UPDATE `order` SET deliveryStatus = 'Chờ xác nhận' WHERE deliveryStatus = 'Chờ xử lý';\n";
    echo "UPDATE `order` SET deliveryStatus = 'Đang tiến hành vận chuyển' WHERE deliveryStatus IN ('Đang xử lý', 'Đang giao');\n";
    echo "UPDATE `order` SET deliveryStatus = 'Hoàn thành' WHERE deliveryStatus = 'Đã giao';\n\n";
    echo "-- Verify the changes\n";
    echo "SELECT deliveryStatus, COUNT(*) as total FROM `order` GROUP BY deliveryStatus;\n";
    echo "</pre>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>✅ Inspection Complete!</strong></p>";
?>
