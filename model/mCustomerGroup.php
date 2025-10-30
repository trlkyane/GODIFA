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
    
    // Lấy chỉ nhóm đang hoạt động
    public function getActiveGroups() {
        $sql = "SELECT * FROM customer_group WHERE status = 1 ORDER BY minSpent ASC, groupID ASC";
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
        $sql = "INSERT INTO customer_group (groupName, description, minSpent, maxSpent, color, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssddsi", 
            $data['groupName'],
            $data['description'],
            $data['minSpent'],
            $data['maxSpent'],
            $data['color'],
            $data['status']
        );
        return mysqli_stmt_execute($stmt);
    }
    
    // Cập nhật nhóm
    public function updateGroup($id, $data) {
        $sql = "UPDATE customer_group 
                SET groupName = ?, description = ?, minSpent = ?, maxSpent = ?, color = ?, status = ?
                WHERE groupID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssddsii", 
            $data['groupName'],
            $data['description'],
            $data['minSpent'],
            $data['maxSpent'],
            $data['color'],
            $data['status'],
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
    
    // Toggle status
    public function toggleStatus($id) {
        $sql = "UPDATE customer_group SET status = 1 - status WHERE groupID = ?";
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
                WHERE cg.status = 1
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
    
    /**
     * Kiểm tra khoảng chi tiêu có bị giao nhau với nhóm khác không
     * @param float $minSpent - Chi tiêu tối thiểu
     * @param float|null $maxSpent - Chi tiêu tối đa (null = không giới hạn)
     * @param int|null $excludeGroupID - ID nhóm cần loại trừ khi kiểm tra (dùng khi update)
     * @return array|false - Trả về nhóm bị trùng hoặc false nếu không trùng
     */
    public function checkOverlappingRange($minSpent, $maxSpent, $excludeGroupID = null) {
        // Build query
        $sql = "SELECT groupID, groupName, minSpent, maxSpent 
                FROM customer_group 
                WHERE 1=1";
        
        // Loại trừ nhóm hiện tại nếu đang update
        if ($excludeGroupID !== null) {
            $sql .= " AND groupID != " . intval($excludeGroupID);
        }
        
        // Kiểm tra giao nhau
        // Case 1: Nhóm mới có maxSpent = NULL (không giới hạn)
        if ($maxSpent === null) {
            // Trùng nếu có nhóm nào có minSpent >= minSpent của nhóm mới
            $sql .= " AND minSpent >= " . floatval($minSpent);
        } else {
            // Case 2: Nhóm mới có maxSpent xác định
            // Trùng nếu:
            // - Nhóm khác có maxSpent = NULL và minSpent < maxSpent của nhóm mới
            // - Hoặc khoảng [minSpent, maxSpent] giao nhau
            $sql .= " AND (
                (maxSpent IS NULL AND minSpent < " . floatval($maxSpent) . ")
                OR (
                    maxSpent IS NOT NULL 
                    AND NOT (
                        maxSpent < " . floatval($minSpent) . " 
                        OR minSpent > " . floatval($maxSpent) . "
                    )
                )
            )";
        }
        
        $result = mysqli_query($this->conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result); // Trả về nhóm bị trùng
        }
        
        return false; // Không trùng
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
