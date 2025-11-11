<?php
require_once __DIR__ . '/database.php';

class Review {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Thêm đánh giá mới
    public function addReview($productId, $customerId, $orderId, $rating, $comment) {
        $status = 1; // Mặc định là chờ duyệt
        
        // SỬA: Loại bỏ dateReview khỏi câu lệnh INSERT vì CSDL đã có DEFAULT CURRENT_TIMESTAMP
        $sql = "INSERT INTO review (rating, comment, productID, customerID, orderID, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        $stmt = mysqli_prepare($this->conn, $sql);
        
        // SỬA: Chuỗi tham số: i (rating), s (comment), i (productID), i (customerID), i (orderID), i (status)
        // Đây là 6 tham số tương ứng với 6 dấu ?
        mysqli_stmt_bind_param($stmt, "isiiii", $rating, $comment, $productId, $customerId, $orderId, $status); 
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Lấy đánh giá theo sản phẩm (Giữ nguyên)
    public function getReviewsByProduct($productId) {
        $sql = "SELECT r.*, c.customerName 
                FROM review r 
                INNER JOIN customer c ON r.customerID = c.customerID 
                WHERE r.productID = ? AND r.status = 1 
                ORDER BY r.dateReview DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reviews = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reviews[] = $row;
        }
        return $reviews;
    }
    
    // Lấy đánh giá trung bình của sản phẩm (Giữ nguyên)
    public function getAverageRating($productId) {
        $sql = "SELECT AVG(rating) as avgRating, COUNT(*) as totalReviews 
                FROM review 
                WHERE productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Kiểm tra khách hàng đã đánh giá sản phẩm chưa (Giữ nguyên)
    public function hasReviewed($productId, $customerId, $orderId) {
        // Kiểm tra chính xác 3 cột: productID, customerID, orderID
        $sql = "SELECT reviewID FROM review 
                WHERE productID = ? AND customerID = ? AND orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $productId, $customerId, $orderId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    // Xóa đánh giá (Giữ nguyên)
    public function deleteReview($reviewId) {
        $sql = "DELETE FROM review WHERE reviewID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $reviewId);
        return mysqli_stmt_execute($stmt);
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
    public function getReviewsByCustomer($customerId) {
        $sql = "SELECT r.*, p.productName, p.image 
                FROM review r 
                INNER JOIN product p ON r.productID = p.productID 
                WHERE r.customerID = ?
                ORDER BY r.dateReview DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reviews = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reviews[] = $row;
        }
        return $reviews;
    }
}