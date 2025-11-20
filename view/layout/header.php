<?php
  if (session_status() === PHP_SESSION_NONE) {
      session_name('GODIFA_USER_SESSION');
      session_start();
  }
  // Đếm số lượng giỏ hàng
  $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($pageTitle) ? $pageTitle : 'GODIFA'; ?> - Minimalist Store</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  
  <style>
    body { font-family: 'Inter', sans-serif; }
    h1, h2, h3, .brand-font { font-family: 'Playfair Display', serif; }
    /* Ẩn thanh cuộn */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
  </style>
</head>
<body class="bg-white text-gray-900 antialiased">

  <header class="fixed top-0 left-0 right-0 w-full bg-white shadow-sm z-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-20">
        
        <div class="flex-shrink-0 flex items-center">
          <a href="/GODIFA/index.php" class="block">
            <img src="/GODIFA/image/logo.jpg" alt="GODIFA Logo" class="h-16 w-auto object-contain">
          </a>
        </div>

        <nav class="hidden md:flex space-x-8 text-sm font-medium text-gray-500">
          <a href="/GODIFA/index.php" class="hover:text-black transition-colors">Trang chủ</a>
          <a href="/GODIFA/view/product/list.php" class="hover:text-black transition-colors">Sản phẩm</a>
          <a href="/GODIFA/view/news/news.php" class="hover:text-black transition-colors">Tin tức</a>
          <a href="/GODIFA/view/pages/about.php" class="hover:text-black transition-colors">Về chúng tôi</a>
          <a href="/GODIFA/view/pages/contact.php" class="hover:text-black transition-colors">Liên hệ</a>
        </nav>

    <div class="flex items-center space-x-5">
      <div class="relative group">
        <a href="/GODIFA/view/product/list.php" class="text-gray-500 hover:text-black transition" aria-label="Tìm kiếm">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
          </svg>
        </a>
      </div>

            <a href="/GODIFA/view/cart/viewcart.php" class="relative text-gray-500 hover:text-black transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <?php if($cartCount > 0): ?>
                  <span class="absolute -top-1 -right-2 bg-black text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                    <?php echo $cartCount; ?>
                  </span>
                <?php endif; ?>
            </a>

            <div class="relative group">
                <button class="text-gray-500 hover:text-black focus:outline-none flex items-center h-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </button>
                
                <div class="absolute right-0 top-full mt-2 w-48 bg-white border border-gray-100 shadow-lg rounded-sm opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-xs text-gray-500">Xin chào,</p>
                            <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Khách hàng'); ?></p>
                        </div>
                        <a href="/GODIFA/view/account/profile.php" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-black">Tài khoản</a>
                        <a href="/GODIFA/view/account/order_history.php" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-black">Đơn mua</a>
                        <a href="/GODIFA/view/auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Đăng xuất</a>
                    <?php else: ?>
                        <a href="/GODIFA/view/auth/customer-login.php" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-black">Đăng nhập</a>
                        <a href="/GODIFA/view/auth/customer-register.php" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-black">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>

            <button onclick="toggleMobileMenu()" class="md:hidden text-gray-500 hover:text-black">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>
      </div>
    </div>

    

    <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-gray-100">
    <div class="px-4 pt-2 pb-6 space-y-1">
            <a href="/GODIFA/index.php" class="block px-3 py-2 text-base font-medium text-gray-900 rounded-md hover:bg-gray-50">Trang chủ</a>
            <a href="/GODIFA/view/product/list.php" class="block px-3 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-black">Sản phẩm</a>
            <a href="/GODIFA/view/news/news.php" class="block px-3 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-black">Tin tức</a>
            <a href="/GODIFA/view/pages/about.php" class="block px-3 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-black">Về chúng tôi</a>
        </div>
    </div>
  </header>
  
  <div class="h-20"></div>

  <script>
    function toggleMobileMenu() {
      const menu = document.getElementById('mobileMenu');
      menu.classList.toggle('hidden');
    }
  </script>