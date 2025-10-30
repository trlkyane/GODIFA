<?php
/**
 * Middleware: Chỉ cho phép khách hàng (customer) truy cập
 * Chặn admin/staff truy cập trang người dùng
 */

// Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

// Kiểm tra nếu user đã đăng nhập và có roleID
if (isset($_SESSION['user_id']) && isset($_SESSION['roleID'])) {
    $roleID = (int)$_SESSION['roleID'];
    
    // roleID: 0 = Customer, 1 = Staff, 2 = Admin
    // Chỉ cho phép Customer (roleID = 0)
    if ($roleID !== 0) {
        // Nếu là Staff hoặc Admin, chuyển hướng về trang admin
        header('Location: /GODIFA/admin/index.php');
        exit();
    }
}

// Nếu chưa đăng nhập hoặc là customer → cho phép tiếp tục
