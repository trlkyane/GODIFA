<?php
/**
 * Frontend Logout Handler
 * File: view/auth/logout.php
 * 
 * Xử lý đăng xuất cho CUSTOMER (Frontend)
 * - Xóa session customer
 * - Redirect về trang chủ
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION'); // Sử dụng session name đúng
    session_start();
}

// Xóa các session liên quan đến customer
if (isset($_SESSION['customer_id'])) {
    unset($_SESSION['customer_id']);
}
if (isset($_SESSION['customer_name'])) {
    unset($_SESSION['customer_name']);
}
if (isset($_SESSION['customer_email'])) {
    unset($_SESSION['customer_email']);
}
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
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

// Redirect về trang chủ frontend
header("Location: /GODIFA/index.php?logout=success");
exit();

