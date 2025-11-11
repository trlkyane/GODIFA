<?php
/**
 * Middleware: Chỉ cho phép khách hàng (customer) truy cập
 * Chặn admin/staff truy cập trang người dùng
 */

// Load constants
require_once __DIR__ . '/../config/constants.php';

// Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_USER);
    session_start();
}

// Kiểm tra nếu user đã đăng nhập và có role_id
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id'])) {
    $roleID = (int)$_SESSION['role_id'];
    
    // Chỉ cho phép Customer (role_id = 0)
    if ($roleID !== ROLE_CUSTOMER) {
        // Nếu là Staff hoặc Admin, chuyển hướng về trang admin
        header('Location: /GODIFA/admin/index.php');
        exit();
    }
}

// Nếu chưa đăng nhập hoặc là customer → cho phép tiếp tục
