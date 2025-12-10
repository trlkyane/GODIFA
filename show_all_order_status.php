<?php
/**
 * Thống kê đầy đủ tất cả trạng thái đơn hàng
 * Phân tích tất cả các trường hợp Payment Status + Delivery Status
 */

require_once 'model/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "╔═══════════════════════════════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                          THỐNG KÊ ĐẦY ĐỦ TRẠNG THÁI ĐỠN HÀNG                                           ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════════════════════════════════════╝\n\n";

// Lấy tất cả các trường hợp
$query = "SELECT 
            paymentStatus, 
            deliveryStatus, 
            COUNT(*) as soLuong,
            SUM(totalAmount) as tongTien,
            MIN(orderDate) as donDauTien,
            MAX(orderDate) as donMoiNhat
          FROM `order` 
          GROUP BY paymentStatus, deliveryStatus
          ORDER BY paymentStatus, deliveryStatus";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$stats = [];
$totalOrders = 0;
$totalAmount = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $stats[] = $row;
    $totalOrders += $row['soLuong'];
    $totalAmount += $row['tongTien'];
}

// Lấy danh sách tất cả Payment Status có trong DB
$psQuery = "SELECT DISTINCT paymentStatus FROM `order` ORDER BY paymentStatus";
$psResult = mysqli_query($conn, $psQuery);
$paymentStatuses = [];
while ($row = mysqli_fetch_assoc($psResult)) {
    $paymentStatuses[] = $row['paymentStatus'];
}

// Lấy danh sách tất cả Delivery Status có trong DB
$dsQuery = "SELECT DISTINCT deliveryStatus FROM `order` ORDER BY deliveryStatus";
$dsResult = mysqli_query($conn, $dsQuery);
$deliveryStatuses = [];
while ($row = mysqli_fetch_assoc($dsResult)) {
    $deliveryStatuses[] = $row['deliveryStatus'] ?: '(NULL)';
}

echo "=== DANH SÁCH TẤT CẢ CÁC TRẠNG THÁI ===\n\n";

echo "📌 PAYMENT STATUS có trong hệ thống:\n";
foreach ($paymentStatuses as $ps) {
    echo "   • " . ($ps ?: '(NULL)') . "\n";
}

echo "\n📌 DELIVERY STATUS có trong hệ thống:\n";
foreach ($deliveryStatuses as $ds) {
    echo "   • " . $ds . "\n";
}

echo "\n" . str_repeat("═", 130) . "\n\n";

// Hiển thị bảng chi tiết
echo "=== CHI TIẾT TẤT CẢ CÁC TRƯỜNG HỢP ===\n\n";
printf("%-20s | %-20s | %-8s | %-15s | %-19s | %-19s | %-10s\n",
    "PAYMENT STATUS", "DELIVERY STATUS", "SỐ ĐƠN", "TỔNG TIỀN", "ĐƠN ĐẦU TIÊN", "ĐƠN MỚI NHẤT", "TRẠNG THÁI"
);
echo str_repeat("─", 130) . "\n";

foreach ($stats as $stat) {
    $payment = $stat['paymentStatus'] ?: '(NULL)';
    $delivery = $stat['deliveryStatus'] ?: '(NULL)';
    $count = $stat['soLuong'];
    $total = number_format($stat['tongTien'], 0, ',', '.') . ' đ';
    $first = date('d/m/Y H:i', strtotime($stat['donDauTien']));
    $last = date('d/m/Y H:i', strtotime($stat['donMoiNhat']));
    
    // Đánh giá trạng thái
    $status = evaluateOrderStatus($payment, $delivery);
    
    printf("%-20s | %-20s | %8s | %15s | %-19s | %-19s | %-10s\n",
        substr($payment, 0, 20),
        substr($delivery, 0, 20),
        $count,
        $total,
        $first,
        $last,
        $status
    );
}

echo str_repeat("─", 130) . "\n";
printf("%-20s | %-20s | %8s | %15s\n",
    "TỔNG CỘNG", "", $totalOrders, number_format($totalAmount, 0, ',', '.') . ' đ'
);
echo str_repeat("═", 130) . "\n\n";

// Phân tích các trường hợp bất thường
echo "=== PHÂN TÍCH CÁC TRƯỜNG HỢP ===\n\n";

$issues = [];

foreach ($stats as $stat) {
    $payment = $stat['paymentStatus'] ?: '(NULL)';
    $delivery = $stat['deliveryStatus'] ?: '(NULL)';
    $count = $stat['soLuong'];
    
    // Kiểm tra các trường hợp bất thường
    $issue = checkIssue($payment, $delivery);
    if ($issue) {
        $issues[] = [
            'payment' => $payment,
            'delivery' => $delivery,
            'count' => $count,
            'issue' => $issue['type'],
            'recommendation' => $issue['recommendation']
        ];
    }
}

if (empty($issues)) {
    echo "✅ KHÔNG CÓ TRƯỜNG HỢP BẤT THƯỜNG!\n\n";
} else {
    echo "⚠️  PHÁT HIỆN " . count($issues) . " TRƯỜNG HỢP CẦN LƯU Ý:\n\n";
    
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". [{$issue['payment']}] + [{$issue['delivery']}] - {$issue['count']} đơn\n";
        echo "   ⚠️  Vấn đề: {$issue['issue']}\n";
        echo "   💡 Đề xuất: {$issue['recommendation']}\n\n";
    }
}

echo str_repeat("═", 130) . "\n\n";

// Thống kê theo nhóm logic
echo "=== THỐNG KÊ THEO NHÓM LOGIC ===\n\n";

$groupQuery = "SELECT 
                CASE 
                    WHEN paymentStatus IN ('Đã thanh toán', 'Đã hoàn tiền') AND deliveryStatus = 'Hoàn thành' THEN 'Đơn hoàn tất'
                    WHEN paymentStatus = 'Đã hủy' OR deliveryStatus = 'Đã hủy' THEN 'Đơn đã hủy'
                    WHEN paymentStatus = 'Đã hoàn tiền' THEN 'Đơn hoàn tiền'
                    WHEN deliveryStatus IN ('Đang tiến hành vận chuyển', 'Đang chuẩn bị hàng') THEN 'Đơn đang xử lý'
                    WHEN paymentStatus LIKE 'Chờ%' OR deliveryStatus LIKE 'Chờ%' THEN 'Đơn chờ xử lý'
                    ELSE 'Khác'
                END as nhom,
                COUNT(*) as soLuong,
                SUM(totalAmount) as tongTien
              FROM `order`
              GROUP BY nhom
              ORDER BY soLuong DESC";

$groupResult = mysqli_query($conn, $groupQuery);

printf("%-25s | %-10s | %-15s | %-10s\n", "NHÓM", "SỐ ĐƠN", "TỔNG TIỀN", "TỶ LỆ");
echo str_repeat("─", 70) . "\n";

while ($row = mysqli_fetch_assoc($groupResult)) {
    $percent = round(($row['soLuong'] / $totalOrders) * 100, 1);
    printf("%-25s | %10s | %15s | %8s%%\n",
        $row['nhom'],
        $row['soLuong'],
        number_format($row['tongTien'], 0, ',', '.') . ' đ',
        $percent
    );
}

echo str_repeat("═", 70) . "\n\n";

echo "=== HOÀN TẤT ===\n\n";

// Hàm đánh giá trạng thái
function evaluateOrderStatus($payment, $delivery) {
    // Bình thường
    if ($payment == 'Đã thanh toán' && $delivery == 'Hoàn thành') return '✅ OK';
    if ($payment == 'Đã hủy' && $delivery == 'Đã hủy') return '✅ OK';
    if ($payment == 'Chờ thanh toán' && $delivery == 'Chờ xác nhận') return '✅ OK';
    if ($payment == 'Chờ thanh toán (COD)' && $delivery == 'Chờ xác nhận') return '✅ OK';
    if ($payment == 'Đã thanh toán' && $delivery == 'Đang tiến hành vận chuyển') return '✅ OK';
    if ($payment == 'Đã thanh toán' && $delivery == 'Đang chuẩn bị hàng') return '✅ OK';
    
    // Hoàn tiền
    if ($payment == 'Đã hoàn tiền' && $delivery == 'Đã hủy') return '✅ OK';
    if ($payment == 'Đã hoàn tiền' && $delivery == 'Đã hoàn tiền') return '✅ OK';
    if ($payment == 'Đã hoàn tiền' && $delivery == 'Hoàn thành') return '⚠️ CHECK';
    
    // Bất thường
    if ($payment == 'Đã hoàn tiền' && ($delivery == '(NULL)' || $delivery == '')) return '❌ SAI';
    
    return '⚠️ REVIEW';
}

// Hàm kiểm tra vấn đề
function checkIssue($payment, $delivery) {
    // Đã hoàn tiền nhưng delivery NULL
    if ($payment == 'Đã hoàn tiền' && ($delivery == '(NULL)' || $delivery == '')) {
        return [
            'type' => 'Payment đã hoàn tiền nhưng Delivery NULL',
            'recommendation' => 'Nên cập nhật deliveryStatus = "Đã hủy"'
        ];
    }
    
    // Đã hoàn tiền nhưng vẫn Hoàn thành
    if ($payment == 'Đã hoàn tiền' && $delivery == 'Hoàn thành') {
        return [
            'type' => 'Đã hoàn tiền sau khi giao hàng thành công',
            'recommendation' => 'Có thể là trường hợp trả hàng, cần kiểm tra'
        ];
    }
    
    // NULL values
    if ($payment == '(NULL)' || $payment == '') {
        return [
            'type' => 'Payment Status bị NULL',
            'recommendation' => 'Cần cập nhật trạng thái thanh toán'
        ];
    }
    
    return null;
}
