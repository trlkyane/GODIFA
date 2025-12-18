<?php
require_once 'model/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   KIỂM TRA 3 ĐƠN: Đã hoàn tiền + Hoàn thành\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$orderIDs = [197, 201, 202];

foreach ($orderIDs as $orderID) {
    echo "─────────────────────────────────────────────────────────────────\n";
    echo "OrderID: #{$orderID}\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Lấy thông tin đơn hàng
    $query = "SELECT o.*, 
                     r.returnID, r.status as returnStatus, r.reason as returnReason,
                     r.createdAt as returnCreatedAt, r.processedAt as returnProcessedAt
              FROM `order` o
              LEFT JOIN return_requests r ON o.orderID = r.orderID
              WHERE o.orderID = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo "❌ Lỗi prepare query: " . $conn->error . "\n\n";
        continue;
    }
    $stmt->bind_param("i", $orderID);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        echo "❌ Không tìm thấy đơn hàng!\n\n";
        continue;
    }
    
    // Hiển thị thông tin
    echo "Payment Status:  {$order['paymentStatus']}\n";
    echo "Delivery Status: {$order['deliveryStatus']}\n";
    echo "Total Amount:    " . number_format($order['totalAmount']) . " đ\n";
    echo "Order Date:      {$order['orderDate']}\n";
    echo "Payment Date:    " . ($order['paymentDate'] ?: '(NULL)') . "\n";
    echo "\n";
    
    // Kiểm tra return request
    if ($order['returnID']) {
        echo "✅ CÓ Return Request:\n";
        echo "   Return ID:       {$order['returnID']}\n";
        echo "   Status:          {$order['returnStatus']}\n";
        echo "   Reason:          {$order['returnReason']}\n";
        echo "   Created At:      {$order['returnCreatedAt']}\n";
        echo "   Processed At:    " . ($order['returnProcessedAt'] ?: '(NULL)') . "\n";
        echo "\n";
        echo "💡 ĐÁNH GIÁ: Đây là đơn trả hàng HỢP LỆ\n";
        echo "   - Khách đã nhận hàng (Hoàn thành)\n";
        echo "   - Khách tạo yêu cầu trả hàng\n";
        echo "   - Admin xác nhận và hoàn tiền\n";
        echo "   ✅ Delivery Status = 'Hoàn thành' là ĐÚNG\n";
    } else {
        echo "❌ KHÔNG có Return Request\n";
        echo "\n";
        echo "⚠️  ĐÁNH GIÁ: Trường hợp BẤT THƯỜNG\n";
        echo "   - Đơn đã giao thành công (Hoàn thành)\n";
        echo "   - Đã hoàn tiền nhưng KHÔNG có return request\n";
        echo "   - Có thể là:\n";
        echo "     • Hoàn tiền thủ công không qua hệ thống\n";
        echo "     • Lỗi nghiệp vụ khi xử lý\n";
        echo "     • Dữ liệu cũ chưa chuẩn hóa\n";
        echo "\n";
        echo "💡 ĐỀ XUẤT: Nên đổi deliveryStatus = 'Đã hoàn tiền'\n";
        echo "   Vì đã hoàn tiền rồi thì không còn 'Hoàn thành' nữa\n";
    }
    
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   TỔNG KẾT\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Đếm số đơn có return request
$countQuery = "SELECT COUNT(*) as count 
               FROM `order` o
               INNER JOIN return_requests r ON o.orderID = r.orderID
               WHERE o.orderID IN (197, 201, 202)";
$result = $conn->query($countQuery);
$count = $result->fetch_assoc()['count'];

$validCount = 0;
$invalidCount = 0;

// Đếm từng đơn
foreach ($orderIDs as $oid) {
    $checkQuery = "SELECT returnID FROM return_requests WHERE orderID = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $oid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $validCount++;
    } else {
        $invalidCount++;
    }
}

echo "Có {$validCount}/3 đơn có Return Request\n\n";

if ($validCount == 3) {
    echo "✅ KẾT LUẬN: CẢ 3 ĐƠN ĐỀU HỢP LỆ\n";
    echo "   → Đây là đơn trả hàng sau khi nhận\n";
    echo "   → Delivery Status = 'Hoàn thành' là ĐÚNG\n";
    echo "   → KHÔNG CẦN sửa gì\n";
    echo "\n";
    echo "💡 GIẢI THÍCH:\n";
    echo "   Flow: Đặt hàng → Thanh toán → Giao hàng (Hoàn thành)\n";
    echo "         → Khách tạo yêu cầu trả hàng → Admin hoàn tiền\n";
    echo "   → Payment Status = 'Đã hoàn tiền' (vì đã hoàn tiền)\n";
    echo "   → Delivery Status = 'Hoàn thành' (vì đã giao thành công)\n";
} elseif ($validCount == 0) {
    echo "❌ KẾT LUẬN: CẢ 3 ĐƠN KHÔNG CÓ RETURN REQUEST\n";
    echo "   → Đây là trường hợp bất thường\n";
    echo "   → NÊN đổi deliveryStatus = 'Đã hoàn tiền'\n";
    echo "   → Chạy lệnh update để chuẩn hóa\n";
} else {
    echo "⚠️  KẾT LUẬN: CÓ {$validCount} ĐƠN HỢP LỆ, {$invalidCount} ĐƠN CẦN SỬA\n";
    echo "   → Các đơn CÓ return request: Giữ nguyên\n";
    echo "   → Các đơn KHÔNG có return request: Đổi thành 'Đã hoàn tiền'\n";
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
