<?php
session_start();
ob_start(); // Bắt đầu output buffering

// Xử lý đăng nhập TRƯỚC khi xuất HTML
include_once(__DIR__ . "/../../controller/cLogin.php");

if (isset($_REQUEST['btn_login'])) {
    $email = trim($_REQUEST['email']);
    $password = trim($_REQUEST['password']);

    if (empty($email)) {
        echo "<script>alert('Vui lòng nhập email.');</script>";
    } elseif (empty($password)) {
        echo "<script>alert('Vui lòng nhập mật khẩu.');</script>";
    } else {
        $loginController = new cLogin();
        $loginController->getUser($email, $password);
        // Nếu đăng nhập thành công, controller sẽ redirect và exit
        // Không chạy xuống dưới nữa
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
            <h1 class="text-4xl font-bold text-blue-600 mb-2">GODIFA</h1>
            <p class="text-gray-600">Chào mừng bạn quay trở lại</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Đăng nhập</h2>
            
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

                <!-- Register Button -->
                <button type="button" 
                        onclick="window.location.href='/GODIFA/view/auth/register.php'"
                        class="w-full bg-white border-2 border-blue-600 text-blue-600 hover:bg-blue-50 font-semibold py-3 rounded-lg transition duration-300">
                    <i class="fas fa-user-plus mr-2"></i>Đăng ký tài khoản
                </button>

                <!-- Back to Home -->
                <div class="text-center pt-4">
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
ob_end_flush(); // Kết thúc output buffering
?>
