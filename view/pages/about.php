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

<section class="bg-white py-16">
  <div class="max-w-5xl mx-auto px-4">
    <h1 class="text-4xl font-bold text-center text-blue-700 mb-10"> Giới thiệu về BICOIF</h1>

    <div class="space-y-8 text-gray-700 text-lg leading-relaxed">
      <p>
        <strong>BICOIF</strong> là hệ thống thương mại điện tử chuyên cung cấp các dòng <strong>xe điện hiện đại, chất lượng cao</strong> dành cho mọi nhu cầu di chuyển trong thành phố.
      </p>

      <p>
        Với sứ mệnh <strong>“Mang công nghệ xanh đến gần hơn với cộng đồng”</strong>, BICOIF không chỉ bán sản phẩm, chúng tôi còn mang lại trải nghiệm mua sắm trực tuyến an toàn, tiện lợi và nhanh chóng.
      </p>

      <p>
        Tại BICOIF, bạn sẽ tìm thấy đa dạng mẫu mã xe điện từ các thương hiệu uy tín, từ xe điện học sinh, xe điện thể thao đến các dòng xe cao cấp, phù hợp với mọi lứa tuổi và mục đích sử dụng.
      </p>

      <p>
        Hệ thống được xây dựng với giao diện thân thiện, hỗ trợ <strong>đặt hàng trực tuyến, thanh toán nhanh chóng</strong> và <strong>vận chuyển toàn quốc</strong>. Ngoài ra, đội ngũ hỗ trợ khách hàng của chúng tôi luôn sẵn sàng 24/7 để giải đáp mọi thắc mắc của bạn.
      </p>

      <p class="bg-blue-50 p-4 rounded-xl border-l-4 border-blue-600 shadow">
        👉 Chọn BICOIF – Chọn phong cách sống hiện đại, an toàn và thân thiện với môi trường.
      </p>
    </div>
  </div>
</section>

<section class="bg-blue-600 text-white py-12 mt-12">
  <div class="max-w-4xl mx-auto px-4 text-center">
    <h2 class="text-2xl font-semibold mb-4">🌟 Cam kết từ BICOIF</h2>
    <p class="text-lg">Chất lượng – Giá tốt – Hỗ trợ tận tâm – Giao hàng toàn quốc</p>
  </div>
</section>

<?php include '../layout/footer.php'; ?>

