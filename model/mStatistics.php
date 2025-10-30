<?php
/**
 * Model: Statistics
 * Xử lý dữ liệu thống kê, báo cáo
 */

require_once __DIR__ . '/database.php';

class Statistics {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    /**
     * Lấy thống kê doanh thu theo kỳ
     * @param string $period - 'day', 'week', 'month', 'year', 'custom'
     * @param string $startDate - Ngày bắt đầu (cho custom)
     * @param string $endDate - Ngày kết thúc (cho custom)
     * @return array
     */
    public function getRevenueStats($period = 'month', $startDate = null, $endDate = null) {
        $stats = [
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'growth' => 0
        ];
        
        // Doanh thu kỳ hiện tại
        $sql = "SELECT 
            SUM(CASE WHEN paymentStatus = 'Đã thanh toán' THEN totalAmount ELSE 0 END) as completed,
            SUM(CASE WHEN paymentStatus = 'Chờ thanh toán' THEN totalAmount ELSE 0 END) as pending,
            SUM(totalAmount) as total
        FROM `order` 
        WHERE paymentStatus != 'Đã hủy'";
        
        $sql .= $this->buildDateFilter($period, $startDate, $endDate);
        
        $result = mysqli_query($this->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $stats['total'] = $row['total'] ?? 0;
            $stats['completed'] = $row['completed'] ?? 0;
            $stats['pending'] = $row['pending'] ?? 0;
        }
        
        // Tính tăng trưởng so với kỳ trước
        $prevTotal = $this->getPreviousPeriodRevenue($period);
        if ($prevTotal > 0) {
            $stats['growth'] = (($stats['completed'] - $prevTotal) / $prevTotal) * 100;
        }
        
        return $stats;
    }
    
    /**
     * Lấy thống kê đơn hàng theo kỳ
     */
    public function getOrderStats($period = 'month', $startDate = null, $endDate = null) {
        $stats = [
            'total' => 0,
            'completed' => 0,
            'processing' => 0,
            'pending' => 0,
            'cancelled' => 0
        ];
        
        $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN deliveryStatus = 'Hoàn thành' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN paymentStatus = 'Đã thanh toán' AND deliveryStatus IN ('Đang giao', 'Đang xử lý') THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN paymentStatus = 'Chờ thanh toán' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN paymentStatus = 'Đã hủy' OR deliveryStatus = 'Đã hủy' THEN 1 ELSE 0 END) as cancelled
        FROM `order` WHERE 1=1";
        
        $sql .= $this->buildDateFilter($period, $startDate, $endDate);
        
        $result = mysqli_query($this->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $stats = array_merge($stats, $row);
        }
        
        return $stats;
    }
    
    /**
     * Lấy top sản phẩm bán chạy
     */
    public function getTopProducts($period = 'month', $limit = 5, $startDate = null, $endDate = null) {
        $products = [];
        
        $sql = "SELECT p.productID, p.productName, p.image, 
                SUM(od.quantity) as totalSold,
                SUM(od.quantity * od.price) as revenue
        FROM product p
        INNER JOIN order_details od ON p.productID = od.productID
        INNER JOIN `order` o ON od.orderID = o.orderID
        WHERE o.paymentStatus = 'Đã thanh toán'";
        
        $sql .= $this->buildDateFilter($period, $startDate, $endDate, 'o.');
        $sql .= " GROUP BY p.productID ORDER BY totalSold DESC LIMIT ?";
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        
        return $products;
    }
    
    /**
     * Phân tích phương thức thanh toán
     */
    public function getPaymentMethods($period = 'month', $startDate = null, $endDate = null) {
        $methods = [];
        
        $sql = "SELECT 
            paymentMethod,
            COUNT(*) as orderCount,
            SUM(totalAmount) as revenue
        FROM `order`
        WHERE paymentStatus = 'Đã thanh toán'";
        
        $sql .= $this->buildDateFilter($period, $startDate, $endDate);
        $sql .= " GROUP BY paymentMethod";
        
        $result = mysqli_query($this->conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $methods[] = $row;
            }
        }
        
        return $methods;
    }
    
    /**
     * Lấy dữ liệu biểu đồ doanh thu (30 ngày gần nhất)
     */
    public function getRevenueChartData($days = 30) {
        $chartData = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            
            $sql = "SELECT COALESCE(SUM(totalAmount), 0) as revenue 
                    FROM `order` 
                    WHERE DATE(orderDate) = ? 
                    AND paymentStatus = 'Đã thanh toán'";
            
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $date);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $revenue = 0;
            if ($row = mysqli_fetch_assoc($result)) {
                $revenue = $row['revenue'];
            }
            
            $chartData[] = [
                'date' => date('d/m', strtotime($date)),
                'fullDate' => $date,
                'revenue' => $revenue
            ];
        }
        
        return $chartData;
    }
    
    /**
     * Lấy doanh thu kỳ trước (để tính growth)
     */
    private function getPreviousPeriodRevenue($period) {
        $sql = "SELECT SUM(totalAmount) as total FROM `order` WHERE paymentStatus = 'Đã thanh toán'";
        
        if ($period == 'day') {
            $sql .= " AND DATE(orderDate) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        } elseif ($period == 'week') {
            $sql .= " AND YEARWEEK(orderDate, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)";
        } elseif ($period == 'month') {
            $sql .= " AND MONTH(orderDate) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                     AND YEAR(orderDate) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
        } elseif ($period == 'year') {
            $sql .= " AND YEAR(orderDate) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))";
        }
        
        $result = mysqli_query($this->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row['total'] ?? 0;
        }
        
        return 0;
    }
    
    /**
     * Build WHERE clause cho filter theo thời gian
     */
    private function buildDateFilter($period, $startDate = null, $endDate = null, $tableAlias = '') {
        $filter = '';
        $dateColumn = $tableAlias . 'orderDate';
        
        if ($period == 'day') {
            $filter = " AND DATE($dateColumn) = CURDATE()";
        } elseif ($period == 'week') {
            $filter = " AND YEARWEEK($dateColumn, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($period == 'month') {
            $filter = " AND MONTH($dateColumn) = MONTH(CURDATE()) AND YEAR($dateColumn) = YEAR(CURDATE())";
        } elseif ($period == 'year') {
            $filter = " AND YEAR($dateColumn) = YEAR(CURDATE())";
        } elseif ($period == 'custom' && $startDate && $endDate) {
            $filter = " AND DATE($dateColumn) BETWEEN '$startDate' AND '$endDate'";
        }
        
        return $filter;
    }
    
    /**
     * Lấy thống kê khách hàng (mở rộng sau)
     */
    public function getCustomerStats($period = 'month') {
        // TODO: Implement customer statistics
        return [
            'new_customers' => 0,
            'returning_customers' => 0,
            'avg_order_value' => 0
        ];
    }
}
