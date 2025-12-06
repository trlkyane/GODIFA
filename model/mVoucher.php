<?php
require_once __DIR__ . '/database.php';

class Voucher {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Láº¥y táº¥t cáº£ vouchers
    public function getAllVouchers() {
        $sql = "SELECT * FROM voucher ORDER BY voucherID DESC";
        $result = mysqli_query($this->conn, $sql);
        $vouchers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $vouchers[] = $row;
        }
        return $vouchers;
    }
    
    // TÃ¬m kiáº¿m vouchers theo tÃªn
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
    
    // Láº¥y vouchers Ä‘ang hoáº¡t Ä‘á»™ng (trong thá»i háº¡n vÃ  cÃ²n sá»‘ lÆ°á»£ng)
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
    
    // Láº¥y voucher theo ID
    public function getVoucherById($id) {
        $sql = "SELECT * FROM voucher WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // ThÃªm voucher má»›i
    public function addVoucher($voucherName, $value, $quantity, $startDate, $endDate, $minOrderValue, $requirement) {
        // Äáº£m báº£o minOrderValue khÃ´ng Ã¢m
        if ($minOrderValue < 0) { $minOrderValue = 0; }
        // Má»™t sá»‘ mÃ´i trÆ°á»ng chÆ°a cÃ³ cá»™t minOrderValue -> fallback
        $hasMinOrderColumn = $this->hasMinOrderValueColumn();
        if ($hasMinOrderColumn) {
            $sql = "INSERT INTO voucher (voucherName, value, quantity, startDate, endDate, minOrderValue, requirement) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "sdissis", $voucherName, $value, $quantity, $startDate, $endDate, $minOrderValue, $requirement);
        } else {
            // Fallback náº¿u chÆ°a cháº¡y migration
            $sql = "INSERT INTO voucher (voucherName, value, quantity, startDate, endDate, requirement) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "sdisss", $voucherName, $value, $quantity, $startDate, $endDate, $requirement);
        }
        return mysqli_stmt_execute($stmt);
    }
    
    // Cáº­p nháº­t voucher
    public function updateVoucher($id, $voucherName, $value, $quantity, $startDate, $endDate, $minOrderValue, $requirement) {
        if ($minOrderValue < 0) { $minOrderValue = 0; }
        $hasMinOrderColumn = $this->hasMinOrderValueColumn();
        if ($hasMinOrderColumn) {
            $sql = "UPDATE voucher 
                    SET voucherName = ?, value = ?, quantity = ?, startDate = ?, endDate = ?, minOrderValue = ?, requirement = ? 
                    WHERE voucherID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "sdissisi", $voucherName, $value, $quantity, $startDate, $endDate, $minOrderValue, $requirement, $id);
        } else {
            $sql = "UPDATE voucher 
                    SET voucherName = ?, value = ?, quantity = ?, startDate = ?, endDate = ?, requirement = ? 
                    WHERE voucherID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "sdisssi", $voucherName, $value, $quantity, $startDate, $endDate, $requirement, $id);
        }
        return mysqli_stmt_execute($stmt);
    }
    
    // XÃ³a voucher
    public function deleteVoucher($id) {
        $sql = "DELETE FROM voucher WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Toggle tráº¡ng thÃ¡i voucher (khÃ³a/má»Ÿ khÃ³a)
    public function toggleStatus($id) {
        $sql = "UPDATE voucher SET status = IF(status = 1, 0, 1) WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Äáº¿m tá»•ng sá»‘ vouchers
    public function countVouchers() {
        $sql = "SELECT COUNT(*) as total FROM voucher";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Kiá»ƒm tra voucher cÃ²n hiá»‡u lá»±c khÃ´ng
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

    // Helper: kiá»ƒm tra cá»™t minOrderValue Ä‘Ã£ tá»“n táº¡i chÆ°a
    private function hasMinOrderValueColumn() {
        static $cached = null;
        if ($cached !== null) return $cached;
        $res = mysqli_query($this->conn, "SHOW COLUMNS FROM voucher LIKE 'minOrderValue'");
        $cached = ($res && mysqli_num_rows($res) > 0);
        return $cached;
    }
    
    // Giáº£m sá»‘ lÆ°á»£ng voucher khi sá»­ dá»¥ng
    public function useVoucher($id) {
        $sql = "UPDATE voucher SET quantity = quantity - 1 WHERE voucherID = ? AND quantity > 0";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Láº¥y connection Ä‘á»ƒ dÃ¹ng mysqli_insert_id
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
