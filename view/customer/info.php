<?php 
// File: /GODIFA/view/customer/info.php
include_once(__DIR__ . '/../layout/header.php');

// Kiểm tra xem có đang ở chế độ chỉnh sửa không
$isEditing = isset($_GET['edit']) && $_GET['edit'] == '1';
?>

<div class="max-w-2xl mx-auto p-4 md:p-8 bg-white rounded-xl shadow-lg my-10">
    <h1 class="text-3xl font-bold mb-8 text-blue-600 border-b pb-3">
        <i class="fas fa-user-circle mr-2"></i> 
        <?php echo $isEditing ? 'Cập Nhật Thông Tin' : 'Thông Tin Tài Khoản'; ?>
    </h1>
    
    <!-- Hiển thị cấp độ khách hàng -->
    <?php if (isset($group) && $group): ?>
    <div class="mb-8 p-6 rounded-xl shadow-md" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($group['color'] ?? '#f3f4f6'); ?>15, <?php echo htmlspecialchars($group['color'] ?? '#f3f4f6'); ?>05);">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Cấp độ thành viên</p>
                <p class="text-2xl font-bold" style="color: <?php echo htmlspecialchars($group['color'] ?? '#374151'); ?>">
                    <i class="fas fa-crown mr-2"></i><?php echo htmlspecialchars($group['groupName'] ?? 'Chưa xếp hạng'); ?>
                </p>
                <?php if (!empty($group['description'])): ?>
                <p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($group['description']); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($group['maxSpent'] > 0): ?>
            <div class="text-right bg-white p-4 rounded-lg shadow-sm">
                <p class="text-xs text-gray-500 mb-1">Ngưỡng chi tiêu</p>
                <p class="text-sm font-bold text-gray-700"><?php echo number_format($group['minSpent']); ?>₫</p>
                <p class="text-xs text-gray-400">đến</p>
                <p class="text-sm font-bold text-gray-700"><?php echo number_format($group['maxSpent']); ?>₫</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="mb-8 p-6 rounded-xl bg-gray-50 border-2 border-dashed border-gray-300">
        <p class="text-center text-gray-500">
            <i class="fas fa-info-circle mr-2"></i>Bạn chưa được xếp vào nhóm khách hàng nào
        </p>
    </div>
    <?php endif; ?>
    
    <?php if ($isEditing): ?>
        <!-- FORM CHỈNH SỬA -->
        <form action="cCustomerAccount.php?action=update_info" method="POST" class="space-y-6">
            
            <div>
                <label for="customerName" class="block text-sm font-medium text-gray-700 mb-2">
                    Họ và Tên <span class="text-red-500">*</span>
                </label>
                <input type="text" name="customerName" id="customerName" required
                       value="<?php echo htmlspecialchars($customer['customerName'] ?? ''); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email <span class="text-gray-500 text-xs">(Không thể thay đổi)</span>
                </label>
                <input type="email" name="email" id="email" 
                       value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>"
                       readonly 
                       class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 bg-gray-100 cursor-not-allowed text-gray-600">
            </div>
            
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Số Điện Thoại <span class="text-red-500">*</span>
                </label>
                <input type="text" name="phone" id="phone" required
                       value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                       pattern="[0-9]{10,11}"
                       placeholder="Ví dụ: 0123456789"
                       class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>Nhập 10-11 chữ số
                </p>
            </div>
            
            <div class="flex gap-4 pt-4 border-t">
                <a href="cCustomerAccount.php?action=view" 
                   class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 text-center">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
                <button type="submit" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md">
                    <i class="fas fa-save mr-2"></i>Lưu Thay Đổi
                </button>
            </div>
        </form>
    <?php else: ?>
        <!-- HIỂN THỊ THÔNG TIN -->
        <div class="space-y-6">
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm font-medium text-gray-600 mb-2">Họ và Tên</p>
                <p class="text-xl text-gray-900 font-semibold"><?php echo htmlspecialchars($customer['customerName'] ?? 'Chưa cập nhật'); ?></p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm font-medium text-gray-600 mb-2">Email</p>
                <p class="text-lg text-gray-900"><?php echo htmlspecialchars($customer['email'] ?? 'Không có'); ?></p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm font-medium text-gray-600 mb-2">Số Điện Thoại</p>
                <p class="text-lg text-gray-900"><?php echo htmlspecialchars($customer['phone'] ?? 'Chưa cập nhật'); ?></p>
            </div>
            
            <div class="flex justify-end pt-4 border-t">
                <a href="cCustomerAccount.php?action=view&edit=1" 
                   class="bg-blue-600 hover:bg-blue-700 text-white text-center font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md">
                    <i class="fas fa-edit mr-2"></i> Cập Nhật Thông Tin
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
include_once(__DIR__ . '/../layout/footer.php');
?>