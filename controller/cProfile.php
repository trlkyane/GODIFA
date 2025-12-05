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
        
        $stmt = mysqli_prepare($conn, "
            SELECT 
                c.customerID,
                c.customerName,
                c.phone,
                c.email,
                c.status,
                c.groupID,
                cg.groupName,
                cg.description as groupDescription,
                cg.color as groupColor,
                cg.minSpent as groupMinSpent,
                cg.maxSpent as groupMaxSpent
            FROM customer c
            LEFT JOIN customer_group cg ON c.groupID = cg.groupID
            WHERE c.customerID = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $customerID);
        mysqli_stmt_execute($stmt);
        $resultProfile = mysqli_stmt_get_result($stmt);
        $result = mysqli_fetch_assoc($resultProfile);
        
        return $result;
    }
    
    /**
     * Lấy thống kê đơn hàng
     */
    public function getOrderStats($customerID) {
        $conn = $this->db->connect();
        
        $stmt = mysqli_prepare($conn, "
            SELECT 
                COUNT(*) as totalOrders,
                SUM(CASE WHEN paymentStatus = 'Đã thanh toán' THEN 1 ELSE 0 END) as paidOrders,
                SUM(CASE WHEN deliveryStatus = 'Đã giao' THEN 1 ELSE 0 END) as deliveredOrders,
                SUM(CASE WHEN paymentStatus = 'Đã thanh toán' THEN totalAmount ELSE 0 END) as totalSpent
            FROM `order`
            WHERE customerID = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $customerID);
        mysqli_stmt_execute($stmt);
        $resultStats = mysqli_stmt_get_result($stmt);
        $result = mysqli_fetch_assoc($resultStats);
        
        return $result;
    }
    
    /**
     * Cập nhật thông tin cơ bản (CHỈ TÊN VÀ SỐ ĐIỆN THOẠI)
     */
    public function updateCustomerBasicInfo($customerID, $data) {
        $conn = $this->db->connect();
        
        $stmt = mysqli_prepare($conn, "
            UPDATE customer 
            SET customerName = ?, phone = ?
            WHERE customerID = ?
        ");
        mysqli_stmt_bind_param($stmt, 
            "ssi",
            $data['customerName'],
            $data['phone'],
            $customerID
        );
        
        $success = mysqli_stmt_execute($stmt);
        
        if ($success) {
            // Cập nhật session
            $_SESSION['customer_name'] = $data['customerName'];
        }
        
        return $success;
    }
    
    /**
     * Cập nhật thông tin khách hàng (DEPRECATED - giữ để tương thích)
     */
    public function updateCustomerInfo($customerID, $data) {
        $conn = $this->db->connect();
        
        $stmt = mysqli_prepare($conn, "
            UPDATE customer 
            SET customerName = ?, phone = ?, email = ?
            WHERE customerID = ?
        ");
        mysqli_stmt_bind_param($stmt, 
            "sssi",
            $data['customerName'],
            $data['phone'],
            $data['email'],
            $customerID
        );
        
        $success = mysqli_stmt_execute($stmt);
        
        if ($success) {
            // Cập nhật session
            $_SESSION['customer_name'] = $data['customerName'];
        }
        
        return $success;
    }
}
