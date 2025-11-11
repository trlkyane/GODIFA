<?php
session_start();
require_once __DIR__ . '/../../model/database.php';

// Lấy orderID từ URL
$orderID = isset($_GET['orderID']) ? intval($_GET['orderID']) : 0;

if (!$orderID) {
    header('Location: /GODIFA');
    exit;
}

// Lấy thông tin đơn hàng với thông tin giao hàng
$db = Database::getInstance();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT o.orderID, o.orderDate, o.totalAmount, o.paymentStatus, o.paymentMethod, 
           o.deliveryStatus, o.transactionCode,
           d.recipientName, d.recipientPhone, d.recipientEmail, d.fullAddress, d.deliveryNotes
    FROM `order` o
    LEFT JOIN order_delivery d ON o.orderID = d.orderID
    WHERE o.orderID = ?
");
$stmt->bind_param("i", $orderID);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Không tìm thấy đơn hàng!");
}

// Lấy chi tiết sản phẩm trong đơn hàng
$stmt = $conn->prepare("
    SELECT od.productID, od.quantity, od.price, p.productName, p.image
    FROM order_details od
    JOIN product p ON od.productID = p.productID
    WHERE od.orderID = ?
");
$stmt->bind_param("i", $orderID);
$stmt->execute();
$orderDetails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Xóa giỏ hàng nếu thanh toán thành công
if ($order['paymentStatus'] === 'Đã thanh toán' && isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
    unset($_SESSION['appliedVoucher']);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/GODIFA" class="text-2xl font-bold text-indigo-600">🇯🇵 GODIFA</a>
            <nav class="space-x-4 text-sm">
                <a href="/GODIFA" class="hover:text-indigo-600">Trang chủ</a>
                <a href="/GODIFA/view/cart/viewcart.php" class="hover:text-indigo-600">Giỏ hàng</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto mt-10 mb-10 px-4">
        
        <!-- Success Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full mb-4">
                    <i class="fas fa-check text-green-600 text-4xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">Đặt hàng thành công!</h1>
                <p class="opacity-90">Cảm ơn bạn đã mua sắm tại GODIFA</p>
            </div>

            <!-- Order Status -->
            <div class="p-6 border-b bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <p class="text-gray-600 text-sm mb-1">Trạng thái thanh toán</p>
                        <p class="font-semibold text-lg <?= $order['paymentStatus'] === 'Đã thanh toán' ? 'text-green-600' : 'text-yellow-600' ?>">
                            <i class="fas <?= $order['paymentStatus'] === 'Đã thanh toán' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                            <?= htmlspecialchars($order['paymentStatus']) ?>
                        </p>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <p class="text-gray-600 text-sm mb-1">Trạng thái giao hàng</p>
                        <p class="font-semibold text-lg text-blue-600">
                            <i class="fas fa-box"></i>
                            <?= htmlspecialchars($order['deliveryStatus']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Order Info -->
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-receipt mr-2 text-indigo-600"></i>
                    Thông tin đơn hàng
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-3">
                            <p class="text-gray-600 text-sm">Mã đơn hàng</p>
                            <p class="font-semibold">#<?= $order['orderID'] ?></p>
                        </div>
                        <?php if ($order['transactionCode']): ?>
                        <div class="mb-3">
                            <p class="text-gray-600 text-sm">Mã giao dịch</p>
                            <p class="font-semibold text-indigo-600"><?= htmlspecialchars($order['transactionCode']) ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <p class="text-gray-600 text-sm">Ngày đặt</p>
                            <p class="font-semibold"><?= date('d/m/Y H:i', strtotime($order['orderDate'])) ?></p>
                        </div>
                        <div class="mb-3">
                            <p class="text-gray-600 text-sm">Phương thức thanh toán</p>
                            <p class="font-semibold"><?= htmlspecialchars($order['paymentMethod']) ?></p>
                        </div>
                    </div>
                    <div>
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <p class="text-gray-600 text-sm mb-1">Tổng tiền</p>
                            <p class="font-bold text-3xl text-indigo-600">
                                <?= number_format($order['totalAmount'], 0, ',', '.') ?>₫
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Info -->
            <div class="p-6 bg-gray-50 border-t">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-shipping-fast mr-2 text-indigo-600"></i>
                    Thông tin giao hàng
                </h2>
                <div class="bg-white p-4 rounded-lg">
                    <p class="font-semibold mb-2"><?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?></p>
                    <p class="text-gray-700 mb-1">
                        <i class="fas fa-phone mr-2"></i>
                        <?= htmlspecialchars($order['recipientPhone'] ?? '') ?>
                    </p>
                    <?php if (!empty($order['recipientEmail'])): ?>
                    <p class="text-gray-700 mb-1">
                        <i class="fas fa-envelope mr-2"></i>
                        <?= htmlspecialchars($order['recipientEmail']) ?>
                    </p>
                    <?php endif; ?>
                    <p class="text-gray-700 mb-1">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <?= htmlspecialchars($order['fullAddress'] ?? 'Chưa cập nhật') ?>
                    </p>
                    <?php if (!empty($order['deliveryNotes'])): ?>
                    <p class="text-gray-600 text-sm mt-2">
                        <i class="fas fa-sticky-note mr-2"></i>
                        <em><?= htmlspecialchars($order['deliveryNotes']) ?></em>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Details -->
            <div class="p-6 border-t">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-box-open mr-2 text-indigo-600"></i>
                    Chi tiết sản phẩm
                </h2>
                <div class="space-y-3">
                    <?php foreach ($orderDetails as $item): ?>
                    <div class="flex items-center bg-gray-50 p-3 rounded-lg">
                        <img src="/GODIFA/image/<?= htmlspecialchars($item['image']) ?>" 
                             alt="<?= htmlspecialchars($item['productName']) ?>"
                             class="w-16 h-16 object-cover rounded-lg mr-4">
                        <div class="flex-1">
                            <p class="font-semibold"><?= htmlspecialchars($item['productName']) ?></p>
                            <p class="text-gray-600 text-sm">
                                <?= number_format($item['price'], 0, ',', '.') ?>₫ × <?= $item['quantity'] ?>
                            </p>
                        </div>
                        <p class="font-bold text-indigo-600">
                            <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>₫
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="p-6 bg-gray-50 border-t text-center">
                <a href="/GODIFA" 
                   class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-8 py-3 rounded-lg transition duration-300 shadow-lg">
                    <i class="fas fa-home mr-2"></i> Về trang chủ
                </a>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-10">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm text-gray-400">© <?= date('Y') ?> GODIFA. Mọi quyền được bảo lưu.</p>
        </div>
    </footer>

</body>
</html>
