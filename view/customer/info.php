<?php 
// File: /GODIFA/view/customer/info.php (Trang Dùng để XEM/Hiển thị thông tin)
include_once(__DIR__ . '/../layout/header.php');
?>

<div class="max-w-xl mx-auto p-4 md:p-8 bg-white rounded-xl shadow-lg my-10">
    <h1 class="text-3xl font-bold mb-8 text-blue-600 border-b pb-3">
        <i class="fas fa-user-circle mr-2"></i> Thông Tin Tài Khoản
    </h1>
    
    <div class="space-y-6">
        
        <div class="border-b pb-2">
            <p class="text-sm font-medium text-gray-500">Họ và Tên</p>
            <p class="text-lg text-gray-800 font-semibold"><?php echo htmlspecialchars($customer['customerName'] ?? 'Chưa cập nhật'); ?></p>
        </div>

        <div class="border-b pb-2">
            <p class="text-sm font-medium text-gray-500">Email</p>
            <p class="text-lg text-gray-800"><?php echo htmlspecialchars($customer['email'] ?? 'Không có'); ?></p>
        </div>
        
        <div class="border-b pb-2">
            <p class="text-sm font-medium text-gray-500">Số Điện Thoại</p>
            <p class="text-lg text-gray-800"><?php echo htmlspecialchars($customer['phone'] ?? 'Chưa cập nhật'); ?></p>
        </div>
        
        <div class="grid grid-cols-2 gap-6 border-b pb-2">
            <div>
                <p class="text-sm font-medium text-gray-500">Giới Tính</p>
                <p class="text-lg text-gray-800"><?php echo htmlspecialchars($customer['gender'] ?? 'Chưa cập nhật'); ?></p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-500">Ngày Sinh</p>
                <p class="text-lg text-gray-800">
                    <?php 
                    // Hiển thị Ngày Sinh từ cột 'birthdate'
                    $birthdate = $customer['birthdate'] ?? null;
                    if (!empty($birthdate) && $birthdate !== '0000-00-00') {
                        echo date('d/m/Y', strtotime($birthdate));
                    } else {
                        echo 'Chưa cập nhật';
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <div class="flex justify-end pt-4 border-t">
            <a href="cCustomerAccount.php?action=update_info" 
               class="bg-blue-600 hover:bg-blue-700 text-white text-center font-semibold py-3 px-6 rounded-lg transition duration-200">
                <i class="fas fa-edit mr-2"></i> Cập Nhật Thông Tin
            </a>
        </div>
    </div>
</div>

<?php 
include_once(__DIR__ . '/../layout/footer.php');
?>