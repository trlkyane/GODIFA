<?php
/**
 * Middleware: Owner Only Access
 * File: admin/middleware/owner_only.php
 * 
 * Ch? cho phép Ch? Doanh Nghi?p (roleID = 1) truy c?p
 */

// Load constants
require_once __DIR__ . '/../../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_ADMIN);
    session_start();
}

// Ki?m tra dã dang nh?p chua (S? d?ng user_id nhu trong auth.php)
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . ADMIN_BASE_URL . 'login.php?error=unauthorized');
    exit;
}

// Ki?m tra có ph?i Ch? Doanh Nghi?p không (S? d?ng role_id nhu trong login controller)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != ROLE_OWNER) {
    // Ghi log truy c?p trái phép
    error_log(sprintf(
        "[SECURITY] User #%d (%s) tried to access owner-only page: %s",
        $_SESSION['user_id'],
        $_SESSION['username'] ?? 'Unknown',
        $_SERVER['REQUEST_URI']
    ));
    
    // Chuy?n hu?ng v? trang ch? admin v?i thông báo l?i
    header('Location: ' . ADMIN_BASE_URL . 'index.php?error=permission_denied');
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
