<?php
require_once __DIR__ . '/database.php';

class Customer {
    // Cáº­p nháº­t thÃ´ng tin khÃ¡ch hÃ ng (bao gá»“m ghi chÃº)
public function updateCustomer($id, $data) {
    
    // 1. GÃ¡n cÃ¡c giÃ¡ trá»‹ cáº§n rÃ ng buá»™c vÃ o cÃ¡c biáº¿n Ä‘á»™c láº­p
    $customerName = $data['customerName'];
    $phone = $data['phone'];
    $email = $data['email'];
    
    // Xá»­ lÃ½ biáº¿n 'note' Ä‘á»ƒ Ä‘áº£m báº£o nÃ³ lÃ  má»™t biáº¿n vÃ  cÃ³ giÃ¡ trá»‹ máº·c Ä‘á»‹nh
    // DÃ¹ isset() kiá»ƒm tra cÃ³ tá»“n táº¡i hay khÃ´ng, giÃ¡ trá»‹ gÃ¡n cuá»‘i cÃ¹ng pháº£i lÃ  má»™t biáº¿n Ä‘á»™c láº­p
    $note = isset($data['note']) ? $data['note'] : '';

    $sql = "UPDATE customer SET customerName = ?, phone = ?, email = ?, note = ? WHERE customerID = ?";
    $stmt = mysqli_prepare($this->conn, $sql);
    
    // 2. Sá»­ dá»¥ng cÃ¡c biáº¿n Ä‘á»™c láº­p trong mysqli_stmt_bind_param()
    mysqli_stmt_bind_param(
        $stmt,
        "ssssi",
        $customerName, // Biáº¿n Ä‘á»™c láº­p (Argument 3)
        $phone,        // Biáº¿n Ä‘á»™c láº­p (Argument 4)
        $email,        // Biáº¿n Ä‘á»™c láº­p (Argument 5)
        $note,         // Biáº¿n Ä‘á»™c láº­p (Argument 6 - ÄÃ£ gÃ¢y ra lá»—i trÆ°á»›c Ä‘Ã³)
        $id            // Biáº¿n Ä‘á»™c láº­p (Argument 7)
    );
    
    return mysqli_stmt_execute($stmt);
}
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // ÄÄƒng kÃ½ khÃ¡ch hÃ ng má»›i
    public function register($customerName, $phone, $email, $password) {
        // Hash password báº±ng MD5 (nhÆ° trong database máº«u)
        $hashedPassword = md5($password);
        
        $sql = "INSERT INTO customer (customerName, phone, email, password) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $customerName, $phone, $email, $hashedPassword);
        return mysqli_stmt_execute($stmt);
    }
    
    // ÄÄƒng nháº­p
    public function login($email, $password) {
        $hashedPassword = md5($password);
        
        $sql = "SELECT * FROM customer WHERE email = ? AND password = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $hashedPassword);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Kiá»ƒm tra email Ä‘Ã£ tá»“n táº¡i
    public function emailExists($email) {
        $sql = "SELECT customerID FROM customer WHERE email = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    // Láº¥y khÃ¡ch hÃ ng theo sá»‘ Ä‘iá»‡n thoáº¡i
    public function getCustomerByPhone($phone) {
        $sql = "SELECT * FROM customer WHERE phone = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $phone);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Táº¡o khÃ¡ch hÃ ng má»›i (cho admin táº¡o Ä‘Æ¡n)
    public function createCustomer($data) {
        $hashedPassword = md5($data['password']);
        $note = isset($data['note']) ? $data['note'] : '';
        $sql = "INSERT INTO customer (customerName, phone, email, address, password, note) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", 
            $data['customerName'], 
            $data['phone'], 
            $data['email'], 
            $data['address'], 
            $hashedPassword,
            $note
        );
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }
    
    // Láº¥y thÃ´ng tin khÃ¡ch hÃ ng theo ID
    public function getCustomerById($id) {
    $sql = "SELECT * FROM customer WHERE customerID = ?";             
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Láº¥y táº¥t cáº£ khÃ¡ch hÃ ng (vá»›i thÃ´ng tin nhÃ³m)
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
    
    // Cáº­p nháº­t thÃ´ng tin cÆ¡ báº£n khÃ¡ch hÃ ng (chá»‰ tÃªn vÃ  SÄT)
    public function updateCustomerBasicInfo($id, $customerName, $phone) {
        $sql = "UPDATE customer 
                SET customerName = ?, phone = ?
                WHERE customerID = ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $customerName, $phone, $id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Cáº­p nháº­t tráº¡ng thÃ¡i khÃ¡ch hÃ ng (Hoáº¡t Ä‘á»™ng/ÄÃ£ khÃ³a)
    public function updateStatus($id, $status) {
        $sql = "UPDATE customer SET status = ? WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $status, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Cáº­p nháº­t nhÃ³m khÃ¡ch hÃ ng
    public function updateGroup($id, $groupID) {
        $sql = "UPDATE customer SET groupID = ? WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $groupID, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Äá»•i máº­t kháº©u
    public function changePassword($id, $newPassword) {
        $hashedPassword = md5($newPassword);
        $sql = "UPDATE customer SET password = ? WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // XÃ³a khÃ¡ch hÃ ng
    public function deleteCustomer($id) {
        $sql = "DELETE FROM customer WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Äáº¿m tá»•ng sá»‘ khÃ¡ch hÃ ng
    public function countCustomers() {
        $sql = "SELECT COUNT(*) as total FROM customer";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Láº¥y lá»‹ch sá»­ mua hÃ ng cá»§a khÃ¡ch hÃ ng
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
    
    // Thá»‘ng kÃª khÃ¡ch hÃ ng
    public function getCustomerStats($customerID) {
        $sql = "SELECT 
                    COUNT(o.orderID) as totalOrders,
                    COALESCE(SUM(CASE WHEN o.paymentStatus = 'ÄÃ£ thanh toÃ¡n' THEN o.totalAmount ELSE 0 END), 0) as totalSpent,
                    MAX(o.orderDate) as lastOrderDate
                FROM `order` o
                WHERE o.customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // TÃ¬m kiáº¿m khÃ¡ch hÃ ng (vá»›i thÃ´ng tin nhÃ³m)
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
