<?php
require_once __DIR__ . '/database.php';

class Product {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Lấy tất cả sản phẩm
    public function getAllProducts($limit = null, $offset = 0) {
        $sql = "SELECT p.*, c.categoryName 
                FROM product p 
                LEFT JOIN category c ON p.categoryID = c.categoryID 
                ORDER BY p.productID DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        }
        
        $result = mysqli_query($this->conn, $sql);
        if (!$result) {
            error_log("SQL Error in getAllProducts: " . mysqli_error($this->conn));
            return [];
        }
        
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        return $products;
    }
    
    // Lấy sản phẩm đang hoạt động
    public function getActiveProducts($limit = null, $offset = 0) {
        $sql = "SELECT p.*, c.categoryName 
                FROM product p 
                LEFT JOIN category c ON p.categoryID = c.categoryID 
                WHERE p.status = 1 AND (c.status = 1 OR c.status IS NULL)
                ORDER BY p.productID DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        }
        
        $result = mysqli_query($this->conn, $sql);
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        return $products;
    }
    
    // Lấy sản phẩm theo ID
    public function getProductById($id) {
        $sql = "SELECT p.*, c.categoryName 
                FROM product p 
                LEFT JOIN category c ON p.categoryID = c.categoryID 
                WHERE p.productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Lấy sản phẩm theo danh mục
    public function getProductsByCategory($categoryId, $limit = null) {
        $sql = "SELECT p.*, c.categoryName 
                FROM product p 
                LEFT JOIN category c ON p.categoryID = c.categoryID 
                WHERE p.categoryID = ? AND p.status = 1 AND c.status = 1
                ORDER BY p.productID DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $categoryId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        return $products;
    }
    
    // Tìm kiếm sản phẩm
    public function searchProducts($keyword) {
        $keyword = "%$keyword%";
        $sql = "SELECT p.*, c.categoryName 
                FROM product p 
                LEFT JOIN category c ON p.categoryID = c.categoryID 
                WHERE (p.productName LIKE ? OR p.description LIKE ?) AND p.status = 1 AND (c.status = 1 OR c.status IS NULL)
                ORDER BY p.productID DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $keyword, $keyword);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        return $products;
    }
    
    // Thêm sản phẩm mới
    public function addProduct($productName, $SKU_MRK, $stockQuantity, $price, $description, $image, $categoryID, $promotional_price = null) {
        $sql = "INSERT INTO product (productName, SKU_MRK, stockQuantity, price, promotional_price, description, image, categoryID) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssiddssi", $productName, $SKU_MRK, $stockQuantity, $price, $promotional_price, $description, $image, $categoryID);
        return mysqli_stmt_execute($stmt);
    }
    
    // Kiểm tra SKU đã tồn tại chưa (dùng trước khi thêm/sửa)
    public function skuExists($sku, $excludeId = null) {
        if ($excludeId) {
            // Khi update - bỏ qua sản phẩm hiện tại
            $sql = "SELECT COUNT(*) as count FROM product WHERE SKU_MRK = ? AND productID != ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $sku, $excludeId);
        } else {
            // Khi thêm mới
            $sql = "SELECT COUNT(*) as count FROM product WHERE SKU_MRK = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $sku);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }
    
    // Cập nhật sản phẩm
    public function updateProduct($id, $productName, $SKU_MRK, $stockQuantity, $price, $description, $image, $categoryID, $promotional_price = null) {
        $sql = "UPDATE product 
                SET productName = ?, SKU_MRK = ?, stockQuantity = ?, price = ?, promotional_price = ?, description = ?, image = ?, categoryID = ? 
                WHERE productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssiddssii", $productName, $SKU_MRK, $stockQuantity, $price, $promotional_price, $description, $image, $categoryID, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Cập nhật số lượng tồn kho
    public function updateStock($productId, $quantity) {
        $sql = "UPDATE product SET stockQuantity = stockQuantity - ? WHERE productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $quantity, $productId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Xóa sản phẩm
    public function deleteProduct($id) {
        $sql = "DELETE FROM product WHERE productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đếm tổng số sản phẩm
    public function countProducts() {
        $sql = "SELECT COUNT(*) as total FROM product";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Toggle trạng thái sản phẩm (Khóa/Mở khóa)
    public function toggleStatus($id) {
        $sql = "UPDATE product SET status = IF(status = 1, 0, 1) WHERE productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Lấy sản phẩm kèm đánh giá và số lượng đã bán
    public function getProductsWithRatings($limit = null, $offset = 0) {
        $sql = "SELECT p.*, c.categoryName,
                COALESCE(AVG(r.rating), 0) as avgRating,
                COALESCE(COUNT(DISTINCT r.reviewID), 0) as reviewCount,
                COALESCE(SUM(od.quantity), 0) as soldCount
                FROM product p 
                LEFT JOIN category c ON p.categoryID = c.categoryID 
                LEFT JOIN review r ON p.productID = r.productID
                LEFT JOIN order_details od ON p.productID = od.productID
                LEFT JOIN `order` o ON od.orderID = o.orderID AND o.deliveryStatus IN ('Đã giao', 'Hoàn thành')
                GROUP BY p.productID
                ORDER BY p.productID DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        }
        
        $result = mysqli_query($this->conn, $sql);
        if (!$result) {
            error_log("SQL Error in getProductsWithRatings: " . mysqli_error($this->conn));
            return [];
        }
        
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        return $products;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
