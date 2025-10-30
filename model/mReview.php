<?php
require_once __DIR__ . '/database.php';

class Review {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Thêm đánh giá mới
    public function addReview($productId, $customerId, $rating, $comment) {
        $sql = "INSERT INTO review (rating, comment, dateReview, productID, customerID) 
                VALUES (?, ?, NOW(), ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "isii", $rating, $comment, $productId, $customerId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Lấy đánh giá theo sản phẩm
    public function getReviewsByProduct($productId) {
        $sql = "SELECT r.*, c.customerName 
                FROM review r 
                INNER JOIN customer c ON r.customerID = c.customerID 
                WHERE r.productID = ? 
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
    
    // Lấy đánh giá trung bình của sản phẩm
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
    
    // Kiểm tra khách hàng đã đánh giá sản phẩm chưa
    public function hasReviewed($productId, $customerId) {
        $sql = "SELECT reviewID FROM review WHERE productID = ? AND customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $productId, $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    // Xóa đánh giá
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
}
?>
