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
    
    // Lấy tất cả bài viết
    public function getAllBlogs() {
        $sql = "SELECT * FROM blog ORDER BY date DESC";
        $result = mysqli_query($this->conn, $sql);
        $blogs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $blogs[] = $row;
        }
        return $blogs;
    }
    
    // Lấy bài viết theo ID
    public function getBlogById($id) {
        $sql = "SELECT * FROM blog WHERE blogID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Thêm bài viết mới
    public function addBlog($title, $content) {
        $sql = "INSERT INTO blog (title, content, date) 
                VALUES (?, ?, NOW())";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $title, $content);
        return mysqli_stmt_execute($stmt);
    }
    
    // Cập nhật bài viết
    public function updateBlog($id, $title, $content) {
        $sql = "UPDATE blog SET title = ?, content = ? WHERE blogID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $title, $content, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Xóa bài viết
    public function deleteBlog($id) {
        $sql = "DELETE FROM blog WHERE blogID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Toggle trạng thái (Khóa/Mở khóa)
    public function toggleStatus($id) {
        $sql = "UPDATE blog SET status = IF(status = 1, 0, 1) WHERE blogID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đếm tổng số bài viết
    public function countBlogs() {
        $sql = "SELECT COUNT(*) as total FROM blog";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Tìm kiếm bài viết
    public function searchBlogs($keyword) {
        $searchTerm = "%$keyword%";
        $sql = "SELECT b.*, u.userName as authorName 
                FROM blog b 
                LEFT JOIN user u ON b.userID = u.userID 
                WHERE b.title LIKE ? OR b.content LIKE ?
                ORDER BY b.date DESC";
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
    
    // Lấy bài viết mới nhất
    public function getRecentBlogs($limit = 5) {
        $sql = "SELECT b.*, u.userName as authorName 
                FROM blog b 
                LEFT JOIN user u ON b.userID = u.userID 
                ORDER BY b.date DESC 
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
