<?php
require_once __DIR__ . '/database.php';

class Customer {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Đăng ký khách hàng mới
    public function register($customerName, $phone, $email, $password) {
        // Hash password bằng MD5 (như trong database mẫu)
        $hashedPassword = md5($password);
        
        $sql = "INSERT INTO customer (customerName, phone, email, password) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $customerName, $phone, $email, $hashedPassword);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đăng nhập
    public function login($email, $password) {
        $hashedPassword = md5($password);
        
        $sql = "SELECT * FROM customer WHERE email = ? AND password = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $hashedPassword);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Kiểm tra email đã tồn tại
    public function emailExists($email) {
        $sql = "SELECT customerID FROM customer WHERE email = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    // Lấy khách hàng theo số điện thoại
    public function getCustomerByPhone($phone) {
        $sql = "SELECT * FROM customer WHERE phone = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $phone);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Tạo khách hàng mới (cho admin tạo đơn)
    public function createCustomer($data) {
        $hashedPassword = md5($data['password']);
        $sql = "INSERT INTO customer (customerName, phone, email, address, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", 
            $data['customerName'], 
            $data['phone'], 
            $data['email'], 
            $data['address'], 
            $hashedPassword
        );
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }
    
    // Lấy thông tin khách hàng theo ID
    public function getCustomerById($id) {
        $sql = "SELECT * FROM customer WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Lấy tất cả khách hàng (với thông tin nhóm)
    public function getAllCustomers() {
        $sql = "SELECT c.*, cg.groupName, cg.color as groupColor
                FROM customer c
                LEFT JOIN customer_group cg ON c.groupID = cg.groupID
                ORDER BY c.customerID DESC";
        $result = mysqli_query($this->conn, $sql);
        $customers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $customers[] = $row;
        }
        return $customers;
    }
    
    // Cập nhật thông tin khách hàng
    public function updateCustomerAccount($id, $customerName, $phone, $gender, $dateOfBirth) {
        $sql = "UPDATE customer 
                SET customerName = ?, phone = ?, gender = ?, birthdate = ?  
                WHERE customerID = ?"; // <-- ĐÃ SỬA: dateOfBirth thành birthdate
        
        $stmt = mysqli_prepare($this->conn, $sql);
        
        // Tham số: 4 strings (s) và 1 integer (i)
        mysqli_stmt_bind_param($stmt, "ssssi", 
            $customerName, 
            $phone, 
            $gender,
            $dateOfBirth, // Giá trị của biến $dateOfBirth sẽ được bind vào cột birthdate
            $id
        );
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Cập nhật trạng thái khách hàng (Hoạt động/Đã khóa)
    public function updateStatus($id, $status) {
        $sql = "UPDATE customer SET status = ? WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $status, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Cập nhật nhóm khách hàng
    public function updateGroup($id, $groupID) {
        $sql = "UPDATE customer SET groupID = ? WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $groupID, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đổi mật khẩu
    public function changePassword($id, $newPassword) {
        $hashedPassword = md5($newPassword);
        $sql = "UPDATE customer SET password = ? WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Xóa khách hàng
    public function deleteCustomer($id) {
        $sql = "DELETE FROM customer WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đếm tổng số khách hàng
    public function countCustomers() {
        $sql = "SELECT COUNT(*) as total FROM customer";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Lấy lịch sử mua hàng của khách hàng
    public function getOrderHistory($customerID) {
        $sql = "SELECT o.*, 
                       COUNT(od.productID) as productCount,
                       SUM(od.quantity) as totalProducts
                FROM `order` o
                LEFT JOIN order_details od ON o.orderID = od.orderID
                WHERE o.customerID = ?
                GROUP BY o.orderID
                ORDER BY o.orderDate DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
        return $orders;
    }
    
    // Thống kê khách hàng
    public function getCustomerStats($customerID) {
        $sql = "SELECT 
                    COUNT(o.orderID) as totalOrders,
                    COALESCE(SUM(CASE WHEN o.paymentStatus = 'Đã thanh toán' THEN o.totalAmount ELSE 0 END), 0) as totalSpent,
                    MAX(o.orderDate) as lastOrderDate
                FROM `order` o
                WHERE o.customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Tìm kiếm khách hàng (với thông tin nhóm)
    public function searchCustomers($keyword) {
        $searchTerm = "%$keyword%";
        $sql = "SELECT c.*, cg.groupName, cg.color as groupColor
                FROM customer c
                LEFT JOIN customer_group cg ON c.groupID = cg.groupID
                WHERE c.customerName LIKE ? OR c.email LIKE ? OR c.phone LIKE ?
                ORDER BY c.customerID DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $searchTerm, $searchTerm, $searchTerm);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $customers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $customers[] = $row;
        }
        return $customers;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
