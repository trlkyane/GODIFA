<?php
/**
 * Full Database Audit - Check all tables and unused columns
 */
require_once __DIR__ . '/includes/database_simple.php';

$db = Database::getInstance();
$conn = $db->connect();

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
table { border-collapse: collapse; margin: 20px 0; width: 100%; font-size: 13px; }
th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background-color: #4CAF50; color: white; position: sticky; top: 0; }
tr:nth-child(even) { background-color: #f9f9f9; }
.warning { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; border-radius: 4px; }
.success { background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0; border-radius: 4px; }
.error { background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; border-radius: 4px; }
.info { background-color: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 15px 0; border-radius: 4px; }
.unused { background-color: #fff3cd !important; }
.duplicate { background-color: #fdd !important; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
.table-section { margin: 30px 0; padding: 20px; border: 2px solid #ddd; border-radius: 8px; }
h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
.stat-box { display: inline-block; padding: 15px 25px; margin: 10px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.stat-box strong { display: block; font-size: 24px; color: #4CAF50; }
</style>";

echo "<div class='container'>";
echo "<h1>🔍 Full Database Audit</h1>";

// Get all tables
$tables_result = $conn->query("SHOW TABLES");
$all_tables = [];
while ($row = $tables_result->fetch_array()) {
    $all_tables[] = $row[0];
}

echo "<div class='info'>";
echo "<h3>📊 Database Overview</h3>";
echo "<p><strong>Total Tables:</strong> " . count($all_tables) . "</p>";
echo "<p><strong>Tables found:</strong> " . implode(', ', $all_tables) . "</p>";
echo "</div>";

// Critical tables to analyze
$important_tables = [
    'order' => 'Main order table',
    'order_delivery' => 'Delivery information',
    'order_item' => 'Order line items',
    'shipping_history' => '🔍 GHN webhook history - CHECK IF USED',
    'customer' => 'Customer information',
    'product' => 'Product catalog',
    'voucher' => 'Voucher/discount codes',
    'customer_group' => 'Customer loyalty groups',
    'user' => 'Admin/staff users',
    'cart' => 'Shopping cart sessions'
];

$tables_to_analyze = [];
$tables_not_found = [];

foreach ($important_tables as $table => $desc) {
    if (in_array($table, $all_tables)) {
        $tables_to_analyze[$table] = $desc;
    } else {
        $tables_not_found[] = $table;
    }
}

if (!empty($tables_not_found)) {
    echo "<div class='warning'>";
    echo "<p><strong>⚠️ Tables not found:</strong> " . implode(', ', $tables_not_found) . "</p>";
    echo "</div>";
}

// Analyze shipping_history specifically
if (in_array('shipping_history', $all_tables)) {
    echo "<div class='table-section' style='border-color: #ffc107;'>";
    echo "<h2>📦 SHIPPING_HISTORY Table Analysis</h2>";
    
    $result = $conn->query("SELECT COUNT(*) as total FROM shipping_history");
    $total = $result->fetch_assoc()['total'];
    
    echo "<div class='stat-box'>";
    echo "<strong>$total</strong>";
    echo "Total Records";
    echo "</div>";
    
    // Get table structure
    $result = $conn->query("DESCRIBE shipping_history");
    echo "<h3>Table Structure:</h3>";
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><code>" . $row['Field'] . "</code></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check when last used
    if ($total > 0) {
        // First, check what columns exist
        $cols_result = $conn->query("DESCRIBE shipping_history");
        $available_cols = [];
        while ($col_row = $cols_result->fetch_assoc()) {
            $available_cols[] = $col_row['Field'];
        }
        
        // Find timestamp column
        $timestamp_col = null;
        $possible_timestamp_cols = ['created_at', 'createdAt', 'timestamp', 'created', 'date'];
        foreach ($possible_timestamp_cols as $possible_col) {
            if (in_array($possible_col, $available_cols)) {
                $timestamp_col = $possible_col;
                break;
            }
        }
        
        if ($timestamp_col) {
            $result = $conn->query("SELECT MIN(`$timestamp_col`) as first_used, MAX(`$timestamp_col`) as last_used FROM shipping_history");
            $usage = $result->fetch_assoc();
            
            echo "<div class='info'>";
            echo "<p><strong>First record:</strong> " . ($usage['first_used'] ?? 'N/A') . "</p>";
            echo "<p><strong>Last record:</strong> " . ($usage['last_used'] ?? 'N/A') . "</p>";
            echo "</div>";
        }
        
        // Sample data
        $order_by = $timestamp_col ? "ORDER BY `$timestamp_col` DESC" : "";
        $result = $conn->query("SELECT * FROM shipping_history $order_by LIMIT 5");
        if ($result->num_rows > 0) {
            echo "<h3>Latest 5 Records:</h3>";
            echo "<table>";
            $first = true;
            while ($row = $result->fetch_assoc()) {
                if ($first) {
                    echo "<tr>";
                    foreach (array_keys($row) as $col) {
                        echo "<th>$col</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td>" . htmlspecialchars(substr($val, 0, 100)) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<div class='error'>";
        echo "<h3>⚠️ RECOMMENDATION for shipping_history:</h3>";
        echo "<p><strong>Status:</strong> Table has $total records but GHN webhook was removed</p>";
        echo "<p><strong>Action:</strong> This table is NO LONGER USED since we removed GHN order creation & webhook</p>";
        echo "<p><strong>Suggestion:</strong> ❌ SAFE TO DROP (backup first)</p>";
        echo "</div>";
        
        echo "<h3>SQL to remove:</h3>";
        echo "<pre>";
        echo "-- Backup first\n";
        echo "CREATE TABLE shipping_history_backup AS SELECT * FROM shipping_history;\n\n";
        echo "-- Drop table\n";
        echo "DROP TABLE shipping_history;\n";
        echo "</pre>";
    } else {
        echo "<div class='warning'>";
        echo "<p><strong>⚠️ Table is EMPTY</strong></p>";
        echo "<p><strong>Action:</strong> SAFE TO DROP</p>";
        echo "</div>";
        
        echo "<h3>SQL to remove:</h3>";
        echo "<pre>DROP TABLE shipping_history;</pre>";
    }
    
    echo "</div>";
}

// Analyze each important table
foreach ($tables_to_analyze as $table => $description) {
    if ($table === 'shipping_history') continue; // Already analyzed
    
    echo "<div class='table-section'>";
    echo "<h2>📋 Table: `$table`</h2>";
    echo "<p><em>$description</em></p>";
    
    // Get row count
    $result = $conn->query("SELECT COUNT(*) as total FROM `$table`");
    $total_rows = $result->fetch_assoc()['total'];
    
    echo "<div class='stat-box'>";
    echo "<strong>$total_rows</strong>";
    echo "Total Records";
    echo "</div>";
    
    // Get columns
    $result = $conn->query("DESCRIBE `$table`");
    $columns = [];
    $column_info = [];
    
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        $column_info[$row['Field']] = [
            'type' => $row['Type'],
            'null' => $row['Null'],
            'key' => $row['Key'],
            'default' => $row['Default']
        ];
    }
    
    echo "<h3>Columns Analysis:</h3>";
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Key</th><th>Usage</th><th>Status</th></tr>";
    
    $unused_columns = [];
    $duplicate_columns = [];
    
    foreach ($columns as $col) {
        $info = $column_info[$col];
        
        // Check usage
        $colType = strtolower($info['type']);
        // Check if it's a date/time type that doesn't support empty string comparison
        if (strpos($colType, 'datetime') !== false || 
            strpos($colType, 'timestamp') !== false || 
            strpos($colType, 'date') !== false || 
            strpos($colType, 'time') !== false) {
            $result = $conn->query("SELECT COUNT(*) as used FROM `$table` WHERE `$col` IS NOT NULL");
        } else {
            $result = $conn->query("SELECT COUNT(*) as used FROM `$table` WHERE `$col` IS NOT NULL AND `$col` != ''");
        }
        
        $usage = 0;
        if ($result) {
            $usage = $result->fetch_assoc()['used'];
        }
        
        $usage_pct = $total_rows > 0 ? round(($usage / $total_rows) * 100, 1) : 0;
        
        // Determine status
        $status = '✅ Used';
        $rowClass = '';
        
        // Check if unused
        if ($usage == 0 && $info['key'] != 'PRI') {
            $status = '❌ UNUSED';
            $rowClass = ' class="unused"';
            $unused_columns[] = $col;
        }
        
        // Check if duplicate (for order table)
        if ($table === 'order') {
            $delivery_columns = ['recipientName', 'recipientEmail', 'recipientPhone', 'deliveryAddress', 'address', 'ward', 'district', 'city', 'deliveryNotes'];
            if (in_array($col, $delivery_columns)) {
                $status = '⚠️ DUPLICATE (in order_delivery)';
                $rowClass = ' class="duplicate"';
                $duplicate_columns[] = $col;
            }
        }
        
        echo "<tr$rowClass>";
        echo "<td><code>$col</code></td>";
        echo "<td>" . $info['type'] . "</td>";
        echo "<td>" . ($info['key'] ?: '-') . "</td>";
        echo "<td>$usage / $total_rows ($usage_pct%)</td>";
        echo "<td><strong>$status</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Show SQL to clean
    $columns_to_remove = array_merge($unused_columns, $duplicate_columns);
    
    if (!empty($columns_to_remove)) {
        echo "<div class='warning'>";
        echo "<h3>🧹 Columns to remove from `$table`:</h3>";
        echo "<ul>";
        foreach ($columns_to_remove as $col) {
            $reason = in_array($col, $duplicate_columns) ? 'Duplicate' : 'Unused';
            echo "<li><code>$col</code> - $reason</li>";
        }
        echo "</ul>";
        
        echo "<h4>SQL Commands:</h4>";
        echo "<pre>";
        foreach ($columns_to_remove as $col) {
            echo "ALTER TABLE `$table` DROP COLUMN `$col`;\n";
        }
        echo "</pre>";
        echo "</div>";
    } else {
        echo "<div class='success'>✅ No columns to remove from this table</div>";
    }
    
    echo "</div>";
}

// Summary and final recommendations
echo "<div class='table-section' style='border-color: #dc3545;'>";
echo "<h2>📝 FINAL RECOMMENDATIONS</h2>";

$final_sql = [];

// 1. Drop shipping_history if exists
if (in_array('shipping_history', $all_tables)) {
    $final_sql[] = [
        'action' => 'Drop shipping_history table',
        'reason' => 'GHN webhook removed - table no longer used',
        'priority' => 'HIGH',
        'sql' => "-- Backup\nCREATE TABLE shipping_history_backup AS SELECT * FROM shipping_history;\n\n-- Drop\nDROP TABLE shipping_history;"
    ];
}

echo "<table>";
echo "<tr><th>Action</th><th>Reason</th><th>Priority</th><th>SQL</th></tr>";
foreach ($final_sql as $item) {
    $priorityColor = $item['priority'] === 'HIGH' ? 'color: red;' : 'color: orange;';
    echo "<tr>";
    echo "<td><strong>" . $item['action'] . "</strong></td>";
    echo "<td>" . $item['reason'] . "</td>";
    echo "<td style='$priorityColor'><strong>" . $item['priority'] . "</strong></td>";
    echo "<td><pre style='margin:0;'>" . $item['sql'] . "</pre></td>";
    echo "</tr>";
}
echo "</table>";

echo "<div class='info'>";
echo "<h3>✅ Next Steps:</h3>";
echo "<ol>";
echo "<li>Review all recommendations above</li>";
echo "<li>Backup database: <code>mysqldump godifa > backup.sql</code></li>";
echo "<li>Run SQL commands in phpMyAdmin</li>";
echo "<li>Run <a href='cleanup_database.php?auto_run=yes'>Auto-Clean Order Table</a></li>";
echo "<li>Verify all changes</li>";
echo "</ol>";
echo "</div>";

echo "</div>";

echo "<hr>";
echo "<p style='text-align: center;'>";
echo "<a href='cleanup_database.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px;'>Clean Order Table</a> ";
echo "<a href='inspect_database.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 5px;'>View DB Structure</a>";
echo "</p>";

echo "</div>";
?>
