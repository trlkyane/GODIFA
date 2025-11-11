<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../model/database.php';

$search = $_GET['search'] ?? '';

if (empty($search)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập mã tìm kiếm'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Search by orderID only (shippingCode removed)
    $sql = "SELECT 
                o.orderID,
                o.deliveryStatus,
                o.paymentStatus,
                od.recipientName,
                od.recipientPhone,
                od.deliveryAddress,
                od.ward,
                od.district,
                od.province
            FROM `order` o
            LEFT JOIN order_delivery od ON o.orderID = od.orderID
            WHERE o.orderID = :search
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':search' => $search]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy đơn hàng'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Shipping history simplified - just return order info
    echo json_encode([
        'success' => true,
        'order' => $order,
        'history' => [] // Empty history (feature removed)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
