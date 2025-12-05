<?php
/**
 * Blog Model
 * File: model/mBlog.php
 */

require_once __DIR__ . '/database.php';

class Blog {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Láº¥y táº¥t cáº£ bÃ i viáº¿t
    public function getAllBlogs() {
        $sql = "SELECT * FROM blog ORDER BY date DESC";
        $result = mysqli_query($this->conn, $sql);
        $blogs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $blogs[] = $row;
        }
        return $blogs;
    }
    
    // Láº¥y bÃ i viáº¿t theo ID
    public function getBlogById($id) {
        $sql = "SELECT * FROM blog WHERE blogID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // ThÃªm bÃ i viáº¿t má»›i
    public function addBlog($title, $content, $image = '') {
        $sql = "INSERT INTO blog (title, content, image, date) 
                VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $title, $content, $image);
        return mysqli_stmt_execute($stmt);
    }
    
    // Cáº­p nháº­t bÃ i viáº¿t
    public function updateBlog($id, $title, $content, $image = null) {
        if ($image !== null) {
            // Cáº­p nháº­t cáº£ hÃ¬nh áº£nh
            $sql = "UPDATE blog SET title = ?, content = ?, image = ? WHERE blogID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $title, $content, $image, $id);
        } else {
            // KhÃ´ng cáº­p nháº­t hÃ¬nh áº£nh
            $sql = "UPDATE blog SET title = ?, content = ? WHERE blogID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $title, $content, $id);
        }
        return mysqli_stmt_execute($stmt);
    }
    
    // XÃ³a bÃ i viáº¿t
    public function deleteBlog($id) {
        $sql = "DELETE FROM blog WHERE blogID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Toggle tráº¡ng thÃ¡i (KhÃ³a/Má»Ÿ khÃ³a)
    public function toggleStatus($id) {
        $sql = "UPDATE blog SET status = IF(status = 1, 0, 1) WHERE blogID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Äáº¿m tá»•ng sá»‘ bÃ i viáº¿t
    public function countBlogs() {
        $sql = "SELECT COUNT(*) as total FROM blog";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // TÃ¬m kiáº¿m bÃ i viáº¿t
    public function searchBlogs($keyword) {
        $searchTerm = "%$keyword%";
        $sql = "SELECT * FROM blog 
                WHERE title LIKE ? OR content LIKE ?
                ORDER BY date DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $blogs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $blogs[] = $row;
        }
        return $blogs;
    }
    
    // Láº¥y bÃ i viáº¿t má»›i nháº¥t
    public function getRecentBlogs($limit = 5) {
        $sql = "SELECT * FROM blog 
                WHERE status = 1
                ORDER BY date DESC 
                LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $blogs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $blogs[] = $row;
        }
        return $blogs;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
