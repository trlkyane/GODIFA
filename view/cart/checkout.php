<?php
/**
 * Checkout Page - Form nhập thông tin giao hàng
 * Updated: 2025-11-05
 * Yêu cầu: Phải đăng nhập mới được thanh toán
 */

require_once __DIR__ . '/../../middleware/customer_only.php';

// ✅ Bắt buộc đăng nhập trước khi checkout
if (!isset($_SESSION['customer_id']) || !isset($_SESSION['is_customer_logged_in'])) {
    // Lưu URL hiện tại để redirect về sau khi đăng nhập
    $_SESSION['redirect_after_login'] = '/GODIFA/view/cart/checkout.php';
    
    echo "<script>
        alert('Vui lòng đăng nhập để tiếp tục thanh toán!');
        window.location.href = '/GODIFA/view/auth/customer-login.php';
    </script>";
    exit;
}

// Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: viewcart.php');
    exit;
}

// Lấy thông tin customer từ database để pre-fill form
require_once __DIR__ . '/../../model/database.php';
$db = Database::getInstance();
$conn = $db->connect();

$customerID = $_SESSION['customer_id']; // Sửa từ customerID thành customer_id
$stmt = $conn->prepare("SELECT customerName as fullName, email, phone FROM customer WHERE customerID = ?");
$stmt->bind_param("i", $customerID);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

// Nếu không tìm thấy customer, set giá trị mặc định
if (!$customer) {
    $customer = [
        'fullName' => $_SESSION['customer_name'] ?? '',
        'email' => $_SESSION['customer_email'] ?? '',
        'phone' => $_SESSION['customer_phone'] ?? ''
    ];
}

// Tính tổng tiền
$totalAmount = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalAmount += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/GODIFA" class="text-2xl font-bold text-indigo-600">🇯🇵 GODIFA</a>
            <nav class="space-x-4 text-sm">
                <a href="/GODIFA" class="hover:text-indigo-600">Trang chủ</a>
                <a href="viewcart.php" class="hover:text-indigo-600">Giỏ hàng</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto mt-8 mb-10 px-4">
        
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            <i class="fas fa-shopping-cart text-indigo-600"></i> Thanh toán đơn hàng
        </h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Form thông tin -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">
                        <i class="fas fa-truck"></i> Thông tin giao hàng
                    </h2>

                    <form action="/GODIFA/controller/cCheckout.php" method="POST" id="checkoutForm">
                        
                        <!-- Họ và tên -->
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-user text-indigo-600"></i> Họ và tên người nhận *
                            </label>
                            <input type="text" 
                                   name="fullName" 
                                   required 
                                   value="<?= htmlspecialchars($customer['fullName'] ?? '') ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="Nguyễn Văn A">
                        </div>

                        <!-- Email và SĐT -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-envelope text-indigo-600"></i> Email *
                                </label>
                                <input type="email" 
                                       name="email" 
                                       required 
                                       value="<?= htmlspecialchars($customer['email'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       placeholder="example@gmail.com">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-phone text-indigo-600"></i> Số điện thoại *
                                </label>
                                <input type="tel" 
                                       name="phone" 
                                       required 
                                       pattern="[0-9]{10,11}"
                                       value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                       placeholder="0987654321">
                            </div>
                        </div>

                        <!-- Tỉnh/Thành phố, Quận/Huyện, Phường/Xã (GHN 3-level cascading) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <!-- Province -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-map-marked-alt text-indigo-600"></i> Tỉnh/Thành phố *
                                </label>
                                <select id="province" 
                                        name="city" 
                                        required 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Đang tải...</option>
                                </select>
                                <!-- Hidden inputs for GHN IDs -->
                                <input type="hidden" id="province-id" name="provinceId">
                                <input type="hidden" id="province-name" name="provinceName">
                            </div>
                            
                            <!-- District -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-map text-indigo-600"></i> Quận/Huyện *
                                </label>
                                <select id="district" 
                                        name="district" 
                                        required 
                                        disabled
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100">
                                    <option value="">-- Chọn Quận/Huyện --</option>
                                </select>
                                <!-- Hidden inputs for GHN IDs -->
                                <input type="hidden" id="district-id" name="districtId">
                                <input type="hidden" id="district-name" name="districtName">
                            </div>
                            
                            <!-- Ward (MỚI THÊM) -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-map-marker-alt text-indigo-600"></i> Phường/Xã *
                                </label>
                                <select id="ward" 
                                        name="ward" 
                                        required 
                                        disabled
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100">
                                    <option value="">-- Chọn Phường/Xã --</option>
                                </select>
                                <!-- Hidden inputs for GHN IDs -->
                                <input type="hidden" id="ward-code" name="wardCode">
                                <input type="hidden" id="ward-name" name="wardName">
                            </div>
                        </div>

                        <!-- Hidden service type (always Standard - service 5 not available for all routes) -->
                        <input type="hidden" name="service_type" value="2">
                        <input type="hidden" id="service-type-id" name="serviceTypeId" value="2">

                        <!-- Địa chỉ chi tiết -->
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-home text-indigo-600"></i> Địa chỉ chi tiết *
                            </label>
                            <input type="text" 
                                   name="address" 
                                   required 
                                   value="<?= htmlspecialchars($customer['address'] ?? '') ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="Số nhà, tên đường, phường/xã">
                        </div>

                        <!-- Ghi chú -->
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">
                                <i class="fas fa-comment text-indigo-600"></i> Ghi chú (tùy chọn)
                            </label>
                            <textarea name="notes" 
                                      rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                      placeholder="Ghi chú về đơn hàng (giao giờ hành chính, không gọi chuông...)"></textarea>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-medium mb-2">Phương thức thanh toán *</label>
                            <div class="space-y-2">
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-indigo-50">
                                    <input type="radio" name="paymentMethod" value="QR" checked class="mr-3">
                                    <i class="fas fa-qrcode text-indigo-600 mr-2"></i>
                                    <span>Thanh toán QR Code (SePay)</span>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-green-50">
                                    <input type="radio" name="paymentMethod" value="COD" class="mr-3">
                                    <i class="fas fa-money-bill-wave text-green-600 mr-2"></i>
                                    <div>
                                        <div>COD - Thanh toán khi nhận hàng</div>
                                        <div class="text-xs text-gray-500">Thanh toán bằng tiền mặt khi nhận hàng</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Hidden inputs for form submission (MUST be inside form) -->
                        <input type="hidden" id="shipping-fee-value" name="shippingFee" value="0">
                        <input type="hidden" id="total-amount-value" name="totalAmount" value="<?= $totalAmount ?>">
                        <input type="hidden" id="voucher-id" name="voucherID" value="">
                        <input type="hidden" id="discount-amount" name="discountAmount" value="0">

                        <button type="submit" 
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition duration-300 shadow-lg">
                            <i class="fas fa-credit-card mr-2"></i> Tiến hành thanh toán
                        </button>

                    </form>

                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">
                        <i class="fas fa-receipt"></i> Tóm tắt đơn hàng
                    </h2>

                    <div class="space-y-3 mb-4">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">
                                    <?= htmlspecialchars($item['name'] ?? 'Sản phẩm') ?> 
                                    <span class="text-gray-400">x<?= $item['quantity'] ?? 0 ?></span>
                                </span>
                                <span class="font-semibold">
                                    <?= number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 0, ',', '.') ?>₫
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-t pt-3 mb-3">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Tạm tính:</span>
                            <span class="font-semibold" id="subtotal"><?= number_format($totalAmount, 0, ',', '.') ?>₫</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Phí vận chuyển:</span>
                            <span id="shipping-fee" class="text-indigo-600 font-semibold">0₫</span>
                        </div>
                        
                        <!-- Voucher Section -->
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Giảm giá:</span>
                            <span id="discount-display" class="text-green-600 font-semibold">0₫</span>
                        </div>
                        
                        <!-- Voucher Selector -->
                        <div class="mb-3">
                            <button type="button" 
                                    onclick="showVoucherModal()"
                                    class="w-full text-left px-3 py-2 border-2 border-dashed border-indigo-300 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition flex items-center justify-between">
                                <span id="voucher-label" class="text-sm text-gray-600">
                                    <i class="fas fa-ticket-alt text-indigo-600 mr-2"></i>
                                    <span>Chọn voucher</span>
                                </span>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </button>
                        </div>
                        
                        <div class="text-xs text-gray-500 italic mt-1">
                            <i class="fas fa-info-circle"></i> Chọn địa chỉ để tính phí ship
                        </div>
                    </div>

                    <div class="border-t pt-3">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Tổng cộng:</span>
                            <span id="total-amount" class="text-indigo-600"><?= number_format($totalAmount, 0, ',', '.') ?>₫</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-10">
        <div class="max-w-6xl mx-auto px-4 text-center text-sm">
            <p>&copy; <?= date('Y') ?> GODIFA. All rights reserved.</p>
        </div>
    </footer>

    <!-- Voucher Modal -->
    <div id="voucher-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-ticket-alt text-indigo-600 mr-2"></i>
                    Chọn Voucher
                </h3>
                <button onclick="closeVoucherModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div id="voucher-list" class="p-6 overflow-y-auto" style="max-height: calc(80vh - 120px);">
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                    <p>Đang tải voucher...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Voucher & Checkout Script -->
    <script>
    // Global variables
    const SUBTOTAL = <?= $totalAmount ?>;
    let shippingFee = 0;
    let discountAmount = 0;
    let selectedVoucher = null;
    let availableVouchers = [];
    
    // Update total amount
    function updateTotal() {
        const subtotal = parseFloat(SUBTOTAL) || 0;
        const shipping = parseFloat(shippingFee) || 0;
        const discount = Math.abs(parseFloat(discountAmount)) || 0;
        
        // Công thức: Tạm tính + Phí ship - Giảm giá
        const total = subtotal + shipping - discount;
        
        // Đảm bảo total không âm
        const finalTotal = Math.max(0, total);
        
        document.getElementById('total-amount').textContent = formatMoney(finalTotal) + '₫';
        document.getElementById('total-amount-value').value = finalTotal;
    }
    
    // Format số tiền
    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }
    
    // Override callback từ ghn-address.js để cập nhật shipping fee
    window.onShippingFeeCalculated = function(fee) {
        shippingFee = parseFloat(fee) || 0;
        document.getElementById('shipping-fee').textContent = formatMoney(shippingFee) + '₫';
        document.getElementById('shipping-fee-value').value = shippingFee;
        updateTotal();
    };
    </script>
    
    <!-- GHN Address Selector Script -->
    <script src="/GODIFA/public/js/ghn-address.js?v=3.0"></script>
    
    <script>
    // Load vouchers khi trang load
    document.addEventListener('DOMContentLoaded', function() {
        loadVouchers();
    });
    
    // Load danh sách voucher từ API
    function loadVouchers() {
        fetch('/GODIFA/api/get_vouchers.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    availableVouchers = data.vouchers;
                }
            })
            .catch(error => {
                console.error('Error loading vouchers:', error);
            });
    }
    
    // Hiển thị modal voucher
    function showVoucherModal() {
        const modal = document.getElementById('voucher-modal');
        const voucherList = document.getElementById('voucher-list');
        
        modal.classList.remove('hidden');
        
        if (availableVouchers.length === 0) {
            voucherList.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-ticket-alt text-5xl mb-3 text-gray-300"></i>
                    <p class="text-lg font-medium">Không có voucher khả dụng</p>
                    <p class="text-sm mt-2">Hãy quay lại sau để nhận ưu đãi!</p>
                </div>
            `;
            return;
        }
        
        // Render voucher list
        let html = '<div class="space-y-3">';
        
        // Nút xóa voucher (nếu đã chọn)
        if (selectedVoucher) {
            html += `
                <button onclick="removeVoucher()" 
                        class="w-full p-4 border-2 border-red-300 rounded-lg hover:bg-red-50 transition text-left">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-red-600">
                                <i class="fas fa-times-circle mr-2"></i>Bỏ voucher
                            </p>
                            <p class="text-sm text-gray-600">Không sử dụng voucher cho đơn hàng này</p>
                        </div>
                    </div>
                </button>
                <div class="border-t my-3"></div>
            `;
        }
        
        availableVouchers.forEach(voucher => {
            const isSelected = selectedVoucher && selectedVoucher.voucherID === voucher.voucherID;
            const borderClass = isSelected ? 'border-green-500 bg-green-50' : 'border-gray-300 hover:border-indigo-500 hover:bg-gray-50';
            const groupBadge = voucher.isGroupVoucher ? 
                `<span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded-full ml-2">
                    <i class="fas fa-crown"></i> ${voucher.groupName || 'VIP'}
                </span>` : '';
            
            html += `
                <button onclick='selectVoucher(${JSON.stringify(voucher)})' 
                        class="w-full p-4 border-2 ${borderClass} rounded-lg transition text-left">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-1">
                                <i class="fas fa-ticket-alt text-indigo-600 mr-2"></i>
                                <p class="font-semibold">${voucher.voucherName}</p>
                                ${groupBadge}
                            </div>
                            <p class="text-2xl font-bold text-indigo-600 mb-2">
                                -${voucher.discountFormatted}
                            </p>
                            <div class="text-xs text-gray-600 space-y-1">
                                <p><i class="fas fa-calendar mr-1"></i> HSD: ${voucher.endDate}</p>
                                <p><i class="fas fa-box mr-1"></i> Còn ${voucher.quantity} voucher</p>
                                ${voucher.requirement ? `<p><i class="fas fa-info-circle mr-1"></i> ${voucher.requirement}</p>` : ''}
                            </div>
                        </div>
                        ${isSelected ? `
                            <div class="ml-3">
                                <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                            </div>
                        ` : ''}
                    </div>
                </button>
            `;
        });
        
        html += '</div>';
        voucherList.innerHTML = html;
    }
    
    // Chọn voucher
    function selectVoucher(voucher) {
        selectedVoucher = voucher;
        discountAmount = Math.abs(voucher.discountValue);
        
        // Update UI
        document.getElementById('voucher-label').innerHTML = `
            <i class="fas fa-check-circle text-green-500 mr-2"></i>
            <span class="font-medium text-green-600">${voucher.voucherName}: -${voucher.discountFormatted}</span>
        `;
        document.getElementById('discount-display').textContent = '-' + voucher.discountFormatted;
        document.getElementById('voucher-id').value = voucher.voucherID;
        document.getElementById('discount-amount').value = Math.abs(voucher.discountValue);
        
        // Recalculate total
        updateTotal();
        
        // Close modal
        closeVoucherModal();
    }
    
    // Bỏ voucher
    function removeVoucher() {
        selectedVoucher = null;
        discountAmount = 0;
        
        // Reset UI
        document.getElementById('voucher-label').innerHTML = `
            <i class="fas fa-ticket-alt text-indigo-600 mr-2"></i>
            <span>Chọn voucher</span>
        `;
        document.getElementById('discount-display').textContent = '0₫';
        document.getElementById('voucher-id').value = '';
        document.getElementById('discount-amount').value = '0';
        
        // Recalculate
        updateTotal();
        
        // Close modal
        closeVoucherModal();
    }
    
    // Đóng modal
    function closeVoucherModal() {
        document.getElementById('voucher-modal').classList.add('hidden');
    }
    
    // Update hidden inputs before form submit
    document.querySelector('form[action="/GODIFA/controller/cCheckout.php"]').addEventListener('submit', function(e) {
        const selector = window.ghnAddressSelector;
        if (selector) {
            const data = selector.getSelectedData();
            
            // Update hidden inputs với GHN IDs
            document.getElementById('province-id').value = data.provinceId || '';
            document.getElementById('province-name').value = data.provinceName || '';
            document.getElementById('district-id').value = data.districtId || '';
            document.getElementById('district-name').value = data.districtName || '';
            document.getElementById('ward-code').value = data.wardCode || '';
            document.getElementById('ward-name').value = data.wardName || '';
        }
    });
    </script>

</body>
</html>
