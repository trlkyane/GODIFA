<?php
require_once __DIR__ . '/middleware/auth.php';

// Yêu cầu đăng nhập với vai trò nhân viên
requireStaff();

// Router - Xử lý các trang con
$requestedPage = isset($_GET['page']) ? $_GET['page'] : 'statistics';

// Danh sách pages hợp lệ
$validPages = [
    'statistics' => 'pages/statistics.php',
    'products' => 'pages/products.php',
    'categories' => 'pages/categories.php',
    'orders' => 'pages/orders.php',
    'users' => 'pages/users.php',
    'customers' => 'pages/customers.php',
    'customer_groups' => 'pages/customer_groups.php',
    'auto_assign_groups' => 'pages/auto_assign_groups.php',
    'vouchers' => 'pages/vouchers.php',
    'chat' => 'pages/chat.php',
    'blog' => 'pages/blog.php',
];

// Nếu request page hợp lệ và file tồn tại
if (array_key_exists($requestedPage, $validPages)) {
    $pageFile = __DIR__ . '/' . $validPages[$requestedPage];
    if (file_exists($pageFile)) {
        include $pageFile;
        exit; // Stop execution sau khi include page
    }
}

// Nếu không có page hoặc page không tồn tại, hiển thị statistics mặc định
include __DIR__ . '/pages/statistics.php';
