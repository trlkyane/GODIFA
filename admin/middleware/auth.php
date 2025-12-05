<?php
/**
 * Middleware ki?m tra quy?n truy c?p
 * File: admin/middleware/auth.php
 */

// Load constants
require_once __DIR__ . '/../../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_ADMIN); // Session riêng cho admin
    session_start();
}

// ==================== FUNCTIONS ====================

/**
 * Ki?m tra dang nh?p
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . ADMIN_BASE_URL . 'login.php');
        exit();
    }
}

/**
 * Yêu c?u vai trò c? th?
 */
function requireRole($allowedRoles = []) {
    requireLogin();
    
    $userRole = $_SESSION['role_id'] ?? 0;
    
    if (!in_array($userRole, $allowedRoles)) {
        header('Location: ' . ADMIN_BASE_URL . '403.php');
        exit();
    }
}

/**
 * Yêu c?u ph?i là nhân viên (role 1,2,3,4)
 */
function requireStaff() {
    requireRole([ROLE_OWNER, ROLE_ADMIN, ROLE_SALES, ROLE_SUPPORT]);
}

// ==================== PERMISSIONS ====================

// Danh sách quy?n theo vai trò
$rolePermissions = [
    ROLE_OWNER => [
        // Dashboard & Th?ng kê
        'view_dashboard',       // Xem dashboard
        'view_statistics',      // Xem th?ng kê
        
        // Nhân viên
        'manage_users',         // Qu?n lý nhân viên (toàn quy?n)
        'delete_user',          // Xóa nhân viên (ch? Owner)
        
        // S?n ph?m & Danh m?c
        'view_products',        // Xem s?n ph?m
        'manage_products',      // Qu?n lý s?n ph?m (thêm, s?a)
        'delete_product',       // Xóa s?n ph?m (Owner & Admin)
        'view_categories',      // Xem danh m?c
        'manage_categories',    // Qu?n lý danh m?c
        
        // Ðon hàng
        'view_orders',          // Xem don hàng
        'create_order',         // T?o don hàng
        'edit_own_order',       // S?a don do mình t?o
        'edit_all_orders',      // S?a t?t c? don hàng
        'update_order_status',  // C?p nh?t tr?ng thái
        'enter_order',          // Nh?p don th? công
        'delete_order',         // Xóa don hàng
        
        // Voucher
        'view_vouchers',        // Xem voucher
        'create_voucher',       // T?o voucher
        'apply_voucher',        // Áp d?ng voucher
        'manage_vouchers',      // Qu?n lý voucher (s?a, xóa)
        
        // Bài vi?t
        'view_blog',            // Xem bài vi?t
        'manage_blog',          // Qu?n lý bài vi?t (thêm, s?a)
        'delete_blog',          // Xóa bài vi?t (Owner & Admin)
        
        // Chat
        'view_chat',            // Xem chat
        'manage_chat',          // Qu?n lý chat
        
        // Khách hàng
        'view_customers',       // Xem khách hàng
        'manage_customers',     // Qu?n lý khách hàng (s?a thông tin, khóa/m?)
        'view_customer_history',// Xem l?ch s? mua hàng
        
        // Ðánh giá
        'view_reviews',         // Xem dánh giá
        'manage_reviews',       // Qu?n lý dánh giá (duy?t, xóa)
        
        'full_access'           // Toàn quy?n
    ],
    ROLE_ADMIN => [
        // Dashboard & Th?ng kê (Ch? xem)
        'view_dashboard',       // Xem dashboard
        'view_statistics',      // Xem th?ng kê
        
        // S?n ph?m & Danh m?c (Toàn quy?n)
        'view_products',        // Xem s?n ph?m
        'manage_products',      // Qu?n lý s?n ph?m (thêm, s?a)
        'delete_product',       // Xóa s?n ph?m
        'view_categories',      // Xem danh m?c
        'manage_categories',    // Qu?n lý danh m?c (thêm, s?a, xóa)
        
        // Nhân viên (Toàn quy?n)
        'manage_users',         // Qu?n lý nhân viên (thêm, s?a, xóa)
        'delete_user',          // Xóa nhân viên
        
        // Bài vi?t (Toàn quy?n)
        'view_blog',            // Xem bài vi?t
        'manage_blog',          // Qu?n lý bài vi?t (thêm, s?a)
        'delete_blog',          // Xóa bài vi?t
        
        // Ðon hàng (Ch? xem)
        'view_orders',          // Xem don hàng
        
        // Voucher (Ch? xem)
        'view_vouchers',        // Xem voucher
        
        // Khách hàng (Ch? xem)
        'view_customers',       // Xem thông tin khách hàng
        'view_customer_history',// Xem l?ch s? mua hàng
        
        // Nhóm khách hàng (Ch? xem)
        'view_customer_groups', // Xem nhóm khách hàng
        
        // Ðánh giá (Ch? xem)
        'view_reviews',         // Xem dánh giá
    ],
    ROLE_SALES => [
        // Dashboard & Th?ng kê (Ch? xem)
        'view_dashboard',       // Xem dashboard
        'view_statistics',      // Xem th?ng kê
        
        // Ðon hàng (Toàn quy?n)
        'view_orders',          // Xem don hàng
        'create_order',         // T?o don hàng m?i
        'edit_own_order',       // Ch?nh s?a don do mình t?o
        'edit_all_orders',      // S?a t?t c? don hàng
        'update_order_status',  // C?p nh?t tr?ng thái
        'enter_order',          // Nh?p don th? công
        'delete_order',         // Xóa don hàng
        
        // Voucher (Toàn quy?n)
        'view_vouchers',        // Xem voucher
        'create_voucher',       // T?o voucher
        'manage_vouchers',      // S?a, xóa voucher
        'apply_voucher',        // Áp d?ng voucher
        
        // S?n ph?m & Danh m?c (Ch? xem)
        'view_products',        // Xem s?n ph?m
        'view_categories',      // Xem danh m?c
        
        // Bài vi?t (Ch? xem)
        'view_blog',            // Xem bài vi?t
        
        
        // Khách hàng (Ch? xem)
        'view_customers',       // Xem khách hàng
        'view_customer_history',// Xem l?ch s? mua hàng
        'view_customer_groups', // Xem nhóm khách hàng
        
        // Ðánh giá (Ch? xem)
        'view_reviews',         // Xem dánh giá
    ],
    ROLE_SUPPORT => [
        // Dashboard & Th?ng kê (Ch? xem)
        'view_dashboard',       // Xem dashboard
        'view_statistics',      // Xem th?ng kê
        
        // Ðánh giá (Toàn quy?n)
        'view_reviews',         // Xem dánh giá
        'manage_reviews',       // Qu?n lý dánh giá (duy?t, ?n/hi?n, xóa)
        'respond_to_reviews',   // Ph?n h?i dánh giá
        
        // Chat (Toàn quy?n)
        'view_chat',            // Xem chat
        'manage_chat',          // Qu?n lý chat, ph?n h?i khách hàng
        
        // Khách hàng (Toàn quy?n)
        'view_customers',       // Xem khách hàng
        'manage_customers',     // Qu?n lý khách hàng (s?a thông tin, khóa/m?)
        'view_customer_history',// Xem l?ch s? mua hàng
        'view_customer_groups', // Xem nhóm khách hàng
        'edit_customer_info',   // C?p nh?t thông tin khách hàng
        'add_customer_notes',   // Ghi chú khách hàng
        
        // S?n ph?m & Danh m?c (Ch? xem)
        'view_products',        // Xem s?n ph?m
        'view_categories',      // Xem danh m?c
        
        // Ðon hàng (Ch? xem)
        'view_orders',          // Xem don hàng
        
        // Voucher (Ch? xem)
        'view_vouchers',        // Xem voucher
        
        // Bài vi?t (Ch? xem)
        'view_blog',            // Xem bài vi?t
    ]
];

/**
 * Ki?m tra quy?n c? th?
 */
function hasPermission($permission) {
    global $rolePermissions;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $userRole = $_SESSION['role_id'] ?? 0;
    
    // Ch? doanh nghi?p có toàn quy?n
    if ($userRole == ROLE_OWNER) {
        return true;
    }
    
    if (!isset($rolePermissions[$userRole])) {
        return false;
    }
    
    return in_array($permission, $rolePermissions[$userRole]);
}

/**
 * L?y tên vai trò
 */
function getRoleName($roleId) {
    $roles = [
        ROLE_OWNER => 'Ch? Doanh Nghi?p',
        ROLE_ADMIN => 'Qu?n Tr? Viên',
        ROLE_SALES => 'Nhân Viên Bán Hàng',
        ROLE_SUPPORT => 'Nhân Viên CSKH'
    ];
    
    return $roles[$roleId] ?? 'Khách hàng';
}

/**
 * L?y màu badge theo role
 */
function getRoleBadgeClass($roleId) {
    $badges = [
        ROLE_OWNER => 'bg-purple-600',
        ROLE_ADMIN => 'bg-blue-600',
        ROLE_SALES => 'bg-green-600',
        ROLE_SUPPORT => 'bg-orange-600'
    ];
    
    return $badges[$roleId] ?? 'bg-gray-600';
}

/**
 * L?y icon theo role
 */
function getRoleIcon($roleId) {
    $icons = [
        ROLE_OWNER => 'fa-crown',
        ROLE_ADMIN => 'fa-user-shield',
        ROLE_SALES => 'fa-user-tie',
        ROLE_SUPPORT => 'fa-headset'
    ];
    
    return $icons[$roleId] ?? 'fa-user';
}

/**
 * Ki?m tra quy?n ch?nh s?a/xóa/d?i m?t kh?u nhân viên
 * Quy t?c:
 * - Owner có th? s?a t?t c?
 * - Admin ch? du?c s?a nhân viên c?p du?i (Sales, Support) - roleID > 2
 * - Không du?c s?a nhân viên cùng c?p ho?c c?p cao hon
 * 
 * @param int $currentUserRoleID - Role ID c?a ngu?i dang th?c hi?n
 * @param int $targetUserRoleID - Role ID c?a nhân viên mu?n s?a
 * @return bool
 */
function canEditUser($currentUserRoleID, $targetUserRoleID) {
    // Owner có th? s?a t?t c?
    if ($currentUserRoleID == ROLE_OWNER) {
        return true;
    }
    
    // Admin ch? du?c s?a nhân viên có roleID > 2 (Sales và Support)
    if ($currentUserRoleID == ROLE_ADMIN && $targetUserRoleID > ROLE_ADMIN) {
        return true;
    }
    
    // Các tru?ng h?p khác không du?c phép
    return false;
}

/**
 * Ki?m tra quy?n thêm nhân viên v?i roleID c? th?
 * Quy t?c:
 * - Owner có th? thêm t?t c? các roleID
 * - Các role khác KHÔNG du?c thêm nhân viên
 * 
 * @param int $currentUserRoleID - Role ID c?a ngu?i dang th?c hi?n
 * @param int $newRoleID - Role ID mu?n t?o
 * @return bool
 */
function canAddUserWithRole($currentUserRoleID, $newRoleID) {
    // Ch? Owner m?i du?c thêm nhân viên
    return ($currentUserRoleID == ROLE_OWNER);
}
?>
