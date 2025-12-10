<?php
require_once 'model/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Kiểm tra các đơn có payment_status = 'refunded' nhưng delivery_status = 'pending'
$query = "SELECT orderID, customerID, paymentMethod, paymentStatus, deliveryStatus, 
                 totalAmount, orderDate
          FROM `order` 
          WHERE paymentStatus = 'refunded' AND deliveryStatus = 'pending'
          ORDER BY orderDate DESC";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$results = [];
while ($row = mysqli_fetch_assoc($result)) {
    $results[] = $row;
}

echo "=== KIỂM TRA ĐỐI SOÁT: Payment Status vs Delivery Status ===\n\n";
echo "Số đơn hàng có payment_status='refunded' nhưng delivery_status='pending': " . count($results) . "\n\n";

if (count($results) > 0) {
    echo "Chi tiết các đơn hàng:\n";
    echo str_repeat("=", 100) . "\n";
    
    foreach ($results as $row) {
        echo "OrderID: {$row['orderID']}\n";
        echo "Customer: {$row['customerID']}\n";
        echo "Payment Method: {$row['paymentMethod']}\n";
        echo "Payment Status: {$row['paymentStatus']}\n";
        echo "Delivery Status: {$row['deliveryStatus']}\n";
        echo "Total Amount: " . number_format($row['totalAmount']) . " VND\n";
        echo "Order Date: {$row['orderDate']}\n";
        echo str_repeat("-", 100) . "\n";
    }
} else {
    echo "✅ KHÔNG có trường hợp nào paymentStatus='refunded' nhưng deliveryStatus='pending'\n";
}

// Kiểm tra thống kê tổng quan
echo "\n=== THỐNG KÊ TỔNG QUAN ===\n\n";

$statsQuery = "SELECT 
                paymentStatus, 
                deliveryStatus, 
                COUNT(*) as count,
                SUM(totalAmount) as total
               FROM `order` 
               GROUP BY paymentStatus, deliveryStatus
               ORDER BY paymentStatus, deliveryStatus";

$statsResult = mysqli_query($conn, $statsQuery);
$stats = [];
while ($row = mysqli_fetch_assoc($statsResult)) {
    $stats[] = $row;
}

echo "Payment Status | Delivery Status | Số đơn | Tổng tiền\n";
echo str_repeat("=", 100) . "\n";

foreach ($stats as $stat) {
    printf("%-15s | %-16s | %6d | %s VND\n", 
        $stat['paymentStatus'], 
        $stat['deliveryStatus'], 
        $stat['count'],
        number_format($stat['total'])
    );
}
