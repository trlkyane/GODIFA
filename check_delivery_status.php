<?php
/**
 * Check deliveryStatus column definition
 */
require_once __DIR__ . '/model/database.php';

$conn = Database::getInstance()->getConnection();

echo "<h2>Check deliveryStatus Column</h2>";
$sql = "SHOW COLUMNS FROM `order` LIKE 'deliveryStatus'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

echo "<pre>";
print_r($row);
echo "</pre>";
