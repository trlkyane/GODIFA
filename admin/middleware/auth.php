<?php
/**
 * Middleware kiểm tra quyền truy cập
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
 * Kiểm tra đăng nhập
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /GODIFA/admin/login.php');
        exit();
    }
}

/**
 * Yêu cầu vai trò cụ thể
 */
function requireRole($allowedRoles = []) {
    requireLogin();
    
    $userRole = $_SESSION['role_id'] ?? 0;
    
    if (!in_array($userRole, $allowedRoles)) {
        header('Location: 403.php');
        exit();
    }
}

/**
 * Yêu cầu phải là nhân viên (role 1,2,3,4)
 */
function requireStaff() {
    requireRole([ROLE_OWNER, ROLE_ADMIN, ROLE_SALES, ROLE_SUPPORT]);
}

// ==================== PERMISSIONS ====================

// Danh sách quyền theo vai trò
$rolePermissions = [
    ROLE_OWNER => [
        // Dashboard & Thống kê
        'view_dashboard',       // Xem dashboard
        'view_statistics',      // Xem thống kê
        
        // Nhân viên
        'manage_users',         // Quản lý nhân viên (toàn quyền)
        'delete_user',          // Xóa nhân viên (chỉ Owner)
        
        // Sản phẩm & Danh mục
        'view_products',        // Xem sản phẩm
        'manage_products',      // Quản lý sản phẩm (thêm, sửa)
        'delete_product',       // Xóa sản phẩm (Owner & Admin)
        'view_categories',      // Xem danh mục
        'manage_categories',    // Quản lý danh mục
        
        // Đơn hàng
        'view_orders',          // Xem đơn hàng
        'create_order',         // Tạo đơn hàng
        'edit_own_order',       // Sửa đơn do mình tạo
        'edit_all_orders',      // Sửa tất cả đơn hàng
        'update_order_status',  // Cập nhật trạng thái
        'enter_order',          // Nhập đơn thủ công
        'delete_order',         // Xóa đơn hàng
        
        // Voucher
        'view_vouchers',        // Xem voucher
        'create_voucher',       // Tạo voucher
        'apply_voucher',        // Áp dụng voucher
        'manage_vouchers',      // Quản lý voucher (sửa, xóa)
        
        // Bài viết
        'view_blog',            // Xem bài viết
        'manage_blog',          // Quản lý bài viết (thêm, sửa)
        'delete_blog',          // Xóa bài viết (Owner & Admin)
        
        // Chat
        'view_chat',            // Xem chat
        'manage_chat',          // Quản lý chat
        
        // Khách hàng
        'view_customers',       // Xem khách hàng
        'manage_customers',     // Quản lý khách hàng (sửa thông tin, khóa/mở)
        'view_customer_history',// Xem lịch sử mua hàng
        
        // Đánh giá
        'view_reviews',         // Xem đánh giá
        'manage_reviews',       // Quản lý đánh giá (duyệt, xóa)
        
        'full_access'           // Toàn quyền
    ],
    ROLE_ADMIN => [
        // Dashboard & Thống kê (Chỉ xem)
        'view_dashboard',       // Xem dashboard
        'view_statistics',      // Xem thống kê
        
        // Sản phẩm & Danh mục (Toàn quyền)
        'view_products',        // Xem sản phẩm
        'manage_products',      // Quản lý sản phẩm (thêm, sửa)
        'delete_product',       // Xóa sản phẩm
        'view_categories',      // Xem danh mục
        'manage_categories',    // Quản lý danh mục (thêm, sửa, xóa)
        
        // Nhân viên (Toàn quyền)
        'manage_users',         // Quản lý nhân viên (thêm, sửa, xóa)
        'delete_user',          // Xóa nhân viên
        
        // Bài viết (Toàn quyền)
        'view_blog',            // Xem bài viết
        'manage_blog',          // Quản lý bài viết (thêm, sửa)
        'delete_blog',          // Xóa bài viết
        
        // Đơn hàng (Chỉ xem)
        'view_orders',          // Xem đơn hàng
        
        // Voucher (Chỉ xem)
        'view_vouchers',        // Xem voucher
        
        // Chat (Chỉ xem tin nhắn)
        'view_chat',            // Xem tin nhắn khách hàng
        
        // Khách hàng (Chỉ xem)
        'view_customers',       // Xem thông tin khách hàng
        'view_customer_history',// Xem lịch sử mua hàng
        
        // Nhóm khách hàng (Chỉ xem)
        'view_customer_groups', // Xem nhóm khách hàng
        
        // Đánh giá (Chỉ xem)
        'view_reviews',         // Xem đánh giá
    ],
    ROLE_SALES => [
        // Dashboard & Thống kê (Chỉ xem)
        'view_dashboard',       // Xem dashboard
        'view_statistics',      // Xem thống kê
        
        // Đơn hàng (Toàn quyền)
        'view_orders',          // Xem đơn hàng
        'create_order',         // Tạo đơn hàng mới
        'edit_own_order',       // Chỉnh sửa đơn do mình tạo
        'edit_all_orders',      // Sửa tất cả đơn hàng
        'update_order_status',  // Cập nhật trạng thái
        'enter_order',          // Nhập đơn thủ công
        'delete_order',         // Xóa đơn hàng
        
        // Voucher (Toàn quyền)
        'view_vouchers',        // Xem voucher
        'create_voucher',       // Tạo voucher
        'manage_vouchers',      // Sửa, xóa voucher
        'apply_voucher',        // Áp dụng voucher
        
        // Sản phẩm & Danh mục (Chỉ xem)
        'view_products',        // Xem sản phẩm
        'view_categories',      // Xem danh mục
        
        // Bài viết (Chỉ xem)
        'view_blog',            // Xem bài viết
        
        // Chat (Chỉ xem)
        'view_chat',            // Xem chat
        
        // Khách hàng (Chỉ xem)
        'view_customers',       // Xem khách hàng
        'view_customer_history',// Xem lịch sử mua hàng
        'view_customer_groups', // Xem nhóm khách hàng
        
        // Đánh giá (Chỉ xem)
        'view_reviews',         // Xem đánh giá
    ],
    ROLE_SUPPORT => [
        // Dashboard & Thống kê (Chỉ xem)
        'view_dashboard',       // Xem dashboard
        'view_statistics',      // Xem thống kê
        
        // Đánh giá (Toàn quyền)
        'view_reviews',         // Xem đánh giá
        'manage_reviews',       // Quản lý đánh giá (duyệt, ẩn/hiện, xóa)
        'respond_to_reviews',   // Phản hồi đánh giá
        
        // Chat (Toàn quyền)
        'view_chat',            // Xem chat
        'manage_chat',          // Quản lý chat, phản hồi khách hàng
        
        // Khách hàng (Toàn quyền)
        'view_customers',       // Xem khách hàng
        'manage_customers',     // Quản lý khách hàng (sửa thông tin, khóa/mở)
        'view_customer_history',// Xem lịch sử mua hàng
        'view_customer_groups', // Xem nhóm khách hàng
        'edit_customer_info',   // Cập nhật thông tin khách hàng
        'add_customer_notes',   // Ghi chú khách hàng
        
        // Sản phẩm & Danh mục (Chỉ xem)
        'view_products',        // Xem sản phẩm
        'view_categories',      // Xem danh mục
        
        // Đơn hàng (Chỉ xem)
        'view_orders',          // Xem đơn hàng
        
        // Voucher (Chỉ xem)
        'view_vouchers',        // Xem voucher
        
        // Bài viết (Chỉ xem)
        'view_blog',            // Xem bài viết
    ]
];

/**
 * Kiểm tra quyền cụ thể
 */
function hasPermission($permission) {
    global $rolePermissions;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $userRole = $_SESSION['role_id'] ?? 0;
    
    // Chủ doanh nghiệp có toàn quyền
    if ($userRole == ROLE_OWNER) {
        return true;
    }
    
    if (!isset($rolePermissions[$userRole])) {
        return false;
    }
    
    return in_array($permission, $rolePermissions[$userRole]);
}

/**
 * Lấy tên vai trò
 */
function getRoleName($roleId) {
    $roles = [
        ROLE_OWNER => 'Chủ Doanh Nghiệp',
        ROLE_ADMIN => 'Quản Trị Viên',
        ROLE_SALES => 'Nhân Viên Bán Hàng',
        ROLE_SUPPORT => 'Nhân Viên CSKH'
    ];
    
    return $roles[$roleId] ?? 'Khách hàng';
}

/**
 * Lấy màu badge theo role
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
 * Lấy icon theo role
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
 * Kiểm tra quyền chỉnh sửa/xóa/đổi mật khẩu nhân viên
 * Quy tắc:
 * - Owner có thể sửa tất cả
 * - Admin chỉ được sửa nhân viên cấp dưới (Sales, Support) - roleID > 2
 * - Không được sửa nhân viên cùng cấp hoặc cấp cao hơn
 * 
 * @param int $currentUserRoleID - Role ID của người đang thực hiện
 * @param int $targetUserRoleID - Role ID của nhân viên muốn sửa
 * @return bool
 */
function canEditUser($currentUserRoleID, $targetUserRoleID) {
    // Owner có thể sửa tất cả
    if ($currentUserRoleID == ROLE_OWNER) {
        return true;
    }
    
    // Admin chỉ được sửa nhân viên có roleID > 2 (Sales và Support)
    if ($currentUserRoleID == ROLE_ADMIN && $targetUserRoleID > ROLE_ADMIN) {
        return true;
    }
    
    // Các trường hợp khác không được phép
    return false;
}

/**
 * Kiểm tra quyền thêm nhân viên với roleID cụ thể
 * Quy tắc:
 * - Owner có thể thêm tất cả các roleID
 * - Các role khác KHÔNG được thêm nhân viên
 * 
 * @param int $currentUserRoleID - Role ID của người đang thực hiện
 * @param int $newRoleID - Role ID muốn tạo
 * @return bool
 */
function canAddUserWithRole($currentUserRoleID, $newRoleID) {
    // Chỉ Owner mới được thêm nhân viên
    return ($currentUserRoleID == ROLE_OWNER);
}
?>
