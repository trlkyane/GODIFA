<?php
/**
 * Fix Script: Sửa trạng thái giao hàng cho các đơn đã hoàn tiền
 * Các đơn có paymentStatus = 'Đã hoàn tiền' cần có deliveryStatus phù hợp
 */

require_once 'model/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== KIỂM TRA VÀ SỬA CÁC ĐƠN ĐÃ HOÀN TIỀN ===\n\n";

// Bước 1: Tìm tất cả các đơn có paymentStatus = 'Đã hoàn tiền' với deliveryStatus không phù hợp
$query = "SELECT orderID, customerID, paymentMethod, paymentStatus, deliveryStatus, 
                 totalAmount, orderDate, paymentDate
          FROM `order` 
          WHERE paymentStatus = 'Đã hoàn tiền' 
          AND (deliveryStatus IS NULL OR deliveryStatus = '' OR deliveryStatus NOT IN ('Đã hủy', 'Đã hoàn tiền', 'Hoàn thành'))
          ORDER BY orderDate DESC";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$problemOrders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $problemOrders[] = $row;
}

echo "Số đơn cần sửa: " . count($problemOrders) . "\n\n";

if (count($problemOrders) == 0) {
    echo "✅ KHÔNG có đơn hàng nào cần sửa!\n";
    exit;
}

// Hiển thị chi tiết các đơn cần sửa
echo "Chi tiết các đơn hàng:\n";
echo str_repeat("=", 120) . "\n";
printf("%-8s | %-12s | %-20s | %-18s | %-18s | %-15s | %-19s\n", 
    "OrderID", "CustomerID", "Payment Method", "Payment Status", "Delivery Status", "Amount", "Order Date");
echo str_repeat("=", 120) . "\n";

foreach ($problemOrders as $order) {
    printf("%-8s | %-12s | %-20s | %-18s | %-18s | %15s | %-19s\n",
        $order['orderID'],
        $order['customerID'],
        $order['paymentMethod'],
        $order['paymentStatus'],
        $order['deliveryStatus'] ?: '(NULL)',
        number_format($order['totalAmount']) . ' đ',
        $order['orderDate']
    );
}

echo str_repeat("=", 120) . "\n\n";

// Bước 2: Đưa ra các phương án xử lý
echo "=== PHƯƠNG ÁN XỬ LÝ ===\n\n";

echo "Có 3 phương án:\n\n";

echo "1. CẬP NHẬT delivery_status = 'Đã hủy'\n";
echo "   - Phù hợp với logic: Đơn đã hoàn tiền thường là đơn đã bị hủy\n";
echo "   - Giữ lại đơn hàng trong database để theo dõi lịch sử\n";
echo "   - Đề xuất: ✅ NÊN CHỌN\n\n";

echo "2. CẬP NHẬT delivery_status = 'Đã hoàn tiền'\n";
echo "   - Đồng bộ với payment_status\n";
echo "   - Rõ ràng về trạng thái đơn hàng\n";
echo "   - Đề xuất: ✅ CŨNG TỐT\n\n";

echo "3. XÓA CÁC ĐƠN NÀY\n";
echo "   - Mất dữ liệu lịch sử\n";
echo "   - Không theo dõi được thống kê hoàn tiền\n";
echo "   - Đề xuất: ❌ KHÔNG NÊN\n\n";

echo str_repeat("=", 120) . "\n\n";

// Bước 3: Tự động sửa (uncomment để chạy)
$autoFix = false; // Đổi thành true để tự động sửa

if ($autoFix) {
    echo "=== BẮT ĐẦU TỰ ĐỘNG SỬA ===\n\n";
    
    $fixQuery = "UPDATE `order` 
                 SET deliveryStatus = 'Đã hủy'
                 WHERE paymentStatus = 'Đã hoàn tiền' 
                 AND (deliveryStatus IS NULL OR deliveryStatus = '' OR deliveryStatus NOT IN ('Đã hủy', 'Đã hoàn tiền', 'Hoàn thành'))";
    
    if (mysqli_query($conn, $fixQuery)) {
        $affectedRows = mysqli_affected_rows($conn);
        echo "✅ Đã cập nhật thành công {$affectedRows} đơn hàng!\n";
        echo "   Delivery Status đã được đổi thành: 'Đã hủy'\n\n";
    } else {
        echo "❌ Lỗi khi cập nhật: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "=== HƯỚNG DẪN SỬA THỦ CÔNG ===\n\n";
    
    echo "Để sửa các đơn này, chạy 1 trong 2 câu lệnh SQL sau:\n\n";
    
    echo "--- PHƯƠNG ÁN 1: Đổi thành 'Đã hủy' ---\n";
    echo "UPDATE `order` \n";
    echo "SET deliveryStatus = 'Đã hủy'\n";
    echo "WHERE paymentStatus = 'Đã hoàn tiền' \n";
    echo "AND (deliveryStatus IS NULL OR deliveryStatus = '' \n";
    echo "     OR deliveryStatus NOT IN ('Đã hủy', 'Đã hoàn tiền', 'Hoàn thành'));\n\n";
    
    echo "--- PHƯƠNG ÁN 2: Đổi thành 'Đã hoàn tiền' ---\n";
    echo "UPDATE `order` \n";
    echo "SET deliveryStatus = 'Đã hoàn tiền'\n";
    echo "WHERE paymentStatus = 'Đã hoàn tiền' \n";
    echo "AND (deliveryStatus IS NULL OR deliveryStatus = '' \n";
    echo "     OR deliveryStatus NOT IN ('Đã hủy', 'Đã hoàn tiền', 'Hoàn thành'));\n\n";
    
    echo "--- HOẶC SỬA FILE NÀY ---\n";
    echo "Mở file này, đổi dòng: \$autoFix = false; thành \$autoFix = true;\n";
    echo "Sau đó chạy lại: php fix_refunded_orders.php\n\n";
}

echo "\n=== HOÀN TẤT ===\n";
