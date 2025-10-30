<?php
// Middleware: Chỉ cho phép khách hàng truy cập
require_once __DIR__ . '/../../middleware/customer_only.php';

require 'config.php';

// Tính tổng tiền
$tongTien = 0;
foreach ($_SESSION['cart'] as $sp) {
    $tongTien += $sp['gia'] * $sp['soluong'];
}

// Luôn tạo mã đơn hàng mới mỗi lần checkout
$orderCode = 'DH' . time() . rand(100, 999); // VD: DH1716745297123

// Thêm đơn hàng vào DB
$stmt = $conn->prepare("INSERT INTO orders (order_code, total, status, created_at) VALUES (?, ?, 'pending', NOW())");
$stmt->bind_param("si", $orderCode, $tongTien);
$stmt->execute();

// Lưu orderCode vào session nếu muốn dùng sau ở thankyou.php
$_SESSION['order_code'] = $orderCode;

// Thông tin SePay QR
$account = '105875539922';
$bank = 'VietinBank';
$description = 'SEVQR TKP155 ' . $orderCode;
$qrLink = "https://qr.sepay.vn/img?acc=$account&bank=$bank&amount=$tongTien&des=" . urlencode($description);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thanh toán</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
<!-- HEADER -->
<header class="bg-white shadow-md sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-blue-600">BICOIF</h1>
    <nav class="space-x-6 text-sm font-semibold">
      <a href="../index.php" class="hover:text-blue-600">Trang chủ</a>
      <a href="product.php" class="hover:text-blue-600">Sản phẩm</a>
      <a href="/GODIFA/view/cart/viewcart.php" class="hover:text-blue-600">Giỏ hàng</a>
    </nav>
  </div>
</header>

<!-- NỘI DUNG CHÍNH -->
<div class="max-w-xl mx-auto mt-10 bg-white shadow-lg rounded-xl p-6">
  <h2 class="text-2xl font-bold text-blue-600 mb-4">🔐 Thanh toán đơn hàng</h2>
  <p class="mb-2 text-gray-700">Mã đơn hàng: <span class="font-semibold"><?= $orderCode ?></span></p>
  <p class="mb-2 text-gray-700">Số tiền: <span class="text-green-600 font-bold"><?= number_format($tongTien, 0, ',', '.') ?>₫</span></p>

  <div class="my-6 text-center">
    <img src="<?= $qrLink ?>" alt="Mã QR thanh toán" class="mx-auto w-60 border rounded" />
    <p class="mt-2 text-gray-600 text-sm">Quét mã để thanh toán bằng SePay</p>
  </div>

  <div class="text-center mt-6">
    <a href="thankyou.php?order_code=<?= $orderCode ?>" class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
      Tôi đã chuyển khoản
    </a>
  </div>
</div>

<!-- FOOTER -->
<footer class="bg-gray-800 text-white py-10 mt-16">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8 text-sm">
    <div>
      <h4 class="text-lg font-semibold mb-4 text-blue-400">Về BICOIF</h4>
      <p class="text-gray-300">BICOIF là nền tảng mua sắm trực tuyến chuyên cung cấp sản phẩm chất lượng cao với giá cả hợp lý.</p>
    </div>
    <div>
      <h4 class="text-lg font-semibold mb-4 text-blue-400">Liên hệ</h4>
      <ul class="space-y-2 text-gray-300">
        <li>📍 123 Đường ABC, TP.HCM</li>
        <li>📞 0909 999 999</li>
        <li>✉️ support@bicoif.vn</li>
      </ul>
    </div>
    <div>
      <h4 class="text-lg font-semibold mb-4 text-blue-400">Kết nối</h4>
      <div class="flex space-x-4">
        <a href="#" class="hover:text-blue-400 transition">🌐 Facebook</a>
        <a href="#" class="hover:text-blue-400 transition">📸 Instagram</a>
        <a href="#" class="hover:text-blue-400 transition">🎥 YouTube</a>
      </div>
    </div>
  </div>
  <div class="border-t border-gray-700 mt-8 pt-4 text-center text-gray-400 text-xs">
    © <?= date('Y') ?> BICOIF. Mọi quyền được bảo lưu.
  </div>
</footer>

</body>
</html>
