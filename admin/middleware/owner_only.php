<?php
/**
 * Middleware: Owner Only Access
 * File: admin/middleware/owner_only.php
 * 
 * Chỉ cho phép Chủ Doanh Nghiệp (roleID = 1) truy cập
 */

// Load constants
require_once __DIR__ . '/../../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_ADMIN);
    session_start();
}

// Kiểm tra đã đăng nhập chưa (Sử dụng user_id như trong auth.php)
if (!isset($_SESSION['user_id'])) {
    header('Location: /GODIFA/admin/login.php?error=unauthorized');
    exit;
}

// Kiểm tra có phải Chủ Doanh Nghiệp không (Sử dụng role_id như trong login controller)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != ROLE_OWNER) {
    // Ghi log truy cập trái phép
    error_log(sprintf(
        "[SECURITY] User #%d (%s) tried to access owner-only page: %s",
        $_SESSION['user_id'],
        $_SESSION['username'] ?? 'Unknown',
        $_SERVER['REQUEST_URI']
    ));
    
    // Chuyển hướng về trang chủ admin với thông báo lỗi
    header('Location: /GODIFA/admin/index.php?error=permission_denied');
    exit;
}

// Log successful access (optional)
if (isset($_GET['debug'])) {
    error_log(sprintf(
        "[INFO] Owner #%d (%s) accessed: %s",
        $_SESSION['user_id'],
        $_SESSION['username'] ?? 'Unknown',
        $_SERVER['REQUEST_URI']
    ));
}
?>
