<?php
/**
 * Profile Controller
 * File: controller/cProfile.php
 * Xử lý logic cho trang thông tin cá nhân
 */

class ProfileController {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../model/database.php';
        $this->db = Database::getInstance();
    }
    
    /**
     * Lấy thông tin khách hàng
     */
    public function getCustomerInfo($customerID) {
        $conn = $this->db->connect();
        
        $stmt = $conn->prepare("
            SELECT 
                c.customerID,
                c.customerName,
                c.phone,
                c.email,
                c.status,
                c.groupID,
                cg.groupName,
                cg.description as groupDescription
            FROM customer c
            LEFT JOIN customer_group cg ON c.groupID = cg.groupID
            WHERE c.customerID = ?
        ");
        $stmt->bind_param("i", $customerID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result;
    }
    
    /**
     * Lấy thống kê đơn hàng
     */
    public function getOrderStats($customerID) {
        $conn = $this->db->connect();
        
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as totalOrders,
                SUM(CASE WHEN paymentStatus = 'Đã thanh toán' THEN 1 ELSE 0 END) as paidOrders,
                SUM(CASE WHEN deliveryStatus = 'Đã giao' THEN 1 ELSE 0 END) as deliveredOrders,
                SUM(CASE WHEN paymentStatus = 'Đã thanh toán' THEN totalAmount ELSE 0 END) as totalSpent
            FROM `order`
            WHERE customerID = ?
        ");
        $stmt->bind_param("i", $customerID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result;
    }
    
    /**
     * Cập nhật thông tin khách hàng
     */
    public function updateCustomerInfo($customerID, $data) {
        $conn = $this->db->connect();
        
        $stmt = $conn->prepare("
            UPDATE customer 
            SET customerName = ?, phone = ?, email = ?
            WHERE customerID = ?
        ");
        $stmt->bind_param(
            "sssi",
            $data['customerName'],
            $data['phone'],
            $data['email'],
            $customerID
        );
        
        $success = $stmt->execute();
        
        if ($success) {
            // Cập nhật session
            $_SESSION['customer_name'] = $data['customerName'];
        }
        
        return $success;
    }
}
