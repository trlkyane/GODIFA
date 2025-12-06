<footer class="bg-black text-white pt-16 pb-8 mt-auto">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
    
    <div class="space-y-4">
      <h4 class="text-2xl font-bold tracking-wider font-[Playfair_Display]">GODIFA</h4>
      <p class="text-gray-400 text-sm leading-relaxed">
        Chuyên cung cấp mỹ phẩm và thực phẩm chức năng chính hãng từ Nhật Bản. Chất lượng là lời hứa của chúng tôi.
      </p>
      <div class="flex space-x-4 pt-2">
        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
          <i class="fab fa-facebook-f text-lg"></i>
        </a>
        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
          <i class="fab fa-instagram text-lg"></i>
        </a>
        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
          <i class="fab fa-tiktok text-lg"></i>
        </a>
        <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
          <i class="fab fa-youtube text-lg"></i>
        </a>
      </div>
    </div>

    <div>
      <h4 class="text-sm font-bold uppercase tracking-wider mb-6 text-white">Khám phá</h4>
      <ul class="space-y-3 text-sm text-gray-400">
        <li><a href="<?php echo BASE_URL; ?>view/product/list.php" class="hover:text-white transition">Sản phẩm mới</a></li>
        <li><a href="<?php echo BASE_URL; ?>view/product/list.php?type=best-seller" class="hover:text-white transition">Bán chạy nhất</a></li>
        <li><a href="<?php echo BASE_URL; ?>view/news/news.php" class="hover:text-white transition">Tin tức & Blog</a></li>
        <li><a href="<?php echo BASE_URL; ?>view/pages/about.php" class="hover:text-white transition">Về chúng tôi</a></li>
      </ul>
    </div>

    <div>
      <h4 class="text-sm font-bold uppercase tracking-wider mb-6 text-white">Hỗ trợ khách hàng</h4>
      <ul class="space-y-3 text-sm text-gray-400">
        <li><a href="<?php echo BASE_URL; ?>view/pages/contact.php" class="hover:text-white transition">Liên hệ</a></li>
        <li><a href="#" class="hover:text-white transition">Chính sách đổi trả</a></li>
        <li><a href="#" class="hover:text-white transition">Chính sách bảo mật</a></li>
        <li><a href="#" class="hover:text-white transition">Hướng dẫn mua hàng</a></li>
      </ul>
    </div>

    <div>
      <h4 class="text-sm font-bold uppercase tracking-wider mb-6 text-white">Thông tin liên hệ</h4>
      <ul class="space-y-3 text-sm text-gray-400">
        <li class="flex items-start">
          <i class="fas fa-map-marker-alt mt-1 mr-3 text-gray-500"></i>
          <span>Thành phố Hồ Chí Minh, Việt Nam</span>
        </li>
        <li class="flex items-center">
          <i class="fas fa-phone-alt mr-3 text-gray-500"></i>
          <a href="tel:0123456789" class="hover:text-white transition">0123 456 789</a>
        </li>
        <li class="flex items-center">
          <i class="fas fa-envelope mr-3 text-gray-500"></i>
          <a href="mailto:support@godifa.vn" class="hover:text-white transition">support@godifa.vn</a>
        </li>
        <li class="flex items-start">
          <i class="fas fa-clock mt-1 mr-3 text-gray-500"></i>
          <span>T2 - T7: 8:00 - 20:00<br>Chủ nhật: 9:00 - 18:00</span>
        </li>
      </ul>
    </div>
  </div>

  <!-- Payment Methods & Trust Badges -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
    <div class="border-t border-gray-900 pt-8">
      <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="text-center md:text-left">
          <h5 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Phương thức thanh toán</h5>
          <div class="flex items-center gap-3 flex-wrap justify-center md:justify-start">
            <div class="bg-white rounded px-3 py-2">
              <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
            </div>
            <div class="bg-white rounded px-3 py-2">
              <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
            </div>
            <div class="bg-white rounded px-3 py-2">
              <i class="fas fa-credit-card text-2xl text-gray-700"></i>
            </div>
            <div class="bg-white rounded px-3 py-2 text-xs font-bold text-blue-600">
              MOMO
            </div>
            <div class="bg-white rounded px-3 py-2 text-xs font-bold text-red-600">
              ZaloPay
            </div>
          </div>
        </div>
        <div class="text-center md:text-right">
          <h5 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Vận chuyển</h5>
          <div class="flex items-center gap-3 flex-wrap justify-center md:justify-end">
            <div class="bg-orange-500 rounded px-3 py-2 text-xs font-bold text-white">
              GHN
            </div>
            <div class="bg-red-600 rounded px-3 py-2 text-xs font-bold text-white">
              J&T
            </div>
            <div class="bg-blue-600 rounded px-3 py-2 text-xs font-bold text-white">
              GHTK
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Copyright -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 border-t border-gray-900">
    <div class="flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
      <p>&copy; <?php echo date("Y"); ?> GODIFA. All rights reserved. Designed with <i class="fas fa-heart text-red-500"></i> in Vietnam</p>
      <div class="flex space-x-6 mt-4 md:mt-0">
        <a href="#" class="hover:text-white transition">Điều khoản sử dụng</a>
        <a href="#" class="hover:text-white transition">Chính sách bảo mật</a>
      </div>
    </div>
  </div>
</footer>

</body>
</html>