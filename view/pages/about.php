<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Về chúng tôi</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800"> 
  <!-- Header -->
  <header class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-blue-600"><a href="../../index.php">GODIFA </a></h1>
      <nav class="space-x-6 text-sm font-semibold">
        <a href="../../index.php" class="hover:text-blue-600">Trang chủ</a>
        <a href="../product/list.php" class="hover:text-blue-600">Sản phẩm</a>
        <a href="../news/news.php" class="hover:text-blue-600">Tin tức</a>
        <a href="contact.php" class="hover:text-blue-600">Liên hệ</a>
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
    <h1 class="text-4xl font-bold text-center text-blue-700 mb-10"> 🌸 Giới thiệu về Godifa</h1>

    <div class="space-y-8 text-gray-700 text-lg leading-relaxed">
      <p>
        <strong>Godifa</strong> là cửa hàng chuyên cung cấp <strong>sản phẩm gia dụng, thực phẩm bổ sung và chăm sóc sức khỏe dành cho mẹ và bé.</strong> Với sứ mệnh “Mang đến sự tiện nghi và an tâm cho từng gia đình Việt”, Godifa luôn lựa chọn những sản phẩm chất lượng, an toàn và chính hãng từ các thương hiệu uy tín trong và ngoài nước.
      </p>

      <p>
        Ra đời từ tình yêu thương và sự thấu hiểu những vất vả của người mẹ hiện đại, Godifa mang sứ mệnh “Giúp mẹ an tâm chăm con, giúp gia đình tận hưởng cuộc sống tiện nghi và khỏe mạnh hơn mỗi ngày.” Chúng tôi tin rằng, để vun đắp hạnh phúc gia đình, người mẹ cần được hỗ trợ bởi những sản phẩm an toàn – tiện lợi – chất lượng cao, và đó cũng chính là tiêu chí mà Godifa luôn hướng đến trong từng lựa chọn.
      </p>

      <p>
        <strong>Tại Godifa</strong>, mẹ có thể dễ dàng tìm thấy mọi thứ cần thiết — từ đồ dùng cho bé sơ sinh, sữa và vitamin, cho đến đồ gia dụng thông minh giúp tiết kiệm thời gian chăm sóc gia đình. Chúng tôi cam kết mang đến trải nghiệm mua sắm thân thiện, nhanh chóng và đáng tin cậy, cùng đội ngũ tư vấn tận tâm luôn sẵn sàng hỗ trợ.
      </p>

      <p>
        Chúng tôi hợp tác trực tiếp với các thương hiệu uy tín trong và ngoài nước, đảm bảo mọi sản phẩm đều có nguồn gốc rõ ràng, chứng nhận an toàn, và được kiểm tra kỹ lưỡng trước khi đến tay khách hàng.
      </p>

      <p>
         Bên cạnh đó, đội ngũ tư vấn của Godifa luôn sẵn sàng lắng nghe và hỗ trợ khách hàng trong suốt quá trình mua sắm. Với phong cách phục vụ tận tâm, thân thiện và chuyên nghiệp, chúng tôi mong muốn mang lại trải nghiệm mua sắm thoải mái – tiện lợi – trọn niềm tin cho mỗi gia đình.
      </p>

      <p>
            <strong>💖 Godifa – Đồng hành cùng mẹ, chăm sóc yêu thương cho bé và cả gia đình. </strong>
          </p>
    </div>
  </div>
</section>

<section class="bg-blue-600 text-white py-12 mt-12">
  <div class="max-w-4xl mx-auto px-4 text-center">
    <h2 class="text-2xl font-semibold mb-4">🌟 Cam kết từ Godifa</h2>
    <p class="text-lg">Chất lượng – Giá tốt – Hỗ trợ tận tâm – Giao hàng toàn quốc</p>
  </div>
</section>

<?php include '../layout/footer.php'; ?>

