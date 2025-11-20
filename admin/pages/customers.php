<?php
/**
 * Quản lý Khách hàng
 * File: admin/pages/customers.php
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission
if (!hasPermission('view_customers')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

// Load Controller (Admin version)
require_once __DIR__ . '/../../controller/admin/cCustomer.php';
require_once __DIR__ . '/../../controller/admin/cCustomerGroup.php';
require_once __DIR__ . '/../../model/mCustomer.php';
$customerController = new cCustomer();
$groupController = new cCustomerGroup();
$customerModel = new Customer();

$success = '';
$error = '';

// Xử lý THÊM khách hàng
if (isset($_POST['add_customer']) && hasPermission('manage_customers')) {
    $data = [
        'customerName' => trim($_POST['customerName']),
        'phone' => trim($_POST['phone']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'note' => isset($_POST['note']) ? trim($_POST['note']) : ''
    ];
    
    $result = $customerController->addCustomer($data);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý SỬA khách hàng
if (isset($_POST['edit_customer']) && hasPermission('manage_customers')) {
    $customerID = intval($_POST['customerID']);
    $data = [
        'customerName' => trim($_POST['customerName']),
        'phone' => trim($_POST['phone']),
        'email' => trim($_POST['email']),
        'note' => isset($_POST['note']) ? trim($_POST['note']) : ''
    ];
    
    // Chỉ thêm status nếu có permission manage_customers
    if (hasPermission('manage_customers') && isset($_POST['status'])) {
        $data['status'] = intval($_POST['status']);
    }
    
    $currentRoleID = $_SESSION['role_id'] ?? null;
    $result = $customerController->updateCustomer($customerID, $data, $currentRoleID);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý ĐỔI MẬT KHẨU
if (isset($_POST['change_password']) && hasPermission('manage_customers')) {
    $customerID = intval($_POST['customerID']);
    $data = [
        'newPassword' => $_POST['newPassword'],
        'confirmPassword' => $_POST['confirmPassword']
    ];
    
    $result = $customerController->changePassword($customerID, $data);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý THAY ĐỔI TRẠNG THÁI khách hàng
if (isset($_POST['update_status'])) {
    // Lấy đúng key session
    $currentRoleID = $_SESSION['role_id'] ?? null;
    $hasManageCustomers = hasPermission('manage_customers');
    
    if (!$hasManageCustomers) {
        $error = "Bạn không có quyền thay đổi trạng thái khách hàng!";
    } else {
        $customerID = intval($_POST['customerID']);
        $status = intval($_POST['status']);
        
        $result = $customerController->updateStatus($customerID, $status, $currentRoleID);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Xử lý XÓA khách hàng
if (isset($_GET['delete']) && hasPermission('manage_customers')) {
    $customerID = intval($_GET['delete']);
    $result = $customerController->deleteCustomer($customerID);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý TÌM KIẾM
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchKeyword) {
    $customers = $customerController->searchCustomers($searchKeyword);
} else {
    $customers = $customerController->getAllCustomers();
}

// Xử lý AJAX - Lấy chi tiết khách hàng và đơn hàng (MVC pattern)
if (isset($_GET['action']) && $_GET['action'] === 'get_customer_detail' && isset($_GET['id'])) {
    $customerID = intval($_GET['id']);
    
    // Lấy dữ liệu từ Controller
    $customer = $customerController->getCustomerById($customerID);
    
    if (!$customer) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy khách hàng!'
        ]);
        exit;
    }
    
    $stats = $customerController->getCustomerStats($customerID);
    $orders = $customerController->getOrderHistory($customerID);
    
    // Format dữ liệu đơn hàng với thông tin đầy đủ
    $formattedOrders = [];
    foreach ($orders as $order) {
        $formattedOrders[] = [
            'orderID' => $order['orderID'],
            'orderDate' => $order['orderDate'],
            'totalAmount' => $order['totalAmount'] ?? 0,
            'paymentStatus' => $order['paymentStatus'] ?? 'Chưa thanh toán',
            'deliveryStatus' => $order['deliveryStatus'] ?? 'Chờ xử lý',
            'paymentMethod' => $order['paymentMethod'] ?? 'Tiền mặt',
            'totalProducts' => $order['totalProducts'] ?? 0
        ];
    }
    
    // Trả về JSON với dữ liệu được format
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'customer' => [
            'customerID' => $customer['customerID'],
            'customerName' => $customer['customerName'],
            'phone' => $customer['phone'],
            'email' => $customer['email'],
            'status' => $customer['status'] ?? 1
        ],
        'stats' => [
            'totalOrders' => $stats['totalOrders'] ?? 0,
            'totalSpent' => $stats['totalSpent'] ?? 0
        ],
        'orders' => $formattedOrders
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$totalCustomers = $customerController->countCustomers();

$pageTitle = 'Quản lý Khách hàng';
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
                        <i class="fas fa-users text-purple-500 mr-2"></i>
                        Quản lý Khách hàng
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng số: <strong><?php echo $totalCustomers; ?></strong> khách hàng
                    </p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Search -->
                    <form method="GET" class="flex items-center">
                        <input type="hidden" name="page" value="customers">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchKeyword); ?>"
                                   placeholder="Tìm tên, email, SĐT..." 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 w-64">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button type="submit" class="ml-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            Tìm
                        </button>
                    </form>
                    
                    <?php if (hasPermission('manage_customers')): ?>
                    <button onclick="openAddModal()" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                        <i class="fas fa-user-plus mr-2"></i>
                        Thêm khách hàng
                    </button>
                    <?php endif; ?>
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
                    <div><?php echo $error; ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Customers Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Khách hàng</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Nhóm</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">SĐT</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Email</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Trạng thái</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Thống kê</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-users text-4xl mb-2 text-gray-300"></i>
                                    <p>Không tìm thấy khách hàng nào</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                <?php
                                // Safe access
                                $customerID = $customer['customerID'] ?? 0;
                                $customerName = $customer['customerName'] ?? 'N/A';
                                $phone = $customer['phone'] ?? '';
                                $email = $customer['email'] ?? '';
                                $status = $customer['status'] ?? 1; // Mặc định: Hoạt động
                                $groupID = $customer['groupID'] ?? 1;
                                $groupName = $customer['groupName'] ?? 'Khách hàng thường';
                                $groupColor = $customer['groupColor'] ?? '#9ca3af';
                                
                                // Lấy thống kê
                                $stats = $customerController->getCustomerStats($customerID);
                                $totalOrders = $stats['totalOrders'] ?? 0;
                                $totalSpent = $stats['totalSpent'] ?? 0;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-semibold">#<?php echo $customerID; ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mr-3">
                                                <span class="text-white font-bold text-lg">
                                                    <?php echo strtoupper(substr($customerName, 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($customerName); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white" 
                                              style="background-color: <?php echo $groupColor; ?>">
                                            <i class="fas fa-tag mr-1"></i>
                                            <?php echo htmlspecialchars($groupName); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                                        <i class="fas fa-phone mr-1 text-gray-400"></i>
                                        <?php echo htmlspecialchars($phone); ?>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                                        <i class="fas fa-envelope mr-1 text-gray-400"></i>
                                        <?php echo htmlspecialchars($email); ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($status == 1): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Hoạt động
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-lock mr-1"></i>
                                                Đã khóa
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="text-sm">
                                            <div class="font-semibold text-purple-600">
                                                <?php echo $totalOrders; ?> đơn hàng
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Chi tiêu: <strong class="text-green-600"><?php echo number_format($totalSpent, 0, ',', '.'); ?>₫</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center items-center space-x-2">
                                            <!-- Xem chi tiết -->
                                            <button onclick='viewCustomerDetail(<?php echo $customerID; ?>)' 
                                                    class="text-blue-600 hover:text-blue-800 w-8 h-8 flex items-center justify-center" title="Xem chi tiết">
                                                <i class="fas fa-eye text-lg"></i>
                                            </button>
                                            
                                            <?php if (hasPermission('manage_customers')): ?>
                                            <!-- Sửa -->
                                            <button onclick='openEditModal(<?php echo json_encode($customer, JSON_HEX_APOS); ?>)' 
                                                    class="text-green-600 hover:text-green-800 w-8 h-8 flex items-center justify-center" title="Sửa">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>
                                            
                                            <!-- Đổi MK -->
                                            <button onclick='openPasswordModal(<?php echo $customerID; ?>, "<?php echo htmlspecialchars($customerName); ?>")' 
                                                    class="text-orange-600 hover:text-orange-800 w-8 h-8 flex items-center justify-center" title="Đổi mật khẩu">
                                                <i class="fas fa-key text-lg"></i>
                                            </button>
                                            
                                            <?php if (hasPermission('manage_customers')): ?>
                                            <!-- Toggle Status -->
                                            <?php if ($status == 1): ?>
                                            <button onclick="toggleStatus(<?php echo $customerID; ?>, <?php echo $status; ?>)" 
                                                    class="text-yellow-600 hover:text-yellow-800 w-8 h-8 flex items-center justify-center" title="Khóa tài khoản">
                                                <i class="fas fa-lock text-lg"></i>
                                            </button>
                                            <?php else: ?>
                                            <button onclick="toggleStatus(<?php echo $customerID; ?>, <?php echo $status; ?>)" 
                                                    class="text-green-600 hover:text-green-800 w-8 h-8 flex items-center justify-center" title="Mở khóa tài khoản">
                                                <i class="fas fa-lock-open text-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <!-- Xóa -->
                                            <button onclick="deleteCustomer(<?php echo $customerID; ?>, '<?php echo htmlspecialchars($customerName); ?>')" 
                                                    class="text-red-600 hover:text-red-800 w-8 h-8 flex items-center justify-center" title="Xóa">
                                                <i class="fas fa-trash text-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
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

<!-- Modal: Thêm khách hàng -->
<div id="addModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-3 border-b border-purple-200">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-user-plus text-purple-500 mr-2"></i>
                Thêm khách hàng mới
            </h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="customerName">
                    Tên khách hàng <span class="text-red-500">*</span>
                </label>
                <input type="text" name="customerName" id="customerName" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="Nhập tên khách hàng">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="note">
                    Ghi chú
                </label>
                <textarea name="note" id="note" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Nhập ghi chú khách hàng"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                    Số điện thoại <span class="text-red-500">*</span>
                </label>
                <input type="tel" name="phone" id="phone" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="Nhập số điện thoại">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" id="email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="Nhập email">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Mật khẩu <span class="text-red-500">*</span>
                </label>
                <input type="password" name="password" id="password" required minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="Tối thiểu 6 ký tự">
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
                <button type="submit" name="add_customer"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-check mr-1"></i> Thêm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Sửa khách hàng -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-3 border-b border-green-200">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-edit text-green-500 mr-2"></i>
                Sửa thông tin khách hàng
            </h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4">
            <input type="hidden" name="customerID" id="edit_customerID">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_customerName">
                    Tên khách hàng <span class="text-red-500">*</span>
                </label>
                <input type="text" name="customerName" id="edit_customerName" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_phone">
                    Số điện thoại <span class="text-red-500">*</span>
                </label>
                <input type="tel" name="phone" id="edit_phone" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_email">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" id="edit_email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_note">
                    Ghi chú
                </label>
                <textarea name="note" id="edit_note" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Nhập ghi chú khách hàng"></textarea>
            </div>
            
            <?php if (hasPermission('manage_customers')): ?>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_status">
                    Trạng thái <span class="text-red-500">*</span>
                </label>
                <select name="status" id="edit_status" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="1">Hoạt động</option>
                    <option value="0">Đã khóa</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-info-circle"></i> 
                    Khách hàng bị khóa không thể đăng nhập và đặt hàng
                </p>
            </div>
            <?php endif; ?>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
                <button type="submit" name="edit_customer"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-1"></i> Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Đổi mật khẩu -->
<div id="passwordModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b border-orange-200">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-key text-orange-500 mr-2"></i>
                Đổi mật khẩu
            </h3>
            <button onclick="closePasswordModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4">
            <input type="hidden" name="customerID" id="pwd_customerID">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Khách hàng: <span id="pwd_customerName" class="text-purple-600 font-bold"></span>
                </label>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="newPassword">
                    Mật khẩu mới <span class="text-red-500">*</span>
                </label>
                <input type="password" name="newPassword" id="newPassword" required minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                       placeholder="Tối thiểu 6 ký tự">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirmPassword">
                    Xác nhận mật khẩu <span class="text-red-500">*</span>
                </label>
                <input type="password" name="confirmPassword" id="confirmPassword" required minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                       placeholder="Nhập lại mật khẩu mới">
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closePasswordModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
                <button type="submit" name="change_password"
                        class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                    <i class="fas fa-check mr-1"></i> Đổi mật khẩu
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Modal: Chi tiết khách hàng -->
<div id="detailModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-6 border w-11/12 md:w-4/5 lg:w-3/4 shadow-2xl rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-4 border-b border-purple-200">
            <h3 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-user-circle text-purple-500 mr-2"></i>
                Chi tiết khách hàng
            </h3>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <div id="detailContent" class="mt-6">
            <div class="flex items-center justify-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-purple-500"></i>
                <span class="ml-3 text-gray-600">Đang tải thông tin...</span>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Add modal
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

// Edit modal
function openEditModal(customer) {
    document.getElementById('edit_customerID').value = customer.customerID;
    document.getElementById('edit_customerName').value = customer.customerName;
    document.getElementById('edit_phone').value = customer.phone;
    document.getElementById('edit_email').value = customer.email;
    document.getElementById('edit_note').value = customer.note ? customer.note : '';
    
    // Set status if field exists
    const statusField = document.getElementById('edit_status');
    if (statusField) {
        statusField.value = customer.status || 1;
    }
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Password modal
function openPasswordModal(id, name) {
    document.getElementById('pwd_customerID').value = id;
    document.getElementById('pwd_customerName').textContent = name;
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    
    document.getElementById('passwordModal').classList.remove('hidden');
}

function closePasswordModal() {
    document.getElementById('passwordModal').classList.add('hidden');
}

// View history
function viewHistory(customer) {
    document.getElementById('history_customerName').textContent = customer.customerName;
    document.getElementById('historyModal').classList.remove('hidden');
    
    // Load order history via PHP (you can enhance this with AJAX)
    const customerID = customer.customerID;
    fetch(`?page=customers&action=get_history&id=${customerID}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('historyContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('historyContent').innerHTML = '<p class="text-red-500 text-center">Lỗi khi tải dữ liệu</p>';
        });
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}

// Xem chi tiết khách hàng
function viewCustomerDetail(customerID) {
    document.getElementById('detailModal').classList.remove('hidden');
    
    // Hiển thị loading
    document.getElementById('detailContent').innerHTML = `
        <div class="flex items-center justify-center py-8">
            <i class="fas fa-spinner fa-spin text-4xl text-purple-500"></i>
            <span class="ml-3 text-gray-600">Đang tải thông tin...</span>
        </div>
    `;
    
    // Lấy dữ liệu qua AJAX
    fetch(`?page=customers&action=get_customer_detail&id=${customerID}`)
        .then(response => response.json())
        .then(data => {
            const customer = data.customer;
            const stats = data.stats;
            const orders = data.orders;
            
            // Format số tiền
            const formatMoney = (amount) => {
                return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
            };
            
            // Format ngày
            const formatDate = (dateString) => {
                const date = new Date(dateString);
                return date.toLocaleDateString('vi-VN', { 
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };
            
            // Badge trạng thái
            const getStatusBadge = (paymentStatus, deliveryStatus) => {
                if (paymentStatus === 'Đã hủy' || deliveryStatus === 'Đã hủy') {
                    return '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-times-circle"></i> Đã hủy</span>';
                } else if (deliveryStatus === 'Hoàn thành') {
                    return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle"></i> Hoàn thành</span>';
                } else if (deliveryStatus === 'Đang giao') {
                    return '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-shipping-fast"></i> Đang giao</span>';
                } else {
                    return '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-clock"></i> Chờ xử lý</span>';
                }
            };
            
            // Tạo HTML hiển thị
            let html = `
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Thông tin khách hàng -->
                    <div class="lg:col-span-1">
                        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-6 text-white shadow-lg">
                            <div class="flex flex-col items-center">
                                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-4 shadow-lg">
                                    <span class="text-purple-600 font-bold text-4xl">
                                        ${customer.customerName.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                                <h3 class="text-2xl font-bold mb-1">${customer.customerName}</h3>
                                <p class="text-purple-100 text-sm mb-4">Khách hàng #${customer.customerID}</p>
                                
                                <div class="w-full space-y-3 mt-4">
                                    <div class="flex items-center bg-white bg-opacity-20 rounded-lg p-3">
                                        <i class="fas fa-envelope w-6 text-center"></i>
                                        <span class="ml-3 text-sm">${customer.email}</span>
                                    </div>
                                    <div class="flex items-center bg-white bg-opacity-20 rounded-lg p-3">
                                        <i class="fas fa-phone w-6 text-center"></i>
                                        <span class="ml-3 text-sm">${customer.phone}</span>
                                    </div>
                                    <div class="flex items-center bg-white bg-opacity-20 rounded-lg p-3">
                                        <i class="fas fa-sticky-note w-6 text-center"></i>
                                        <span class="ml-3 text-sm">${customer.note ? customer.note : 'Chưa có ghi chú'}</span>
                                    </div>
                                    <div class="flex items-center bg-white bg-opacity-20 rounded-lg p-3">
                                        <i class="fas ${customer.status == 1 ? 'fa-check-circle' : 'fa-lock'} w-6 text-center"></i>
                                        <span class="ml-3 text-sm">${customer.status == 1 ? 'Đang hoạt động' : 'Đã bị khóa'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thống kê & Đơn hàng -->
                    <div class="lg:col-span-2">
                        <!-- Thống kê -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white shadow-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-blue-100 text-sm mb-1">Tổng đơn hàng</p>
                                        <p class="text-3xl font-bold">${stats.totalOrders || 0}</p>
                                    </div>
                                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                        <i class="fas fa-shopping-cart text-3xl"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white shadow-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-green-100 text-sm mb-1">Tổng chi tiêu</p>
                                        <p class="text-2xl font-bold">${formatMoney(stats.totalSpent || 0)}</p>
                                    </div>
                                    <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                        <i class="fas fa-money-bill-wave text-3xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Danh sách đơn hàng -->
                        <div class="bg-white rounded-xl shadow-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h4 class="text-lg font-bold text-gray-800">
                                    <i class="fas fa-list-alt text-purple-500 mr-2"></i>
                                    Lịch sử đơn hàng (${orders.length})
                                </h4>
                            </div>
                            <div class="p-4 max-h-96 overflow-y-auto">`;
            
            if (orders.length === 0) {
                html += `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-shopping-bag text-5xl mb-3 text-gray-300"></i>
                        <p>Chưa có đơn hàng nào</p>
                    </div>`;
            } else {
                orders.forEach(order => {
                    html += `
                        <div class="border border-gray-200 rounded-lg p-4 mb-3 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h5 class="font-bold text-gray-800">
                                        <i class="fas fa-receipt text-purple-500 mr-1"></i>
                                        Đơn hàng #${order.orderID}
                                    </h5>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <i class="fas fa-clock text-gray-400 mr-1"></i>
                                        ${formatDate(order.orderDate)}
                                    </p>
                                </div>
                                ${getStatusBadge(order.paymentStatus, order.deliveryStatus)}
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-credit-card w-5 text-purple-500"></i>
                                    <span class="ml-2">${order.paymentMethod}</span>
                                </div>
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-box w-5 text-blue-500"></i>
                                    <span class="ml-2">${order.totalProducts || 0} sản phẩm</span>
                                </div>
                            </div>
                            
                            <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                <span class="text-sm text-gray-600">Tổng tiền:</span>
                                <span class="text-lg font-bold text-green-600">${formatMoney(order.totalAmount)}</span>
                            </div>
                        </div>`;
                });
            }
            
            html += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('detailContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detailContent').innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-3"></i>
                    <p class="text-red-600">Lỗi khi tải dữ liệu. Vui lòng thử lại!</p>
                </div>
            `;
        });
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

// Delete customer
function deleteCustomer(id, name) {
    if (confirm(`Bạn có chắc muốn XÓA khách hàng "${name}"?\n\nCảnh báo: Hành động này không thể hoàn tác!`)) {
        window.location.href = `?page=customers&delete=${id}`;
    }
}

// Toggle customer status
function toggleStatus(customerID, currentStatus) {
    const newStatus = currentStatus == 1 ? 0 : 1;
    const action = newStatus == 1 ? 'MỞ KHÓA' : 'KHÓA';
    const statusText = newStatus == 1 ? 'Hoạt động' : 'Đã khóa';
    
    if (confirm(`Bạn có chắc muốn ${action} khách hàng này?\n\nTrạng thái mới: ${statusText}`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="customerID" value="${customerID}">
            <input type="hidden" name="status" value="${newStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const passwordModal = document.getElementById('passwordModal');
    const detailModal = document.getElementById('detailModal');
    
    if (event.target == addModal) closeAddModal();
    if (event.target == editModal) closeEditModal();
    if (event.target == passwordModal) closePasswordModal();
    if (event.target == detailModal) closeDetailModal();
}
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
#addModal, #editModal, #passwordModal, #detailModal {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Custom scrollbar cho modal */
#detailContent::-webkit-scrollbar {
    width: 8px;
}

#detailContent::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#detailContent::-webkit-scrollbar-thumb {
    background: #a855f7;
    border-radius: 4px;
}

#detailContent::-webkit-scrollbar-thumb:hover {
    background: #9333ea;
}
</style>
