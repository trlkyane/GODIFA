<?php
require_once __DIR__ . '/model/database.php';
$conn = Database::getInstance()->getConnection();

$sql = "SHOW COLUMNS FROM user";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . "<br>";
}
?>