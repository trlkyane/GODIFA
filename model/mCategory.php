<?php
require_once __DIR__ . '/database.php';

class Category {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Láº¥y táº¥t cáº£ danh má»¥c
    public function getAllCategories() {
        $sql = "SELECT * FROM category ORDER BY categoryName";
        $result = mysqli_query($this->conn, $sql);
        $categories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
        return $categories;
    }
    
    // Láº¥y danh má»¥c Ä‘ang hoáº¡t Ä‘á»™ng (Frontend)
    public function getActiveCategories() {
        $sql = "SELECT * FROM category WHERE status = 1 ORDER BY categoryName";
        $result = mysqli_query($this->conn, $sql);
        $categories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
        return $categories;
    }
    
    // Láº¥y danh má»¥c theo ID
    public function getCategoryById($id) {
        $sql = "SELECT * FROM category WHERE categoryID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // ThÃªm danh má»¥c má»›i
    public function addCategory($categoryName) {
        $sql = "INSERT INTO category (categoryName) VALUES (?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $categoryName);
        return mysqli_stmt_execute($stmt);
    }
    
    // Kiá»ƒm tra tÃªn danh má»¥c Ä‘Ã£ tá»“n táº¡i chÆ°a (dÃ¹ng trÆ°á»›c khi thÃªm)
    public function categoryNameExists($categoryName, $excludeId = null) {
        if ($excludeId) {
            // Khi update - bá» qua danh má»¥c hiá»‡n táº¡i
            $sql = "SELECT COUNT(*) as count FROM category WHERE categoryName = ? AND categoryID != ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $categoryName, $excludeId);
        } else {
            // Khi thÃªm má»›i
            $sql = "SELECT COUNT(*) as count FROM category WHERE categoryName = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $categoryName);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }
    
    // Cáº­p nháº­t danh má»¥c
    public function updateCategory($id, $categoryName) {
        $sql = "UPDATE category SET categoryName = ? WHERE categoryID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $categoryName, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // REMOVED: deleteCategory() - Chá»‰ dÃ¹ng khÃ³a (toggleStatus), khÃ´ng xÃ³a
    
    // Chuyá»ƒn Ä‘á»•i tráº¡ng thÃ¡i (KhÃ³a/Má»Ÿ khÃ³a)
    public function toggleStatus($id) {
        $sql = "UPDATE category SET status = 1 - status WHERE categoryID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Äáº¿m sá»‘ sáº£n pháº©m trong danh má»¥c
    public function countProductsInCategory($id) {
        $sql = "SELECT COUNT(*) as total FROM product WHERE categoryID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Kiá»ƒm tra danh má»¥c cÃ³ sáº£n pháº©m khÃ´ng
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
