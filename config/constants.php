<?php
/**
 * Constants & Configuration
 * File: config/constants.php
 * 
 * Định nghĩa các hằng số sử dụng trong toàn project
 */

// ==================== ROLE CONSTANTS ====================
define('ROLE_CUSTOMER', 0);        // Khách hàng
define('ROLE_OWNER', 1);           // Chủ Doanh Nghiệp
define('ROLE_ADMIN', 2);           // Quản Trị Viên
define('ROLE_SALES', 3);           // Nhân Viên Bán Hàng
define('ROLE_SUPPORT', 4);         // Nhân Viên CSKH

// ==================== STATUS CONSTANTS ====================
// Order Payment Status
define('PAYMENT_PENDING', 'Chờ thanh toán');
define('PAYMENT_PENDING_COD', 'Chờ thanh toán (COD)');
define('PAYMENT_PAID', 'Đã thanh toán');
define('PAYMENT_CANCELLED', 'Đã hủy');

// Order Delivery Status
define('DELIVERY_PENDING', 'Chờ xác nhận');
define('DELIVERY_PROCESSING', 'Đang tiến hành vận chuyển');
define('DELIVERY_COMPLETED', 'Hoàn thành');
define('DELIVERY_CANCELLED', 'Đã hủy');

// Product Status
define('PRODUCT_ACTIVE', 1);
define('PRODUCT_INACTIVE', 0);

// User Status
define('USER_ACTIVE', '1');
define('USER_INACTIVE', '0');

// ==================== PAYMENT METHODS ====================
define('PAYMENT_METHOD_QR', 'QR');
define('PAYMENT_METHOD_COD', 'COD');
define('PAYMENT_METHOD_BANK', 'Bank');

// ==================== SESSION NAMES ====================
define('SESSION_ADMIN', 'GODIFA_ADMIN_SESSION');
define('SESSION_USER', 'GODIFA_USER_SESSION');

// ==================== DATE FORMATS ====================
define('DATE_FORMAT_DB', 'Y-m-d H:i:s');
define('DATE_FORMAT_DISPLAY', 'd/m/Y H:i');
define('DATE_FORMAT_SHORT', 'd/m/Y');

// ==================== PATHS ====================
define('BASE_PATH', __DIR__ . '/..');
define('UPLOAD_PATH', BASE_PATH . '/image/');
define('LOG_PATH', BASE_PATH . '/logs/');

// ==================== PAGINATION ====================
define('ITEMS_PER_PAGE', 20);
define('PRODUCTS_PER_PAGE', 12);

// ==================== GHN SERVICE TYPES ====================
define('GHN_SERVICE_STANDARD', 2);
define('GHN_SERVICE_EXPRESS', 5);

// ==================== CUSTOMER GROUPS ====================
define('GROUP_NEW', 1);           // Khách hàng mới
define('GROUP_BRONZE', 2);        // Đồng
define('GROUP_SILVER', 3);        // Bạc
define('GROUP_GOLD', 4);          // Vàng
define('GROUP_PLATINUM', 5);      // Bạch Kim

// ==================== VOUCHER TYPES ====================
define('VOUCHER_PERCENT', 'percent');
define('VOUCHER_FIXED', 'fixed');

// ==================== QR CODE EXPIRY ====================
define('QR_EXPIRY_MINUTES', 15);

?>
