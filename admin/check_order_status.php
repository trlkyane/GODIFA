<?php
/**
 * Quick check: View order statuses
 */
require_once __DIR__ . '/../model/database.php';

$db = Database::getInstance();
$conn = $db->connect();

// Get all distinct delivery statuses
$result = $conn->query("
    SELECT 
        deliveryStatus,
        COUNT(*) as total
    FROM `order`
    GROUP BY deliveryStatus
    ORDER BY total DESC
");

echo "<h2>Current Delivery Statuses in Database:</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th style='padding: 10px;'>Status</th><th style='padding: 10px;'>Count</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td style='padding: 10px;'>" . htmlspecialchars($row['deliveryStatus']) . "</td>";
    echo "<td style='padding: 10px; text-align: center;'>" . $row['total'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check specific recent orders
echo "<br><h2>Last 10 Orders:</h2>";
$result = $conn->query("
    SELECT 
        orderID,
        orderDate,
        deliveryStatus,
        paymentStatus,
        paymentMethod
    FROM `order`
    ORDER BY orderDate DESC
    LIMIT 10
");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th style='padding: 10px;'>Order ID</th><th style='padding: 10px;'>Date</th><th style='padding: 10px;'>Delivery Status</th><th style='padding: 10px;'>Payment Status</th><th style='padding: 10px;'>Method</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td style='padding: 10px;'>#" . $row['orderID'] . "</td>";
    echo "<td style='padding: 10px;'>" . $row['orderDate'] . "</td>";
    echo "<td style='padding: 10px; font-weight: bold;'>" . htmlspecialchars($row['deliveryStatus']) . "</td>";
    echo "<td style='padding: 10px;'>" . htmlspecialchars($row['paymentStatus']) . "</td>";
    echo "<td style='padding: 10px;'>" . $row['paymentMethod'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
