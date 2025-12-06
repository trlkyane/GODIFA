<?php
require_once __DIR__ . '/database.php';

class Role {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Láº¥y táº¥t cáº£ roles
    public function getAllRoles() {
        $sql = "SELECT * FROM role ORDER BY roleID ASC";
        $result = mysqli_query($this->conn, $sql);
        $roles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $roles[] = $row;
        }
        return $roles;
    }
    
    // Láº¥y role theo ID
    public function getRoleById($id) {
        $sql = "SELECT * FROM role WHERE roleID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
