<?php
/**
 * L?ch s? don hàng c?a khách hàng
 * File: view/order_history.php
 */

// Load constants first
require_once __DIR__ . '/../../config/constants.php';

// Ki?m tra dang nh?p
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: ' . BASE_URL . 'view/auth/customer-login.php');
    exit;
}

require_once __DIR__ . '/../../controller/cOrderHistory.php';

$customerID = $_SESSION['customer_id'];

// L?y filter t? URL (m?c d?nh: all)
$filter = $_GET['filter'] ?? 'all';

// L?y danh sách don hàng
$orderHistoryController = new OrderHistoryController();
$allOrders = $orderHistoryController->getCustomerOrders($customerID);

// L?c don hàng theo tab
$orders = [];
switch ($filter) {
    case 'pending':
        // Chỉ thanh toán
        $orders = array_filter($allOrders, function($order) {
            return $order['paymentStatus'] === 'Chỉ thanh toán';
        });
        break;
    case 'paid':
        // Đã thanh toán
        $orders = array_filter($allOrders, function($order) {
            return $order['paymentStatus'] === 'Ðã thanh toán';
        });
        break;
    case 'cancelled':
        // Ðã h?y
        $orders = array_filter($allOrders, function($order) {
            return $order['paymentStatus'] === 'Đã hủy';
        });
        break;
    case 'all':
    default:
        // Tất cả
        $orders = $allOrders;
        break;
}

// Đếm số lượng đơn theo từng trạng thái
$countAll = count($allOrders);
$countPending = count(array_filter($allOrders, function($o) { return $o['paymentStatus'] === 'Chỉ thanh toán'; }));
$countPaid = count(array_filter($allOrders, function($o) { return $o['paymentStatus'] === 'Đã thanh toán'; }));
$countCancelled = count(array_filter($allOrders, function($o) { return $o['paymentStatus'] === 'Đã hủy'; }));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đơn Hàng - GODIFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <?php include __DIR__ . '/../layout/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">📜 Lịch Sử Đơn Hàng</h1>
            <p class="text-gray-600 mt-2">Quản lý và theo dõi đơn hàng của bạn</p>
        </div>

        <!-- Tabs Filter -->
        <div class="bg-white rounded-lg shadow-md mb-6 overflow-hidden">
            <div class="flex flex-wrap border-b">
                <!-- Tab: Tất cả -->
                <a href="?filter=all" 
                   class="flex-1 text-center px-6 py-4 font-semibold transition <?= $filter === 'all' ? 'bg-indigo-600 text-white border-b-4 border-indigo-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <i class="fas fa-list mr-2"></i>Tất cả
                    <?php if ($countAll > 0): ?>
                    <span class="ml-2 px-2 py-1 rounded-full text-xs <?= $filter === 'all' ? 'bg-white text-indigo-600' : 'bg-gray-200 text-gray-700' ?>">
                        <?= $countAll ?>
                    </span>
                    <?php endif; ?>
                </a>

                <!-- Tab: Chưa thanh toán -->
                <a href="?filter=pending" 
                   class="flex-1 text-center px-6 py-4 font-semibold transition <?= $filter === 'pending' ? 'bg-yellow-500 text-white border-b-4 border-yellow-500' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <i class="fas fa-clock mr-2"></i>Chưa thanh toán
                    <?php if ($countPending > 0): ?>
                    <span class="ml-2 px-2 py-1 rounded-full text-xs <?= $filter === 'pending' ? 'bg-white text-yellow-600' : 'bg-gray-200 text-gray-700' ?>">
                        <?= $countPending ?>
                    </span>
                    <?php endif; ?>
                </a>

                <!-- Tab: Đã thanh toán -->
                <a href="?filter=paid" 
                   class="flex-1 text-center px-6 py-4 font-semibold transition <?= $filter === 'paid' ? 'bg-green-600 text-white border-b-4 border-green-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <i class="fas fa-check-circle mr-2"></i>Đã thanh toán
                    <?php if ($countPaid > 0): ?>
                    <span class="ml-2 px-2 py-1 rounded-full text-xs <?= $filter === 'paid' ? 'bg-white text-green-600' : 'bg-gray-200 text-gray-700' ?>">
                        <?= $countPaid ?>
                    </span>
                    <?php endif; ?>
                </a>

                <!-- Tab: Đã hủy -->
                <a href="?filter=cancelled" 
                   class="flex-1 text-center px-6 py-4 font-semibold transition <?= $filter === 'cancelled' ? 'bg-red-600 text-white border-b-4 border-red-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <i class="fas fa-times-circle mr-2"></i>Đã hủy
                    <?php if ($countCancelled > 0): ?>
                    <span class="ml-2 px-2 py-1 rounded-full text-xs <?= $filter === 'cancelled' ? 'bg-white text-red-600' : 'bg-gray-200 text-gray-700' ?>">
                        <?= $countCancelled ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <?php if (empty($orders)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <div class="text-6xl mb-4">📭</div>
            <?php if ($filter === 'all'): ?>
                <h2 class="text-2xl font-semibold text-gray-700 mb-2">Chưa có đơn hàng nào</h2>
                <p class="text-gray-500 mb-6">Bạn chưa đặt hàng. Hãy khám phá các sản phẩm của chúng tôi!</p>
                <a href="<?php echo BASE_URL; ?>view/product/list.php" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-shopping-bag mr-2"></i> Mua Sắm Ngay
                </a>
            <?php else: ?>
                <h2 class="text-2xl font-semibold text-gray-700 mb-2">Không có đơn hàng nào</h2>
                <p class="text-gray-500 mb-6">
                    <?php if ($filter === 'pending'): ?>
                        Bạn không có đơn hàng nào đang chờ thanh toán.
                    <?php elseif ($filter === 'paid'): ?>
                        Bạn chưa có đơn hàng nào đã thanh toán.
                    <?php elseif ($filter === 'cancelled'): ?>
                        Bạn không có đơn hàng nào bị hủy.
                    <?php endif; ?>
                </p>
                <a href="?filter=all" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-list mr-2"></i> Xem Tất Cả Đơn Hàng
                </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Orders List -->
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between mb-4">
                        <div class="flex-1 min-w-0 mr-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-receipt text-indigo-600 mr-2"></i>
                                Đơn hàng #<?= $order['orderID'] ?>
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="far fa-calendar-plus mr-1"></i>
                                <strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['orderDate'])) ?>
                            </p>
                            <?php if (!empty($order['paymentDate'])): ?>
                            <p class="text-sm text-green-600 mt-1">
                                <i class="far fa-check-circle mr-1"></i>
                                <strong>Ðã thanh toán:</strong> <?= date('d/m/Y H:i', strtotime($order['paymentDate'])) ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($order['transactionCode']): ?>
                            <p class="text-sm text-gray-600 font-mono mt-1">
                                Mã GD: <?= $order['transactionCode'] ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-right">
                            <p class="text-2xl font-bold text-indigo-600">
                                <?= number_format($order['totalAmount'], 0, ',', '.') ?>₫
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <!-- Thanh toán -->
                        <div class="flex items-center">
                            <span class="text-gray-600 mr-2">Trạng thái thanh toán:</span>
                            <?php
                            $paymentColors = [
                                'Đã thanh toán' => 'bg-green-100 text-green-800',
                                'Chưa thanh toán' => 'bg-yellow-100 text-yellow-800',
                                'Đã hủy' => 'bg-red-100 text-red-800',
                                'Đã hoàn tiền' => 'bg-purple-100 text-purple-800'
                            ];
                            $paymentColor = $paymentColors[$order['paymentStatus']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $paymentColor ?>">
                                <?= $order['paymentStatus'] ?>
                            </span>
                        </div>

                        <!-- Giao hàng -->
                        <div class="flex items-center">
                            <span class="text-gray-600 mr-2">Trạng thái giao hàng:</span>
                            <?php
                            $deliveryColors = [
                                'Hoàn thành' => 'bg-green-100 text-green-800',
                                'Đang tiến hành vận chuyển' => 'bg-blue-100 text-blue-800',
                                'Chờ xác nhận' => 'bg-yellow-100 text-yellow-800',
                                'Chờ xử lý hoàn tiền' => 'bg-orange-100 text-orange-800',
                                'Đã hủy' => 'bg-red-100 text-red-800',
                                'Đã hoàn tiền' => 'bg-purple-100 text-purple-800',
                                // Backward compatibility
                                'Đã giao' => 'bg-green-100 text-green-800',
                                'Đang giao' => 'bg-blue-100 text-blue-800',
                                'Đang xử lý' => 'bg-blue-100 text-blue-800',
                                'Chờ xử lý' => 'bg-yellow-100 text-yellow-800',
                            ];
                            $deliveryColor = $deliveryColors[$order['deliveryStatus']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $deliveryColor ?>">
                                <?= $order['deliveryStatus'] ?>
                            </span>
                            
                            <?php 
                            // Hiển thị badge trạng thái hoàn trả (nếu có)
                            $returnStatus = $order['returnStatus'] ?? null;
                            if ($returnStatus && $returnStatus !== 'Không') {
                                $returnColors = [
                                    'Chờ xử lý' => 'bg-yellow-100 text-yellow-800 border border-yellow-300',
                                    'Đã chấp nhận' => 'bg-green-100 text-green-800 border border-green-300',
                                    'Đã từ chối' => 'bg-red-100 text-red-800 border border-red-300',
                                    'Đã hoàn tiền' => 'bg-purple-100 text-purple-800 border border-purple-300',
                                ];
                                $returnColor = $returnColors[$returnStatus] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $returnColor ?>">
                                    <i class="fas fa-undo mr-1"></i><?= $returnStatus ?>
                                </span>
                            <?php } ?>
                        </div>

                        <!-- Người nhận -->
                        <div class="flex items-center">
                            <span class="text-gray-600 mr-2">Người nhận:</span>
                            <span class="text-sm">
                                <?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?>
                                <br>
                                <span class="text-gray-500"><?= htmlspecialchars($order['recipientPhone'] ?? '') ?></span>
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-200">
                        <a href="<?php echo BASE_URL; ?>view/order/detail.php?id=<?= $order['orderID'] ?>" 
                           class="flex-1 text-center bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm font-semibold">
                            <i class="fas fa-eye mr-2"></i>Xem Chi Tiết
                        </a>
                        
                        <?php if ($order['paymentStatus'] === 'Đã thanh toán' && $order['deliveryStatus'] === 'Đã giao'): ?>
                        <a href="<?php echo BASE_URL; ?>view/product/detail.php?id=<?= $order['orderID'] ?>" 
                           class="flex-1 text-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-semibold">
                            <i class="fas fa-star mr-2"></i>Đánh Giá
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        // Hiển thị nút Yêu cầu hoàn trả cho đơn đã hoàn thành
                        $canReturn = false;
                        $returnMessage = '';
                        $returnStatus = $order['returnStatus'] ?? null;
                        
                        if ($order['deliveryStatus'] === 'Hoàn thành') {
                            if (!$returnStatus) {
                                // Chưa có yêu cầu hoàn trả nào
                                $canReturn = true;
                            } elseif ($returnStatus === 'Chờ xử lý') {
                                $returnMessage = 'Yêu cầu hoàn trả đang được xử lý';
                            } elseif ($returnStatus === 'Đã chấp nhận') {
                                $returnMessage = 'Yêu cầu hoàn trả đã được chấp nhận';
                            } elseif ($returnStatus === 'Đã từ chối') {
                                $returnMessage = 'Yêu cầu hoàn trả đã bị từ chối';
                            } elseif ($returnStatus === 'Đã hoàn tiền') {
                                $returnMessage = 'Đơn hàng đã hoàn tiền';
                            }
                        }
                        
                        if ($canReturn): ?>
                        <button onclick="openReturnModal(<?= $order['orderID'] ?>, <?= $order['totalAmount'] ?>)" 
                                class="flex-1 text-center bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition text-sm font-semibold">
                            <i class="fas fa-undo mr-2"></i>Yêu cầu hoàn trả
                        </button>
                        <?php elseif ($returnStatus): ?>
                        <button onclick="viewReturnRequest(<?= $order['orderID'] ?>)" 
                                class="flex-1 text-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                            <i class="fas fa-eye mr-2"></i>Xem yêu cầu hoàn trả
                        </button>
                        <?php endif; ?>
                        
                        <?php 
                        // Chỉ hiện nút thanh toán với đơn QR chưa thanh toán
                        $showPaymentButton = false;
                        $paymentButtonText = 'Thanh Toán';
                        
                        if ($order['paymentMethod'] === 'QR') {
                            if ($order['paymentStatus'] === 'Chưa thanh toán' || $order['paymentStatus'] === 'Chờ thanh toán') {
                                $showPaymentButton = true;
                                $paymentButtonText = ($order['paymentStatus'] === 'Chưa thanh toán') ? 'Thanh Toán' : 'Thanh toán lại';
                            }
                        }
                        
                        if ($showPaymentButton): ?>
                        <a href="<?php echo BASE_URL; ?>view/cart/checkout_qr.php?orderID=<?= $order['orderID'] ?>" 
                           class="flex-1 text-center bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition text-sm font-semibold">
                            <i class="fas fa-credit-card mr-2"></i><?= $paymentButtonText ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        // Kiểm tra xem có thể hủy đơn không
                        // Logic mới: Cho phép hủy khi admin CHƯA xác nhận đơn
                        $canCancel = false;
                        $cancelMessage = '';
                        
                        if ($order['paymentStatus'] === 'Đã hủy' || $order['deliveryStatus'] === 'Đã hủy') {
                            // Đơn đã hủy rồi - KHÔNG cho thao tác gì nữa
                            $canCancel = false;
                        } elseif ($order['paymentStatus'] === 'Đã hoàn tiền' || $order['deliveryStatus'] === 'Đã hoàn tiền') {
                            // Đơn đã hoàn tiền - KHÔNG cho hủy nữa
                            $canCancel = false;
                        } elseif ($order['deliveryStatus'] === 'Chờ xử lý hoàn tiền') {
                            // Đơn đang chờ admin xác nhận hoàn tiền - KHÔNG cho hủy thêm
                            $canCancel = false;
                        } elseif ($order['deliveryStatus'] === 'Hoàn thành' || $order['deliveryStatus'] === 'Đã giao') {
                            // Đơn đã hoàn thành
                            $canCancel = false;
                        } elseif (in_array($order['deliveryStatus'], ['Đang giao', 'Đang tiến hành vận chuyển'])) {
                            // Đơn đã được admin xác nhận và đang giao - KHÔNG cho hủy
                            $cancelMessage = 'Đơn hàng đã được xác nhận và đang giao. Vui lòng liên hệ CSKH để hỗ trợ!';
                            $canCancel = false;
                        } else {
                            // Các trường hợp khác (Chờ xử lý, Chờ xác nhận) - CHO PHÉP HỦY
                            // Bất kể đã thanh toán hay chưa, nếu admin chưa xác nhận thì vẫn cho hủy
                            $canCancel = true;
                        }
                        
                        if ($canCancel): ?>
                        <button onclick="openCancelModal(<?= $order['orderID'] ?>, '<?= $order['paymentMethod'] ?>', '<?= $order['paymentStatus'] ?>', <?= $order['totalAmount'] ?>)" 
                                class="flex-1 text-center bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-semibold">
                            <i class="fas fa-times-circle mr-2"></i>Hủy Đơn
                        </button>
                        <?php elseif ($cancelMessage): ?>
                        <button onclick="alert('<?= $cancelMessage ?>')" 
                                class="flex-1 text-center bg-gray-400 text-white px-4 py-2 rounded-lg cursor-not-allowed text-sm font-semibold">
                            <i class="fas fa-ban mr-2"></i>Không thể hủy
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/../layout/footer.php'; ?>

    <!-- Modal Hủy Đơn Hàng -->
    <div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-times-circle text-red-600 mr-2"></i>
                    Hủy Đơn Hàng
                </h3>
                <button onclick="closeCancelModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form id="cancelOrderForm" enctype="multipart/form-data" class="p-6">
                <input type="hidden" id="cancel_orderID" name="orderID">
                <input type="hidden" id="cancel_paymentMethod" name="paymentMethod">
                <input type="hidden" id="cancel_paymentStatus" name="paymentStatus">
                
                <!-- Thông tin đơn hàng -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-gray-800 mb-2">Thông tin đơn hàng</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Mã đơn hàng:</span>
                            <span class="font-semibold" id="modal_orderID">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tổng tiền:</span>
                            <span class="font-semibold text-red-600" id="modal_totalAmount">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phương thức thanh toán:</span>
                            <span class="font-semibold" id="modal_paymentMethod">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Lý do hủy đơn -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-comment-alt text-gray-500 mr-2"></i>
                        Lý do hủy đơn <span class="text-red-500">*</span>
                    </label>
                    <textarea name="cancelReason" id="cancelReason" rows="4" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              placeholder="Vui lòng cho chúng tôi biết lý do bạn muốn hủy đơn hàng này..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle"></i> Thông tin này giúp chúng tôi cải thiện dịch vụ tốt hơn
                    </p>
                </div>
                
                <!-- Phần thông tin hoàn tiền (chỉ hiện với đơn QR đã thanh toán) -->
                <div id="refundSection" class="hidden">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3 mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-yellow-800 mb-1">Thông tin quan trọng về hoàn tiền</h4>
                                <p class="text-sm text-yellow-700">
                                    Đơn hàng của bạn đã thanh toán. Vui lòng cung cấp thông tin ngân hàng và 
                                    <strong>ảnh chụp màn hình giao dịch chuyển khoản</strong> để chúng tôi xác minh và hoàn tiền.
                                    Thời gian xử lý: 24-48 giờ làm việc.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thông tin ngân hàng -->
                    <div class="mb-6">
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-university text-blue-600 mr-2"></i>
                            Thông tin tài khoản nhận hoàn tiền
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Số tài khoản <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="bankAccount" id="bankAccount"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="1234567890"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tên ngân hàng <span class="text-red-500">*</span>
                                </label>
                                <select name="bankName" id="bankName"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">-- Chọn ngân hàng --</option>
                                    <option value="Vietcombank">Vietcombank</option>
                                    <option value="Techcombank">Techcombank</option>
                                    <option value="BIDV">BIDV</option>
                                    <option value="VietinBank">VietinBank</option>
                                    <option value="Agribank">Agribank</option>
                                    <option value="MB Bank">MB Bank</option>
                                    <option value="ACB">ACB</option>
                                    <option value="VPBank">VPBank</option>
                                    <option value="TPBank">TPBank</option>
                                    <option value="Sacombank">Sacombank</option>
                                    <option value="HDBank">HDBank</option>
                                    <option value="SHB">SHB</option>
                                    <option value="OCB">OCB</option>
                                    <option value="MSB">MSB</option>
                                    <option value="VIB">VIB</option>
                                    <option value="SeABank">SeABank</option>
                                    <option value="LienVietPostBank">LienVietPostBank</option>
                                    <option value="PVcomBank">PVcomBank</option>
                                    <option value="BacABank">BacABank</option>
                                    <option value="VietBank">VietBank</option>
                                    <option value="NCB">NCB</option>
                                    <option value="OceanBank">OceanBank</option>
                                    <option value="ABBank">ABBank</option>
                                    <option value="NamABank">NamABank</option>
                                    <option value="VietCapitalBank">VietCapitalBank</option>
                                    <option value="BaoVietBank">BaoVietBank</option>
                                    <option value="CAKE by VPBank">CAKE by VPBank</option>
                                    <option value="Ubank by VPBank">Ubank by VPBank</option>
                                    <option value="Timo">Timo</option>
                                    <option value="ViettelMoney">ViettelMoney</option>
                                    <option value="MoMo">MoMo</option>
                                    <option value="ZaloPay">ZaloPay</option>
                                    <option value="Khác">Ngân hàng khác</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tên chủ tài khoản <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="accountHolder" id="accountHolder"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="NGUYEN VAN A"
                                   style="text-transform: uppercase;">
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle"></i> Tên phải khớp với tên trên tài khoản ngân hàng
                            </p>
                        </div>
                    </div>
                    
                    <!-- Upload ảnh chứng minh -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-image text-blue-600 mr-2"></i>
                            Ảnh chụp màn hình giao dịch chuyển khoản <span class="text-red-500">*</span>
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition">
                            <input type="file" name="proofImage" id="proofImage" accept="image/*" 
                                   class="hidden" onchange="previewImage(this)">
                            <label for="proofImage" class="cursor-pointer">
                                <div id="imagePreview" class="mb-4">
                                    <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                                    <p class="text-sm text-gray-600 mb-1">Nhấn để chọn ảnh hoặc kéo thả ảnh vào đây</p>
                                    <p class="text-xs text-gray-500">PNG, JPG, JPEG (Tối đa 5MB)</p>
                                </div>
                            </label>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-3">
                            <p class="text-xs text-blue-800">
                                <i class="fas fa-lightbulb mr-1"></i>
                                <strong>Lưu ý:</strong> Ảnh cần hiển thị rõ:
                            </p>
                            <ul class="text-xs text-blue-700 mt-2 ml-5 list-disc space-y-1">
                                <li>Số tiền chuyển khoản</li>
                                <li>Ngày giờ giao dịch</li>
                                <li>Số tài khoản người nhận/chuyển</li>
                                <li>Nội dung chuyển khoản (nếu có)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div class="flex gap-3 pt-4 border-t">
                    <button type="button" onclick="closeCancelModal()" 
                            class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                        <i class="fas fa-times mr-2"></i>Đóng
                    </button>
                    <button type="submit" id="submitCancelBtn"
                            class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">
                        <i class="fas fa-check mr-2"></i>Xác Nhận Hủy Đơn
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Mở modal hủy đơn
    function openCancelModal(orderID, paymentMethod, paymentStatus, totalAmount) {
        document.getElementById('cancel_orderID').value = orderID;
        document.getElementById('cancel_paymentMethod').value = paymentMethod;
        document.getElementById('cancel_paymentStatus').value = paymentStatus;
        
        document.getElementById('modal_orderID').textContent = '#' + orderID;
        document.getElementById('modal_totalAmount').textContent = new Intl.NumberFormat('vi-VN').format(totalAmount) + '₫';
        document.getElementById('modal_paymentMethod').textContent = paymentMethod === 'QR' ? 'Chuyển khoản QR' : 'COD';
        
        // Hiện phần hoàn tiền nếu đơn QR đã thanh toán
        if (paymentMethod === 'QR' && paymentStatus === 'Đã thanh toán') {
            document.getElementById('refundSection').classList.remove('hidden');
            document.getElementById('bankAccount').setAttribute('required', 'required');
            document.getElementById('bankName').setAttribute('required', 'required');
            document.getElementById('accountHolder').setAttribute('required', 'required');
            document.getElementById('proofImage').setAttribute('required', 'required');
        } else {
            document.getElementById('refundSection').classList.add('hidden');
            document.getElementById('bankAccount').removeAttribute('required');
            document.getElementById('bankName').removeAttribute('required');
            document.getElementById('accountHolder').removeAttribute('required');
            document.getElementById('proofImage').removeAttribute('required');
        }
        
        document.getElementById('cancelModal').classList.remove('hidden');
    }
    
    // Đóng modal
    function closeCancelModal() {
        document.getElementById('cancelModal').classList.add('hidden');
        document.getElementById('cancelOrderForm').reset();
        document.getElementById('imagePreview').innerHTML = `
            <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
            <p class="text-sm text-gray-600 mb-1">Nhấn để chọn ảnh hoặc kéo thả ảnh vào đây</p>
            <p class="text-xs text-gray-500">PNG, JPG, JPEG (Tối đa 5MB)</p>
        `;
    }
    
    // Preview ảnh
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" class="max-w-full h-48 mx-auto rounded-lg mb-2">
                    <p class="text-sm text-green-600 font-semibold">
                        <i class="fas fa-check-circle mr-1"></i>Đã chọn: ${input.files[0].name}
                    </p>
                    <button type="button" onclick="document.getElementById('proofImage').value=''; previewImage({files: null})" 
                            class="mt-2 text-xs text-red-600 hover:text-red-800">
                        <i class="fas fa-times mr-1"></i>Chọn ảnh khác
                    </button>
                `;
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.innerHTML = `
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                <p class="text-sm text-gray-600 mb-1">Nhấn để chọn ảnh hoặc kéo thả ảnh vào đây</p>
                <p class="text-xs text-gray-500">PNG, JPG, JPEG (Tối đa 5MB)</p>
            `;
        }
    }
    
    // Submit form hủy đơn
    document.getElementById('cancelOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitCancelBtn');
        const formData = new FormData(this);
        
        // Disable button và hiện loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
        
        fetch('<?php echo BASE_URL; ?>api/cancel_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeCancelModal();
                location.reload(); // Reload trang để cập nhật trạng thái
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại!');
        })
        .finally(() => {
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Xác Nhận Hủy Đơn';
        });
    });
    
    // Tự động uppercase tên chủ tài khoản
    document.getElementById('accountHolder')?.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // ============== YÊU CẦU HOÀN TRẢ ==============
    
    function openReturnModal(orderID, totalAmount) {
        console.log('openReturnModal called:', orderID, totalAmount);
        document.getElementById('return_orderID').value = orderID;
        document.getElementById('return_modal_orderID').textContent = '#' + orderID;
        document.getElementById('return_modal_totalAmount').textContent = totalAmount.toLocaleString('vi-VN') + ' đ';
        document.getElementById('returnModal').classList.remove('hidden');
        document.getElementById('returnModal').classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
    
    function closeReturnModal() {
        document.getElementById('returnModal').classList.add('hidden');
        document.getElementById('returnModal').classList.remove('flex');
        document.body.style.overflow = 'auto';
        document.getElementById('returnOrderForm').reset();
        document.getElementById('returnImagePreview').innerHTML = `
            <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
            <p class="text-sm text-gray-600 mb-1">Nhấn để chọn ảnh hoặc kéo thả ảnh vào đây</p>
            <p class="text-xs text-gray-500">PNG, JPG, JPEG (Tối đa 5 ảnh, mỗi ảnh 5MB)</p>
        `;
    }
    
    // Hàm xử lý submit form hoàn trả
    function handleReturnFormSubmit(e) {
        e.preventDefault();
        console.log('Return form submitted!');
        
        const submitBtn = document.getElementById('submitReturnBtn');
        const formData = new FormData(this);
        
        // Disable button và hiện loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang gửi yêu cầu...';
        
        fetch('<?php echo BASE_URL; ?>api/create_return_request.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeReturnModal();
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại!');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Gửi Yêu Cầu';
        });
    }
    
    function previewReturnImages(input) {
        const preview = document.getElementById('returnImagePreview');
        if (input.files && input.files.length > 0) {
            if (input.files.length > 5) {
                alert('Chỉ được chọn tối đa 5 ảnh!');
                input.value = '';
                return;
            }
            
            let html = '<div class="grid grid-cols-3 gap-2">';
            for (let i = 0; i < input.files.length; i++) {
                const file = input.files[i];
                const reader = new FileReader();
                reader.onload = function(e) {
                    html += `
                        <div class="relative">
                            <img src="${e.target.result}" class="w-full h-32 object-cover rounded border">
                            <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 truncate">${file.name}</div>
                        </div>
                    `;
                    if (i === input.files.length - 1) {
                        html += '</div>';
                        html += `
                            <button type="button" onclick="document.getElementById('returnImages').value=''; previewReturnImages({files: null})" 
                                    class="mt-3 text-sm text-red-600 hover:text-red-800">
                                <i class="fas fa-times mr-1"></i>Chọn lại ảnh
                            </button>
                        `;
                        preview.innerHTML = html;
                    }
                };
                reader.readAsDataURL(file);
            }
        } else {
            preview.innerHTML = `
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                <p class="text-sm text-gray-600 mb-1">Nhấn để chọn ảnh hoặc kéo thả ảnh vào đây</p>
                <p class="text-xs text-gray-500">PNG, JPG, JPEG (Tối đa 5 ảnh, mỗi ảnh 5MB)</p>
            `;
        }
    }
    
    // Tự động uppercase tên chủ tài khoản (hoàn trả)
    document.getElementById('return_accountHolder')?.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Event delegation for return form submit - Đảm bảo bắt được sự kiện submit
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'returnOrderForm') {
            console.log('Delegated submit caught for returnOrderForm');
            handleReturnFormSubmit.call(e.target, e);
        }
    });
    
    // Xem chi tiết yêu cầu hoàn trả
    async function viewReturnRequest(orderID) {
        try {
            const response = await fetch('<?php echo BASE_URL; ?>api/get_return_request.php?orderID=' + orderID);
            const result = await response.json();
            
            if (result.success) {
                showReturnDetails(result.data);
            } else {
                alert('Lỗi: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin!');
        }
    }
    
    function showReturnDetails(data) {
        const statusColors = {
            'Chờ xử lý': 'yellow',
            'Đang yêu cầu': 'yellow',
            'Đã chấp nhận': 'green',
            'Đã từ chối': 'red',
            'Đã hoàn tiền': 'purple',
            'Đã hoàn trả': 'purple'
        };
        
        const statusIcons = {
            'Chờ xử lý': 'fa-clock',
            'Đang yêu cầu': 'fa-clock',
            'Đã chấp nhận': 'fa-check-circle',
            'Đã từ chối': 'fa-times-circle',
            'Đã hoàn tiền': 'fa-money-bill-wave',
            'Đã hoàn trả': 'fa-check-double'
        };
        
        const color = statusColors[data.status] || 'gray';
        const icon = statusIcons[data.status] || 'fa-info-circle';
        
        let imagesHtml = '';
        if (data.images && data.images.length > 0) {
            imagesHtml = '<div class="grid grid-cols-3 gap-2 mt-2">';
            data.images.forEach(img => {
                imagesHtml += `<a href="<?php echo BASE_URL; ?>${img}" target="_blank" class="block">
                    <img src="<?php echo BASE_URL; ?>${img}" class="w-full h-32 object-cover rounded border hover:opacity-75">
                </a>`;
            });
            imagesHtml += '</div>';
        } else {
            imagesHtml = '<p class="text-gray-500 italic mt-2 text-sm">Không có ảnh đính kèm</p>';
        }
        
        let adminNoteHtml = '';
        if (data.adminNote) {
            adminNoteHtml = `
                <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-400 rounded">
                    <p class="text-sm font-semibold text-blue-800 mb-1">
                        <i class="fas fa-comment-dots mr-1"></i>Phản hồi từ GODIFA:
                    </p>
                    <p class="text-sm text-blue-700 whitespace-pre-line">${data.adminNote}</p>
                    ${data.processedAt ? `<p class="text-xs text-blue-600 mt-2">Cập nhật lúc: ${new Date(data.processedAt).toLocaleString('vi-VN')}</p>` : ''}
                    ${data.adminName ? `<p class="text-xs text-blue-600">Bởi: ${data.adminName}</p>` : ''}
                </div>
            `;
        }
        
        const html = `
            <div class="space-y-4">
                <div class="bg-gradient-to-r from-${color}-50 to-${color}-100 p-4 rounded-lg border border-${color}-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Trạng thái yêu cầu:</p>
                            <p class="text-xl font-bold text-${color}-800 mt-1">
                                <i class="fas ${icon} mr-2"></i>${data.status}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Đơn hàng:</p>
                            <p class="text-xl font-bold">#${data.orderID}</p>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Ngày yêu cầu:</p>
                        <p class="font-semibold">${new Date(data.createdAt).toLocaleString('vi-VN')}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Số tiền:</p>
                        <p class="font-semibold text-red-600">${parseInt(data.totalAmount).toLocaleString('vi-VN')} đ</p>
                    </div>
                </div>
                
                <!-- Thông tin tài khoản nhận tiền -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <h5 class="font-semibold text-gray-800 mb-2 flex items-center">
                        <i class="fas fa-university text-yellow-600 mr-2"></i>
                        Thông tin tài khoản nhận tiền hoàn
                    </h5>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-gray-600">Ngân hàng:</p>
                            <p class="font-semibold">${data.bankName || 'Chưa cập nhật'}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Số tài khoản:</p>
                            <p class="font-semibold font-mono">${data.bankAccount || 'Chưa cập nhật'}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-gray-600">Chủ tài khoản:</p>
                            <p class="font-semibold">${data.accountHolder || 'Chưa cập nhật'}</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">Lý do hoàn trả:</p>
                    <div class="bg-gray-50 p-3 rounded border">
                        <p class="text-sm whitespace-pre-line">${data.reason}</p>
                    </div>
                </div>
                
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-2">Ảnh chứng minh:</p>
                    ${imagesHtml}
                </div>
                
                ${adminNoteHtml}
            </div>
        `;
        
        document.getElementById('viewReturnModalContent').innerHTML = html;
        document.getElementById('viewReturnModal').classList.remove('hidden');
        document.getElementById('viewReturnModal').classList.add('flex');
    }
    
    function closeViewReturnModal() {
        document.getElementById('viewReturnModal').classList.add('hidden');
        document.getElementById('viewReturnModal').classList.remove('flex');
    }
    </script>
    
    <!-- Modal Yêu Cầu Hoàn Trả -->
    <div id="returnModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-undo text-orange-600 mr-2"></i>
                    Yêu Cầu Hoàn Trả Đơn Hàng
                </h3>
                <button onclick="closeReturnModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form id="returnOrderForm" enctype="multipart/form-data" class="p-6">
                <input type="hidden" id="return_orderID" name="orderID">
                
                <!-- Thông tin đơn hàng -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-gray-800 mb-2">Thông tin đơn hàng</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Mã đơn hàng:</span>
                            <span class="font-semibold" id="return_modal_orderID">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tổng tiền:</span>
                            <span class="font-semibold text-red-600" id="return_modal_totalAmount">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Lý do hoàn trả -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-comment-alt text-gray-500 mr-2"></i>
                        Lý do hoàn trả <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reason" id="returnReason" rows="4" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                              placeholder="Vui lòng mô tả chi tiết vấn đề với sản phẩm (lỗi, hư hỏng, không đúng mô tả...)"></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle"></i> Mô tả chi tiết giúp chúng tôi xử lý nhanh hơn
                    </p>
                </div>
                
                <!-- Thông tin tài khoản nhận tiền hoàn -->
                <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-university text-yellow-600 mr-2"></i>
                        Thông tin tài khoản nhận tiền hoàn
                    </h4>
                    
                    <!-- Tên ngân hàng -->
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            Tên ngân hàng <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="bankName" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                               placeholder="VD: Vietcombank, Techcombank, MB Bank...">
                    </div>
                    
                    <!-- Số tài khoản -->
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            Số tài khoản <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="bankAccount" required pattern="[0-9]{9,16}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"
                               placeholder="Nhập số tài khoản (9-16 chữ số)">
                    </div>
                    
                    <!-- Tên chủ tài khoản -->
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            Tên chủ tài khoản <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="accountHolder" id="return_accountHolder" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 uppercase"
                               placeholder="VD: NGUYEN VAN A"
                               style="text-transform: uppercase;">
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle"></i> Vui lòng nhập CHÍNH XÁC theo tên trên thẻ ngân hàng (IN HOA, không dấu)
                        </p>
                    </div>
                </div>
                
                <!-- Upload ảnh chứng minh -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-images text-gray-500 mr-2"></i>
                        Ảnh chứng minh (Tùy chọn, tối đa 5 ảnh)
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-orange-500 transition"
                         onclick="document.getElementById('returnImages').click()">
                        <div id="returnImagePreview">
                            <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-3"></i>
                            <p class="text-sm text-gray-600 mb-1">Nhấn để chọn ảnh hoặc kéo thả ảnh vào đây</p>
                            <p class="text-xs text-gray-500">PNG, JPG, JPEG (Tối đa 5 ảnh, mỗi ảnh 5MB)</p>
                        </div>
                    </div>
                    <input type="file" id="returnImages" name="images[]" multiple accept="image/*" 
                           class="hidden" onchange="previewReturnImages(this)">
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle"></i> Ảnh chụp sản phẩm lỗi, hư hỏng hoặc không đúng mô tả giúp chúng tôi xử lý nhanh hơn
                    </p>
                </div>
                
                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="button" onclick="closeReturnModal()" 
                            class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-semibold">
                        <i class="fas fa-times mr-2"></i>Hủy
                    </button>
                    <button type="submit" id="submitReturnBtn"
                            class="flex-1 px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition font-semibold">
                        <i class="fas fa-check mr-2"></i>Gửi Yêu Cầu
                    </button>
                </div>
                
                <div class="mt-4 bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 text-xl mr-3 mt-1"></i>
                        <div class="text-sm text-blue-700">
                            <p class="font-semibold mb-1">Lưu ý:</p>
                            <ul class="list-disc list-inside space-y-1 text-xs">
                                <li>Yêu cầu hoàn trả chỉ áp dụng cho đơn hàng đã giao thành công</li>
                                <li>Thời gian xử lý: 24-48 giờ làm việc</li>
                                <li>Chúng tôi sẽ liên hệ với bạn qua số điện thoại đã đăng ký</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Xem Chi Tiết Yêu Cầu Hoàn Trả -->
    <div id="viewReturnModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-clipboard-list text-blue-600 mr-2"></i>
                    Chi Tiết Yêu Cầu Hoàn Trả
                </h3>
                <button onclick="closeViewReturnModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div id="viewReturnModalContent" class="p-6">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
                    <p class="text-gray-500 mt-2">Đang tải...</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
