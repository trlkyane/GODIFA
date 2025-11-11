<?php
require_once __DIR__ . '/database.php';

class Order {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Tạo đơn hàng mới
    public function createOrder($customerId, $totalAmount, $paymentMethod, $voucherId = 0) {
        $paymentStatus = 'Chờ thanh toán';
        $deliveryStatus = 'Chờ xử lý';
        $sql = "INSERT INTO `order` (orderDate, paymentStatus, deliveryStatus, totalAmount, paymentMethod, customerID, voucherID) 
                VALUES (NOW(), ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssdsii", $paymentStatus, $deliveryStatus, $totalAmount, $paymentMethod, $customerId, $voucherId);
        
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }
    
    // Thêm chi tiết đơn hàng
    public function addOrderDetails($orderId, $productId, $quantity, $price) {
        $sql = "INSERT INTO order_details (orderID, productID, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiid", $orderId, $productId, $quantity, $price);
        return mysqli_stmt_execute($stmt);
    }
    
    // Lấy đơn hàng theo ID
    public function getOrderById($orderId) {
        $sql = "SELECT o.*, c.customerName, c.email, c.phone,
                d.recipientName, d.recipientPhone, d.recipientEmail, d.fullAddress, d.deliveryNotes
                FROM `order` o 
                INNER JOIN customer c ON o.customerID = c.customerID 
                LEFT JOIN order_delivery d ON o.orderID = d.orderID
                WHERE o.orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Lấy chi tiết đơn hàng
    public function getOrderDetails($orderId) {
        $sql = "SELECT od.*, p.productName, p.image 
                FROM order_details od 
                INNER JOIN product p ON od.productID = p.productID 
                WHERE od.orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $details = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $details[] = $row;
        }
        return $details;
    }
    
    // Lấy đơn hàng theo khách hàng
    public function getOrdersByCustomer($customerId) {
        $sql = "SELECT * FROM `order` WHERE customerID = ? ORDER BY orderDate DESC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
        return $orders;
    }
    
    // Lấy tất cả đơn hàng (admin)
    public function getAllOrders($limit = null, $offset = 0) {
        $sql = "SELECT o.*, c.customerName, c.phone,
                d.recipientName, d.recipientPhone, d.fullAddress,
                (SELECT SUM(quantity) FROM order_details WHERE orderID = o.orderID) as totalProducts
                FROM `order` o 
                INNER JOIN customer c ON o.customerID = c.customerID 
                LEFT JOIN order_delivery d ON o.orderID = d.orderID
                ORDER BY o.orderDate DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        $result = mysqli_query($this->conn, $sql);
        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
        return $orders;
    }
    
    // Cập nhật trạng thái thanh toán
    public function updatePaymentStatus($orderId, $paymentStatus) {
        $sql = "UPDATE `order` SET paymentStatus = ? WHERE orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $paymentStatus, $orderId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Cập nhật trạng thái giao hàng
    public function updateDeliveryStatus($orderId, $deliveryStatus) {
        $sql = "UPDATE `order` SET deliveryStatus = ? WHERE orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $deliveryStatus, $orderId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Cập nhật cả 2 trạng thái
    public function updateOrderStatus($orderId, $paymentStatus, $deliveryStatus, $cancelReason = null) {
        // Auto update payment status for COD when delivery is completed
        if ($deliveryStatus === 'Hoàn thành') {
            // Get order payment method
            $checkSql = "SELECT paymentMethod, paymentStatus FROM `order` WHERE orderID = ?";
            $checkStmt = mysqli_prepare($this->conn, $checkSql);
            mysqli_stmt_bind_param($checkStmt, "i", $orderId);
            mysqli_stmt_execute($checkStmt);
            $result = mysqli_stmt_get_result($checkStmt);
            $order = mysqli_fetch_assoc($result);
            
            // If COD and payment is pending, auto mark as paid
            if ($order && $order['paymentMethod'] === 'COD' && 
                strpos($order['paymentStatus'], 'Chờ thanh toán') !== false) {
                $paymentStatus = 'Đã thanh toán';
            }
        }
        
        if ($cancelReason !== null) {
            $sql = "UPDATE `order` SET paymentStatus = ?, deliveryStatus = ?, cancelReason = ? WHERE orderID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $paymentStatus, $deliveryStatus, $cancelReason, $orderId);
        } else {
            $sql = "UPDATE `order` SET paymentStatus = ?, deliveryStatus = ? WHERE orderID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $paymentStatus, $deliveryStatus, $orderId);
        }
        return mysqli_stmt_execute($stmt);
    }
    
    // Hủy đơn hàng
    public function cancelOrder($orderId, $cancelReason = 'Không rõ lý do') {
        return $this->updateOrderStatus($orderId, 'Đã hủy', 'Đã hủy', $cancelReason);
    }
    
    // Cập nhật ghi chú đơn hàng (nội bộ - chỉ admin)
    public function updateOrderNote($orderId, $note) {
        $sql = "UPDATE `order` SET note = ? WHERE orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $note, $orderId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Đếm tổng số đơn hàng
    public function countOrders() {
        $sql = "SELECT COUNT(*) as total FROM `order`";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Tính tổng doanh thu (loại trừ đơn đã hủy)
    public function getTotalRevenue() {
        $sql = "SELECT SUM(totalAmount) as revenue FROM `order` WHERE paymentStatus != 'Đã hủy'";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['revenue'] ?? 0;
    }
    
    // Đếm đơn hàng theo trạng thái thanh toán
    public function countByStatus($status) {
        $sql = "SELECT COUNT(*) as total FROM `order` WHERE paymentStatus = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $status);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    // Lấy thống kê đơn hàng theo trạng thái
    public function getOrderStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN paymentStatus = 'Chờ thanh toán' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN deliveryStatus = 'Đang giao' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN deliveryStatus = 'Hoàn thành' AND paymentStatus = 'Đã thanh toán' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN paymentStatus = 'Đã hủy' OR deliveryStatus = 'Đã hủy' THEN 1 ELSE 0 END) as cancelled
                FROM `order`";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }
    
    // Tìm kiếm đơn hàng theo mã đơn hàng hoặc số điện thoại
    public function searchOrders($keyword = null) {
        if ($keyword !== null && $keyword !== '') {
            // Tự động phát hiện: 
            // - Nếu độ dài <= 6 và là số thuần túy → tìm theo mã đơn hàng
            // - Ngược lại → tìm theo số điện thoại
            if (is_numeric($keyword) && strlen($keyword) <= 6) {
                // Tìm theo mã đơn hàng (mã đơn thường ngắn, < 6 chữ số)
                $sql = "SELECT o.*, c.customerName, c.phone,
                        d.recipientName, d.recipientPhone, d.fullAddress,
                        (SELECT SUM(quantity) FROM order_details WHERE orderID = o.orderID) as totalProducts
                        FROM `order` o 
                        INNER JOIN customer c ON o.customerID = c.customerID 
                        LEFT JOIN order_delivery d ON o.orderID = d.orderID
                        WHERE o.orderID = ?
                        ORDER BY o.orderDate DESC";
                $stmt = mysqli_prepare($this->conn, $sql);
                $orderID = intval($keyword);
                mysqli_stmt_bind_param($stmt, "i", $orderID);
            } else {
                // Tìm theo số điện thoại (LIKE để hỗ trợ tìm một phần)
                $sql = "SELECT o.*, c.customerName, c.phone,
                        d.recipientName, d.recipientPhone, d.fullAddress,
                        (SELECT SUM(quantity) FROM order_details WHERE orderID = o.orderID) as totalProducts
                        FROM `order` o 
                        INNER JOIN customer c ON o.customerID = c.customerID 
                        LEFT JOIN order_delivery d ON o.orderID = d.orderID
                        WHERE c.phone LIKE ?
                        ORDER BY o.orderDate DESC";
                $stmt = mysqli_prepare($this->conn, $sql);
                $phonePattern = "%$keyword%";
                mysqli_stmt_bind_param($stmt, "s", $phonePattern);
            }
            
            // Thực thi prepared statement
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            // Không có từ khóa, trả về tất cả
            $sql = "SELECT o.*, c.customerName, c.phone,
                    d.recipientName, d.recipientPhone, d.fullAddress,
                    (SELECT SUM(quantity) FROM order_details WHERE orderID = o.orderID) as totalProducts
                    FROM `order` o 
                    INNER JOIN customer c ON o.customerID = c.customerID 
                    LEFT JOIN order_delivery d ON o.orderID = d.orderID
                    ORDER BY o.orderDate DESC";
            $result = mysqli_query($this->conn, $sql);
        }
        
        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
        return $orders;
    }
    
    // ============================================
    // METHODS MỚI CHO SEPAY & GHN
    // ============================================
    
    /**
     * Cập nhật phí vận chuyển
     */
    public function updateShippingFee($orderID, $shippingFee) {
        $sql = "UPDATE `order` SET shippingFee = ? WHERE orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "di", $shippingFee, $orderID);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Cập nhật shippingMetadata (dữ liệu từ GHN webhook)
     */
    public function updateShippingMetadata($orderID, $metadata) {
        $sql = "UPDATE `order` SET shippingMetadata = ? WHERE orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $metadata, $orderID);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Cập nhật thời gian giao hàng dự kiến
     */
    public function updateExpectedDeliveryTime($orderID, $expectedTime) {
        $sql = "UPDATE `order` SET expectedDeliveryTime = ? WHERE orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $expectedTime, $orderID);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Cập nhật thời gian giao hàng thực tế
     */
    public function updateActualDeliveryTime($orderID, $actualTime) {
        $sql = "UPDATE `order` SET actualDeliveryTime = ? WHERE orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $actualTime, $orderID);
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Thêm lịch sử vận chuyển (shipping_history table removed)
     */
    public function addShippingHistory($data) {
        // Table shipping_history không còn tồn tại
        // Method này giữ lại để tránh breaking code
        return true;
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Lấy lịch sử vận chuyển của đơn hàng
     */
    public function getShippingHistory($orderID) {
        $sql = "SELECT * FROM shipping_history WHERE orderID = ? ORDER BY createdAt ASC";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $history = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $history[] = $row;
        }
        return $history;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
