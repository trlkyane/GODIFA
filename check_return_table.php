<?php
require_once 'model/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== CẤU TRÚC BẢNG return_requests ===\n";
$result = $conn->query("DESCRIBE return_requests");
while ($row = $result->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']}\n";
}

echo "\n=== DỮ LIỆU return_requests ===\n";
$result = $conn->query("SELECT * FROM return_requests LIMIT 5");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
