<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Trang chủ - MyShop</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800"> 
  <!-- Header -->
  <header class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-blue-600">BICOIF</h1>
      <nav class="space-x-6 text-sm font-semibold">
        <a href="../index.php" class="hover:text-blue-600">Trang chủ</a>
        <a href="product.php" class="hover:text-blue-600">Sản phẩm</a>
        <a href="/GODIFA/view/cart/viewcart.php" class="hover:text-blue-600">Giỏ hàng</a>
        <?php
            if(!isset($_SESSION["dn"])){
              echo '<a href="vLogin.php" class="hover:text-blue-600">Đăng nhập</a>';
            }else{
              if($_SESSION["dn"]==1){
                echo '<a href="admin.php" class="hover:text-blue-600">Admin </a>';
                echo '<a href="product.php?dangxuat" class="hover:text-blue-600">Đăng xuất</a>';
              }else{
                echo '<a href="product.php?dangxuat" class="hover:text-blue-600">Đăng xuất</a>';
              }
            }

            if(isset($_REQUEST["dangxuat"])){
              include_once("logout.php");
            }
        ?>
      </nav>
    </div>
  </header>
  
<section class="bg-blue-50 py-16">
  <div class="max-w-6xl mx-auto px-4">
    <h2 class="text-3xl font-bold text-center text-blue-700 mb-10">🎁 Khuyến mãi & Ưu đãi đặc biệt</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Ưu đãi 1 -->
      <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition-all">
        <h3 class="text-xl font-semibold mb-2 text-blue-600">Giảm 10% cho đơn hàng đầu tiên</h3>
        <p class="text-gray-600 mb-4">Áp dụng cho khách hàng mới mua xe điện tại BICOIF.</p>
        <span class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">HSD: 31/12/2025</span>
      </div>

      <!-- Ưu đãi 2 -->
      <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition-all">
        <h3 class="text-xl font-semibold mb-2 text-blue-600">Tặng mũ bảo hiểm cao cấp</h3>
        <p class="text-gray-600 mb-4">Khi mua bất kỳ xe điện nào trị giá từ 8 triệu trở lên.</p>
        <span class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">HSD: 15/06/2025</span>
      </div>

      <!-- Ưu đãi 3 -->
      <div class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition-all">
        <h3 class="text-xl font-semibold mb-2 text-blue-600">Miễn phí vận chuyển toàn quốc</h3>
        <p class="text-gray-600 mb-4">Cho đơn hàng từ 5 triệu đồng trở lên.</p>
        <span class="inline-block bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm">Áp dụng liên tục</span>
      </div>
    </div>
  </div>
</section>

<?php include '../layout/footer.php'; ?>

