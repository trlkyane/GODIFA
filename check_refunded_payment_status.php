<?php
require_once 'model/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   PHÂN TÍCH: Payment Status = 'Đã hoàn tiền'\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Query các trường hợp
$query = "SELECT 
            deliveryStatus, 
            COUNT(*) as soLuong,
            SUM(totalAmount) as tongTien,
            GROUP_CONCAT(orderID ORDER BY orderID SEPARATOR ', ') as danhSachDonID
          FROM `order` 
          WHERE paymentStatus = 'Đã hoàn tiền' 
          GROUP BY deliveryStatus
          ORDER BY soLuong DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$cases = [];
$totalOrders = 0;
$totalAmount = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $cases[] = $row;
    $totalOrders += $row['soLuong'];
    $totalAmount += $row['tongTien'];
}

echo "Có tất cả " . count($cases) . " trường hợp khác nhau:\n\n";

printf("%-25s | %-10s | %-15s | %s\n", "Delivery Status", "Số đơn", "Tổng tiền", "Danh sách OrderID");
echo str_repeat("═", 100) . "\n";

foreach ($cases as $case) {
    $deliveryStatus = $case['deliveryStatus'] ?: '(NULL)';
    $soLuong = $case['soLuong'];
    $tongTien = number_format($case['tongTien'], 0, ',', '.') . ' đ';
    $danhSach = $case['danhSachDonID'];
    
    printf("%-25s | %10d | %15s | %s\n", 
        $deliveryStatus, 
        $soLuong, 
        $tongTien,
        $danhSach
    );
}

echo str_repeat("─", 100) . "\n";
printf("%-25s | %10d | %15s\n", "TỔNG CỘNG", $totalOrders, number_format($totalAmount, 0, ',', '.') . ' đ');
echo str_repeat("═", 100) . "\n\n";

// Phân tích từng trường hợp
echo "PHÂN TÍCH CHI TIẾT:\n\n";

foreach ($cases as $i => $case) {
    $num = $i + 1;
    $deliveryStatus = $case['deliveryStatus'] ?: '(NULL)';
    $soLuong = $case['soLuong'];
    
    echo "{$num}. Trường hợp: [Đã hoàn tiền] + [{$deliveryStatus}] - {$soLuong} đơn\n";
    
    // Đánh giá logic
    if ($deliveryStatus === '(NULL)') {
        echo "   ❌ LỖI: Delivery status bị NULL\n";
        echo "   💡 Giải pháp: Cần update thành 'Đã hủy'\n";
        echo "   🔧 Lệnh: php fix_refunded_orders.php\n";
    } elseif ($deliveryStatus === 'Hoàn thành') {
        echo "   ⚠️  CHÚ Ý: Đã giao xong rồi mới hoàn tiền\n";
        echo "   💡 Lý do có thể:\n";
        echo "      - Khách trả hàng sau khi nhận (return request)\n";
        echo "      - Hoàn tiền một phần do lỗi sản phẩm\n";
        echo "   🔍 Cần check: Có trong bảng return_requests không?\n";
    } elseif ($deliveryStatus === 'Đã hủy') {
        echo "   ✅ ĐÚNG: Đơn đã hủy và đã hoàn tiền\n";
        echo "   💡 Flow: Khách/Admin hủy đơn đã thanh toán → Hoàn tiền\n";
    } elseif ($deliveryStatus === 'Đã hoàn tiền') {
        echo "   ✅ ĐÚNG: Đơn hoàn trả và đã hoàn tiền\n";
        echo "   💡 Flow: Khách trả hàng → Admin xác nhận → Hoàn tiền\n";
    } elseif ($deliveryStatus === 'Chờ xử lý hoàn tiền') {
        echo "   ⚠️  CŨ: Trạng thái cũ (đã bỏ)\n";
        echo "   💡 Nên update: Thành 'Đã hủy' hoặc 'Đã hoàn tiền'\n";
    } else {
        echo "   ⚠️  BẤT THƯỜNG: Trạng thái không mong đợi\n";
        echo "   💡 Cần review: Kiểm tra logic nghiệp vụ\n";
    }
    
    echo "\n";
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "   TỔNG KẾT\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Khi paymentStatus = 'Đã hoàn tiền', deliveryStatus có thể là:\n\n";

$validCases = [
    'Đã hủy' => 'Đơn bị hủy trước khi giao → Hoàn tiền (PHỔ BIẾN)',
    'Đã hoàn tiền' => 'Đơn trả hàng sau khi nhận → Hoàn tiền (RETURN)',
    'Hoàn thành' => 'Đã giao xong nhưng hoàn tiền (CẦN CHECK)',
    '(NULL)' => 'Lỗi dữ liệu cũ (CẦN SỬA)',
];

foreach ($validCases as $status => $desc) {
    $count = 0;
    foreach ($cases as $case) {
        if (($case['deliveryStatus'] ?: '(NULL)') === $status) {
            $count = $case['soLuong'];
            break;
        }
    }
    
    $icon = $count > 0 ? "✅" : "❌";
    printf("  %s %-20s - %2d đơn - %s\n", $icon, $status, $count, $desc);
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
