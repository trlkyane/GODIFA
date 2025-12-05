<?php
/**
 * Debug: Kiểm tra cấu trúc bảng order
 */

require_once __DIR__ . '/model/database.php';

$db = Database::getInstance();
$conn = $db->connect();

echo "<h2>Kiểm tra cấu trúc bảng `order`</h2>";

// 1. Show columns
echo "<h3>1. Cấu trúc columns:</h3>";
$result = mysqli_query($conn, "SHOW COLUMNS FROM `order`");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// 2. Show triggers
echo "<h3>2. Triggers trên bảng order:</h3>";
$result2 = mysqli_query($conn, "SHOW TRIGGERS WHERE `Table` = 'order'");
if (mysqli_num_rows($result2) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Trigger</th><th>Event</th><th>Timing</th><th>Statement</th></tr>";
    while ($row = mysqli_fetch_assoc($result2)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Trigger']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Event']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Timing']) . "</td>";
        echo "<td><pre>" . htmlspecialchars($row['Statement']) . "</pre></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không có trigger nào</p>";
}

// 3. Test UPDATE trực tiếp
echo "<h3>3. Test UPDATE trực tiếp (không qua prepared statement):</h3>";
$sql = "UPDATE `order` SET deliveryStatus = 'Đã hoàn tiền' WHERE orderID = 191";
echo "<p>Query: <code>$sql</code></p>";

if (mysqli_query($conn, $sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "<p style='color:green'>✅ Thành công! Affected rows: $affected</p>";
    
    // Check lại
    $check = mysqli_query($conn, "SELECT deliveryStatus FROM `order` WHERE orderID = 191");
    $row = mysqli_fetch_assoc($check);
    echo "<p>deliveryStatus hiện tại: <strong>" . htmlspecialchars($row['deliveryStatus'] ?? 'NULL/EMPTY') . "</strong></p>";
} else {
    echo "<p style='color:red'>❌ Lỗi: " . mysqli_error($conn) . "</p>";
}

// 4. Test với backticks và quotes khác nhau
echo "<h3>4. Test các cách viết khác nhau:</h3>";

$tests = [
    "UPDATE `order` SET `deliveryStatus` = 'Đã hoàn tiền' WHERE `orderID` = 191",
    "UPDATE `order` SET deliveryStatus = \"Đã hoàn tiền\" WHERE orderID = 191",
    "UPDATE order SET deliveryStatus = 'Đã hoàn tiền' WHERE orderID = 191"
];

foreach ($tests as $i => $testSQL) {
    echo "<p><strong>Test " . ($i + 1) . ":</strong></p>";
    echo "<code>$testSQL</code><br>";
    
    if (mysqli_query($conn, $testSQL)) {
        $affected = mysqli_affected_rows($conn);
        echo "<span style='color:green'>✅ OK - Affected: $affected</span><br>";
    } else {
        echo "<span style='color:red'>❌ Error: " . mysqli_error($conn) . "</span><br>";
    }
    
    // Check value
    $check = mysqli_query($conn, "SELECT deliveryStatus, LENGTH(deliveryStatus) as len FROM `order` WHERE orderID = 191");
    $row = mysqli_fetch_assoc($check);
    echo "Value: '" . htmlspecialchars($row['deliveryStatus']) . "' (length: {$row['len']})<br><br>";
}
?>
