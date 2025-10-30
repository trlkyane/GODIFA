<?php
ob_start();
session_start();

include_once(__DIR__ . "/../../controller/cRegister.php");
$controlRegister = new cRegister();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_register'])) {
    $hoten = trim($_POST['hoten']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);
    
    if ($password === $confirm) {
        // registerAccount($customerName, $password, $email, $phone)
        $result = $controlRegister->registerAccount($hoten, $password, $email, $phone);
        if ($result == 1) {
            echo "<script>alert('Đăng ký thành công! Bạn đã được tự động đăng nhập.'); window.location.href='/GODIFA/index.php';</script>";
        } elseif ($result == 0) {
            echo "<script>alert('Email đã tồn tại. Vui lòng chọn email khác.');</script>";
        } else {
            echo "<script>alert('Đăng ký thất bại. Vui lòng thử lại.');</script>";
        }
    } else {
        echo "<script>alert('Mật khẩu không trùng nhau');</script>";
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="inline-block bg-blue-600 text-white p-4 rounded-full mb-4">
                <i class="fas fa-user-plus text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Đăng ký tài khoản</h1>
            <p class="text-gray-600 mt-2">Tạo tài khoản mới để mua sắm</p>
        </div>

        <form method="POST" autocomplete="off" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user mr-2 text-blue-600"></i>Họ tên
                </label>
                <input type="text" name="hoten" placeholder="Nhập họ tên của bạn" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-2 text-blue-600"></i>Email
                </label>
                <input type="email" name="email" placeholder="example@email.com" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-phone mr-2 text-blue-600"></i>Số điện thoại
                </label>
                <input type="text" name="phone" placeholder="0xxxxxxxxx" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2 text-blue-600"></i>Mật khẩu
                </label>
                <input type="password" name="password" placeholder="Nhập mật khẩu" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2 text-blue-600"></i>Xác nhận mật khẩu
                </label>
                <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            <button type="submit" name="btn_register" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition transform hover:scale-105 shadow-lg">
                <i class="fas fa-user-plus mr-2"></i>Đăng ký
            </button>

            <div class="text-center pt-4">
                <p class="text-gray-600">
                    Đã có tài khoản? 
                    <a href="login.php" class="text-blue-600 hover:text-blue-700 font-semibold">
                        Đăng nhập ngay
                    </a>
                </p>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-200 text-center">
            <a href="../index.php" class="text-gray-600 hover:text-gray-800 transition">
                <i class="fas fa-home mr-2"></i>Về trang chủ
            </a>
        </div>
    </div>
</body>
</html>