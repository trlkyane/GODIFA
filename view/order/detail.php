<?php
/**
 * Chi tiết đơn hàng
 * File: view/order/detail.php
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

require_once __DIR__ . '/../../model/database.php';

$orderID = $_GET['id'] ?? 0;
$customerID = $_SESSION['customer_id'];

if (!$orderID) {
    header('Location: /GODIFA/view/account/order_history.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->connect();

// Lấy thông tin đơn hàng (chỉ của customer này)
$stmt = $conn->prepare("
    SELECT 
        o.orderID,
        o.orderDate,
        o.totalAmount,
        o.shippingFee,
        o.paymentStatus,
        o.paymentMethod,
        o.deliveryStatus,
        o.transactionCode,
        od.recipientName,
        od.recipientPhone,
        od.recipientEmail,
        od.address,
        od.ward,
        od.district,
        od.city,
        od.deliveryNotes
    FROM `order` o
    LEFT JOIN order_delivery od ON o.orderID = od.orderID
    WHERE o.orderID = ? AND o.customerID = ?
");
$stmt->bind_param("ii", $orderID, $customerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: /GODIFA/view/account/order_history.php');
    exit;
}

// Lấy chi tiết sản phẩm
$stmt = $conn->prepare("
    SELECT 
        od.productID,
        od.quantity,
        od.price,
        p.productName,
        p.image
    FROM order_details od
    JOIN product p ON od.productID = p.productID
    WHERE od.orderID = ?
");
$stmt->bind_param("i", $orderID);
$stmt->execute();
$orderDetails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lịch sử vận chuyển đã bị xóa (simplified)
$shippingHistory = [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Đơn Hàng #<?= $orderID ?> - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <?php include __DIR__ . '/../layout/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="/GODIFA/view/account/order_history.php" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại lịch sử đơn hàng
            </a>
        </div>

        <!-- Order Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-receipt text-indigo-600 mr-2"></i>
                        Đơn hàng #<?= $orderID ?>
                    </h1>
                    <p class="text-gray-600 mt-2">
                        <i class="far fa-clock mr-1"></i>
                        Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['orderDate'])) ?>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-indigo-600">
                        <?= number_format($order['totalAmount'], 0, ',', '.') ?>₫
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Thanh toán -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-gray-700 mb-2">💳 Thanh toán</h3>
                    <?php
                    $paymentColors = [
                        'Đã thanh toán' => 'bg-green-100 text-green-800',
                        'Chờ thanh toán' => 'bg-yellow-100 text-yellow-800',
                        'Đã hủy' => 'bg-red-100 text-red-800'
                    ];
                    $paymentColor = $paymentColors[$order['paymentStatus']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold <?= $paymentColor ?> mb-2">
                        <?= $order['paymentStatus'] ?>
                    </span>
                    <p class="text-sm text-gray-600">Phương thức: <?= $order['paymentMethod'] ?></p>
                    <?php if ($order['transactionCode']): ?>
                    <p class="text-sm text-gray-600 font-mono mt-1">Mã GD: <?= $order['transactionCode'] ?></p>
                    <?php endif; ?>
                </div>

                <!-- Giao hàng -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-gray-700 mb-2">🚚 Giao hàng</h3>
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
                    <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold <?= $deliveryColor ?> mb-2">
                        <?= $order['deliveryStatus'] ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Sản phẩm -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-box mr-2"></i>Chi tiết sản phẩm
                    </h2>
                    <div class="space-y-3">
                        <?php foreach ($orderDetails as $item): ?>
                        <div class="flex items-center bg-gray-50 p-4 rounded-lg">
                            <img src="/GODIFA/image/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['productName']) ?>"
                                 class="w-20 h-20 object-cover rounded-lg mr-4">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800"><?= htmlspecialchars($item['productName']) ?></p>
                                <p class="text-gray-600 text-sm mt-1">
                                    <?= number_format($item['price'], 0, ',', '.') ?>₫ × <?= $item['quantity'] ?>
                                </p>
                            </div>
                            <p class="font-bold text-indigo-600 text-lg">
                                <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>₫
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tổng tiền -->
                    <div class="border-t mt-4 pt-4 space-y-2">
                        <div class="flex justify-between text-gray-600">
                            <span>Tạm tính:</span>
                            <span><?= number_format($order['totalAmount'] - ($order['shippingFee'] ?? 0), 0, ',', '.') ?>₫</span>
                        </div>
                        <?php if ($order['shippingFee']): ?>
                        <div class="flex justify-between text-gray-600">
                            <span>Phí vận chuyển:</span>
                            <span><?= number_format($order['shippingFee'], 0, ',', '.') ?>₫</span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-xl font-bold text-gray-800 pt-2 border-t">
                            <span>Tổng cộng:</span>
                            <span class="text-indigo-600"><?= number_format($order['totalAmount'], 0, ',', '.') ?>₫</span>
                        </div>
                    </div>
                </div>

                <!-- Lịch sử vận chuyển -->
                <?php if (!empty($shippingHistory)): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-route mr-2"></i>Lịch sử vận chuyển
                    </h2>
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        <div class="space-y-4">
                            <?php foreach ($shippingHistory as $index => $history): ?>
                            <div class="relative pl-10">
                                <div class="absolute left-0 w-8 h-8 rounded-full flex items-center justify-center <?= $index === 0 ? 'bg-green-500' : 'bg-gray-300' ?>">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($history['status']) ?></p>
                                    <?php if ($history['description']): ?>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($history['description']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($history['location']): ?>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($history['location']) ?>
                                    </p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-400 mt-2">
                                        <i class="far fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($history['createdAt'])) ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Thông tin người nhận -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-user mr-2"></i>Người nhận
                    </h2>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">Họ tên:</p>
                            <p class="font-semibold"><?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Số điện thoại:</p>
                            <p class="font-semibold"><?= htmlspecialchars($order['recipientPhone'] ?? '') ?></p>
                        </div>
                        <?php if (!empty($order['recipientEmail'])): ?>
                        <div>
                            <p class="text-sm text-gray-600">Email:</p>
                            <p class="font-semibold"><?= htmlspecialchars($order['recipientEmail']) ?></p>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-sm text-gray-600">Địa chỉ:</p>
                            <p class="font-semibold">
                                <?= htmlspecialchars($order['address'] ?? '') ?><?= !empty($order['address']) ? ',' : '' ?>
                                <?= htmlspecialchars($order['ward'] ?? '') ?><?= !empty($order['ward']) ? ',' : '' ?>
                                <?= htmlspecialchars($order['district'] ?? '') ?><?= !empty($order['district']) ? ',' : '' ?>
                                <?= htmlspecialchars($order['city'] ?? '') ?>
                            </p>
                        </div>
                        <?php if (!empty($order['deliveryNotes'])): ?>
                        <div>
                            <p class="text-sm text-gray-600">Ghi chú:</p>
                            <p class="text-gray-700"><?= htmlspecialchars($order['deliveryNotes']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-tools mr-2"></i>Thao tác
                    </h2>
                    <div class="space-y-2">
                        <?php if ($order['paymentStatus'] === 'Chờ thanh toán'): ?>
                        <a href="/GODIFA/view/cart/checkout_qr.php?orderID=<?= $orderID ?>" 
                           class="block w-full text-center bg-yellow-600 text-white px-4 py-3 rounded-lg hover:bg-yellow-700 transition font-semibold">
                            <i class="fas fa-credit-card mr-2"></i>Thanh Toán Ngay
                        </a>
                        <?php endif; ?>
                        
                        <button onclick="window.print()" 
                                class="block w-full text-center bg-gray-600 text-white px-4 py-3 rounded-lg hover:bg-gray-700 transition font-semibold">
                            <i class="fas fa-print mr-2"></i>In Đơn Hàng
                        </button>
                        
                        <a href="mailto:support@godifa.com?subject=Hỗ trợ đơn hàng #<?= $orderID ?>" 
                           class="block w-full text-center bg-indigo-600 text-white px-4 py-3 rounded-lg hover:bg-indigo-700 transition font-semibold">
                            <i class="fas fa-envelope mr-2"></i>Liên Hệ Hỗ Trợ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/../layout/footer.php'; ?>

</body>
</html>
