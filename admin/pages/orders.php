<?php
/**
 * Quản lý Đơn hàng
 * File: admin/pages/orders.php
 * MVC Pattern: Xử lý logic trực tiếp trong page
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

if (!hasPermission('view_orders')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

require_once __DIR__ . '/../../controller/admin/cOrder.php';
require_once __DIR__ . '/../../model/mOrder.php';

$orderController = new cOrder();
$orderModel = new Order();

// AJAX: Lấy chi tiết đơn hàng
if (isset($_GET['action']) && $_GET['action'] === 'get_order_detail' && isset($_GET['orderID'])) {
    $oid = intval($_GET['orderID']);
    $order = $orderController->getOrderById($oid);
    if (!$order) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
        exit;
    }
    
    $details = $orderModel->getOrderDetails($oid);
    $totalProducts = 0;
    foreach ($details as $d) { 
        $totalProducts += (int)($d['quantity'] ?? 0); 
    }

    // Phân loại trạng thái
    $paymentStatus = $order['paymentStatus'];
    $deliveryStatus = $order['deliveryStatus'];
    $statusBucket = 'pending';
    if ($paymentStatus === 'Đã hủy' || $deliveryStatus === 'Đã hủy') {
        $statusBucket = 'cancelled';
    } elseif ($deliveryStatus === 'Hoàn thành') {
        $statusBucket = 'completed';
    } elseif ($paymentStatus === 'Đã thanh toán' && ($deliveryStatus === 'Đang giao' || $deliveryStatus === 'Đang xử lý')) {
        $statusBucket = 'processing';
    }

    $statusStyles = [
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'text-yellow-600'],
        'processing' => ['label' => 'Đang xử lý', 'class' => 'text-blue-600'],
        'completed' => ['label' => 'Hoàn thành', 'class' => 'text-green-600'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'text-red-600'],
    ];

    $statusDisplay = $statusStyles[$statusBucket];

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'order' => $order,
        'details' => $details,
        'totalProducts' => $totalProducts,
        'statusBucket' => $statusBucket,
        'statusDisplay' => $statusDisplay
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$success = '';
$error = '';

// POST actions (confirm / cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Confirm order
    if (isset($_POST['confirm_order']) && (hasPermission('update_order_status') || hasPermission('manage_orders'))) {
        $orderID = intval($_POST['orderID']);
        $result = $orderModel->updateOrderStatus($orderID, 'Đã thanh toán', 'Đang xử lý');
        $success = $result ? "Đã xác nhận đơn hàng #$orderID thành công!" : "Lỗi khi xác nhận đơn hàng!";
    }

    // Cancel order
    if (isset($_POST['cancel_order']) && (hasPermission('update_order_status') || hasPermission('manage_orders'))) {
        $orderID = intval($_POST['orderID']);
        $cancelReason = trim($_POST['cancelReason'] ?? 'Không rõ lý do');
        $result = $orderModel->updateOrderStatus($orderID, 'Đã hủy', 'Đã hủy', $cancelReason);
        $success = $result ? "Đã hủy đơn hàng #$orderID. Lý do: $cancelReason" : "Lỗi khi hủy đơn hàng!";
    }
}

// SEARCH handling
$isSearching = false;
$searchKeyword = '';
if (isset($_GET['search'])) {
    $isSearching = true;
    $searchKeyword = trim($_GET['keyword'] ?? '');
    if ($searchKeyword !== '') {
        $orders = $orderController->searchOrders($searchKeyword);
    } else {
        $orders = $orderController->getAllOrders();
        $error = 'Vui lòng nhập từ khóa tìm kiếm!';
    }
} else {
    $orders = $orderController->getAllOrders();
}

// Get all order details for product count
$allOrderDetails = [];
foreach ($orders as &$order) {
    $oid = $order['orderID'];
    $details = $orderModel->getOrderDetails($oid);
    $allOrderDetails[$oid] = $details;
    
    // Count total products
    $totalProducts = 0;
    foreach ($details as $d) {
        $totalProducts += (int)($d['quantity'] ?? 0);
    }
    $order['totalProducts'] = $totalProducts;
}
unset($order);

// Statistics
$stats = [
    'total' => count($orders),
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0
];

foreach ($orders as $order) {
    $ps = $order['paymentStatus'] ?? '';
    $ds = $order['deliveryStatus'] ?? '';
    
    if ($ps === 'Đã hủy' || $ds === 'Đã hủy') {
        $stats['cancelled']++;
    } elseif ($ds === 'Hoàn thành') {
        $stats['completed']++;
    } elseif ($ps === 'Đã thanh toán' && ($ds === 'Đang giao' || $ds === 'Đang xử lý')) {
        $stats['processing']++;
    } else {
        $stats['pending']++;
    }
}

$pageTitle = 'Quản lý Đơn hàng';
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
                        <i class="fas fa-shopping-cart text-blue-500 mr-2"></i>
                        Quản lý Đơn hàng
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng số: <strong><?php echo $stats['total'] ?? 0; ?></strong> đơn hàng
                        <span class="mx-2">|</span>
                        Chờ xử lý: <span class="text-yellow-600 font-semibold"><?php echo $stats['pending'] ?? 0; ?></span>
                        <span class="mx-2">|</span>
                        Đang xử lý: <span class="text-blue-600 font-semibold"><?php echo $stats['processing'] ?? 0; ?></span>
                        <span class="mx-2">|</span>
                        Hoàn thành: <span class="text-green-600 font-semibold"><?php echo $stats['completed'] ?? 0; ?></span>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-4 md:p-6">
            <!-- Alerts -->
            <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?php echo $success; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Filter Tabs -->
            <div class="mb-4 flex flex-wrap gap-2">
                <button onclick="filterOrders('all')" class="filter-btn active px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                    <i class="fas fa-list mr-1"></i> Tất cả (<?php echo $stats['total'] ?? 0; ?>)
                </button>
                <button onclick="filterOrders('pending')" class="filter-btn px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                    <i class="fas fa-clock mr-1"></i> Chờ xử lý (<?php echo $stats['pending'] ?? 0; ?>)
                </button>
                <button onclick="filterOrders('processing')" class="filter-btn px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                    <i class="fas fa-sync mr-1"></i> Đang xử lý (<?php echo $stats['processing'] ?? 0; ?>)
                </button>
                <button onclick="filterOrders('completed')" class="filter-btn px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                    <i class="fas fa-check mr-1"></i> Hoàn thành (<?php echo $stats['completed'] ?? 0; ?>)
                </button>
                <button onclick="filterOrders('cancelled')" class="filter-btn px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                    <i class="fas fa-times mr-1"></i> Đã hủy (<?php echo $stats['cancelled'] ?? 0; ?>)
                </button>
            </div>

            <!-- Search Form -->
            <div class="bg-white rounded-lg shadow p-4 mb-4">
                <form method="GET" action="" class="flex gap-3 items-center">
                    <input type="hidden" name="page" value="orders">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            id="keyword" 
                            name="keyword" 
                            value="<?php echo htmlspecialchars($searchKeyword); ?>"
                            placeholder="Nhập mã đơn hàng (VD: 123) hoặc số điện thoại (VD: 0812412573)..."
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        name="search"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2 whitespace-nowrap"
                    >
                        <i class="fas fa-search"></i>
                        Tìm kiếm
                    </button>
                    
                    <?php if ($isSearching): ?>
                    <a 
                        href="?page=orders" 
                        class="px-6 py-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2 whitespace-nowrap"
                    >
                        <i class="fas fa-redo"></i>
                        Xem tất cả
                    </a>
                    <?php endif; ?>
                </form>
                
                <?php if ($isSearching): ?>
                <div class="mt-3 text-sm">
                    <?php if (count($orders) > 0): ?>
                    <div class="text-green-700 bg-green-50 px-3 py-2 rounded">
                        <i class="fas fa-check-circle"></i>
                        Tìm thấy <strong><?php echo count($orders); ?></strong> đơn hàng với từ khóa: <strong>"<?php echo htmlspecialchars($searchKeyword); ?>"</strong>
                    </div>
                    <?php else: ?>
                    <div class="text-orange-700 bg-orange-50 px-3 py-2 rounded">
                        <i class="fas fa-exclamation-triangle"></i>
                        Không tìm thấy đơn hàng nào với từ khóa: <strong>"<?php echo htmlspecialchars($searchKeyword); ?>"</strong>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Mã</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Ngày tạo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Khách hàng</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Phương thức</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Thanh toán</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Giao hàng</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">SL</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Tổng tiền</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-shopping-cart text-4xl mb-2 text-gray-300"></i>
                                    <p>Chưa có đơn hàng nào</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <?php
                                // Safe access
                                $orderID = $order['orderID'] ?? 0;
                                $customerName = $order['customerName'] ?? 'N/A';
                                $phone = $order['phone'] ?? '';
                                $orderDate = $order['orderDate'] ?? '';
                                $totalAmount = $order['totalAmount'] ?? 0;
                                $paymentMethod = $order['paymentMethod'] ?? 'N/A';
                                $paymentStatus = $order['paymentStatus'] ?? 'Chờ thanh toán';
                                $deliveryStatus = $order['deliveryStatus'] ?? 'Chờ xử lý';
                                $totalProducts = $order['totalProducts'] ?? 0;
                                
                                // Phương thức thanh toán - Hiển thị icon + text
                                $isCOD = (strtolower($paymentMethod) === 'cod' || stripos($paymentMethod, 'cod') !== false);
                                $isQR = (stripos($paymentMethod, 'qr') !== false || 
                                         stripos($paymentMethod, 'bank') !== false || 
                                         stripos($paymentMethod, 'transfer') !== false);
                                
                                if ($isCOD) {
                                    $methodIcon = 'fa-money-bill-wave';
                                    $methodText = 'COD';
                                    $methodColor = 'bg-yellow-50 text-yellow-700 border-yellow-200';
                                } elseif ($isQR) {
                                    $methodIcon = 'fa-qrcode';
                                    $methodText = 'QR Code';
                                    $methodColor = 'bg-blue-50 text-blue-700 border-blue-200';
                                } else {
                                    $methodIcon = 'fa-credit-card';
                                    $methodText = htmlspecialchars($paymentMethod);
                                    $methodColor = 'bg-gray-50 text-gray-700 border-gray-200';
                                }
                                
                                // Trạng thái thanh toán (Tiếng Việt)
                                $paymentStatusLabels = [
                                    'Đã thanh toán' => 'Đã thanh toán',
                                    'Chờ thanh toán' => 'Chờ thanh toán',
                                    'Đã hủy' => 'Đã hủy',
                                    // Backward compatibility
                                    'completed' => 'Đã thanh toán',
                                    'pending' => 'Chờ thanh toán',
                                    'cancelled' => 'Đã hủy'
                                ];
                                
                                $paymentColors = [
                                    'Đã thanh toán' => 'bg-green-100 text-green-800 border-green-300',
                                    'Chờ thanh toán' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    'Đã hủy' => 'bg-red-100 text-red-800 border-red-300',
                                    // Backward compatibility
                                    'completed' => 'bg-green-100 text-green-800 border-green-300',
                                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    'cancelled' => 'bg-red-100 text-red-800 border-red-300'
                                ];
                                
                                // Trạng thái giao hàng (Tiếng Việt)
                                $deliveryColors = [
                                    'Hoàn thành' => 'bg-green-100 text-green-800 border-green-300',
                                    'Đang giao' => 'bg-blue-100 text-blue-800 border-blue-300',
                                    'Chờ xử lý' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    'Đã hủy' => 'bg-red-100 text-red-800 border-red-300',
                                    // Backward compatibility
                                    'completed' => 'bg-green-100 text-green-800 border-green-300',
                                    'shipping' => 'bg-blue-100 text-blue-800 border-blue-300',
                                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    'cancelled' => 'bg-red-100 text-red-800 border-red-300'
                                ];
                                
                                $deliveryLabels = [
                                    'Hoàn thành' => 'Hoàn thành',
                                    'Đang giao' => 'Đang giao',
                                    'Chờ xử lý' => 'Chờ xử lý',
                                    'Đã hủy' => 'Đã hủy',
                                    // Backward compatibility
                                    'completed' => 'Hoàn thành',
                                    'shipping' => 'Đang giao',
                                    'pending' => 'Chờ xử lý',
                                    'cancelled' => 'Giao thất bại'
                                ];
                                
                                // Lấy labels
                                $paymentStatusLabel = $paymentStatusLabels[$paymentStatus] ?? 'Chờ thanh toán';
                                $paymentColor = $paymentColors[$paymentStatus] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                                $deliveryColor = $deliveryColors[$deliveryStatus] ?? 'bg-yellow-100 text-yellow-800 border-yellow-300';
                                $deliveryLabel = $deliveryLabels[$deliveryStatus] ?? 'Chờ xử lý';
                                
                                // Xác định trạng thái filter (cho filter buttons)
                                $filterStatus = 'pending'; // Mặc định
                                if ($paymentStatus == 'Đã hủy' || $deliveryStatus == 'Đã hủy') {
                                    $filterStatus = 'cancelled';
                                } elseif ($deliveryStatus == 'Hoàn thành') {
                                    $filterStatus = 'completed';
                                } elseif ($paymentStatus == 'Đã thanh toán' && ($deliveryStatus == 'Đang giao' || $deliveryStatus == 'Đang xử lý')) {
                                    $filterStatus = 'processing';
                                } else {
                                    $filterStatus = 'pending'; // Chờ xử lý, Chờ thanh toán
                                }
                                ?>
                                <tr class="hover:bg-gray-50 order-row cursor-pointer" data-status="<?php echo $filterStatus; ?>" onclick='viewOrderDetailAjax(<?php echo $orderID; ?>)'>
                                    <!-- Mã -->
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-blue-600">#<?php echo $orderID; ?></div>
                                    </td>
                                    
                                    <!-- Ngày tạo -->
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($orderDate)); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo strtoupper(date('D', strtotime($orderDate))); ?></div>
                                    </td>
                                    
                                    <!-- Khách hàng -->
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($customerName); ?></div>
                                        <?php if ($phone): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($phone); ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Phương thức -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-3 py-1 <?php echo $methodColor; ?> border rounded-full text-xs font-medium inline-flex items-center justify-center">
                                            <i class="fas <?php echo $methodIcon; ?> mr-1"></i><?php echo $methodText; ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Thanh toán -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-3 py-1 <?php echo $paymentColor; ?> border rounded-full text-xs font-medium">
                                            <?php echo htmlspecialchars($paymentStatusLabel); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Giao hàng -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-3 py-1 <?php echo $deliveryColor; ?> border rounded-full text-xs font-medium">
                                            <?php echo $deliveryLabel; ?>
                                        </span>
                                    </td>
                                    
                                    <!-- SL sản phẩm -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-semibold text-gray-900"><?php echo $totalProducts; ?></span>
                                    </td>
                                    
                                    <!-- Tổng tiền -->
                                    <td class="px-4 py-3 text-right">
                                        <div class="font-bold text-gray-900">
                                            <?php echo number_format($totalAmount, 0, ',', '.'); ?> đ
                                        </div>
                                    </td>
                                    
                                    <!-- Thao tác -->
                                    <td class="px-4 py-3 text-center" onclick="event.stopPropagation()">
                                        <div class="flex justify-center items-center gap-2">
                                            <!-- Xác nhận đơn (Owner, Admin, Sales) -->
                                            <?php if (($paymentStatus == 'Chờ thanh toán') && hasPermission('update_order_status')): ?>
                                            <button onclick='confirmOrder(<?php echo $orderID; ?>, "<?php echo htmlspecialchars($customerName); ?>")' 
                                                    class="p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition-all duration-200" 
                                                    title="Xác nhận đơn hàng">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <!-- Hủy đơn (Owner, Admin, Sales) -->
                                            <?php if (($paymentStatus != 'Đã hủy') && hasPermission('update_order_status')): ?>
                                            <button onclick='openCancelOrderModal(<?php echo $orderID; ?>, "<?php echo htmlspecialchars($customerName); ?>")' 
                                                    class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all duration-200" 
                                                    title="Hủy đơn hàng">
                                                <i class="fas fa-times"></i>
                                            </button>
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

<!-- Modal: Xem chi tiết đơn hàng -->
<div id="detailModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-receipt text-blue-500 mr-2"></i>
                Chi tiết đơn hàng <span id="detail_orderID" class="text-blue-600"></span>
            </h3>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <div class="mt-4 space-y-4">
            <!-- Thông tin khách hàng -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <h4 class="font-bold text-gray-700 mb-2">
                    <i class="fas fa-user mr-2"></i>Thông tin khách hàng
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div><strong>Tên:</strong> <span id="detail_customerName"></span></div>
                    <div><strong>SĐT:</strong> <span id="detail_phone"></span></div>
                </div>
            </div>
            
            <!-- Thông tin đơn hàng -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-bold text-gray-700 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Thông tin đơn hàng
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div><strong>Ngày đặt:</strong> <span id="detail_orderDate"></span></div>
                    <div><strong>Thanh toán:</strong> <span id="detail_paymentMethod"></span></div>
                    <div><strong>Trạng thái:</strong> <span id="detail_status"></span></div>
                    <div><strong>Nhân viên xử lý:</strong> <span id="detail_staffName"></span></div>
                </div>
            </div>
            
            <!-- Lý do hủy đơn (nếu có) -->
            <div id="cancelReasonBox" class="hidden bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                <h4 class="font-bold text-red-700 mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Lý do hủy đơn
                </h4>
                <p id="detail_cancelReason" class="text-sm text-gray-700 italic"></p>
            </div>
            
            <!-- Chi tiết sản phẩm (sẽ load bằng AJAX hoặc PHP) -->
            <div class="bg-green-50 p-4 rounded-lg">
                <h4 class="font-bold text-gray-700 mb-2">
                    <i class="fas fa-box mr-2"></i>Chi tiết sản phẩm
                </h4>
                <div id="detail_products" class="text-sm">
                    <p class="text-gray-500">Đang tải...</p>
                </div>
            </div>
            
            <!-- Tổng tiền -->
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="flex justify-between items-center">
                    <h4 class="font-bold text-gray-700 text-lg">
                        <i class="fas fa-money-bill-wave mr-2"></i>Tổng tiền:
                    </h4>
                    <span id="detail_totalAmount" class="text-2xl font-bold text-blue-600"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Hủy đơn hàng -->
<div id="cancelOrderModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-times-circle text-red-500 mr-2"></i>
                Hủy đơn hàng
            </h3>
            <button onclick="closeCancelOrderModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4">
            <input type="hidden" name="cancel_order" value="1">
            <input type="hidden" name="orderID" id="cancel_orderID">
            
            <div class="mb-4">
                <p class="text-gray-700">
                    Bạn có chắc muốn hủy đơn hàng <strong id="cancel_orderLabel"></strong>?
                </p>
                <p class="text-sm text-gray-600 mt-2">
                    Khách hàng: <strong id="cancel_customerName"></strong>
                </p>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Lý do hủy <span class="text-red-500">*</span>
                </label>
                <textarea name="cancelReason" required rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                          placeholder="Nhập lý do hủy đơn..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeCancelOrderModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-ban mr-1"></i> Hủy đơn
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Không còn embed toàn bộ chi tiết sản phẩm (tối ưu). Sử dụng AJAX khi người dùng mở chi tiết.

// Filter orders by status
function filterOrders(status) {
    const rows = document.querySelectorAll('.order-row');
    const btns = document.querySelectorAll('.filter-btn');
    
    // Update active button
    btns.forEach(btn => btn.classList.remove('active', 'bg-blue-600', 'text-white'));
    event.target.closest('.filter-btn').classList.add('active', 'bg-blue-600', 'text-white');
    
    // Filter rows
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// View order details
// AJAX lấy chi tiết đơn hàng
function viewOrderDetailAjax(orderID) {
    // Reset modal content trước khi load
    document.getElementById('detail_orderID').textContent = '#'+orderID;
    document.getElementById('detail_customerName').textContent = '...';
    document.getElementById('detail_phone').textContent = '...';
    document.getElementById('detail_orderDate').textContent = '...';
    document.getElementById('detail_paymentMethod').textContent = '...';
    document.getElementById('detail_status').innerHTML = '<span class="text-gray-500">Đang tải...</span>';
    document.getElementById('detail_staffName').textContent = '...';
    document.getElementById('detail_totalAmount').textContent = '...';
    document.getElementById('detail_products').innerHTML = '<p class="text-gray-500">Đang tải sản phẩm...</p>';
    document.getElementById('cancelReasonBox').classList.add('hidden');
    document.getElementById('detailModal').classList.remove('hidden');

    fetch(`?page=orders&action=get_order_detail&orderID=${orderID}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('detail_products').innerHTML = '<p class="text-red-600">'+(data.message||'Lỗi tải dữ liệu')+'</p>';
                return;
            }
            const order = data.order;
            const details = data.details;
            const statusDisplay = data.statusDisplay;
            
            document.getElementById('detail_customerName').textContent = order.customerName || 'N/A';
            document.getElementById('detail_phone').textContent = order.phone || 'N/A';
            document.getElementById('detail_orderDate').textContent = new Date(order.orderDate).toLocaleString('vi-VN');
            document.getElementById('detail_paymentMethod').textContent = order.paymentMethod || 'N/A';
            document.getElementById('detail_staffName').textContent = order.staffName || 'Chưa xử lý';
            document.getElementById('detail_totalAmount').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(order.totalAmount);
            document.getElementById('detail_status').innerHTML = `<span class="font-bold ${statusDisplay.class}">${statusDisplay.label}</span>`;
            
            if (order.cancelReason && (order.paymentStatus === 'Đã hủy' || data.statusBucket === 'cancelled')) {
                document.getElementById('detail_cancelReason').textContent = order.cancelReason;
                document.getElementById('cancelReasonBox').classList.remove('hidden');
            }
            // Render products
            if (details.length === 0) {
                document.getElementById('detail_products').innerHTML = '<p class="text-gray-500 italic">Không có sản phẩm</p>';
            } else {
                let html = '<div class="space-y-3">';
                details.forEach(item => {
                    const subtotal = item.quantity * item.price;
                    const img = item.image ? `/GODIFA/image/${item.image}` : '/GODIFA/image/no-image.png';
                    html += `
                        <div class="flex items-center gap-3 p-2 bg-white rounded border">
                            <img src="${img}" alt="${item.productName}" class="w-16 h-16 object-cover rounded" onerror="this.src='/GODIFA/image/no-image.png'">
                            <div class="flex-1">
                                <h5 class="font-semibold text-gray-900">${item.productName}</h5>
                                <p class="text-sm text-gray-600">Số lượng: <span class="font-medium">${item.quantity}</span> × <span class="font-medium">${new Intl.NumberFormat('vi-VN').format(item.price)} đ</span></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-blue-600">${new Intl.NumberFormat('vi-VN').format(subtotal)} đ</p>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('detail_products').innerHTML = html;
            }
        })
        .catch(err => {
            document.getElementById('detail_products').innerHTML = '<p class="text-red-600">Lỗi khi tải dữ liệu</p>';
            console.error(err);
        });
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

// Xác nhận đơn hàng
function confirmOrder(orderID, customerName) {
    if (confirm(`Xác nhận đơn hàng #${orderID} của khách "${customerName}"?\n\nTrạng thái sẽ được cập nhật thành: Đã thanh toán & Đang xử lý`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="confirm_order" value="1">
            <input type="hidden" name="orderID" value="${orderID}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Mở modal hủy đơn
function openCancelOrderModal(orderID, customerName) {
    document.getElementById('cancel_orderID').value = orderID;
    document.getElementById('cancel_orderLabel').textContent = '#' + orderID;
    document.getElementById('cancel_customerName').textContent = customerName;
    document.getElementById('cancelOrderModal').classList.remove('hidden');
}

function closeCancelOrderModal() {
    document.getElementById('cancelOrderModal').classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const detailModal = document.getElementById('detailModal');
    const cancelModal = document.getElementById('cancelOrderModal');
    if (event.target == detailModal) closeDetailModal();
    if (event.target == cancelModal) closeCancelOrderModal();
}

// Initialize filter
document.addEventListener('DOMContentLoaded', function() {
    const firstBtn = document.querySelector('.filter-btn');
    if (firstBtn) {
        firstBtn.classList.add('bg-blue-600', 'text-white');
    }
});
</script>

<style>
/* Filter buttons */
.filter-btn {
    background: #f3f4f6;
    color: #374151;
}

.filter-btn:hover {
    background: #e5e7eb;
}

.filter-btn.active {
    background: #2563eb;
    color: white;
}

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
#detailModal, #statusModal {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
