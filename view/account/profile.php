<?php
/**
 * Trang thông tin cá nhân
 * File: view/profile.php
 */

// Ki?m tra dang nh?p
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: ' . BASE_URL . 'view/auth/customer-login.php');
    exit;
}

require_once __DIR__ . '/../../controller/cProfile.php';

$customerID = $_SESSION['customer_id'];
$successMessage = '';
$errorMessage = '';

$profileController = new ProfileController();

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (strlen($newPassword) < 6) {
        $errorMessage = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'Mật khẩu xác nhận không khớp!';
    } else {
        $result = $profileController->changePassword($customerID, $oldPassword, $newPassword);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['message'];
        }
    }
}

// Xử lý cập nhật thông tin (CHỈ TÊN VÀ SỐ ĐIỆN THOẠI)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'customerName' => trim($_POST['customerName'] ?? ''),
        'phone' => trim($_POST['phone'] ?? '')
    ];
    
    if (empty($data['customerName']) || empty($data['phone'])) {
        $errorMessage = 'Vui lòng điền đầy đủ họ tên và số điện thoại!';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $data['phone'])) {
        $errorMessage = 'Số điện thoại không hợp lệ (10-11 chữ số)!';
    } else {
        $result = $profileController->updateCustomerBasicInfo($customerID, $data);
        if ($result) {
            $successMessage = 'Cập nhật thông tin thành công!';
            $_SESSION['customer_name'] = $data['customerName'];
        } else {
            $errorMessage = 'Có lỗi xảy ra. Vui lòng thử lại!';
        }
    }
}

// Lấy thông tin khách hàng
$customer = $profileController->getCustomerInfo($customerID);

if (!$customer) {
    header('Location: ' . BASE_URL . 'view/auth/logout.php');
    exit;
}

// Thống kê đơn hàng
$stats = $profileController->getOrderStats($customerID);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Cá Nhân - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <?php include __DIR__ . '/../layout/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">👤 Thông Tin Cá Nhân</h1>
            <p class="text-gray-600 mt-2">Quản lý thông tin tài khoản của bạn</p>
        </div>

        <!-- Messages -->
        <?php if ($successMessage): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <span><?= htmlspecialchars($successMessage) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
            <span><?= htmlspecialchars($errorMessage) ?></span>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Sidebar -->
            <div class="lg:col-span-1">
                <!-- Profile Card -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="text-center">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 mx-auto mb-4 flex items-center justify-center text-white text-3xl font-bold">
                            <?= strtoupper(mb_substr($customer['customerName'], 0, 2)) ?>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($customer['customerName']) ?></h2>
                        <p class="text-gray-600 mt-1"><?= htmlspecialchars($customer['email']) ?></p>
                    </div>
                </div>

                <!-- Stats Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar mr-2 text-indigo-600"></i>Thống Kê
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-shopping-bag text-blue-600 text-xl mr-3"></i>
                                <span class="text-gray-700">Tổng đơn hàng</span>
                            </div>
                            <span class="font-bold text-blue-600 text-xl"><?= $stats['totalOrders'] ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                                <span class="text-gray-700">Đã thanh toán</span>
                            </div>
                            <span class="font-bold text-green-600 text-xl"><?= $stats['paidOrders'] ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-truck text-purple-600 text-xl mr-3"></i>
                                <span class="text-gray-700">Đã giao</span>
                            </div>
                            <span class="font-bold text-purple-600 text-xl"><?= $stats['deliveredOrders'] ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-coins text-yellow-600 text-xl mr-3"></i>
                                <span class="text-gray-700">Tổng chi tiêu</span>
                            </div>
                            <span class="font-bold text-yellow-600"><?= number_format($stats['totalSpent'], 0, ',', '.') ?>₫</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-link mr-2 text-indigo-600"></i>Liên Kết Nhanh
                    </h3>
                    <div class="space-y-2">
                        <a href="<?php echo BASE_URL; ?>view/account/order_history.php" class="flex items-center px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                            <i class="fas fa-history text-indigo-600 mr-3"></i>
                            <span class="font-semibold text-gray-700">Lịch Sử Đơn Hàng</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>view/cart/viewcart.php" class="flex items-center px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                            <i class="fas fa-shopping-cart text-indigo-600 mr-3"></i>
                            <span class="font-semibold text-gray-700">Giỏ Hàng</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>view/product/list.php" class="flex items-center px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition">
                            <i class="fas fa-store text-indigo-600 mr-3"></i>
                            <span class="font-semibold text-gray-700">Mua Sắm</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Account Info Form -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-user-edit mr-2 text-indigo-600"></i>Thông Tin Tài Khoản
                    </h2>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-user mr-2 text-gray-500"></i>Họ và Tên <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="customerName" value="<?= htmlspecialchars($customer['customerName']) ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-2 text-gray-500"></i>Số Điện Thoại <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>"
                                       pattern="0[0-9]{9,10}"
                                       maxlength="11"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       title="Số điện thoại phải bắt đầu bằng số 0 và có 10-11 chữ số"
                                       placeholder="0987654321"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle"></i> Phải bắt đầu bằng số 0 và có 10-11 chữ số
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-2 text-gray-500"></i>Email 
                                <span class="text-gray-500 text-xs">(Không thể thay đổi)</span>
                            </label>
                            <input type="email" value="<?= htmlspecialchars($customer['email']) ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed text-gray-600"
                                   readonly>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" name="update_profile"
                                    class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition font-semibold shadow-md">
                                <i class="fas fa-save mr-2"></i>Lưu Thay Đổi
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-lock mr-2 text-indigo-600"></i>Đổi Mật Khẩu
                    </h2>
                    
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-key mr-2 text-gray-500"></i>Mật khẩu cũ <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="old_password"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="Nhập mật khẩu hiện tại"
                                   required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2 text-gray-500"></i>Mật khẩu mới <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="new_password" id="new_password"
                                       minlength="6"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       placeholder="Ít nhất 6 ký tự"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-info-circle"></i> Tối thiểu 6 ký tự
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-2 text-gray-500"></i>Xác nhận mật khẩu mới <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="confirm_password" id="confirm_password"
                                       minlength="6"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       placeholder="Nhập lại mật khẩu mới"
                                       required>
                                <p class="text-xs text-red-500 mt-1 hidden" id="password-match-error">
                                    <i class="fas fa-exclamation-circle"></i> Mật khẩu không khớp!
                                </p>
                            </div>
                        </div>

                        <div>
                            <button type="submit" name="change_password"
                                    class="w-full bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition font-semibold shadow-md">
                                <i class="fas fa-shield-alt mr-2"></i>Đổi Mật Khẩu
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Customer Group Information -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-star mr-2 text-yellow-600"></i>Nhóm Khách Hàng
                    </h2>
                    
                    <?php if (!empty($customer['groupName'])): ?>
                    <div class="rounded-lg p-6 border-2" style="background: linear-gradient(135deg, <?= htmlspecialchars($customer['groupColor'] ?? '#f59e0b') ?>15, <?= htmlspecialchars($customer['groupColor'] ?? '#f59e0b') ?>05); border-color: <?= htmlspecialchars($customer['groupColor'] ?? '#f59e0b') ?>;">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-600 mb-2">Cấp độ hiện tại</p>
                                <p class="text-3xl font-bold" style="color: <?= htmlspecialchars($customer['groupColor'] ?? '#ca8a04') ?>;">
                                    <i class="fas fa-crown mr-2"></i><?= htmlspecialchars($customer['groupName']) ?>
                                </p>
                                <?php if (!empty($customer['groupDescription'])): ?>
                                <p class="text-sm text-gray-600 mt-2">
                                    <?= htmlspecialchars($customer['groupDescription']) ?>
                                </p>
                                <?php endif; ?>
                                
                                <?php if ($customer['groupMaxSpent'] > 0): ?>
                                <div class="mt-4 p-3 bg-white rounded-lg shadow-sm">
                                    <p class="text-xs text-gray-500 mb-1">Ngưỡng chi tiêu của nhóm</p>
                                    <p class="text-lg font-bold" style="color: <?= htmlspecialchars($customer['groupColor'] ?? '#ca8a04') ?>;">
                                        <?= number_format($customer['groupMinSpent']) ?>₫ - <?= number_format($customer['groupMaxSpent']) ?>₫
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-6xl ml-6" style="color: <?= htmlspecialchars($customer['groupColor'] ?? '#f59e0b') ?>;">
                                <i class="fas fa-crown"></i>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-2">Cấp độ hiện tại</p>
                                <p class="text-2xl font-bold text-gray-500">
                                    <i class="fas fa-user mr-2"></i>Chưa xếp hạng
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    Bạn chưa được xếp vào nhóm khách hàng nào. Hãy mua sắm để nhận ưu đãi!
                                </p>
                            </div>
                            <div class="text-6xl text-gray-300">
                                <i class="fas fa-user-circle"></i>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/../layout/footer.php'; ?>

    <script>
        // Check password match
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const errorMsg = document.getElementById('password-match-error');
        
        function checkPasswordMatch() {
            if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                errorMsg.classList.remove('hidden');
                confirmPassword.setCustomValidity('Mật khẩu không khớp');
            } else {
                errorMsg.classList.add('hidden');
                confirmPassword.setCustomValidity('');
            }
        }
        
        if (newPassword && confirmPassword) {
            newPassword.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }
        
        // Confirm delete account
        function confirmDeleteAccount() {
            if (confirm('Bạn có chắc chắn muốn xóa tài khoản? Hành động này không thể hoàn tác!')) {
                if (confirm('Xác nhận lần cuối: Tất cả dữ liệu của bạn sẽ bị xóa vĩnh viễn!')) {
                    // TODO: Implement delete account API
                    alert('Tính năng này đang được phát triển.');
                }
            }
        }
    </script>

</body>
</html>
