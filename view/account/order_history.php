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

// Lấy filter từ URL (mặc định: all)
$filter = $_GET['filter'] ?? 'all';

// Lấy danh sách đơn hàng
$orderHistoryController = new OrderHistoryController();
$allOrders = $orderHistoryController->getCustomerOrders($customerID);

// Lọc đơn hàng theo tab
$orders = [];
switch ($filter) {
    case 'pending':
        // Chờ thanh toán
        $orders = array_filter($allOrders, function($order) {
            return $order['paymentStatus'] === 'Chờ thanh toán';
        });
        break;
    case 'paid':
        // Đã thanh toán
        $orders = array_filter($allOrders, function($order) {
            return $order['paymentStatus'] === 'Đã thanh toán';
        });
        break;
    case 'cancelled':
        // Đã hủy
        $orders = array_filter($allOrders, function($order) {
            return $order['paymentStatus'] === 'Đã hủy';
        });
        break;
    case 'all':
    default:
        // Tất cả
        $orders = $allOrders;
        break;
}

// Đếm số lượng đơn theo từng trạng thái
$countAll = count($allOrders);
$countPending = count(array_filter($allOrders, function($o) { return $o['paymentStatus'] === 'Chờ thanh toán'; }));
$countPaid = count(array_filter($allOrders, function($o) { return $o['paymentStatus'] === 'Đã thanh toán'; }));
$countCancelled = count(array_filter($allOrders, function($o) { return $o['paymentStatus'] === 'Đã hủy'; }));
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

        <!-- Tabs Filter -->
        <div class="bg-white rounded-lg shadow-md mb-6 overflow-hidden">
            <div class="flex flex-wrap border-b">
                <!-- Tab: Tất cả -->
                <a href="?filter=all" 
                   class="flex-1 text-center px-6 py-4 font-semibold transition <?= $filter === 'all' ? 'bg-indigo-600 text-white border-b-4 border-indigo-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <i class="fas fa-list mr-2"></i>Tất cả
                    <?php if ($countAll > 0): ?>
                    <span class="ml-2 px-2 py-1 rounded-full text-xs <?= $filter === 'all' ? 'bg-white text-indigo-600' : 'bg-gray-200 text-gray-700' ?>">
                        <?= $countAll ?>
                    </span>
                    <?php endif; ?>
                </a>

                <!-- Tab: Chờ thanh toán -->
                <a href="?filter=pending" 
                   class="flex-1 text-center px-6 py-4 font-semibold transition <?= $filter === 'pending' ? 'bg-yellow-500 text-white border-b-4 border-yellow-500' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <i class="fas fa-clock mr-2"></i>Chờ thanh toán
                    <?php if ($countPending > 0): ?>
                    <span class="ml-2 px-2 py-1 rounded-full text-xs <?= $filter === 'pending' ? 'bg-white text-yellow-600' : 'bg-gray-200 text-gray-700' ?>">
                        <?= $countPending ?>
                    </span>
                    <?php endif; ?>
                </a>

                <!-- Tab: Đã thanh toán -->
                <a href="?filter=paid" 
                   class="flex-1 text-center px-6 py-4 font-semibold transition <?= $filter === 'paid' ? 'bg-green-600 text-white border-b-4 border-green-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <i class="fas fa-check-circle mr-2"></i>Đã thanh toán
                    <?php if ($countPaid > 0): ?>
                    <span class="ml-2 px-2 py-1 rounded-full text-xs <?= $filter === 'paid' ? 'bg-white text-green-600' : 'bg-gray-200 text-gray-700' ?>">
                        <?= $countPaid ?>
                    </span>
                    <?php endif; ?>
                </a>

                <!-- Tab: Đã hủy -->
                <a href="?filter=cancelled" 
                   class="flex-1 text-center px-6 py-4 font-semibold transition <?= $filter === 'cancelled' ? 'bg-red-600 text-white border-b-4 border-red-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <i class="fas fa-times-circle mr-2"></i>Đã hủy
                    <?php if ($countCancelled > 0): ?>
                    <span class="ml-2 px-2 py-1 rounded-full text-xs <?= $filter === 'cancelled' ? 'bg-white text-red-600' : 'bg-gray-200 text-gray-700' ?>">
                        <?= $countCancelled ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <?php if (empty($orders)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <div class="text-6xl mb-4">📭</div>
            <?php if ($filter === 'all'): ?>
                <h2 class="text-2xl font-semibold text-gray-700 mb-2">Chưa có đơn hàng nào</h2>
                <p class="text-gray-500 mb-6">Bạn chưa đặt hàng. Hãy khám phá các sản phẩm của chúng tôi!</p>
                <a href="/GODIFA/view/product/list.php" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-shopping-bag mr-2"></i> Mua Sắm Ngay
                </a>
            <?php else: ?>
                <h2 class="text-2xl font-semibold text-gray-700 mb-2">Không có đơn hàng nào</h2>
                <p class="text-gray-500 mb-6">
                    <?php if ($filter === 'pending'): ?>
                        Bạn không có đơn hàng nào đang chờ thanh toán.
                    <?php elseif ($filter === 'paid'): ?>
                        Bạn chưa có đơn hàng nào đã thanh toán.
                    <?php elseif ($filter === 'cancelled'): ?>
                        Bạn không có đơn hàng nào bị hủy.
                    <?php endif; ?>
                </p>
                <a href="?filter=all" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-list mr-2"></i> Xem Tất Cả Đơn Hàng
                </a>
            <?php endif; ?>
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
                                <i class="far fa-calendar-plus mr-1"></i>
                                <strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['orderDate'])) ?>
                            </p>
                            <?php if (!empty($order['paymentDate'])): ?>
                            <p class="text-sm text-green-600 mt-1">
                                <i class="far fa-check-circle mr-1"></i>
                                <strong>Đã thanh toán:</strong> <?= date('d/m/Y H:i', strtotime($order['paymentDate'])) ?>
                            </p>
                            <?php endif; ?>
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
