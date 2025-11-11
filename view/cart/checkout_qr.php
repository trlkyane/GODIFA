<?php
session_start();
require_once __DIR__ . '/../../model/database.php';

// Lấy orderID từ URL
$orderID = isset($_GET['orderID']) ? intval($_GET['orderID']) : 0;

if (!$orderID) {
    header('Location: checkout.php');
    exit;
}

// Lấy thông tin đơn hàng và thông tin giao hàng
$db = Database::getInstance();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT o.orderID, o.orderDate, o.totalAmount, o.paymentStatus, o.qrExpiredAt, o.transactionCode, o.qrUrl,
           d.recipientName, d.recipientPhone, d.fullAddress
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

// Kiểm tra đã thanh toán chưa
if ($order['paymentStatus'] === 'Đã thanh toán') {
    header('Location: ../payment/thankyou.php?orderID=' . $orderID);
    exit;
}

// Kiểm tra đã hủy chưa
if ($order['paymentStatus'] === 'Đã hủy') {
    echo "<script>alert('Đơn hàng này đã bị hủy!'); window.location.href = '/GODIFA';</script>";
    exit;
}

// Tính thời gian còn lại (seconds)
$now = time();
$expiredTime = strtotime($order['qrExpiredAt']);
$remainingSeconds = $expiredTime - $now;

// Nếu hết hạn thì hiển thị nút tạo QR mới
$isExpired = $remainingSeconds <= 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán QR - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="max-w-4xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/GODIFA" class="text-2xl font-bold text-indigo-600">🇯🇵 GODIFA</a>
            <nav class="space-x-4 text-sm">
                <a href="/GODIFA" class="hover:text-indigo-600">Trang chủ</a>
                <a href="/GODIFA/view/cart/viewcart.php" class="hover:text-indigo-600">Giỏ hàng</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-2xl mx-auto mt-10 mb-10">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- Header Card -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-6">
                <h1 class="text-2xl font-bold mb-2">
                    <i class="fas fa-qrcode"></i> Thanh toán QR Code
                </h1>
                <p class="opacity-90">Quét mã QR để hoàn tất thanh toán</p>
            </div>

            <!-- Order Info -->
            <div class="p-6 bg-gray-50 border-b">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Mã đơn hàng:</span>
                        <p class="font-semibold text-gray-900">#<?= $order['orderID'] ?></p>
                    </div>
                    <div>
                        <span class="text-gray-600">Mã giao dịch:</span>
                        <p class="font-semibold text-indigo-600"><?= $order['transactionCode'] ?></p>
                    </div>
                    <div>
                        <span class="text-gray-600">Tổng tiền:</span>
                        <p class="font-bold text-green-600 text-lg"><?= number_format($order['totalAmount'], 0, ',', '.') ?>₫</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Trạng thái:</span>
                        <p class="font-semibold text-yellow-600">
                            <i class="fas fa-clock"></i> Chờ thanh toán
                        </p>
                    </div>
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="p-8" id="qrSection">
                <?php if (!$isExpired): ?>
                    <!-- Countdown Timer -->
                    <div class="mb-6 text-center">
                        <div class="inline-flex items-center bg-yellow-50 border-2 border-yellow-400 rounded-full px-6 py-3">
                            <i class="fas fa-hourglass-half text-yellow-600 mr-3 text-xl"></i>
                            <div>
                                <p class="text-xs text-gray-600 mb-1">QR code hết hạn sau</p>
                                <p class="text-2xl font-bold text-yellow-600" id="countdown">
                                    <?= gmdate("i:s", $remainingSeconds) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code Image -->
                    <div class="text-center mb-6">
                        <div class="inline-block p-4 bg-white rounded-xl shadow-lg">
                            <img src="<?= htmlspecialchars($order['qrUrl']) ?>" 
                                 alt="QR Code" 
                                 class="w-80 h-80 mx-auto border-4 border-indigo-100 rounded-lg">
                        </div>
                        <p class="mt-4 text-gray-600 text-sm">
                            <i class="fas fa-mobile-alt"></i> Mở app ngân hàng và quét mã QR
                        </p>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-blue-50 rounded-lg p-4 mb-4">
                        <h3 class="font-semibold text-blue-900 mb-3 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i> Hướng dẫn thanh toán
                        </h3>
                        <ol class="text-sm text-blue-800 space-y-2 list-decimal list-inside">
                            <li>Mở ứng dụng Mobile Banking của bạn</li>
                            <li>Chọn chức năng <strong>Chuyển khoản QR</strong></li>
                            <li>Quét mã QR bên trên</li>
                            <li>Kiểm tra thông tin và xác nhận thanh toán</li>
                            <li>Đợi hệ thống xác nhận (thường 10-30 giây)</li>
                        </ol>
                    </div>

                    <!-- Status Indicator -->
                    <div class="text-center">
                        <div class="inline-flex items-center bg-indigo-50 text-indigo-700 px-4 py-2 rounded-full">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-700 mr-2"></div>
                            <span class="text-sm font-medium">Đang chờ xác nhận thanh toán...</span>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- QR Expired -->
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-4">
                            <i class="fas fa-clock text-red-600 text-4xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-red-600 mb-2">Mã QR đã hết hạn!</h3>
                        <p class="text-gray-600 mb-6">Vui lòng tạo mã QR mới để tiếp tục thanh toán</p>
                        
                        <button onclick="renewQR()" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-8 py-3 rounded-lg transition duration-300 shadow-lg">
                            <i class="fas fa-redo-alt mr-2"></i> Tạo mã QR mới
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer Note -->
            <div class="bg-gray-50 px-6 py-4 border-t text-center text-xs text-gray-500">
                <p><i class="fas fa-shield-alt"></i> Giao dịch được bảo mật bởi SePay</p>
            </div>

        </div>
    </div>

    <script>
        // Countdown Timer
        let remainingSeconds = <?= max(0, $remainingSeconds) ?>;
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            if (remainingSeconds <= 0) {
                // QR hết hạn
                location.reload(); // Reload để hiển thị nút "Tạo QR mới"
                return;
            }
            
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            countdownElement.textContent = 
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            remainingSeconds--;
        }
        
        // Update mỗi giây
        <?php if (!$isExpired): ?>
        setInterval(updateCountdown, 1000);
        <?php endif; ?>

        // Polling API để check payment status
        function checkPaymentStatus() {
            fetch('/GODIFA/api/check_payment_status.php?orderID=<?= $orderID ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'Đã thanh toán') {
                        // Chuyển sang trang thank you
                        window.location.href = '/GODIFA/view/payment/thankyou.php?orderID=<?= $orderID ?>';
                    }
                })
                .catch(error => console.error('Error checking payment:', error));
        }

        // Poll mỗi 3 giây
        <?php if (!$isExpired): ?>
        setInterval(checkPaymentStatus, 3000);
        <?php endif; ?>

        // Renew QR Code
        function renewQR() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang tạo...';
            
            fetch('/GODIFA/api/renew_qr.php?orderID=<?= $orderID ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Reload page với QR mới
                    } else {
                        alert('Lỗi: ' + data.message);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-redo-alt mr-2"></i> Tạo mã QR mới';
                    }
                })
                .catch(error => {
                    alert('Có lỗi xảy ra! Vui lòng thử lại.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-redo-alt mr-2"></i> Tạo mã QR mới';
                });
        }
    </script>

</body>
</html>
