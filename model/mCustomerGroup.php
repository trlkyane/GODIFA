<?php
/**
 * Customer Group Model
 * File: model/mCustomerGroup.php
 * Xử lý dữ liệu nhóm khách hàng
 */

require_once __DIR__ . '/database.php';

class CustomerGroup {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Lấy tất cả nhóm khách hàng
    public function getAllGroups() {
        $sql = "SELECT * FROM customer_group ORDER BY minSpent ASC, groupID ASC";
        $result = mysqli_query($this->conn, $sql);
        $groups = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $groups[] = $row;
        }
        return $groups;
    }
    
    // Lấy tất cả nhóm
    public function getActiveGroups() {
        $sql = "SELECT * FROM customer_group ORDER BY minSpent ASC, groupID ASC";
        $result = mysqli_query($this->conn, $sql);
        $groups = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $groups[] = $row;
        }
        return $groups;
    }
    
    // Lấy nhóm theo ID
    public function getGroupById($id) {
        $sql = "SELECT * FROM customer_group WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Thêm nhóm mới
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
    
    // Cập nhật nhóm
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
    
    // Xóa nhóm
    public function deleteGroup($id) {
        // Kiểm tra xem có khách hàng nào trong nhóm không
        $sql = "SELECT COUNT(*) as count FROM customer WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        if ($row['count'] > 0) {
            return false; // Không thể xóa nhóm đang có khách hàng
        }
        
        $sql = "DELETE FROM customer_group WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đếm số khách hàng trong nhóm
    public function countCustomersInGroup($groupID) {
        $sql = "SELECT COUNT(*) as total FROM customer WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $groupID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Thống kê theo nhóm
    public function getGroupStats($groupID) {
        $sql = "SELECT 
                    COUNT(DISTINCT c.customerID) as totalCustomers,
                    COUNT(DISTINCT o.orderID) as totalOrders,
                    COALESCE(SUM(o.totalAmount), 0) as totalRevenue,
                    COALESCE(AVG(o.totalAmount), 0) as avgOrderValue
                FROM customer c
                LEFT JOIN `order` o ON c.customerID = o.customerID AND o.paymentStatus != 'Đã hủy'
                WHERE c.groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $groupID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Lấy thống kê tất cả nhóm
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
                LEFT JOIN `order` o ON c.customerID = o.customerID AND o.paymentStatus != 'Đã hủy'
                GROUP BY cg.groupID, cg.groupName, cg.description, cg.minSpent, cg.maxSpent, cg.color
                ORDER BY cg.minSpent ASC, cg.groupID ASC";
        $result = mysqli_query($this->conn, $sql);
        $stats = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $stats[] = $row;
        }
        return $stats;
    }
    
    // Tìm kiếm nhóm
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
    
    // Đếm tổng số nhóm
    public function countGroups() {
        $sql = "SELECT COUNT(*) as total FROM customer_group";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Chạy stored procedure phân nhóm tự động
    public function runAutoAssign() {
        $sql = "CALL auto_assign_customer_groups_by_spending()";
        return mysqli_query($this->conn, $sql);
    }
    
    // Lấy thống kê khách hàng chưa phân nhóm
    public function getUnassignedCount() {
        $sql = "SELECT COUNT(*) as count FROM customer WHERE groupID IS NULL OR groupID = 0";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['count'];
    }
    
    // ============================================
    // XÓA HẾT VALIDATION GAP (KHÔNG CẦN NỮA - DÙNG FIXED TIERS)
    // ============================================
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
