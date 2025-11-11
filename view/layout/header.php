<?php
  if (session_status() === PHP_SESSION_NONE) {
      session_name('GODIFA_USER_SESSION'); // Session riêng cho user
      session_start();
  }
  
  // Đếm số lượng sản phẩm trong giỏ hàng từ Session
  $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($pageTitle) ? $pageTitle : 'GODIFA Shop'; ?> - Mỹ phẩm & Thực phẩm chức năng Nhật Bản</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* CSS Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      margin: 0 !important;
      padding: 0 !important;
    }
    
    .cart-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ef4444;
      color: white;
      font-size: 0.75rem;
      padding: 2px 6px;
      border-radius: 9999px;
      min-width: 20px;
      text-align: center;
    }
    
    /* Dropdown menu - Improved UX */
    .user-dropdown {
      position: relative;
    }
    
    /* Tạo vùng hover invisible để không bị mất dropdown */
    .user-dropdown::after {
      content: '';
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      height: 12px; /* Khoảng cách hover */
      display: none;
    }
    
    .user-dropdown:hover::after,
    .user-dropdown:hover .dropdown-menu {
      display: block;
    }
    
    .dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      top: calc(100% + 12px); /* Cách button 12px */
      width: 13rem;
      background: white;
      border-radius: 0.75rem;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      padding: 0.5rem 0;
      z-index: 1000;
      animation: slideDown 0.2s ease-out;
      border: 1px solid #e5e7eb;
    }
    
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .dropdown-menu a {
      transition: all 0.15s ease;
    }
    
    .dropdown-menu a:hover {
      background-color: #f3f4f6;
      padding-left: 1.25rem;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800"> 
  <!-- Header -->
  <header class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4">
      <div class="flex justify-between items-center">
        <!-- Logo -->
        <a href="/GODIFA/index.php" class="text-2xl font-bold text-blue-600 hover:text-blue-700">
          <i class="fas fa-shopping-bag mr-2"></i>GODIFA
        </a>
        
        <!-- Navigation -->
        <nav class="hidden md:flex space-x-6 text-sm font-semibold">
          <a href="/GODIFA/index.php" class="hover:text-blue-600 transition">
            <i class="fas fa-home mr-1"></i>Trang chủ
          </a>
          <a href="/GODIFA/view/product/list.php" class="hover:text-blue-600 transition">
            <i class="fas fa-box mr-1"></i>Sản phẩm
          </a>
          <a href="/GODIFA/view/pages/about.php" class="hover:text-blue-600 transition">
            <i class="fas fa-info-circle mr-1"></i>Về chúng tôi
          </a>
          <a href="/GODIFA/view/pages/contact.php" class="hover:text-blue-600 transition">
            <i class="fas fa-phone mr-1"></i>Liên hệ
          </a>
        </nav>
        
        <!-- User Menu -->
        <div class="flex items-center space-x-4">
          <!-- Cart -->
          <a href="/GODIFA/view/cart/viewcart.php" 
             class="relative hover:text-blue-600 transition" title="Giỏ hàng">
            <i class="fas fa-shopping-cart text-xl"></i>
            <?php if ($cartCount > 0): ?>
              <span class="cart-badge cart-count"><?php echo $cartCount; ?></span>
            <?php endif; ?>
          </a>
          
          <!-- User -->
          <?php if (isset($_SESSION['customer_id']) || isset($_SESSION['user_id'])): ?>
            <?php 
              // Xác định tên hiển thị và vai trò
              $displayName = '';
              $isStaff = false;
              $roleName = '';
              
              if (isset($_SESSION['user_id'])) {
                // Nhân viên/Admin
                $isStaff = true;
                $displayName = $_SESSION['hoten'] ?? $_SESSION['username'] ?? 'User';
                
                // Lấy tên vai trò
                if (isset($_SESSION['role_id'])) {
                  switch($_SESSION['role_id']) {
                    case 1: $roleName = 'Chủ DN'; break;
                    case 2: $roleName = 'Admin'; break;
                    case 3: $roleName = 'Bán hàng'; break;
                    case 4: $roleName = 'CSKH'; break;
                    default: $roleName = 'Staff';
                  }
                }
              } else {
                // Khách hàng
                $displayName = $_SESSION['customer_name'] ?? 'Khách hàng';
              }
            ?>
            <div class="user-dropdown">
              <button class="flex items-center space-x-2 hover:text-blue-600 transition">
                <i class="fas <?php echo $isStaff ? 'fa-user-shield' : 'fa-user-circle'; ?> text-xl"></i>
                <div class="text-left">
                  <div class="text-sm font-semibold"><?php echo htmlspecialchars($displayName); ?></div>
                  <?php if ($isStaff): ?>
                    <div class="text-xs text-gray-500"><?php echo $roleName; ?></div>
                  <?php endif; ?>
                </div>
                <i class="fas fa-chevron-down text-xs"></i>
              </button>
              <div class="dropdown-menu">
                <a href="/GODIFA/view/account/order_history.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-gray-50">
                  <i class="fas fa-history mr-3 w-5 text-gray-400"></i>
                  <span>Lịch sử đơn hàng</span>
                </a>
                <a href="/GODIFA/view/account/profile.php" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-gray-50">
                  <i class="fas fa-user mr-3 w-5 text-gray-400"></i>
                  <span>Thông tin cá nhân</span>
                </a>
                <div class="border-t border-gray-200 my-1"></div>
                <a href="/GODIFA/view/auth/logout.php" class="flex items-center px-4 py-2.5 text-red-600 hover:bg-red-50 font-medium">
                  <i class="fas fa-sign-out-alt mr-3 w-5"></i>
                  <span>Đăng xuất</span>
                </a>
              </div>
            </div>
          <?php else: ?>
            <a href="/GODIFA/view/auth/customer-login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
              <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập
            </a>
          <?php endif; ?>
          
          <!-- Mobile Menu Button -->
          <button class="md:hidden text-2xl" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
          </button>
        </div>
      </div>
      
      <!-- Mobile Menu -->
      <div id="mobileMenu" class="hidden md:hidden mt-4 pb-4">
        <nav class="flex flex-col space-y-3">
          <a href="/GODIFA/index.php" class="hover:text-blue-600 transition">
            <i class="fas fa-home mr-2"></i>Trang chủ
          </a>
          <a href="/GODIFA/view/product/list.php" class="hover:text-blue-600 transition">
            <i class="fas fa-box mr-2"></i>Sản phẩm
          </a>
          <a href="/GODIFA/view/pages/about.php" class="hover:text-blue-600 transition">
            <i class="fas fa-info-circle mr-2"></i>Về chúng tôi
          </a>
          <a href="/GODIFA/view/pages/contact.php" class="hover:text-blue-600 transition">
            <i class="fas fa-phone mr-2"></i>Liên hệ
          </a>
        </nav>
      </div>
    </div>
  </header>
  
  <script>
    function toggleMobileMenu() {
      const menu = document.getElementById('mobileMenu');
      menu.classList.toggle('hidden');
    }
    
    // Đóng dropdown khi click ra ngoài (Optional - giúp UX tốt hơn trên mobile)
    document.addEventListener('click', function(event) {
      const dropdown = document.querySelector('.user-dropdown');
      if (!dropdown) return;
      
      const dropdownMenu = dropdown.querySelector('.dropdown-menu');
      if (!dropdown.contains(event.target)) {
        // Click outside - không làm gì vì dùng CSS hover
      }
    });
    
    // Prevent dropdown close on click inside (cho mobile)
    const dropdownMenus = document.querySelectorAll('.dropdown-menu');
    dropdownMenus.forEach(menu => {
      menu.addEventListener('click', function(e) {
        // Link vẫn hoạt động bình thường
      });
    });
  </script>
