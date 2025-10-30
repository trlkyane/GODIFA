<?php
require_once __DIR__ . '/database.php';

class Voucher {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Lấy tất cả vouchers
    public function getAllVouchers() {
        $sql = "SELECT * FROM voucher ORDER BY voucherID DESC";
        $result = mysqli_query($this->conn, $sql);
        $vouchers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $vouchers[] = $row;
        }
        return $vouchers;
    }
    
    // Tìm kiếm vouchers theo tên
    public function searchVouchers($keyword) {
        $keyword = "%{$keyword}%";
        $sql = "SELECT * FROM voucher 
                WHERE voucherName LIKE ? OR requirement LIKE ?
                ORDER BY voucherID DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $keyword, $keyword);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $vouchers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $vouchers[] = $row;
        }
        return $vouchers;
    }
    
    // Lấy vouchers đang hoạt động (trong thời hạn và còn số lượng)
    public function getActiveVouchers() {
        $today = date('Y-m-d');
        $sql = "SELECT * FROM voucher 
                WHERE startDate <= ? AND endDate >= ? AND quantity > 0 
                ORDER BY endDate ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $today, $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $vouchers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $vouchers[] = $row;
        }
        return $vouchers;
    }
    
    // Lấy voucher theo ID
    public function getVoucherById($id) {
        $sql = "SELECT * FROM voucher WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Thêm voucher mới
    public function addVoucher($voucherName, $value, $quantity, $startDate, $endDate, $requirement) {
        $sql = "INSERT INTO voucher (voucherName, value, quantity, startDate, endDate, requirement) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "sdiiss", $voucherName, $value, $quantity, $startDate, $endDate, $requirement);
        return mysqli_stmt_execute($stmt);
    }
    
    // Cập nhật voucher
    public function updateVoucher($id, $voucherName, $value, $quantity, $startDate, $endDate, $requirement) {
        $sql = "UPDATE voucher 
                SET voucherName = ?, value = ?, quantity = ?, startDate = ?, endDate = ?, requirement = ? 
                WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        // Fix: startDate phải là 's' (string) không phải 'i' (integer)
        mysqli_stmt_bind_param($stmt, "sdisssi", $voucherName, $value, $quantity, $startDate, $endDate, $requirement, $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Xóa voucher
    public function deleteVoucher($id) {
        $sql = "DELETE FROM voucher WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Toggle trạng thái voucher (khóa/mở khóa)
    public function toggleStatus($id) {
        $sql = "UPDATE voucher SET status = IF(status = 1, 0, 1) WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đếm tổng số vouchers
    public function countVouchers() {
        $sql = "SELECT COUNT(*) as total FROM voucher";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Kiểm tra voucher còn hiệu lực không
    public function isVoucherValid($id) {
        $today = date('Y-m-d');
        $sql = "SELECT * FROM voucher 
                WHERE voucherID = ? AND startDate <= ? AND endDate >= ? AND quantity > 0";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $id, $today, $today);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    // Giảm số lượng voucher khi sử dụng
    public function useVoucher($id) {
        $sql = "UPDATE voucher SET quantity = quantity - 1 WHERE voucherID = ? AND quantity > 0";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Lấy connection để dùng mysqli_insert_id
    public function getConnection() {
        return $this->conn;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
