<?php
/**
 * API: Lấy thông tin chi tiết yêu cầu hoàn trả của khách hàng
 * File: api/get_return_request.php
 * VPS Compatible: Sử dụng BASE_URL constant
 */

session_name('GODIFA_USER_SESSION');
session_start();
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../model/database.php';

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để xem thông tin'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$customerID = $_SESSION['customer_id'];
$orderID = intval($_GET['orderID'] ?? 0);

if ($orderID <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Mã đơn hàng không hợp lệ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conn = Database::getInstance()->getConnection();
    
    // Lấy thông tin yêu cầu hoàn trả (chỉ của khách hàng hiện tại)
    $sql = "SELECT 
                rr.*,
                o.totalAmount,
                u.userName as adminName
            FROM return_requests rr
            INNER JOIN `order` o ON rr.orderID = o.orderID
            LEFT JOIN user u ON rr.processedBy = u.userID
            WHERE rr.orderID = ? AND rr.customerID = ?
            ORDER BY rr.createdAt DESC
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $orderID, $customerID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Parse images từ JSON
        $images = json_decode($row['images'] ?? '[]', true);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'returnID' => $row['returnID'],
                'orderID' => $row['orderID'],
                'status' => $row['status'],
                'reason' => $row['reason'],
                'images' => $images,
                'bankName' => $row['bankName'] ?? '',
                'bankAccount' => $row['bankAccount'] ?? '',
                'accountHolder' => $row['accountHolder'] ?? '',
                'adminNote' => $row['adminNote'],
                'createdAt' => $row['createdAt'],
                'processedAt' => $row['processedAt'],
                'adminName' => $row['adminName'],
                'totalAmount' => $row['totalAmount']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy yêu cầu hoàn trả cho đơn hàng này'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
