<?php
/**
 * Customer Group Model
 * File: model/mCustomerGroup.php
 * Xá»­ lÃ½ dá»¯ liá»‡u nhÃ³m khÃ¡ch hÃ ng
 */

require_once __DIR__ . '/database.php';

class CustomerGroup {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Láº¥y táº¥t cáº£ nhÃ³m khÃ¡ch hÃ ng
    public function getAllGroups() {
        $sql = "SELECT * FROM customer_group ORDER BY minSpent ASC, groupID ASC";
        $result = mysqli_query($this->conn, $sql);
        $groups = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $groups[] = $row;
        }
        return $groups;
    }
    
    // Láº¥y táº¥t cáº£ nhÃ³m
    public function getActiveGroups() {
        $sql = "SELECT * FROM customer_group ORDER BY minSpent ASC, groupID ASC";
        $result = mysqli_query($this->conn, $sql);
        $groups = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $groups[] = $row;
        }
        return $groups;
    }
    
    // Láº¥y nhÃ³m theo ID
    public function getGroupById($id) {
        $sql = "SELECT * FROM customer_group WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // ThÃªm nhÃ³m má»›i
    public function addGroup($data) {
        $sql = "INSERT INTO customer_group (groupName, description, minSpent, maxSpent, color) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssdds", 
            $data['groupName'],
            $data['description'],
            $data['minSpent'],
            $data['maxSpent'],
            $data['color']
        );
        return mysqli_stmt_execute($stmt);
    }
    
    // Cáº­p nháº­t nhÃ³m
    public function updateGroup($id, $data) {
        $sql = "UPDATE customer_group 
                SET groupName = ?, description = ?, minSpent = ?, maxSpent = ?, color = ?
                WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssddsi", 
            $data['groupName'],
            $data['description'],
            $data['minSpent'],
            $data['maxSpent'],
            $data['color'],
            $id
        );
        return mysqli_stmt_execute($stmt);
    }
    
    // XÃ³a nhÃ³m
    public function deleteGroup($id) {
        // Kiá»ƒm tra xem cÃ³ khÃ¡ch hÃ ng nÃ o trong nhÃ³m khÃ´ng
        $sql = "SELECT COUNT(*) as count FROM customer WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            return false; // KhÃ´ng thá»ƒ xÃ³a nhÃ³m Ä‘ang cÃ³ khÃ¡ch hÃ ng
        }
        
        $sql = "DELETE FROM customer_group WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Äáº¿m sá»‘ khÃ¡ch hÃ ng trong nhÃ³m
    public function countCustomersInGroup($groupID) {
        $sql = "SELECT COUNT(*) as total FROM customer WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $groupID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Thá»‘ng kÃª theo nhÃ³m
    public function getGroupStats($groupID) {
        $sql = "SELECT 
                    COUNT(DISTINCT c.customerID) as totalCustomers,
                    COUNT(DISTINCT o.orderID) as totalOrders,
                    COALESCE(SUM(o.totalAmount), 0) as totalRevenue,
                    COALESCE(AVG(o.totalAmount), 0) as avgOrderValue
                FROM customer c
                LEFT JOIN `order` o ON c.customerID = o.customerID AND o.paymentStatus != 'ÄÃ£ há»§y'
                WHERE c.groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $groupID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Láº¥y thá»‘ng kÃª táº¥t cáº£ nhÃ³m
    public function getAllGroupStats() {
        $sql = "SELECT 
                    cg.groupID,
                    cg.groupName,
                    cg.description,
                    cg.minSpent,
                    cg.maxSpent,
                    cg.color,
                    COUNT(DISTINCT c.customerID) as totalCustomers,
                    COUNT(DISTINCT o.orderID) as totalOrders,
                    COALESCE(SUM(o.totalAmount), 0) as totalRevenue,
                    COALESCE(AVG(o.totalAmount), 0) as avgOrderValue
                FROM customer_group cg
                LEFT JOIN customer c ON cg.groupID = c.groupID
                LEFT JOIN `order` o ON c.customerID = o.customerID AND o.paymentStatus != 'ÄÃ£ há»§y'
                GROUP BY cg.groupID, cg.groupName, cg.description, cg.minSpent, cg.maxSpent, cg.color
                ORDER BY cg.minSpent ASC, cg.groupID ASC";
        $result = mysqli_query($this->conn, $sql);
        $stats = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $stats[] = $row;
        }
        return $stats;
    }
    
    // TÃ¬m kiáº¿m nhÃ³m
    public function searchGroups($keyword) {
        $searchTerm = "%$keyword%";
        $sql = "SELECT * FROM customer_group 
                WHERE groupName LIKE ? OR description LIKE ?
                ORDER BY minSpent ASC, groupID ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $groups = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $groups[] = $row;
        }
        return $groups;
    }
    
    // Äáº¿m tá»•ng sá»‘ nhÃ³m
    public function countGroups() {
        $sql = "SELECT COUNT(*) as total FROM customer_group";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Cháº¡y stored procedure phÃ¢n nhÃ³m tá»± Ä‘á»™ng
    public function runAutoAssign() {
        $sql = "CALL auto_assign_customer_groups_by_spending()";
        return mysqli_query($this->conn, $sql);
    }
    
    // Láº¥y thá»‘ng kÃª khÃ¡ch hÃ ng chÆ°a phÃ¢n nhÃ³m
    public function getUnassignedCount() {
        $sql = "SELECT COUNT(*) as count FROM customer WHERE groupID IS NULL OR groupID = 0";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['count'];
    }
    
    // ============================================
    // XÃ“A Háº¾T VALIDATION GAP (KHÃ”NG Cáº¦N Ná»®A - DÃ™NG FIXED TIERS)
    // ============================================
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
