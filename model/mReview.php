<?php
require_once __DIR__ . '/database.php';

class Review {
    private $conn;
    
    public function __construct() {
        // Giả định clsKetNoi và moKetNoi hoạt động với mysqli
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Thêm đánh giá mới
    public function addReview($productId, $customerId, $orderId, $rating, $comment) {
        $status = 0; // 🌟 THAY ĐỔI: 0 = Chờ duyệt 🌟
        
        $sql = "INSERT INTO review (rating, comment, productID, customerID, orderID, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        $stmt = mysqli_prepare($this->conn, $sql);
        
        // Chuỗi tham số: i (rating), s (comment), i (productID), i (customerID), i (orderID), i (status)
        mysqli_stmt_bind_param($stmt, "isiiii", $rating, $comment, $productId, $customerId, $orderId, $status); 
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Lấy đánh giá theo sản phẩm (Chỉ lấy những đánh giá ĐÃ DUYỆT)
    public function getReviewsByProduct($productId) {
        $sql = "SELECT r.*, c.customerName 
                FROM review r 
                INNER JOIN customer c ON r.customerID = c.customerID 
                WHERE r.productID = ? AND r.status = 1 /* 🌟 THAY ĐỔI: 1 = Đã duyệt 🌟 */
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
        // Chỉ tính trung bình các đánh giá ĐÃ DUYỆT
        $sql = "SELECT AVG(rating) as avgRating, COUNT(*) as totalReviews 
                FROM review 
                WHERE productID = ? AND status = 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Kiểm tra khách hàng đã đánh giá sản phẩm chưa
    public function hasReviewed($productId, $customerId, $orderId) {
        $sql = "SELECT reviewID FROM review 
                WHERE productID = ? AND customerID = ? AND orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $productId, $customerId, $orderId);
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
    
    // Lấy lịch sử đánh giá của khách hàng (Lấy cả chờ duyệt và đã duyệt)
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
    
    /**
     * Cập nhật trạng thái đánh giá (Dùng cho Admin)
     */
    public function updateReviewStatus($reviewId, $newStatus) {
        $sql = "UPDATE review SET status = ? WHERE reviewID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        // Tham số: i (newStatus), i (reviewId)
        mysqli_stmt_bind_param($stmt, "ii", $newStatus, $reviewId);
        
        return mysqli_stmt_execute($stmt);
    }

    /**
     * Đếm số lượng đánh giá theo trạng thái (status)
     */
    public function countByStatus($status) {
        $sql = "SELECT COUNT(reviewID) AS total FROM review WHERE status = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        
        if ($stmt === FALSE) {
            return 0; 
        }
        
        mysqli_stmt_bind_param($stmt, "i", $status);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }

    // Lấy danh sách sản phẩm chờ đánh giá
    public function getProductsPendingReview($customerId) {
        $sql = "
            SELECT 
                od.orderID,
                od.productID,
                p.productName, 
                od.quantity,
                od.price,
                o.deliveryStatus,
                p.image 
            FROM 
                order_details od
            INNER JOIN 
                `order` o ON od.orderID = o.orderID
            INNER JOIN 
                product p ON od.productID = p.productID 
            LEFT JOIN 
                review r ON od.productID = r.productID 
                AND o.orderID = r.orderID 
                AND o.customerID = r.customerID
            WHERE 
                o.customerID = ? 
                AND (o.deliveryStatus = 'Hoàn thành' OR o.deliveryStatus = 'Đã giao')
                AND r.reviewID IS NULL
            ORDER BY
                o.orderDate DESC;
        ";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        
        if ($stmt === FALSE) {
            return []; 
        }
        
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        return $products;
    }
    
    /**
     * Lấy danh sách đánh giá có lọc và tìm kiếm (Admin)
     * ĐÃ SỬA LỖI BIND PARAM CHO TRẠNG THÁI VÀ TÌM KIẾM
     */
    public function getFilteredReviews($search = '', $status = -1) {
        $sql = "
            SELECT 
                r.*, c.customerName, p.productName 
            FROM review r 
            INNER JOIN customer c ON r.customerID = c.customerID 
            INNER JOIN product p ON r.productID = p.productID
            WHERE 1=1
        ";
        $types = '';
        $params = [];
        
        // --- CÁCH KHẮC PHỤC LỖI THAM CHIẾU (REF) CHO mysqli_stmt_bind_param ---
        // Chúng ta cần tạo một mảng chứa tham chiếu đến các biến, 
        // không phải giá trị của chúng.

        $bind_params = []; // Mảng chứa tham chiếu đến các biến sẽ được bind
    
        // 1. Lọc theo trạng thái
        if ($status != -1) {
            $sql .= " AND r.status = ?";
            $types .= 'i';
            $bind_params[] = &$status; // Thêm tham chiếu đến $status
        }
    
        // 2. Tìm kiếm (theo tên khách hàng hoặc tên sản phẩm)
        $searchParam = null;
        if (!empty($search)) {
            $sql .= " AND (c.customerName LIKE ? OR p.productName LIKE ?)";
            $types .= 'ss';
            $searchParam = "%" . $search . "%";
            $bind_params[] = &$searchParam; // Thêm tham chiếu đến $searchParam
            $bind_params[] = &$searchParam; // Thêm tham chiếu đến $searchParam (lần 2)
        }
        
        $sql .= " ORDER BY r.dateReview DESC";
    
        $stmt = mysqli_prepare($this->conn, $sql);
        
        if ($stmt === FALSE) {
            // Xử lý lỗi SQL nếu cần
            error_log("SQL Prepare Error: " . mysqli_error($this->conn));
            return [];
        }
    
        // Bind parameters nếu có
        if (!empty($types)) {
            // Tạo mảng đối số đầu tiên là $stmt, thứ hai là $types, sau đó là các tham chiếu
            array_unshift($bind_params, $types);
            
            // Sử dụng call_user_func_array để bind số lượng tham số động
            // Lưu ý: Đối số đầu tiên của call_user_func_array phải là callable, 
            // và đối số thứ hai là một mảng các đối số cho hàm callable đó.
            // Vì bind_param cần $stmt, chúng ta phải đưa nó vào array_merge.
            
            $bind_args = array_merge([$stmt], $bind_params);
            
            // Hàm này sẽ gọi: mysqli_stmt_bind_param($stmt, $types, $param1, $param2, ...)
            if (!call_user_func_array('mysqli_stmt_bind_param', $bind_args)) {
                 error_log("Binding parameters failed: " . mysqli_stmt_error($stmt));
                 return [];
            }
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reviews = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reviews[] = $row;
        }
        return $reviews;
    }
    
    public function countReviews() {
        $sql = "SELECT COUNT(reviewID) AS total FROM review";
        $result = mysqli_query($this->conn, $sql);
    
        // Kiểm tra kết quả truy vấn
        if ($result === FALSE) {
            return 0; 
        }
        
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }

    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}