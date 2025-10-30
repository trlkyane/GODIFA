<?php
/**
 * Admin Login Page
 * File: admin/login.php
 * 
 * Trang đăng nhập cho ADMIN/STAFF (Quản trị viên/Nhân viên)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_ADMIN_SESSION'); // Session riêng cho admin
    session_start();
}
ob_start();

// Xử lý đăng nhập
include_once(__DIR__ . "/../controller/admin/cAdminLogin.php");

if (isset($_REQUEST['btn_login'])) {
    $email = trim($_REQUEST['email']);
    $password = trim($_REQUEST['password']);

    if (empty($email)) {
        echo "<script>alert('Vui lòng nhập email.');</script>";
    } elseif (empty($password)) {
        echo "<script>alert('Vui lòng nhập mật khẩu.');</script>";
    } else {
        $loginController = new cAdminLogin();
        $loginController->login($email, $password);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 p-4 rounded-2xl mb-4 shadow-2xl">
                <i class="fas fa-user-shield text-5xl text-white"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">GODIFA Admin</h1>
            <p class="text-gray-400">Admin & Staff Control Panel</p>
            <p class="text-sm text-gray-500 mt-2">Chỉ dành cho quản trị viên và nhân viên</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-lock text-blue-600 mr-2"></i>Đăng nhập Admin
                </h2>
                <p class="text-sm text-gray-500 mt-2">Vui lòng sử dụng tài khoản Admin/Staff</p>
            </div>
            
            <form action="" method="post" class="space-y-5">
                <!-- Email Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope text-blue-600 mr-2"></i>Email
                    </label>
                    <input type="email" 
                           name="email" 
                           placeholder="admin@godifa.com"
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

                <!-- Security Notice -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-2"></i>
                        <p class="text-sm text-yellow-700">
                            <strong>Lưu ý:</strong> Trang này chỉ dành cho Admin và Nhân viên. 
                            Khách hàng vui lòng sử dụng trang đăng nhập bên dưới.
                        </p>
                    </div>
                </div>

                <!-- Login Button -->
                <button type="submit" 
                        name="btn_login"
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 rounded-lg transition duration-300 transform hover:scale-[1.02] shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập Admin
                </button>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">Bạn là khách hàng?</span>
                    </div>
                </div>

                <!-- Customer Login Link -->
                <div class="text-center">
                    <a href="/GODIFA/view/auth/customer-login.php" 
                       class="text-blue-600 hover:text-blue-700 font-semibold inline-flex items-center gap-2">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Đăng nhập dành cho Khách hàng</span>
                    </a>
                </div>

                <!-- Back to Home -->
                <div class="text-center pt-4 border-t border-gray-200">
                    <a href="/GODIFA/index.php" 
                       class="text-gray-600 hover:text-gray-800 font-medium inline-flex items-center gap-2 transition">
                        <i class="fas fa-home"></i>
                        <span>Quay về trang chủ</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-gray-400 text-sm">
            <p><i class="fas fa-shield-alt mr-1"></i> Secure Admin Portal</p>
            <p class="mt-1">&copy; 2025 GODIFA. All rights reserved.</p>
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
