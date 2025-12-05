<?php
/**
 * Customer Voucher Page - Hiển thị tất cả voucher khả dụng cho khách hàng
 * File: view/voucher/vouchers.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

require_once __DIR__ . '/../../model/database.php';

// Kiểm tra đăng nhập (optional - vẫn hiển thị voucher cho guest nhưng không có group-specific)
$isLoggedIn = isset($_SESSION['customer_id']);
$customerID = $isLoggedIn ? $_SESSION['customer_id'] : null;

// Lấy thông tin customer và group (nếu đăng nhập)
$customerGroupName = null;
if ($isLoggedIn) {
    $db = Database::getInstance();
    $conn = $db->connect();
    
    $stmtCustomer = mysqli_prepare($conn, "
        SELECT c.customerName, cg.groupName 
        FROM customer c 
        LEFT JOIN customer_group cg ON c.groupID = cg.groupID 
        WHERE c.customerID = ?
    ");
    mysqli_stmt_bind_param($stmtCustomer, "i", $customerID);
    mysqli_stmt_execute($stmtCustomer);
    $result = mysqli_stmt_get_result($stmtCustomer);
    $customerData = mysqli_fetch_assoc($result);
    
    if ($customerData && isset($customerData['groupName'])) {
        $customerGroupName = $customerData['groupName'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã Giảm Giá - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .voucher-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .voucher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #6366f1;
        }
        .voucher-card > div {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .voucher-card .p-6 {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .voucher-badge {
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        .dashed-border {
            background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' stroke='%23d1d5db' stroke-width='2' stroke-dasharray='10%2c 10' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
        }
        .copy-button {
            transition: all 0.2s;
        }
        .copy-button:active {
            transform: scale(0.95);
        }
        /* Fixed height for description box to maintain consistency */
        .voucher-description {
            min-height: 3rem;
        }
        .voucher-condition {
            min-height: 2.5rem;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">

    <!-- Header -->
    <?php include __DIR__ . '/../layout/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Page Title & Info -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-3">
                Mã Giảm Giá Đặc Biệt
            </h1>
            <p class="text-gray-600 text-lg">
                Sử dụng mã giảm giá để tiết kiệm chi phí cho đơn hàng của bạn!
            </p>
            
            <?php if (!$isLoggedIn): ?>
            <div class="mt-4 bg-blue-50 border border-blue-200 text-blue-800 px-6 py-3 rounded-lg inline-block">
                <i class="fas fa-info-circle mr-2"></i>
                Đăng nhập để xem thêm voucher độc quyền dành riêng cho thành viên! 
            </div>
            <?php endif; ?>
            
        </div>

        <!-- Voucher List Container -->
        <div id="voucher-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Loading State -->
            <div class="col-span-full text-center py-12">
                <i class="fas fa-spinner fa-spin text-4xl text-indigo-600 mb-4"></i>
                <p class="text-gray-600">Đang tải danh sách voucher...</p>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/../layout/footer.php'; ?>

    <!-- Copy Toast Notification -->
    <div id="copy-toast" class="hidden fixed bottom-6 right-6 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce">
        <i class="fas fa-check-circle mr-2"></i>
        <span>Đã sao chép mã!</span>
    </div>

    <script>
    // Load vouchers from API
    document.addEventListener('DOMContentLoaded', function() {
        loadVouchers();
    });

    function loadVouchers() {
        const container = document.getElementById('voucher-container');
        
        fetch('<?php echo BASE_URL; ?>api/get_vouchers.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.vouchers && data.vouchers.length > 0) {
                    renderVouchers(data.vouchers, container);
                } else {
                    showEmptyState(container);
                }
            })
            .catch(error => {
                console.error('Error loading vouchers:', error);
                showErrorState(container);
            });
    }

    function renderVouchers(vouchers, container) {
        container.innerHTML = '';
        
        vouchers.forEach(voucher => {
            const voucherCard = createVoucherCard(voucher);
            container.appendChild(voucherCard);
        });
    }

    function createVoucherCard(voucher) {
        const card = document.createElement('div');
        card.className = 'voucher-card bg-white rounded-xl shadow-md overflow-hidden dashed-border';
        
        const isGroupVoucher = voucher.isGroupVoucher;
        const voucherCode = `VOUCHER${voucher.voucherID}`;
        
        // Build condition text
        let conditionText = '';
        if (voucher.minOrderValue && voucher.minOrderValue > 0) {
            conditionText = `Cho đơn hàng từ ${formatMoney(voucher.minOrderValue)}₫`;
        } else {
            conditionText = 'Không có điều kiện tối thiểu';
        }
        
        // Mô tả voucher (requirement là mô tả)
        const description = voucher.requirement && voucher.requirement.trim() !== '' 
            ? voucher.requirement 
            : '';
        
        card.innerHTML = `
            <div class="relative">
                <!-- Badge cho voucher đặc biệt -->
                ${isGroupVoucher ? `
                <div class="absolute top-0 right-0 bg-gradient-to-r from-amber-400 to-amber-600 text-white px-3 py-1 rounded-bl-lg voucher-badge">
                    <i class="fas fa-crown mr-1"></i>
                    <span class="text-xs font-bold">VIP</span>
                </div>
                ` : ''}
                
                <!-- Header với giá trị giảm -->
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white p-6 text-center">
                    <div class="text-sm font-medium mb-2 opacity-90">GIẢM NGAY</div>
                    <div class="text-4xl font-bold mb-1">${voucher.discountFormatted}</div>
                    ${isGroupVoucher ? `<div class="text-xs bg-white/20 inline-block px-3 py-1 rounded-full mt-2">${voucher.groupName || 'Thành viên'}</div>` : ''}
                </div>
                
                <!-- Body với thông tin chi tiết -->
                <div class="p-6">
                    <!-- Tên voucher -->
                    <h3 class="text-lg font-bold text-gray-800 mb-3 line-clamp-2">
                        ${voucher.voucherName}
                    </h3>
                    
                    <!-- Mô tả voucher (nếu có) -->
                    <div class="voucher-description ${description ? 'bg-gray-50 border border-gray-200 rounded-lg p-3 mb-3' : 'mb-3'}">
                        ${description ? `<p class="text-sm text-gray-700 leading-relaxed line-clamp-2">${description}</p>` : '<div class="h-12"></div>'}
                    </div>
                    
                    <!-- Điều kiện áp dụng -->
                    <div class="voucher-condition bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mt-1 mr-2 flex-shrink-0"></i>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-blue-900 mb-1">Điều kiện áp dụng:</p>
                                <p class="text-sm text-blue-800">${conditionText}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thông tin thêm -->
                    <div class="space-y-2 mb-4 text-sm text-gray-600">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt w-5 text-indigo-600"></i>
                            <span>HSD: ${voucher.endDate}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-box w-5 text-indigo-600"></i>
                            <span>Còn lại: <strong class="text-indigo-600">${voucher.quantity}</strong> voucher</span>
                        </div>
                    </div>
                    
                    <!-- Spacer to push bottom content down -->
                    <div class="flex-1"></div>
                    
                    <!-- Mã voucher & Copy Button -->
                    <div class="border-t pt-4 mt-auto">
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 mb-1">Mã khuyến mãi</p>
                                <p class="font-bold text-gray-800 truncate">${voucher.voucherName}</p>
                            </div>
                            <button onclick="copyVoucherCode('${voucher.voucherName}')" 
                                    class="copy-button bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold text-sm flex-shrink-0 ml-2">
                                <i class="fas fa-copy mr-1"></i>
                                Sao chép
                            </button>
                        </div>
                    </div>
                    
                    <!-- CTA Button -->
                    <a href="<?php echo BASE_URL; ?>view/cart/checkout.php" 
                       class="block mt-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-center py-3 rounded-lg font-semibold transition">
                        <i class="fas fa-shopping-cart mr-2"></i>Sử dụng ngay
                    </a>
                </div>
            </div>
        `;
        
        return card;
    }

    function showEmptyState(container) {
        container.innerHTML = '';
    }

    function showErrorState(container) {
        container.innerHTML = `
            <div class="col-span-full text-center py-16">
                <i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Lỗi tải dữ liệu</h3>
                <p class="text-gray-500 mb-4">Không thể tải danh sách voucher. Vui lòng thử lại!</p>
                <button onclick="loadVouchers()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg">
                    <i class="fas fa-redo mr-2"></i>Thử lại
                </button>
            </div>
        `;
    }

    function formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }

    function copyVoucherCode(code) {
        // Copy to clipboard
        navigator.clipboard.writeText(code).then(() => {
            showCopyToast();
        }).catch(err => {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = code;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showCopyToast();
        });
    }

    function showCopyToast() {
        const toast = document.getElementById('copy-toast');
        toast.classList.remove('hidden');
        setTimeout(() => {
            toast.classList.add('hidden');
        }, 2000);
    }
    </script>

</body>
</html>
