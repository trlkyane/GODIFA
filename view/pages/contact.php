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

<main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  
  <!-- Page Header -->
  <div class="mb-8 text-center">
    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3 brand-font">Liên hệ</h1>
    <p class="text-sm text-gray-500">Chúng tôi luôn sẵn sàng hỗ trợ bạn</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- Contact Info -->
    <div class="bg-white rounded-sm shadow-sm p-6">
      <h2 class="text-xl font-bold text-gray-900 mb-4 brand-font">Thông tin liên hệ</h2>
      
      <div class="space-y-4">
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 bg-black text-white rounded-sm flex items-center justify-center text-sm flex-shrink-0">📍</div>
          <div>
            <div class="font-semibold text-sm text-gray-900">Địa chỉ</div>
            <div class="text-sm text-gray-600">4 Nguyễn Văn Bảo, Phường 4, Gò Vấp, TP.HCM</div>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <div class="w-8 h-8 bg-black text-white rounded-sm flex items-center justify-center text-sm flex-shrink-0">📞</div>
          <div>
            <div class="font-semibold text-sm text-gray-900">Hotline</div>
            <div class="text-sm text-gray-600">1900 xxxx xxxx</div>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <div class="w-8 h-8 bg-black text-white rounded-sm flex items-center justify-center text-sm flex-shrink-0">✉️</div>
          <div>
            <div class="font-semibold text-sm text-gray-900">Email</div>
            <div class="text-sm text-gray-600">support@godifa.vn</div>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <div class="w-8 h-8 bg-black text-white rounded-sm flex items-center justify-center text-sm flex-shrink-0">⏰</div>
          <div>
            <div class="font-semibold text-sm text-gray-900">Giờ làm việc</div>
            <div class="text-sm text-gray-600">8:00 - 22:00 (Tất cả các ngày)</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Support Message -->
    <div class="bg-white rounded-sm shadow-sm p-6">
      <h2 class="text-xl font-bold text-gray-900 mb-4 brand-font">Hỗ trợ trực tuyến</h2>
      
      <p class="text-sm text-gray-600 leading-relaxed mb-6">
        Mọi thắc mắc xin liên hệ với chúng tôi qua email: <strong>support@godifa.vn</strong> hoặc dùng chức năng chat trực tuyến với nhân viên chăm sóc khách hàng ở góc dưới bên phải màn hình.
      </p>

      <p class="text-sm text-gray-600 leading-relaxed mb-6">
        Chúng tôi sẽ hỗ trợ giải đáp thắc mắc trong vòng <strong>24-48 giờ</strong>.
      </p>

      <div class="bg-gray-50 rounded-sm p-4 border-l-4 border-black">
        <p class="text-xs text-gray-700 leading-relaxed">
          💬 <strong>Chat ngay</strong> với đội ngũ tư vấn chuyên nghiệp của chúng tôi để được hỗ trợ nhanh chóng!
        </p>
      </div>
    </div>

  </div>

  <!-- CTA Section -->
  <div class="bg-black text-white rounded-sm p-6 mt-6 text-center">
    <h3 class="text-lg font-bold mb-2 brand-font">Cảm ơn bạn đã tin tưởng Godifa!</h3>
    <p class="text-sm text-gray-300">Chúng tôi luôn sẵn sàng lắng nghe và phục vụ bạn tốt nhất</p>
  </div>

</main>

<?php include '../chat/index.php'; ?>
<?php include '../layout/footer.php'; ?>

</body>
</html>

