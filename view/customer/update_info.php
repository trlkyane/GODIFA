<?php 
// File: /GODIFA/view/customer/info.php
include_once(__DIR__ . '/../layout/header.php');
?>

<div class="max-w-xl mx-auto p-4 md:p-8 bg-white rounded-xl shadow-lg my-10">
    <h1 class="text-3xl font-bold mb-8 text-blue-600 border-b pb-3">
        <i class="fas fa-edit mr-2"></i> Cập Nhật Thông Tin Cá Nhân
    </h1>
    
    <form action="cCustomerAccount.php?action=update_info" method="POST" class="space-y-6">
        
        <div>
            <label for="customerName" class="block text-sm font-medium text-gray-700">Họ và Tên (*)</label>
            <input type="text" name="customerName" id="customerName" required
                   value="<?php echo htmlspecialchars($customer['customerName'] ?? ''); ?>"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email (Không thể thay đổi)</label>
            <input type="email" name="email" id="email" 
                   value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>"
                   readonly 
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 bg-gray-100 cursor-not-allowed">
            </div>
        
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Số Điện Thoại (*)</label>
            <input type="text" name="phone" id="phone" required
                   value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700">Giới Tính</label>
                <select name="gender" id="gender" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Chọn --</option>
                    <option value="Nam" <?php echo (isset($customer['gender']) && $customer['gender'] == 'Nam') ? 'selected' : ''; ?>>Nam</option>
                    <option value="Nữ" <?php echo (isset($customer['gender']) && $customer['gender'] == 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                    <option value="Khác" <?php echo (isset($customer['gender']) && $customer['gender'] == 'Khác') ? 'selected' : ''; ?>>Khác</option>
                </select>
            </div>
            
            <div>
                <label for="birthdate" class="block text-sm font-medium text-gray-700">Ngày Sinh</label>
                <input type="date" name="birthdate" id="birthdate"
                       value="<?php echo htmlspecialchars($customer['birthdate'] ?? ''); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        
        <div class="flex justify-end pt-4 border-t">
            <button type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                Lưu Thay Đổi
            </button>
        </div>
    </form>
</div>

<?php 
include_once(__DIR__ . '/../layout/footer.php');
?>