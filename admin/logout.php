<?php
/**
 * Admin Logout Handler
 * File: admin/logout.php
 * 
 * Xử lý đăng xuất cho ADMIN/STAFF (Backend)
 * - Xóa session admin/staff
 * - Redirect về trang đăng nhập admin
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_ADMIN_SESSION'); // Session riêng cho admin
    session_start();
}

// Xóa các session liên quan đến admin/staff
if (isset($_SESSION['user_id'])) {
    unset($_SESSION['user_id']);
}
if (isset($_SESSION['user_name'])) {
    unset($_SESSION['user_name']);
}
if (isset($_SESSION['user_email'])) {
    unset($_SESSION['user_email']);
}
if (isset($_SESSION['role_id'])) {
    unset($_SESSION['role_id']);
}
if (isset($_SESSION['role_name'])) {
    unset($_SESSION['role_name']);
}

// Xóa toàn bộ session
session_unset();
session_destroy();

// Xóa cookie phiên nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Redirect về trang đăng nhập admin
header("Location: /GODIFA/admin/login.php?logout=success");
exit();
