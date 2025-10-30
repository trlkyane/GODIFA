<?php
/**
 * Voucher Group Model
 * File: model/mVoucherGroup.php
 * Xử lý quan hệ giữa voucher và nhóm khách hàng
 */

require_once __DIR__ . '/database.php';

class VoucherGroup {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Gán voucher cho một nhóm
    public function assignVoucherToGroup($voucherID, $groupID) {
        // Kiểm tra xem đã gán chưa
        $checkSql = "SELECT * FROM voucher_group WHERE voucherID = ? AND groupID = ?";
        $stmt = mysqli_prepare($this->conn, $checkSql);
        mysqli_stmt_bind_param($stmt, "ii", $voucherID, $groupID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            return true; // Đã tồn tại
        }
        
        // Thêm mới
        $sql = "INSERT INTO voucher_group (voucherID, groupID) VALUES (?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $voucherID, $groupID);
        return mysqli_stmt_execute($stmt);
    }
    
    // Gán voucher cho nhiều nhóm
    public function assignVoucherToMultipleGroups($voucherID, $groupIDs) {
        // Xóa tất cả gán cũ
        $this->removeAllGroupsFromVoucher($voucherID);
        
        // Gán mới
        if (empty($groupIDs)) {
            return true; // Không gán nhóm nào = voucher công khai
        }
        
        foreach ($groupIDs as $groupID) {
            $this->assignVoucherToGroup($voucherID, $groupID);
        }
        return true;
    }
    
    // Xóa một nhóm khỏi voucher
    public function removeGroupFromVoucher($voucherID, $groupID) {
        $sql = "DELETE FROM voucher_group WHERE voucherID = ? AND groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $voucherID, $groupID);
        return mysqli_stmt_execute($stmt);
    }
    
    // Xóa tất cả nhóm khỏi voucher
    public function removeAllGroupsFromVoucher($voucherID) {
        $sql = "DELETE FROM voucher_group WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $voucherID);
        return mysqli_stmt_execute($stmt);
    }
    
    // Lấy danh sách nhóm được gán cho voucher
    public function getGroupsByVoucher($voucherID) {
        $sql = "
            SELECT cg.* 
            FROM customer_group cg
            INNER JOIN voucher_group vg ON cg.groupID = vg.groupID
            WHERE vg.voucherID = ?
            ORDER BY cg.minSpent ASC
        ";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $voucherID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $groups = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $groups[] = $row;
        }
        return $groups;
    }
    
    // Lấy IDs của các nhóm được gán cho voucher
    public function getGroupIDsByVoucher($voucherID) {
        $sql = "SELECT groupID FROM voucher_group WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $voucherID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $groupIDs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $groupIDs[] = $row['groupID'];
        }
        return $groupIDs;
    }
    
    // Lấy voucher theo nhóm khách hàng (cho frontend)
    public function getVouchersByGroup($groupID) {
        $sql = "
            SELECT DISTINCT v.* 
            FROM voucher v
            LEFT JOIN voucher_group vg ON v.voucherID = vg.voucherID
            WHERE v.status = 1 
              AND v.endDate >= CURDATE()
              AND (vg.groupID = ? OR vg.groupID IS NULL)
            ORDER BY v.value DESC
        ";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $groupID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $vouchers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $vouchers[] = $row;
        }
        return $vouchers;
    }
    
    // Kiểm tra voucher có public không (không gán nhóm nào)
    public function isPublicVoucher($voucherID) {
        $sql = "SELECT COUNT(*) as count FROM voucher_group WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $voucherID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] == 0;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
