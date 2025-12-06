<?php
/**
 * Voucher Group Model
 * File: model/mVoucherGroup.php
 * Xá»­ lÃ½ quan há»‡ giá»¯a voucher vÃ  nhÃ³m khÃ¡ch hÃ ng
 */

require_once __DIR__ . '/database.php';

class VoucherGroup {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // GÃ¡n voucher cho má»™t nhÃ³m
    public function assignVoucherToGroup($voucherID, $groupID) {
        // Kiá»ƒm tra xem Ä‘Ã£ gÃ¡n chÆ°a
        $checkSql = "SELECT * FROM voucher_group WHERE voucherID = ? AND groupID = ?";
        $stmt = mysqli_prepare($this->conn, $checkSql);
        mysqli_stmt_bind_param($stmt, "ii", $voucherID, $groupID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            return true; // ÄÃ£ tá»“n táº¡i
        }
        
        // ThÃªm má»›i
        $sql = "INSERT INTO voucher_group (voucherID, groupID) VALUES (?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $voucherID, $groupID);
        return mysqli_stmt_execute($stmt);
    }
    
    // GÃ¡n voucher cho nhiá»u nhÃ³m
    public function assignVoucherToMultipleGroups($voucherID, $groupIDs) {
        // XÃ³a táº¥t cáº£ gÃ¡n cÅ©
        $this->removeAllGroupsFromVoucher($voucherID);
        
        // GÃ¡n má»›i
        if (empty($groupIDs)) {
            return true; // KhÃ´ng gÃ¡n nhÃ³m nÃ o = voucher cÃ´ng khai
        }
        
        foreach ($groupIDs as $groupID) {
            $this->assignVoucherToGroup($voucherID, $groupID);
        }
        return true;
    }
    
    // XÃ³a má»™t nhÃ³m khá»i voucher
    public function removeGroupFromVoucher($voucherID, $groupID) {
        $sql = "DELETE FROM voucher_group WHERE voucherID = ? AND groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $voucherID, $groupID);
        return mysqli_stmt_execute($stmt);
    }
    
    // XÃ³a táº¥t cáº£ nhÃ³m khá»i voucher
    public function removeAllGroupsFromVoucher($voucherID) {
        $sql = "DELETE FROM voucher_group WHERE voucherID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $voucherID);
        return mysqli_stmt_execute($stmt);
    }
    
    // Láº¥y danh sÃ¡ch nhÃ³m Ä‘Æ°á»£c gÃ¡n cho voucher
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
    
    // Láº¥y IDs cá»§a cÃ¡c nhÃ³m Ä‘Æ°á»£c gÃ¡n cho voucher
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
    
    // Láº¥y voucher theo nhÃ³m khÃ¡ch hÃ ng (cho frontend)
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
    
    // Kiá»ƒm tra voucher cÃ³ public khÃ´ng (khÃ´ng gÃ¡n nhÃ³m nÃ o)
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
