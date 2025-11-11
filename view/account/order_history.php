<?php
/**
 * Lịch sử đơn hàng của khách hàng
 * File: view/order_history.php
 */

// Kiểm tra đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: /GODIFA/view/auth/customer-login.php');
    exit;
}

require_once __DIR__ . '/../../controller/cOrderHistory.php';

$customerID = $_SESSION['customer_id'];

// Lấy danh sách đơn hàng
$orderHistoryController = new OrderHistoryController();
$orders = $orderHistoryController->getCustomerOrders($customerID);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đơn Hàng - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <?php include __DIR__ . '/../layout/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">📦 Lịch Sử Đơn Hàng</h1>
            <p class="text-gray-600 mt-2">Quản lý và theo dõi đơn hàng của bạn</p>
        </div>

        <?php if (empty($orders)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <div class="text-6xl mb-4">📭</div>
            <h2 class="text-2xl font-semibold text-gray-700 mb-2">Chưa có đơn hàng nào</h2>
            <p class="text-gray-500 mb-6">Bạn chưa đặt hàng. Hãy khám phá các sản phẩm của chúng tôi!</p>
            <a href="/GODIFA/view/product/list.php" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-shopping-bag mr-2"></i> Mua Sắm Ngay
            </a>
        </div>
        <?php else: ?>
        <!-- Orders List -->
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between mb-4">
                        <div class="flex-1 min-w-0 mr-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-receipt text-indigo-600 mr-2"></i>
                                Đơn hàng #<?= $order['orderID'] ?>
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="far fa-clock mr-1"></i>
                                <?= date('d/m/Y H:i', strtotime($order['orderDate'])) ?>
                            </p>
                            <?php if ($order['transactionCode']): ?>
                            <p class="text-sm text-gray-600 font-mono mt-1">
                                Mã GD: <?= $order['transactionCode'] ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-right">
                            <p class="text-2xl font-bold text-indigo-600">
                                <?= number_format($order['totalAmount'], 0, ',', '.') ?>₫
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <!-- Thanh toán -->
                        <div class="flex items-center">
                            <span class="text-gray-600 mr-2">💳 Thanh toán:</span>
                            <?php
                            $paymentColors = [
                                'Đã thanh toán' => 'bg-green-100 text-green-800',
                                'Chờ thanh toán' => 'bg-yellow-100 text-yellow-800',
                                'Đã hủy' => 'bg-red-100 text-red-800'
                            ];
                            $paymentColor = $paymentColors[$order['paymentStatus']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $paymentColor ?>">
                                <?= $order['paymentStatus'] ?>
                            </span>
                        </div>

                        <!-- Giao hàng -->
                        <div class="flex items-center">
                            <span class="text-gray-600 mr-2">🚚 Giao hàng:</span>
                            <?php
                            $deliveryColors = [
                                'Hoàn thành' => 'bg-green-100 text-green-800',
                                'Đang tiến hành vận chuyển' => 'bg-blue-100 text-blue-800',
                                'Chờ xác nhận' => 'bg-yellow-100 text-yellow-800',
                                'Đã hủy' => 'bg-red-100 text-red-800',
                                // Backward compatibility
                                'Đã giao' => 'bg-green-100 text-green-800',
                                'Đang giao' => 'bg-blue-100 text-blue-800',
                                'Đang xử lý' => 'bg-blue-100 text-blue-800',
                                'Chờ xử lý' => 'bg-yellow-100 text-yellow-800',
                            ];
                            $deliveryColor = $deliveryColors[$order['deliveryStatus']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $deliveryColor ?>">
                                <?= $order['deliveryStatus'] ?>
                            </span>
                        </div>

                        <!-- Người nhận -->
                        <div class="flex items-center">
                            <span class="text-gray-600 mr-2">👤</span>
                            <span class="text-sm">
                                <?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?>
                                <br>
                                <span class="text-gray-500"><?= htmlspecialchars($order['recipientPhone'] ?? '') ?></span>
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-200">
                        <a href="/GODIFA/view/order/detail.php?id=<?= $order['orderID'] ?>" 
                           class="flex-1 text-center bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm font-semibold">
                            <i class="fas fa-eye mr-2"></i>Xem Chi Tiết
                        </a>
                        
                        <?php if ($order['paymentStatus'] === 'Đã thanh toán' && $order['deliveryStatus'] === 'Đã giao'): ?>
                        <a href="/GODIFA/view/product/detail.php?id=<?= $order['orderID'] ?>" 
                           class="flex-1 text-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-semibold">
                            <i class="fas fa-star mr-2"></i>Đánh Giá
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($order['paymentStatus'] === 'Chờ thanh toán'): ?>
                        <a href="/GODIFA/view/cart/checkout_qr.php?orderID=<?= $order['orderID'] ?>" 
                           class="flex-1 text-center bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition text-sm font-semibold">
                            <i class="fas fa-credit-card mr-2"></i>Thanh Toán
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/../layout/footer.php'; ?>

</body>
</html>
