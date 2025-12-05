<?php
require_once __DIR__ . '/model/database.php';
$conn = Database::getInstance()->getConnection();

$tables = ['order', 'customer', 'user'];

foreach ($tables as $table) {
    $sql = "SHOW TABLE STATUS LIKE '$table'";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        echo "Table: $table | Engine: " . $row['Engine'] . "<br>";
    } else {
        echo "Table: $table NOT FOUND<br>";
    }
}
?>