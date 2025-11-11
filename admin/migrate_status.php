<?php
/**
 * Auto Migration: Update delivery statuses
 * Run this file ONCE to update all old statuses
 */
require_once __DIR__ . '/../model/database.php';

$db = Database::getInstance();
$conn = $db->connect();

echo "<h2>🔄 Updating Delivery Statuses...</h2>";

// 1. Update "Chờ xử lý" -> "Chờ xác nhận"
$result1 = $conn->query("UPDATE `order` SET deliveryStatus = 'Chờ xác nhận' WHERE deliveryStatus = 'Chờ xử lý'");
$affected1 = $conn->affected_rows;
echo "<p>✅ Updated '$affected1' orders from 'Chờ xử lý' to 'Chờ xác nhận'</p>";

// 2. Update "Đang xử lý" -> "Đang tiến hành vận chuyển"
$result2 = $conn->query("UPDATE `order` SET deliveryStatus = 'Đang tiến hành vận chuyển' WHERE deliveryStatus = 'Đang xử lý'");
$affected2 = $conn->affected_rows;
echo "<p>✅ Updated '$affected2' orders from 'Đang xử lý' to 'Đang tiến hành vận chuyển'</p>";

// 3. Update "Đang giao" -> "Đang tiến hành vận chuyển"
$result3 = $conn->query("UPDATE `order` SET deliveryStatus = 'Đang tiến hành vận chuyển' WHERE deliveryStatus = 'Đang giao'");
$affected3 = $conn->affected_rows;
echo "<p>✅ Updated '$affected3' orders from 'Đang giao' to 'Đang tiến hành vận chuyển'</p>";

// 4. Update "Đã giao" -> "Hoàn thành"
$result4 = $conn->query("UPDATE `order` SET deliveryStatus = 'Hoàn thành' WHERE deliveryStatus = 'Đã giao'");
$affected4 = $conn->affected_rows;
echo "<p>✅ Updated '$affected4' orders from 'Đã giao' to 'Hoàn thành'</p>";

$total = $affected1 + $affected2 + $affected3 + $affected4;
echo "<h3 style='color: green;'>✨ Total: Updated $total orders!</h3>";

// Show current statuses
echo "<h3>📊 Current Status Distribution:</h3>";
$result = $conn->query("
    SELECT 
        deliveryStatus,
        COUNT(*) as total
    FROM `order`
    GROUP BY deliveryStatus
    ORDER BY total DESC
");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th style='padding: 10px;'>Status</th><th style='padding: 10px;'>Count</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td style='padding: 10px;'>" . htmlspecialchars($row['deliveryStatus']) . "</td>";
    echo "<td style='padding: 10px; text-align: center;'>" . $row['total'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><p><strong>⚠️ Delete this file after running!</strong></p>";
?>
