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
                d.recipientName, d.recipientPhone, d.recipientEmail, d.fullAddress, d.deliveryNotes,
                TIMESTAMPDIFF(MINUTE, o.orderDate, o.paymentDate) as paymentDelayMinutes,
                CASE 
                    WHEN o.paymentDate IS NOT NULL AND TIMESTAMPDIFF(HOUR, o.orderDate, o.paymentDate) >= 1 
                    THEN 1 
                    ELSE 0 
                END as isLatePayment
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
                (SELECT SUM(quantity) FROM order_details WHERE orderID = o.orderID) as totalProducts,
                TIMESTAMPDIFF(MINUTE, o.orderDate, o.paymentDate) as paymentDelayMinutes,
                CASE 
                    WHEN o.paymentDate IS NOT NULL AND TIMESTAMPDIFF(HOUR, o.orderDate, o.paymentDate) >= 1 
                    THEN 1 
                    ELSE 0 
                END as isLatePayment
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
    public function updatePaymentStatus($orderId, $paymentStatus, $paymentDate = null) {
    // Nếu chuyển sang "Đã thanh toán" và không có paymentDate, tự động set thời gian hiện tại
        // Dùng MySQL NOW() thay vì PHP date() để đảm bảo cùng timezone với orderDate
        if ($paymentStatus === 'Đã thanh toán' && $paymentDate === null) {
            $nowResult = mysqli_query($this->conn, "SELECT NOW() as currentTime");
            $nowRow = mysqli_fetch_assoc($nowResult);
            $paymentDate = $nowRow['currentTime'];
        }
        
    // Nếu chuyển về "Chờ thanh toán" hoặc "Đã hủy", xóa paymentDate
    if (in_array($paymentStatus, ['Chờ thanh toán', 'Đã hủy'])) {
            $sql = "UPDATE `order` SET paymentStatus = ?, paymentDate = NULL WHERE orderID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $paymentStatus, $orderId);
        } else {
            $sql = "UPDATE `order` SET paymentStatus = ?, paymentDate = ? WHERE orderID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $paymentStatus, $paymentDate, $orderId);
        }
        
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
        // Lấy thông tin đơn hàng hiện tại
        $checkSql = "SELECT paymentMethod, paymentStatus, paymentDate, deliveryStatus FROM `order` WHERE orderID = ?";
        $checkStmt = mysqli_prepare($this->conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "i", $orderId);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);
        $order = mysqli_fetch_assoc($result);
        
        if (!$order) {
            return false;
        }
        
        // Giữ nguyên paymentDate hiện tại, không ghi đè
        $paymentDate = $order['paymentDate'];
        $shouldUpdatePaymentDate = false;
        
        // Auto update payment status for COD when delivery is completed
        if ($deliveryStatus === 'Hoàn thành') {
            // If COD and payment is pending, auto mark as paid
            if ($order['paymentMethod'] === 'COD' && 
                strpos($order['paymentStatus'], 'Chờ thanh toán') !== false) {
                $paymentStatus = 'Đã thanh toán';
                // Chỉ set paymentDate nếu chưa có - Dùng MySQL NOW() để đảm bảo cùng timezone với orderDate
                if ($paymentDate === null) {
                    // Lấy timestamp từ MySQL NOW() thay vì PHP date()
                    $nowResult = mysqli_query($this->conn, "SELECT NOW() as currentTime");
                    $nowRow = mysqli_fetch_assoc($nowResult);
                    $paymentDate = $nowRow['currentTime'];
                    $shouldUpdatePaymentDate = true;
                }
            }
        }
        
    // Nếu chuyển sang "Đã thanh toán" và chưa có paymentDate, ghi nhận thời điểm
        if ($paymentStatus === 'Đã thanh toán' && $paymentDate === null) {
            // Dùng MySQL NOW() để đảm bảo cùng timezone với orderDate
            $nowResult = mysqli_query($this->conn, "SELECT NOW() as currentTime");
            $nowRow = mysqli_fetch_assoc($nowResult);
            $paymentDate = $nowRow['currentTime'];
            $shouldUpdatePaymentDate = true;
        }
        
        // Nếu hủy đơn, xóa paymentDate
        if ($paymentStatus === 'Đã hủy') {
            $paymentDate = null;
            $shouldUpdatePaymentDate = true;
        }
        
        // Build SQL dynamically - chỉ update paymentDate khi cần
        if ($cancelReason !== null) {
            $sql = "UPDATE `order` SET paymentStatus = ?, deliveryStatus = ?, cancelReason = ?";
            $types = "sss";
            $params = [$paymentStatus, $deliveryStatus, $cancelReason];
        } else {
            $sql = "UPDATE `order` SET paymentStatus = ?, deliveryStatus = ?";
            $types = "ss";
            $params = [$paymentStatus, $deliveryStatus];
        }
        
        // Chỉ thêm paymentDate vào UPDATE nếu có thay đổi
        if ($shouldUpdatePaymentDate) {
            // Thêm vào trước WHERE
            $sql = str_replace(" WHERE", ", paymentDate = ? WHERE", $sql . " WHERE");
            $types .= "s";
            $params[] = $paymentDate;
        } else {
            $sql .= " WHERE";
        }
        
        $sql .= " orderID = ?";
        $types .= "i";
        $params[] = $orderId;
        
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        return mysqli_stmt_execute($stmt);
    }
    
    // Hủy đơn hàng
    public function cancelOrder($orderId, $cancelReason = 'Không rõ lý do') {
        // Hoàn lại tồn kho trước khi hủy đơn
        $this->restoreStock($orderId);
        
        return $this->updateOrderStatus($orderId, 'Đã hủy', 'Đã hủy', $cancelReason);
    }
    
    // Trừ tồn kho khi xác nhận đơn (chuyển sang vận chuyển)
    private function reduceStock($orderId) {
        // Lấy danh sách sản phẩm trong đơn
        $sql = "SELECT productID, quantity FROM order_details WHERE orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        // Trừ tồn kho cho từng sản phẩm
        while ($item = mysqli_fetch_assoc($result)) {
            $updateSql = "UPDATE product SET stockQuantity = stockQuantity - ? WHERE productID = ?";
            $updateStmt = mysqli_prepare($this->conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "ii", $item['quantity'], $item['productID']);
            mysqli_stmt_execute($updateStmt);
        }
        
        return true;
    }
    
    // Hoàn lại tồn kho khi hủy đơn
    private function restoreStock($orderId) {
        // Lấy danh sách sản phẩm trong đơn
        $sql = "SELECT productID, quantity FROM order_details WHERE orderID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        // Hoàn lại từng sản phẩm
        while ($item = mysqli_fetch_assoc($result)) {
            $updateSql = "UPDATE product SET stockQuantity = stockQuantity + ? WHERE productID = ?";
            $updateStmt = mysqli_prepare($this->conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "ii", $item['quantity'], $item['productID']);
            mysqli_stmt_execute($updateStmt);
        }
        
        return true;
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
    // METHODS Má»šI CHO SEPAY & GHN
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
    
    // Đếm số đơn mới thanh toán trong X phút gần đây
    public function countNewPaidOrders($minutesAgo = 30) {
        // Kiểm tra cột paymentDate có tồn tại không
        $result = mysqli_query($this->conn, "SHOW COLUMNS FROM `order` LIKE 'paymentDate'");
        if (!$result || mysqli_num_rows($result) == 0) {
            return 0; // Chưa có cột paymentDate
        }
        
        // Đếm đơn thanh toán trong X phút gần đây
        $sql = "SELECT COUNT(*) as total 
                FROM `order` 
                WHERE paymentStatus = 'Đã thanh toán'
                AND paymentDate IS NOT NULL
                AND paymentDate >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $minutesAgo);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
