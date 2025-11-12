<?php
require_once __DIR__ . '/database.php';

class Category {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Lấy tất cả danh mục
    public function getAllCategories() {
        $sql = "SELECT * FROM category ORDER BY categoryName";
        $result = mysqli_query($this->conn, $sql);
        $categories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
        return $categories;
    }
    
    // Lấy danh mục đang hoạt động (Frontend)
    public function getActiveCategories() {
        $sql = "SELECT * FROM category WHERE status = 1 ORDER BY categoryName";
        $result = mysqli_query($this->conn, $sql);
        $categories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
        return $categories;
    }
    
    // Lấy danh mục theo ID
    public function getCategoryById($id) {
        $sql = "SELECT * FROM category WHERE categoryID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Thêm danh mục mới
    public function addCategory($categoryName) {
        $sql = "INSERT INTO category (categoryName) VALUES (?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $categoryName);
        return mysqli_stmt_execute($stmt);
    }
    
    // Kiểm tra tên danh mục đã tồn tại chưa (dùng trước khi thêm)
    public function categoryNameExists($categoryName, $excludeId = null) {
        if ($excludeId) {
            // Khi update - bỏ qua danh mục hiện tại
            $sql = "SELECT COUNT(*) as count FROM category WHERE categoryName = ? AND categoryID != ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $categoryName, $excludeId);
        } else {
            // Khi thêm mới
            $sql = "SELECT COUNT(*) as count FROM category WHERE categoryName = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $categoryName);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }
    
    // Cập nhật danh mục
    public function updateCategory($id, $categoryName) {
        $sql = "UPDATE category SET categoryName = ? WHERE categoryID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $categoryName, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // REMOVED: deleteCategory() - Chỉ dùng khóa (toggleStatus), không xóa
    
    // Chuyển đổi trạng thái (Khóa/Mở khóa)
    public function toggleStatus($id) {
        $sql = "UPDATE category SET status = 1 - status WHERE categoryID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đếm số sản phẩm trong danh mục
    public function countProductsInCategory($id) {
        $sql = "SELECT COUNT(*) as total FROM product WHERE categoryID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Kiểm tra danh mục có sản phẩm không
    public function hasProducts($id) {
        return $this->countProductsInCategory($id) > 0;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
