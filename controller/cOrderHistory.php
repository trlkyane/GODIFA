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
                o.totalAmount,
                o.paymentStatus,
                o.deliveryStatus,
                o.transactionCode,
                o.paymentMethod,
                od.recipientName,
                od.recipientPhone,
                od.fullAddress
            FROM `order` o
            LEFT JOIN order_delivery od ON o.orderID = od.orderID
            WHERE o.customerID = ?
            ORDER BY o.orderDate DESC
        ";
        
        if ($limit) {
            $sql .= " LIMIT ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($limit) {
            $stmt->bind_param("ii", $customerID, $limit);
        } else {
            $stmt->bind_param("i", $customerID);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $result;
    }
    
    /**
     * Lấy chi tiết đơn hàng
     */
    public function getOrderDetail($orderID, $customerID) {
        $conn = $this->db->connect();
        
        // Get order info
        $stmt = $conn->prepare("
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
        $stmt->bind_param("ii", $orderID, $customerID);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $stmt = $conn->prepare("
            SELECT 
                oi.*,
                p.productName,
                p.image
            FROM order_item oi
            LEFT JOIN product p ON oi.productID = p.productID
            WHERE oi.orderID = ?
        ");
        $stmt->bind_param("i", $orderID);
        $stmt->execute();
        $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $order;
    }
}
