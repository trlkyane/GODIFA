<?php
/**
 * Order History Controller
 * File: controller/cOrderHistory.php
 * Xử lý logic cho lịch sử đơn hàng
 */

class OrderHistoryController {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../model/database.php';
        $this->db = Database::getInstance();
    }
    
    /**
     * Lấy danh sách đơn hàng của khách hàng
     */
    public function getCustomerOrders($customerID, $limit = null) {
        $conn = $this->db->connect();
        
        $sql = "
            SELECT 
                o.orderID,
                o.orderDate,
                o.paymentDate,
                o.totalAmount,
                o.paymentStatus,
                o.deliveryStatus,
                o.transactionCode,
                o.paymentMethod,
                od.recipientName,
                od.recipientPhone,
                od.fullAddress,
                rr.status AS returnStatus
            FROM `order` o
            LEFT JOIN order_delivery od ON o.orderID = od.orderID
            LEFT JOIN return_requests rr ON o.orderID = rr.orderID
            WHERE o.customerID = ?
            ORDER BY o.orderDate DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
        }
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($limit) {
            mysqli_stmt_bind_param($stmt, "ii", $customerID, $limit);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $customerID);
        }
        
        mysqli_stmt_execute($stmt);
        $resultOrders = mysqli_stmt_get_result($stmt);
        $result = mysqli_fetch_all($resultOrders, MYSQLI_ASSOC);
        
        return $result;
    }
    
    /**
     * Lấy chi tiết đơn hàng
     */
    public function getOrderDetail($orderID, $customerID) {
        $conn = $this->db->connect();
        
        // Get order info
        $stmt = mysqli_prepare($conn, "
            SELECT 
                o.*,
                od.recipientName,
                od.recipientPhone,
                od.recipientEmail,
                od.address,
                od.ward,
                od.district,
                od.city,
                od.fullAddress,
                od.provinceId,
                od.districtId,
                od.wardCode,
                od.deliveryNotes
            FROM `order` o
            LEFT JOIN order_delivery od ON o.orderID = od.orderID
            WHERE o.orderID = ? AND o.customerID = ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $orderID, $customerID);
        mysqli_stmt_execute($stmt);
        $resultOrder = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($resultOrder);
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $stmt = mysqli_prepare($conn, "
            SELECT 
                oi.*,
                p.productName,
                p.image
            FROM order_item oi
            LEFT JOIN product p ON oi.productID = p.productID
            WHERE oi.orderID = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $orderID);
        mysqli_stmt_execute($stmt);
        $resultItems = mysqli_stmt_get_result($stmt);
        $order['items'] = mysqli_fetch_all($resultItems, MYSQLI_ASSOC);
        
        return $order;
    }
}
