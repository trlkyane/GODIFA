<?php
/**
 * Quản lý Voucher/Khuyến mãi
 * File: admin/pages/vouchers.php
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission
if (!hasPermission('manage_vouchers') && !hasPermission('view_vouchers')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

// Load Controller (Admin version)
require_once __DIR__ . '/../../controller/admin/cVoucher.php';
require_once __DIR__ . '/../../model/mCustomerGroup.php';
require_once __DIR__ . '/../../model/mVoucherGroup.php';
$voucherController = new cVoucher();
$groupModel = new CustomerGroup();
$voucherGroupModel = new VoucherGroup();

$success = '';
$error = '';

// Xử lý THÊM voucher
if (isset($_POST['add_voucher']) && (hasPermission('create_voucher') || hasPermission('manage_vouchers'))) {
    // DEBUG: Log giá trị POST
    error_log("POST startDate: '" . ($_POST['startDate'] ?? 'NOT SET') . "'");
    error_log("POST endDate: '" . ($_POST['endDate'] ?? 'NOT SET') . "'");
    
    // Validate dates trước - tự động set default nếu rỗng
    $startDate = trim($_POST['startDate'] ?? '');
    $endDate = trim($_POST['endDate'] ?? '');
    
    // Auto-fill nếu rỗng (bao gồm cả whitespace)
    if (empty($startDate) || $startDate === '') {
        $startDate = date('Y-m-d');
        error_log("Auto-filled startDate: $startDate");
    }
    if (empty($endDate) || $endDate === '') {
        $endDate = date('Y-m-d', strtotime('+30 days'));
        error_log("Auto-filled endDate: $endDate");
    }
    
    $validationErrors = [];
    
    // Kiểm tra ngày hợp lệ
    if (!empty($startDate) && strtotime($startDate) === false) {
        $validationErrors[] = "Ngày bắt đầu không hợp lệ!";
    }
    if (!empty($endDate) && strtotime($endDate) === false) {
        $validationErrors[] = "Ngày kết thúc không hợp lệ!";
    }
    
    // Kiểm tra ngày kết thúc phải sau ngày bắt đầu
    if (!empty($startDate) && !empty($endDate) && strtotime($endDate) < strtotime($startDate)) {
        $validationErrors[] = "Ngày kết thúc phải sau ngày bắt đầu!";
    }
    
    if (empty($validationErrors)) {
        $data = [
            'voucherName' => trim($_POST['voucherName']),
            'value' => floatval($_POST['value']),
            'quantity' => intval($_POST['quantity']),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'requirement' => trim($_POST['requirement'] ?? '')
        ];
        
        $result = $voucherController->addVoucher($data);
        
        if ($result['success']) {
            // Lấy ID voucher vừa tạo
            $voucherID = $result['voucherID'];
            
            // Xử lý gán nhóm khách hàng
            if (isset($_POST['groups']) && is_array($_POST['groups'])) {
                $groupIDs = array_map('intval', $_POST['groups']);
                $voucherGroupModel->assignVoucherToMultipleGroups($voucherID, $groupIDs);
            }
            
            // Redirect để reload dữ liệu mới từ database
            echo "<script>
                alert('Thêm voucher thành công!');
                window.location.href = '?page=vouchers';
            </script>";
            exit();
        } else {
            $error = implode('<br>', $result['errors']);
        }
    } else {
        $error = implode('<br>', $validationErrors);
    }
}

// Xử lý SỬA voucher
if (isset($_POST['edit_voucher']) && hasPermission('manage_vouchers')) {
    $voucherID = intval($_POST['voucherID']);
    
    // Validate dates trước - tự động set default nếu rỗng
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    
    // Auto-fill nếu rỗng
    if (empty($startDate)) {
        $startDate = date('Y-m-d');
    }
    if (empty($endDate)) {
        $endDate = date('Y-m-d', strtotime('+30 days'));
    }
    
    $validationErrors = [];
    
    // Kiểm tra ngày hợp lệ
    if (!empty($startDate) && strtotime($startDate) === false) {
        $validationErrors[] = "Ngày bắt đầu không hợp lệ!";
    }
    if (!empty($endDate) && strtotime($endDate) === false) {
        $validationErrors[] = "Ngày kết thúc không hợp lệ!";
    }
    
    // Kiểm tra ngày kết thúc phải sau ngày bắt đầu
    if (!empty($startDate) && !empty($endDate) && strtotime($endDate) < strtotime($startDate)) {
        $validationErrors[] = "Ngày kết thúc phải sau ngày bắt đầu!";
    }
    
    if (empty($validationErrors)) {
        $data = [
            'voucherName' => trim($_POST['voucherName']),
            'value' => floatval($_POST['value']),
            'quantity' => intval($_POST['quantity']),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'requirement' => trim($_POST['requirement'] ?? '')
        ];
        
        $result = $voucherController->updateVoucher($voucherID, $data);
        
        if ($result['success']) {
            // Cập nhật nhóm khách hàng cho voucher
            // Xóa tất cả nhóm cũ trước
            $voucherGroupModel->removeAllGroupsFromVoucher($voucherID);
            
            // Gán lại các nhóm mới được chọn
            if (isset($_POST['groups']) && is_array($_POST['groups']) && count($_POST['groups']) > 0) {
                $voucherGroupModel->assignVoucherToMultipleGroups($voucherID, $_POST['groups']);
            }
            
            $success = $result['message'];
            // Redirect để reload dữ liệu mới từ database
            echo "<script>
                alert('Cập nhật voucher thành công!');
                window.location.href = '?page=vouchers';
            </script>";
            exit();
        } else {
            $error = implode('<br>', $result['errors']);
        }
    } else {
        $error = implode('<br>', $validationErrors);
    }
}

// Xử lý TOGGLE STATUS voucher (khóa/mở khóa)
if (isset($_GET['toggle']) && hasPermission('manage_vouchers')) {
    $voucherID = intval($_GET['toggle']);
    $result = $voucherController->toggleStatus($voucherID);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý TÌM KIẾM
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

// Lấy danh sách vouchers
if ($searchKeyword !== '') {
    $vouchers = $voucherController->searchVouchers($searchKeyword, $filterStatus);
} else {
    $vouchers = $voucherController->getAllVouchers($filterStatus);
}

// Thêm thông tin nhóm khách hàng cho mỗi voucher
foreach ($vouchers as &$voucher) {
    $assignedGroups = $voucherGroupModel->getGroupIDsByVoucher($voucher['voucherID']);
    $voucher['assignedGroups'] = $assignedGroups;
    
    // Lấy thông tin chi tiết các nhóm (để hiển thị badge)
    $voucher['groupDetails'] = $voucherGroupModel->getGroupsByVoucher($voucher['voucherID']);
}
unset($voucher); // Break reference

$today = date('Y-m-d');

$pageTitle = 'Quản lý Voucher';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto ml-64">
        <!-- Header -->
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-4 md:px-6 py-4 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                        <i class="fas fa-ticket-alt text-orange-500 mr-2"></i>
                        Quản lý Voucher/Khuyến mãi
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng số: <strong><?php echo count($vouchers); ?></strong> voucher
                    </p>
                </div>
                <?php if (hasPermission('create_voucher') || hasPermission('manage_vouchers')): ?>
                <button onclick="openAddModal()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg inline-flex items-center text-sm md:text-base">
                    <i class="fas fa-plus mr-2"></i>Tạo voucher mới
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 md:p-6">
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Search & Filter -->
            <div class="bg-white rounded-lg shadow p-4 mb-4">
                <form method="GET" action="" class="flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="page" value="vouchers">
                    
                    <!-- Search Input -->
                    <div class="flex-1 min-w-[250px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-search mr-1"></i>Tìm kiếm
                        </label>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($searchKeyword); ?>"
                            placeholder="Nhập tên voucher..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Status Filter -->
                    <div class="min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-filter mr-1"></i>Trạng thái
                        </label>
                        <select 
                            name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                        >
                            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                            <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Đang hoạt động</option>
                            <option value="expired" <?php echo $filterStatus === 'expired' ? 'selected' : ''; ?>>Hết hạn</option>
                            <option value="out_of_stock" <?php echo $filterStatus === 'out_of_stock' ? 'selected' : ''; ?>>Hết số lượng</option>
                            <option value="locked" <?php echo $filterStatus === 'locked' ? 'selected' : ''; ?>>Bị khóa</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-2">
                        <button 
                            type="submit" 
                            class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg inline-flex items-center"
                        >
                            <i class="fas fa-search mr-2"></i>Tìm kiếm
                        </button>
                    </div>
                </form>
                
                <!-- Search Results Info -->
                <?php if ($searchKeyword !== '' || $filterStatus !== 'all'): ?>
                <div class="mt-3 text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Tìm thấy <strong><?php echo count($vouchers); ?></strong> kết quả
                    <?php if ($searchKeyword !== ''): ?>
                        cho từ khóa "<strong><?php echo htmlspecialchars($searchKeyword); ?></strong>"
                    <?php endif; ?>
                    <?php if ($filterStatus !== 'all'): ?>
                        với trạng thái "<strong><?php 
                            $statusLabels = [
                                'active' => 'Đang hoạt động',
                                'expired' => 'Hết hạn',
                                'out_of_stock' => 'Hết số lượng',
                                'locked' => 'Bị khóa'
                            ];
                            echo $statusLabels[$filterStatus] ?? $filterStatus;
                        ?></strong>"
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Vouchers Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Tên voucher</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Giá trị</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Số lượng</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Thời hạn</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Trạng thái</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($vouchers)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-ticket-alt text-4xl mb-2 text-gray-300"></i>
                                    <p>Chưa có voucher nào</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($vouchers as $voucher): ?>
                                <?php
                                // Safe access
                                $voucherID = $voucher['voucherID'] ?? 0;
                                $voucherName = $voucher['voucherName'] ?? 'N/A';
                                $value = $voucher['value'] ?? 0;
                                $quantity = $voucher['quantity'] ?? 0;
                                $startDate = $voucher['startDate'] ?? '';
                                $endDate = $voucher['endDate'] ?? '';
                                $requirement = $voucher['requirement'] ?? '';
                                $userName = $voucher['userName'] ?? 'N/A';
                                $status = $voucher['status'] ?? 1;
                                
                                // Kiểm tra ngày hợp lệ
                                $validStartDate = !empty($startDate) && $startDate != '0000-00-00' && strtotime($startDate) !== false;
                                $validEndDate = !empty($endDate) && $endDate != '0000-00-00' && strtotime($endDate) !== false;
                                
                                // Kiểm tra trạng thái thời gian (chỉ khi ngày hợp lệ)
                                $isActive = false;
                                $isExpired = false;
                                $isUpcoming = false;
                                
                                if ($validStartDate && $validEndDate) {
                                    $isActive = ($startDate <= $today && $endDate >= $today && $quantity > 0 && $status == 1);
                                    $isExpired = ($endDate < $today);
                                    $isUpcoming = ($startDate > $today);
                                }
                                
                                $isOutOfStock = ($quantity <= 0);
                                $isLocked = ($status == 0);
                                $isInvalidDate = (!$validStartDate || !$validEndDate);
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($voucherName); ?></div>
                                        
                                        <!-- Hiển thị nhóm khách hàng được áp dụng -->
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <?php if (empty($voucher['groupDetails'])): ?>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs inline-flex items-center">
                                                <i class="fas fa-globe mr-1"></i>
                                                Voucher công khai
                                            </span>
                                            <?php else: ?>
                                                <?php foreach ($voucher['groupDetails'] as $group): ?>
                                                <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs inline-flex items-center">
                                                    <i class="fas fa-users mr-1"></i>
                                                    <?php echo htmlspecialchars($group['groupName']); ?>
                                                </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($requirement): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            <?php echo htmlspecialchars(mb_substr($requirement, 0, 50)); ?>...
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-lg font-bold text-orange-600">
                                            <?php echo number_format($value, 0, ',', '.'); ?>₫
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-3 py-1 <?php echo $quantity > 0 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'; ?> rounded-full text-xs font-semibold">
                                            <?php echo $quantity; ?> voucher
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        <?php 
                                        // Kiểm tra và xử lý ngày hợp lệ
                                        $validStartDate = !empty($startDate) && $startDate != '0000-00-00' && strtotime($startDate) !== false;
                                        $validEndDate = !empty($endDate) && $endDate != '0000-00-00' && strtotime($endDate) !== false;
                                        ?>
                                        
                                        <?php if ($validStartDate): ?>
                                        <div class="text-gray-600">
                                            <i class="far fa-calendar mr-1"></i>
                                            <?php echo date('d/m/Y', strtotime($startDate)); ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="text-red-500 text-xs">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Ngày không hợp lệ
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-gray-400 text-xs">đến</div>
                                        
                                        <?php if ($validEndDate): ?>
                                        <div class="text-gray-600">
                                            <i class="far fa-calendar mr-1"></i>
                                            <?php echo date('d/m/Y', strtotime($endDate)); ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="text-red-500 text-xs">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Ngày không hợp lệ
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($isInvalidDate): ?>
                                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>Ngày không hợp lệ
                                        </span>
                                        <?php elseif ($isLocked): ?>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <i class="fas fa-lock mr-1"></i>Đã khóa
                                        </span>
                                        <?php elseif ($isOutOfStock): ?>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <i class="fas fa-ban mr-1"></i>Hết voucher
                                        </span>
                                        <?php elseif ($isExpired): ?>
                                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <i class="fas fa-times-circle mr-1"></i>Hết hạn
                                        </span>
                                        <?php elseif ($isUpcoming): ?>
                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <i class="fas fa-clock mr-1"></i>Sắp diễn ra
                                        </span>
                                        <?php elseif ($isActive): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium inline-flex items-center">
                                            <i class="fas fa-check-circle mr-1"></i>Đang hoạt động
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center items-center space-x-2">
                                            <?php if (hasPermission('manage_vouchers')): ?>
                                            <!-- Sửa (chỉ Owner/Admin) -->
                                            <button onclick='openEditModal(<?php echo json_encode($voucher, JSON_HEX_APOS); ?>)' 
                                                    class="text-blue-600 hover:text-blue-800" title="Sửa">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>
                                            
                                            <!-- Khóa/Mở khóa (chỉ Owner/Admin) -->
                                            <?php if ($status == 1): ?>
                                            <button onclick="toggleVoucherStatus(<?php echo $voucherID; ?>, '<?php echo htmlspecialchars($voucherName); ?>', 0)" 
                                                    class="text-orange-600 hover:text-orange-800" title="Khóa voucher">
                                                <i class="fas fa-lock text-lg"></i>
                                            </button>
                                            <?php else: ?>
                                            <button onclick="toggleVoucherStatus(<?php echo $voucherID; ?>, '<?php echo htmlspecialchars($voucherName); ?>', 1)" 
                                                    class="text-green-600 hover:text-green-800" title="Mở khóa voucher">
                                                <i class="fas fa-unlock text-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal THÊM voucher -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-orange-500 text-white px-6 py-4 rounded-t-lg">
            <h3 class="text-xl font-bold">
                <i class="fas fa-ticket-alt mr-2"></i>Tạo voucher mới
            </h3>
        </div>
        
        <form method="POST" class="p-6" onsubmit="return validateAddDates()">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên voucher <span class="text-red-500">*</span></label>
                    <input type="text" name="voucherName" required
                           placeholder="VD: Giảm 50K cho đơn từ 500K"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giá trị giảm (VNĐ) <span class="text-red-500">*</span></label>
                    <input type="number" name="value" required min="1000" step="1000"
                           placeholder="50000"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" required min="1"
                           placeholder="100"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày bắt đầu <span class="text-red-500">*</span></label>
                    <input type="date" id="add_startDate" name="startDate" required 
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo date('Y-m-d'); ?>"
                           onchange="validateAddDates()"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                    <p id="add_startDate_error" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày kết thúc <span class="text-red-500">*</span></label>
                    <input type="date" id="add_endDate" name="endDate" required 
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                           onchange="validateAddDates()"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                    <p id="add_endDate_error" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Điều kiện áp dụng</label>
                    <textarea name="requirement" rows="3"
                              placeholder="VD: Áp dụng cho đơn hàng từ 500.000đ"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                
                <!-- Phần chọn nhóm khách hàng -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-users text-indigo-500 mr-1"></i>
                        Áp dụng cho nhóm khách hàng
                    </label>
                    <div class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3">
                        <?php 
                        $allGroups = $groupModel->getAllGroups();
                        foreach ($allGroups as $group): 
                        ?>
                        <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                            <input type="checkbox" name="groups[]" value="<?php echo $group['groupID']; ?>" 
                                   class="mr-3 w-4 h-4 text-indigo-600">
                            <i class="fas fa-users mr-2 text-indigo-600"></i>
                            <span class="flex-1 font-medium"><?php echo htmlspecialchars($group['groupName']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle text-blue-500"></i> 
                        <strong>Không chọn nhóm nào</strong> = Voucher công khai (tất cả khách đều thấy). 
                        Chọn nhóm = Chỉ nhóm đó mới thấy voucher này.
                    </p>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeAddModal()" 
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">
                    Hủy
                </button>
                <button type="submit" name="add_voucher" 
                        class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Tạo voucher
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal SỬA voucher -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-blue-500 text-white px-6 py-4 rounded-t-lg">
            <h3 class="text-xl font-bold">
                <i class="fas fa-edit mr-2"></i>Sửa voucher
            </h3>
        </div>
        
        <form method="POST" class="p-6" onsubmit="return validateEditDates()">
            <input type="hidden" name="voucherID" id="edit_voucherID">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên voucher <span class="text-red-500">*</span></label>
                    <input type="text" name="voucherName" id="edit_voucherName" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giá trị giảm (VNĐ) <span class="text-red-500">*</span></label>
                    <input type="number" name="value" id="edit_value" required min="1000" step="1000"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" id="edit_quantity" required min="0"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày bắt đầu <span class="text-red-500">*</span></label>
                    <input type="date" name="startDate" id="edit_startDate" required
                           onchange="validateEditDates()"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p id="edit_startDate_error" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngày kết thúc <span class="text-red-500">*</span></label>
                    <input type="date" name="endDate" id="edit_endDate" required
                           onchange="validateEditDates()"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p id="edit_endDate_error" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Điều kiện áp dụng</label>
                    <textarea name="requirement" id="edit_requirement" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <!-- Phần chọn nhóm khách hàng -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-users text-indigo-500 mr-1"></i>
                        Áp dụng cho nhóm khách hàng
                    </label>
                    <div id="edit_groups_container" class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3">
                        <?php 
                        foreach ($allGroups as $group): 
                        ?>
                        <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                            <input type="checkbox" name="groups[]" value="<?php echo $group['groupID']; ?>" 
                                   class="mr-3 w-4 h-4 text-blue-600 edit-group-checkbox"
                                   data-group-id="<?php echo $group['groupID']; ?>">
                            <i class="fas fa-users mr-2 text-blue-600"></i>
                            <span class="flex-1 font-medium"><?php echo htmlspecialchars($group['groupName']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle text-blue-500"></i> 
                        Không chọn nhóm nào = Voucher công khai.
                    </p>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">
                    Hủy
                </button>
                <button type="submit" name="edit_voucher" 
                        class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Date validation functions
function validateAddDates() {
    const startDateInput = document.getElementById('add_startDate');
    const endDateInput = document.getElementById('add_endDate');
    const today = new Date().toISOString().split('T')[0];
    const next30Days = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    const startError = document.getElementById('add_startDate_error');
    const endError = document.getElementById('add_endDate_error');
    
    // Reset errors
    if (startError) startError.classList.add('hidden');
    if (endError) endError.classList.add('hidden');
    
    // Auto-fill nếu rỗng
    if (!startDateInput.value || startDateInput.value.trim() === '') {
        startDateInput.value = today;
        console.log('Auto-filled startDate:', today);
    }
    
    if (!endDateInput.value || endDateInput.value.trim() === '') {
        endDateInput.value = next30Days;
        console.log('Auto-filled endDate:', next30Days);
    }
    
    // LẤY LẠI giá trị SAU KHI SET
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;
    
    console.log('Final startDate:', startDate);
    console.log('Final endDate:', endDate);
    
    // Check if start date is before end date
    if (startDate && endDate && startDate > endDate) {
        if (endError) {
            endError.textContent = 'Ngày kết thúc phải sau ngày bắt đầu';
            endError.classList.remove('hidden');
        }
        alert('Lỗi: Ngày kết thúc phải sau ngày bắt đầu!');
        return false;
    }
    
    // Update min date for end date
    if (startDate) {
        document.getElementById('add_endDate').setAttribute('min', startDate);
    }
    
    // ALWAYS return true để cho form submit (PHP sẽ validate lại)
    return true;
}

function validateEditDates() {
    const startDate = document.getElementById('edit_startDate').value;
    const endDate = document.getElementById('edit_endDate').value;
    
    const startError = document.getElementById('edit_startDate_error');
    const endError = document.getElementById('edit_endDate_error');
    
    let isValid = true;
    
    // Reset errors
    startError.classList.add('hidden');
    endError.classList.add('hidden');
    
    // Check if dates are filled
    if (!startDate || startDate === '0000-00-00') {
        startError.textContent = 'Vui lòng chọn ngày bắt đầu hợp lệ';
        startError.classList.remove('hidden');
        isValid = false;
    }
    
    if (!endDate || endDate === '0000-00-00') {
        endError.textContent = 'Vui lòng chọn ngày kết thúc hợp lệ';
        endError.classList.remove('hidden');
        isValid = false;
    }
    
    // Check if start date is before end date
    if (startDate && endDate && startDate !== '0000-00-00' && endDate !== '0000-00-00' && startDate > endDate) {
        endError.textContent = 'Ngày kết thúc phải sau ngày bắt đầu';
        endError.classList.remove('hidden');
        isValid = false;
    }
    
    // Update min date for end date
    if (startDate && startDate !== '0000-00-00') {
        document.getElementById('edit_endDate').setAttribute('min', startDate);
    }
    
    return isValid;
}

// Modal functions
function openAddModal() {
    // Set default dates nếu chưa có
    const today = new Date().toISOString().split('T')[0];
    const next30Days = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    const startDateInput = document.getElementById('add_startDate');
    const endDateInput = document.getElementById('add_endDate');
    
    // Đảm bảo luôn có giá trị
    if (!startDateInput.value || startDateInput.value.trim() === '') {
        startDateInput.value = today;
    }
    if (!endDateInput.value || endDateInput.value.trim() === '') {
        endDateInput.value = next30Days;
    }
    
    document.getElementById('addModal').classList.remove('hidden');
    // Validate on open
    validateAddDates();
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(voucher) {
    document.getElementById('edit_voucherID').value = voucher.voucherID;
    document.getElementById('edit_voucherName').value = voucher.voucherName;
    document.getElementById('edit_value').value = voucher.value;
    document.getElementById('edit_quantity').value = voucher.quantity;
    
    // Xử lý ngày không hợp lệ (0000-00-00 hoặc rỗng)
    const today = new Date().toISOString().split('T')[0];
    const startDate = voucher.startDate && voucher.startDate !== '0000-00-00' ? voucher.startDate : today;
    const endDate = voucher.endDate && voucher.endDate !== '0000-00-00' ? voucher.endDate : today;
    
    document.getElementById('edit_startDate').value = startDate;
    document.getElementById('edit_endDate').value = endDate;
    document.getElementById('edit_requirement').value = voucher.requirement || '';
    
    // Load existing group assignments
    // First, uncheck all checkboxes
    document.querySelectorAll('.edit-group-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Then check the assigned groups
    if (voucher.assignedGroups && Array.isArray(voucher.assignedGroups)) {
        voucher.assignedGroups.forEach(groupID => {
            const checkbox = document.querySelector(`.edit-group-checkbox[data-group-id="${groupID}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
    
    document.getElementById('editModal').classList.remove('hidden');
    
    // Không validate ngay khi mở modal nếu ngày cũ không hợp lệ
    // Cho phép user chọn ngày mới
    if (voucher.startDate && voucher.startDate !== '0000-00-00') {
        validateEditDates();
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function toggleVoucherStatus(id, name, newStatus) {
    const action = newStatus === 0 ? 'KHÓA' : 'MỞ KHÓA';
    const message = newStatus === 0 
        ? `Bạn có chắc muốn KHÓA voucher "${name}"?\n\nVoucher sẽ tạm ngừng sử dụng cho đến khi mở khóa lại.`
        : `Bạn có chắc muốn MỞ KHÓA voucher "${name}"?\n\nVoucher sẽ được kích hoạt lại.`;
    
    if (confirm(message)) {
        window.location.href = `?page=vouchers&toggle=${id}`;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    if (event.target == addModal) closeAddModal();
    if (event.target == editModal) closeEditModal();
}

// Force set default dates on page load
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const next30Days = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    const startInput = document.getElementById('add_startDate');
    const endInput = document.getElementById('add_endDate');
    
    if (startInput && (!startInput.value || startInput.value === '')) {
        startInput.value = today;
    }
    if (endInput && (!endInput.value || endInput.value === '')) {
        endInput.value = next30Days;
    }
});
</script>

<style>
/* Table responsive */
table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    word-wrap: break-word;
    overflow-wrap: break-word;
    vertical-align: middle;
}

/* Action buttons */
table td button {
    padding: 0.25rem;
    transition: all 0.2s ease;
}

table td button:hover {
    transform: scale(1.2);
}

/* Modal animations */
#addModal, #editModal {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Responsive */
@media (max-width: 768px) {
    table {
        font-size: 0.875rem;
    }
    
    .overflow-x-auto {
        overflow-x: auto;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
