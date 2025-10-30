<?php
/**
 * Customer Login Page
 * File: view/auth/customer-login.php
 * 
 * Trang đăng nhập cho CUSTOMER (Khách hàng)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION'); // Sử dụng session name đúng
    session_start();
}
ob_start();

// Xử lý đăng nhập
include_once(__DIR__ . "/../../controller/cCustomerLogin.php");

if (isset($_REQUEST['btn_login'])) {
    $email = trim($_REQUEST['email']);
    $password = trim($_REQUEST['password']);

    if (empty($email)) {
        echo "<script>alert('Vui lòng nhập email.');</script>";
    } elseif (empty($password)) {
        echo "<script>alert('Vui lòng nhập mật khẩu.');</script>";
    } else {
        $loginController = new cCustomerLogin();
        $loginController->login($email, $password);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-blue-600 mb-2">🍫 GODIFA</h1>
            <p class="text-gray-600">Chào mừng khách hàng quay trở lại</p>
            <p class="text-sm text-gray-500 mt-2">Đăng nhập để mua sắm</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
                <i class="fas fa-shopping-bag text-blue-600 mr-2"></i>Đăng nhập Khách hàng
            </h2>
            
            <form action="" method="post" class="space-y-5">
                <!-- Email Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope text-blue-600 mr-2"></i>Email
                    </label>
                    <input type="email" 
                           name="email" 
                           placeholder="example@email.com"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           required>
                </div>

                <!-- Password Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-key text-blue-600 mr-2"></i>Mật khẩu
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="password" 
                               id="password"
                               placeholder="••••••••"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition pr-12"
                               required>
                        <button type="button" 
                                id="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Login Button -->
                <button type="submit" 
                        name="btn_login"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition duration-300 transform hover:scale-[1.02] shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập
                </button>

                <!-- Register Link -->
                <div class="text-center">
                    <p class="text-gray-600">
                        Chưa có tài khoản? 
                        <a href="/GODIFA/view/auth/register.php" class="text-blue-600 hover:text-blue-700 font-semibold">
                            Đăng ký ngay
                        </a>
                    </p>
                </div>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">hoặc</span>
                    </div>
                </div>

                <!-- Admin Login Link -->
                <div class="text-center">
                    <a href="/GODIFA/admin/login.php" 
                       class="text-gray-600 hover:text-gray-800 text-sm inline-flex items-center gap-2">
                        <i class="fas fa-user-shield"></i>
                        <span>Đăng nhập dành cho Admin/Nhân viên</span>
                    </a>
                </div>

                <!-- Back to Home -->
                <div class="text-center pt-4 border-t border-gray-200">
                    <a href="/GODIFA/index.php" 
                       class="text-blue-600 hover:text-blue-700 font-medium inline-flex items-center gap-2 transition">
                        <i class="fas fa-home"></i>
                        <span>Quay về trang chủ</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-gray-600 text-sm">
            <p>&copy; 2025 GODIFA. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            if (type === 'text') {
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>

<?php
ob_end_flush();
?>
