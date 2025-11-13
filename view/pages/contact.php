<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Liên hệ</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800"> 
<?php include '../layout/header.php'; ?>
<section class="py-16 bg-gray-50">
  <div class="max-w-3xl mx-auto px-4">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">📞 Liên hệ với chúng tôi</h2>
      <h3>
        <p>
          Mọi thắc mắc xin liên hệ với chúng tôi qua email: support@godifa.vn hoặc dùng chức năng chat trực tuyến với nhân viên chăm sóc khách hàng tại trang chủ ở dưới góc phải màn hình. Chúng tôi sẽ hỗ trợ giải đáp thắc mắc trong vòng 24-48 giờ. Xin cảm ơn quý khách!
        </p>
      </h3>
    <!-- <form class="bg-white p-8 rounded-2xl shadow space-y-6" action="send_contact.php" method="POST">
      <div>
        <label for="name" class="block font-semibold mb-2">Họ và tên</label>
        <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring focus:ring-blue-200" />
      </div>

      <div>
        <label for="email" class="block font-semibold mb-2">Email</label>
        <input type="email" id="email" name="email" required class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring focus:ring-blue-200" />
      </div>

      <div>
        <label for="message" class="block font-semibold mb-2">Nội dung</label>
        <textarea id="message" name="message" rows="5" required class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring focus:ring-blue-200"></textarea>
      </div>

      <button type="submit" class="bg-blue-600 text-white font-bold px-6 py-3 rounded-xl hover:bg-blue-700 transition">
        Gửi liên hệ
      </button>
    </form> -->

    <div class="mt-10 text-center text-gray-600">
      📍 Địa chỉ: 123 Đường ABC, TP.HCM<br>
      ☎️ Hotline: 1900 xxxx xxxx<br>
      ✉️ Email: support@godifa.vn
    </div>
  </div>
</section>
<?php include '../chat/index.php'; ?>
<?php include '../layout/footer.php'; ?>

