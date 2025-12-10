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
    
    /**
     * Đổi mật khẩu khách hàng
     */
    public function changePassword($customerID, $oldPassword, $newPassword) {
        $conn = $this->db->connect();
        
        // Kiểm tra mật khẩu cũ
        $stmt = mysqli_prepare($conn, "SELECT password FROM customer WHERE customerID = ?");
        mysqli_stmt_bind_param($stmt, "i", $customerID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $customer = mysqli_fetch_assoc($result);
        
        if (!$customer) {
            return ['success' => false, 'message' => 'Không tìm thấy tài khoản!'];
        }
        
        // Verify old password
        if (!password_verify($oldPassword, $customer['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu cũ không đúng!'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = mysqli_prepare($conn, "UPDATE customer SET password = ? WHERE customerID = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $customerID);
        
        if (mysqli_stmt_execute($stmt)) {
            return ['success' => true, 'message' => 'Đổi mật khẩu thành công!'];
        } else {
            return ['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại!'];
        }
    }
}
