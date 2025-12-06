<?php
// Load s? don hàng pending cho badge
require_once __DIR__ . '/../../model/mOrder.php';
$orderModel = new Order();
$pendingOrdersCount = $orderModel->countByStatus('Chờ thanh toán');

// Load s? don m?i thanh toán trong 30 phút g?n dây
$newPaidOrdersCount = 0;
if (hasPermission('view_orders')) {
    $newPaidOrdersCount = $orderModel->countNewPaidOrders(30); // 30 phút
}

// ?? START: LOAD S? LU?NG ÐÁNH GIÁ CH? DUY?T ??
$pendingReviewsCount = 0;
// Ki?m tra quy?n (Gi? d?nh quy?n là 'manage_reviews' ho?c 'view_reviews')
if (hasPermission('view_reviews') || hasPermission('manage_reviews')) {
    require_once __DIR__ . '/../../model/mReview.php'; // Load Review Model
    $reviewModel = new Review();
    // Gi? d?nh tr?ng thái 'Ch? duy?t' là 1
    $pendingReviewsCount = $reviewModel->countByStatus(0); // C?n thêm phuong th?c countByStatus(status) vào mReview.php
}
// ?? END: LOAD S? LU?NG ÐÁNH GIÁ CH? DUY?T ??


// // Load s? tin nh?n chua d?c cho badge
// $unreadMessagesCount = 0;
// if (hasPermission('view_chat') || hasPermission('manage_chat')) {
//     require_once __DIR__ . '/../../model/mChat.php';
//     $chatModel = new Chat();
//     $unreadMessagesCount = $chatModel->countUnreadMessages();
// }
?>
<aside class="w-64 bg-white h-screen shadow-lg fixed left-0 top-0 overflow-y-auto">
    <div class="p-6 border-b border-gray-200">
        <a href="index.php" class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-store text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">GODIFA</h1>
                <p class="text-xs text-gray-500">Admin Panel</p>
            </div>
        </a>
    </div>

    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                <i class="fas <?php echo getRoleIcon($_SESSION['role_id']); ?> text-white"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </p>
                <p class="text-xs text-gray-600 truncate">
                    <?php echo getRoleName($_SESSION['role_id']); ?>
                </p>
            </div>
        </div>
    </div>

    <nav class="p-4 space-y-1">
        <?php if (hasPermission('view_statistics') || hasPermission('view_dashboard')): ?>
        <a href="?page=statistics" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-tachometer-alt w-5"></i>
            <span>Thống kê</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('view_products') || hasPermission('manage_products')): ?>
        <a href="?page=products" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-box w-5"></i>
            <span>Sản phẩm</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('view_categories') || hasPermission('manage_categories')): ?>
        <a href="?page=categories" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-tags w-5"></i>
            <span>Danh mục</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('view_orders')): ?>
        <a href="?page=orders" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-shopping-cart w-5"></i>
            <span>Đơn hàng</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('view_orders') || hasPermission('manage_orders')): ?>
        <?php
        // Đếm số yêu cầu hoàn tiền chờ xử lý
        $pendingRefundsCount = 0;
        $db = Database::getInstance();
        $conn = $db->connect();
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM refund_requests WHERE status = 'Chờ xử lý'");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $pendingRefundsCount = $row['count'];
        }
        ?>
        <a href="?page=refund_requests" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-undo w-5"></i>
            <span>Hoàn tiền</span>
            <?php if ($pendingRefundsCount > 0): ?>
            <span class="ml-auto bg-orange-500 text-white text-xs px-2 py-1 rounded-full font-semibold" title="Yêu cầu hoàn tiền chờ xử lý">
                <?php echo $pendingRefundsCount; ?>
            </span>
            <?php endif; ?>
        </a>
        
        <?php
        // Đếm số yêu cầu hoàn trả chờ xử lý
        $pendingReturnsCount = 0;
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM return_requests WHERE status = 'Chờ xử lý'");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $pendingReturnsCount = $row['count'];
        }
        ?>
        <a href="?page=return_requests" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-box-open w-5"></i>
            <span>Hoàn trả hàng</span>
            <?php if ($pendingReturnsCount > 0): ?>
            <span class="ml-auto bg-purple-500 text-white text-xs px-2 py-1 rounded-full font-semibold" title="Yêu cầu hoàn trả chờ xử lý">
                <?php echo $pendingReturnsCount; ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('view_vouchers') || hasPermission('manage_vouchers')): ?>
        <a href="?page=vouchers" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-ticket-alt w-5"></i>
            <span>Voucher</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('manage_users')): ?>
        <a href="?page=users" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-users w-5"></i>
            <span>Nhân viên</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('view_blog') || hasPermission('manage_blog')): ?>
        <a href="?page=blog" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-blog w-5"></i>
            <span>Bài viết</span>
        </a>
        <?php endif; ?>

        <?php 
        // Chỉ Chủ doanh nghiệp (role_id=1) và Nhân viên CSKH (role_id=4) được xem Chat
        if (isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 4)): 
        ?>
        <a href="?page=chat" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-comments w-5"></i>
            <span>Chat</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('view_customers') || hasPermission('manage_customers')): ?>
        <a href="?page=customers" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-user-friends w-5"></i>
            <span>Khách hàng</span>
        </a>
        <?php endif; ?>
        
        <?php if (hasPermission('view_reviews') || hasPermission('manage_reviews')): ?>
        <a href="?page=reviews" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="fas fa-star-half-alt w-5"></i>
            <span>Đánh giá</span>
            <?php if ($pendingReviewsCount > 0): ?>
            <span class="ml-auto bg-amber-500 text-white text-xs px-2 py-1 rounded-full font-semibold" title="Đánh giá chờ duyệt">
                <?php echo $pendingReviewsCount; ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('view_customer_groups') || hasPermission('full_access')): ?>
        <a href="?page=customer_groups" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 border-l-4 border-transparent hover:border-amber-500">
            <i class="fas fa-users-cog w-5 text-amber-600"></i>
            <span>Nhóm KH</span>
            <?php if ($_SESSION['role_id'] == 1): ?>
            <i class="fas fa-crown text-amber-500 text-xs ml-auto" title="Chỉ Chủ DN được sửa"></i>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <div class="border-t border-gray-200 my-4"></div>

        <a href="<?php echo ADMIN_BASE_URL; ?>logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Đăng xuất</span>
        </a>
    </nav>
</aside>