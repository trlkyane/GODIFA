<?php
require_once __DIR__ . '/database.php';

class User {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Đăng nhập người dùng (admin/staff)
    public function login($email, $password) {
        $hashedPassword = md5($password);
        
        $sql = "SELECT u.*, r.roleName 
                FROM user u 
                INNER JOIN role r ON u.roleID = r.roleID 
                WHERE u.email = ? AND u.password = ? AND u.status = '1'";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $hashedPassword);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Lấy thông tin người dùng theo ID
    public function getUserById($id) {
        $sql = "SELECT u.*, r.roleName 
                FROM user u 
                INNER JOIN role r ON u.roleID = r.roleID 
                WHERE u.userID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Lấy tất cả người dùng
    public function getAllUsers() {
        $sql = "SELECT u.*, r.roleName 
                FROM user u 
                INNER JOIN role r ON u.roleID = r.roleID 
                ORDER BY u.userID DESC";
        $result = mysqli_query($this->conn, $sql);
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        return $users;
    }
    
    // Thêm người dùng mới
    public function addUser($userName, $email, $password, $phone, $roleId) {
        $hashedPassword = md5($password);
        $status = '1';
        
        $sql = "INSERT INTO user (userName, email, password, phone, status, roleID) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssi", $userName, $email, $hashedPassword, $phone, $status, $roleId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Cập nhật thông tin người dùng
    public function updateUser($id, $userName, $email, $phone, $roleId, $status) {
        $sql = "UPDATE user SET userName = ?, email = ?, phone = ?, roleID = ?, status = ? WHERE userID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssisi", $userName, $email, $phone, $roleId, $status, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đổi mật khẩu
    public function changePassword($id, $newPassword) {
        $hashedPassword = md5($newPassword);
        $sql = "UPDATE user SET password = ? WHERE userID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Toggle trạng thái (Khóa/Mở khóa)
    public function toggleStatus($id) {
        $sql = "UPDATE user SET status = IF(status = 1, 0, 1) WHERE userID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Xóa người dùng
    public function deleteUser($id) {
        $sql = "DELETE FROM user WHERE userID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Kiểm tra email đã tồn tại
    public function emailExists($email) {
        $sql = "SELECT userID FROM user WHERE email = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
