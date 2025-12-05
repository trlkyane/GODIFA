<?php
/**
 * Test trang hủy đơn - Kiểm tra nút hủy có hiển thị không
 */

// Kiểm tra đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    die('Vui lòng đăng nhập trước! <a href="view/auth/customer-login.php">Đăng nhập</a>');
}

require_once __DIR__ . '/model/database.php';

$customerID = $_SESSION['customer_id'];
$db = Database::getInstance();
$conn = $db->connect();

// Lấy 5 đơn hàng gần nhất
$sql = "SELECT 
            o.orderID,
            o.orderDate,
            o.totalAmount,
            o.paymentStatus,
            o.deliveryStatus,
            o.paymentMethod,
            c.customerName
        FROM `order` o
        JOIN customer c ON o.customerID = c.customerID
        WHERE o.customerID = ?
        ORDER BY o.orderDate DESC
        LIMIT 5";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $customerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Nút Hủy Đơn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4">🧪 Test Chức Năng Hủy Đơn</h1>
            <p class="text-gray-600">Khách hàng: <strong><?= $_SESSION['customer_name'] ?? 'N/A' ?></strong> (ID: <?= $customerID ?>)</p>
            <p class="text-sm text-gray-500 mt-2">Trang này dùng để test logic hiển thị nút hủy đơn</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-yellow-700">⚠️ Bạn chưa có đơn hàng nào!</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                // Logic kiểm tra có thể hủy không
                $canCancel = false;
                $cancelMessage = '';
                $reason = '';
                
                if ($order['paymentStatus'] === 'Đã hủy') {
                    $canCancel = false;
                    $reason = '❌ Đơn đã bị hủy rồi';
                } elseif ($order['deliveryStatus'] === 'Hoàn thành' || $order['deliveryStatus'] === 'Đã giao') {
                    $canCancel = false;
                    $reason = '✅ Đơn đã hoàn thành/giao';
                } elseif (in_array($order['deliveryStatus'], ['Đang giao', 'Đang tiến hành vận chuyển'])) {
                    $canCancel = false;
                    $cancelMessage = 'Đơn hàng đang giao. Vui lòng liên hệ CSKH!';
                    $reason = '🚚 Đơn đang vận chuyển';
                } else {
                    $canCancel = true;
                    $reason = '✅ Cho phép hủy';
                }
                ?>
                
                <div class="bg-white rounded-lg shadow-md p-6 mb-4 <?= $canCancel ? 'border-l-4 border-green-500' : 'border-l-4 border-gray-300' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <h3 class="font-bold text-lg text-gray-800">Đơn hàng #<?= $order['orderID'] ?></h3>
                            <p class="text-sm text-gray-600">Ngày: <?= date('d/m/Y H:i', strtotime($order['orderDate'])) ?></p>
                            <p class="text-sm text-gray-600">Tổng: <strong class="text-red-600"><?= number_format($order['totalAmount'], 0, ',', '.') ?>₫</strong></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm mb-1">
                                <span class="font-semibold">Thanh toán:</span>
                                <span class="px-2 py-1 rounded text-xs <?= $order['paymentStatus'] === 'Đã thanh toán' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= $order['paymentStatus'] ?>
                                </span>
                            </p>
                            <p class="text-sm mb-1">
                                <span class="font-semibold">Giao hàng:</span>
                                <span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
                                    <?= $order['deliveryStatus'] ?>
                                </span>
                            </p>
                            <p class="text-sm">
                                <span class="font-semibold">Phương thức:</span>
                                <span class="px-2 py-1 rounded text-xs bg-purple-100 text-purple-800">
                                    <?= $order['paymentMethod'] ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-3 rounded mb-3">
                        <p class="text-sm"><strong>🔍 Kiểm tra logic:</strong> <?= $reason ?></p>
                        <?php if ($cancelMessage): ?>
                            <p class="text-xs text-orange-600 mt-1">💬 Message: <?= $cancelMessage ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="flex gap-2">
                        <button class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                            <i class="fas fa-eye mr-2"></i>Xem Chi Tiết
                        </button>
                        
                        <?php if ($canCancel): ?>
                            <button onclick="alert('Mở modal hủy đơn #<?= $order['orderID'] ?>')" 
                                    class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                                <i class="fas fa-times-circle mr-2"></i>Hủy Đơn
                            </button>
                        <?php else: ?>
                            <button disabled 
                                    class="flex-1 bg-gray-400 text-white px-4 py-2 rounded-lg cursor-not-allowed">
                                <i class="fas fa-ban mr-2"></i>Không thể hủy
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
            <h3 class="font-bold text-blue-800 mb-2">📋 Điều kiện cho phép hủy đơn:</h3>
            <ul class="text-sm text-blue-700 space-y-1 ml-5 list-disc">
                <li>✅ Đơn COD chưa thanh toán: Cho phép hủy</li>
                <li>✅ Đơn QR chưa thanh toán: Cho phép hủy</li>
                <li>✅ Đơn QR đã thanh toán nhưng chưa giao: Cho phép hủy (cần ảnh chứng minh)</li>
                <li>❌ Đơn đang vận chuyển: KHÔNG cho phép hủy (liên hệ CSKH)</li>
                <li>❌ Đơn đã giao/hoàn thành: KHÔNG cho phép hủy</li>
                <li>❌ Đơn đã hủy: KHÔNG hiển thị nút</li>
            </ul>
        </div>

        <div class="text-center mt-6">
            <a href="view/account/order_history.php" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại trang lịch sử đơn hàng
            </a>
        </div>
    </div>
</body>
</html>
