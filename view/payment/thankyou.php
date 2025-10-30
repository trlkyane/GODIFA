<?php
session_start();

require 'config.php';
$orderCode = $_GET['order_code'] ?? '';
$status = '';

if ($orderCode) {
    $stmt = $conn->prepare("SELECT status FROM orders WHERE order_code = ?");
    $stmt->bind_param("s", $orderCode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $status = $row['status'];
        // 🟢 Nếu thanh toán thành công thì xóa giỏ hàng
        if ($status === 'paid')
            {unset($_SESSION['cart']);}
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Trạng thái đơn hàng</title>
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
<div class="max-w-xl mx-auto mt-10 bg-white shadow-lg rounded-xl p-6 text-center">
  <h2 class="text-2xl font-bold text-blue-600 mb-4">🎉 Trạng thái đơn hàng</h2>
  <p class="mb-4 text-gray-700">Mã đơn hàng: <span class="font-semibold"><?= htmlspecialchars($orderCode) ?></span></p>

  <?php if ($status === 'paid'): ?>
    <div class="text-green-600 font-semibold text-lg">✅ Thanh toán thành công!</div>
  <?php elseif ($status === 'pending'): ?>
    <div class="text-yellow-500 font-semibold text-lg">⏳ Đang chờ thanh toán...</div>
    <button onclick="location.reload()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
      Kiểm tra lại
    </button>
  <?php else: ?>
    <div class="text-red-600 font-semibold text-lg">❌ Không tìm thấy đơn hàng!</div>
  <?php endif; ?>
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
