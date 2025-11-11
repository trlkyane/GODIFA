<?php
/**
 * API: Get Available Vouchers for Customer
 * File: api/get_vouchers.php
 * 
 * Lấy danh sách voucher khả dụng cho khách hàng dựa trên:
 * - Customer group
 * - Voucher còn hạn
 * - Voucher còn số lượng
 * - Voucher đang active
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

require_once __DIR__ . '/../model/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->connect();
    
    $customerID = $_SESSION['customer_id'];
    
    // Lấy groupID của customer
    $stmtCustomer = $conn->prepare("SELECT groupID FROM customer WHERE customerID = ?");
    $stmtCustomer->bind_param("i", $customerID);
    $stmtCustomer->execute();
    $result = $stmtCustomer->get_result();
    $customer = $result->fetch_assoc();
    
    $groupID = $customer['groupID'] ?? null;
    
    if (!$groupID) {
        // Customer chưa có nhóm - chỉ lấy voucher public (không gắn với nhóm nào)
        $sql = "
            SELECT 
                v.voucherID,
                v.voucherName,
                v.value as discountValue,
                v.quantity,
                v.startDate,
                v.endDate,
                v.requirement,
                v.status,
                NULL as groupName,
                'public' as voucherType
            FROM voucher v
            WHERE v.status = 1
            AND v.quantity > 0
            AND v.startDate <= CURDATE()
            AND v.endDate >= CURDATE()
            AND v.voucherID NOT IN (
                SELECT DISTINCT voucherID FROM voucher_group
            )
            ORDER BY v.value DESC
        ";
        
        $vouchers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
        
    } else {
        // Customer có nhóm - lấy voucher của nhóm đó + voucher public
        $sql = "
            SELECT 
                v.voucherID,
                v.voucherName,
                v.value as discountValue,
                v.quantity,
                v.startDate,
                v.endDate,
                v.requirement,
                v.status,
                cg.groupName,
                CASE 
                    WHEN vg.voucherID IS NOT NULL THEN 'group'
                    ELSE 'public'
                END as voucherType
            FROM voucher v
            LEFT JOIN voucher_group vg ON v.voucherID = vg.voucherID AND vg.groupID = ?
            LEFT JOIN customer_group cg ON vg.groupID = cg.groupID
            WHERE v.status = 1
            AND v.quantity > 0
            AND v.startDate <= CURDATE()
            AND v.endDate >= CURDATE()
            AND (
                vg.groupID = ? 
                OR v.voucherID NOT IN (SELECT DISTINCT voucherID FROM voucher_group)
            )
            GROUP BY v.voucherID
            ORDER BY voucherType DESC, v.value DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $groupID, $groupID);
        $stmt->execute();
        $vouchers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Format data
    foreach ($vouchers as &$voucher) {
        $voucher['discountValue'] = (int)$voucher['discountValue'];
        $voucher['discountFormatted'] = number_format($voucher['discountValue'], 0, ',', '.') . '₫';
        $voucher['quantity'] = (int)$voucher['quantity'];
        $voucher['startDate'] = date('d/m/Y', strtotime($voucher['startDate']));
        $voucher['endDate'] = date('d/m/Y', strtotime($voucher['endDate']));
        $voucher['isGroupVoucher'] = ($voucher['voucherType'] === 'group');
    }
    
    echo json_encode([
        'success' => true,
        'vouchers' => $vouchers,
        'customerGroup' => $groupID ? [
            'groupID' => $groupID,
            'groupName' => $vouchers[0]['groupName'] ?? 'Unknown'
        ] : null
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
