<?php
/**
 * Quản lý Yêu cầu Hoàn tiền
 * File: admin/pages/refund_requests.php
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Giới hạn quyền: Chỉ Chủ doanh nghiệp (1) và Nhân viên bán hàng (3)
if (!in_array($_SESSION['role_id'], [ROLE_OWNER, ROLE_SALES])) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">
        <i class="fas fa-lock mr-2"></i>
        Chỉ Chủ doanh nghiệp và Nhân viên bán hàng mới có quyền truy cập trang này!
    </div></div>');
}

// Check permission
if (!hasPermission('view_orders') && !hasPermission('manage_orders')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

require_once __DIR__ . '/../../model/database.php';

$success = '';
$error = '';

// Xử lý CẬP NHẬT trạng thái hoàn tiền
if (isset($_POST['update_refund_status'])) {
    // Đã kiểm tra role ở đầu file (chỉ OWNER và SALES được vào), nên không cần check thêm
    $refundID = intval($_POST['refundID']);
    $action = $_POST['action']; // 'approve' hoặc 'reject'
    $adminNote = trim($_POST['adminNote'] ?? '');
    
    $db = Database::getInstance();
    $conn = $db->connect();
    
    // Xác định trạng thái dựa trên action
    $status = ($action === 'approve') ? 'Đã hoàn tiền' : 'Từ chối';
    
    // Bắt đầu transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Cập nhật trạng thái yêu cầu hoàn tiền
        $sql = "UPDATE refund_requests 
                SET status = ?, 
                    adminNote = ?, 
                    processedBy = ?, 
                    processedAt = NOW()
                WHERE refundID = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssii", $status, $adminNote, $_SESSION['user_id'], $refundID);
        mysqli_stmt_execute($stmt);
        
        // Nếu ĐỒNG Ý hoàn tiền, cập nhật trạng thái đơn hàng
        if ($action === 'approve') {
            // Lấy thông tin orderID từ refund request
            $sqlGetOrder = "SELECT orderID FROM refund_requests WHERE refundID = ?";
            $stmtGet = mysqli_prepare($conn, $sqlGetOrder);
            mysqli_stmt_bind_param($stmtGet, "i", $refundID);
            mysqli_stmt_execute($stmtGet);
            $resultGet = mysqli_stmt_get_result($stmtGet);
            $orderData = mysqli_fetch_assoc($resultGet);
            
            if ($orderData && isset($orderData['orderID'])) {
                $orderIDToUpdate = $orderData['orderID'];
            
            // Kiểm tra trạng thái trước khi update
            $sqlCheck = "SELECT deliveryStatus, paymentStatus FROM `order` WHERE orderID = ?";
            $stmtCheck = mysqli_prepare($conn, $sqlCheck);
            mysqli_stmt_bind_param($stmtCheck, "i", $orderIDToUpdate);
            mysqli_stmt_execute($stmtCheck);
            $resultCheck = mysqli_stmt_get_result($stmtCheck);
            $beforeUpdate = mysqli_fetch_assoc($resultCheck);
                
                // Cập nhật trạng thái đơn hàng khi Admin xác nhận đã hoàn tiền
                // ĐƠN HỦY: deliveryStatus = "Đã hủy"
                $sqlUpdateOrder = "UPDATE `order` 
                                 SET deliveryStatus = 'Đã hủy',
                                     paymentStatus = 'Đã hoàn tiền'
                                 WHERE orderID = " . intval($orderIDToUpdate);
                
                if (!mysqli_query($conn, $sqlUpdateOrder)) {
                    throw new Exception('Lỗi khi cập nhật trạng thái đơn hàng: ' . mysqli_error($conn));
                }
                
                $affectedRows = mysqli_affected_rows($conn);
                
                // Log để debug (có thể bỏ sau)
                error_log("REFUND UPDATE - OrderID: $orderIDToUpdate | Before: {$beforeUpdate['deliveryStatus']}/{$beforeUpdate['paymentStatus']} | Affected: $affectedRows");
                
                // Verify update thành công
                if ($affectedRows === 0) {
                    // Có thể đơn hàng đã ở trạng thái đúng rồi
                    // Kiểm tra lại
                    mysqli_stmt_execute($stmtCheck);
                    $resultAfter = mysqli_stmt_get_result($stmtCheck);
                    $afterUpdate = mysqli_fetch_assoc($resultAfter);
                    
                    // ĐƠN HỦY: deliveryStatus phải là "Đã hủy", paymentStatus phải là "Đã hoàn tiền"
                    if ($afterUpdate['deliveryStatus'] !== 'Đã hủy' || $afterUpdate['paymentStatus'] !== 'Đã hoàn tiền') {
                        throw new Exception('Không thể cập nhật đơn hàng #' . $orderIDToUpdate . '. Affected rows: 0');
                    }
                    // Nếu đã đúng rồi thì OK
                }
            } else {
                throw new Exception('Không tìm thấy thông tin đơn hàng liên kết với yêu cầu hoàn tiền này');
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        if ($action === 'approve') {
            $success = '✅ Đã xác nhận hoàn tiền thành công! Đơn hàng #' . ($orderData['orderID'] ?? 'N/A') . ' đã được cập nhật sang trạng thái "Đã hoàn tiền".';
        } else {
            $success = '❌ Đã từ chối yêu cầu hoàn tiền.';
        }
        
        // Redirect để tránh submit lại khi refresh
        header('Location: ' . ADMIN_BASE_URL . '?page=refund_requests&status=' . ($action === 'approve' ? 'success' : 'rejected'));
        exit;
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        mysqli_rollback($conn);
        $error = 'Lỗi khi xử lý: ' . $e->getMessage();
    }
}

// Hiển thị thông báo sau redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $success = '✅ Đã xác nhận hoàn tiền thành công! Trạng thái đơn hàng đã được cập nhật.';
    } elseif ($_GET['status'] === 'rejected') {
        $success = '❌ Đã từ chối yêu cầu hoàn tiền.';
    }
}

// Lấy danh sách yêu cầu hoàn tiền
$db = Database::getInstance();
$conn = $db->connect();

$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT 
            r.*,
            o.orderID,
            o.orderDate,
            o.paymentMethod,
            c.customerName,
            c.email,
            c.phone,
            u.userName as processedByName
        FROM refund_requests r
        INNER JOIN `order` o ON r.orderID = o.orderID
        INNER JOIN customer c ON r.customerID = c.customerID
        LEFT JOIN user u ON r.processedBy = u.userID";

if ($filter !== 'all') {
    $sql .= " WHERE r.status = '$filter'";
}

$sql .= " ORDER BY r.createdAt DESC";

$result = mysqli_query($conn, $sql);
$refunds = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Đếm theo trạng thái
$stats = [
    'all' => count($refunds),
    'Chờ xử lý' => 0,
    'Đã hoàn tiền' => 0,
    'Từ chối' => 0
];

foreach ($refunds as $refund) {
    if (isset($stats[$refund['status']])) {
        $stats[$refund['status']]++;
    }
}

$pageTitle = 'Quản lý Yêu cầu Hoàn tiền';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto ml-64">
        <!-- Header -->
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-4 md:px-6 py-4">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                    <i class="fas fa-undo text-orange-500 mr-2"></i>
                    Yêu cầu Hoàn tiền
                </h1>
                <p class="text-sm text-gray-600 mt-1">
                    Quản lý các yêu cầu hoàn tiền khi khách hủy đơn hàng đã thanh toán
                </p>
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

            <!-- Filter Tabs -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="flex border-b overflow-x-auto">
                    <a href="?page=refund_requests&filter=all" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-800'; ?>">
                        <i class="fas fa-list mr-2"></i>Tất cả
                        <span class="ml-2 px-2 py-1 bg-gray-200 rounded-full text-xs"><?php echo $stats['all']; ?></span>
                    </a>
                    <a href="?page=refund_requests&filter=Chờ xử lý" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'Chờ xử lý' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-600 hover:text-gray-800'; ?>">
                        <i class="fas fa-clock mr-2"></i>Chờ xử lý
                        <span class="ml-2 px-2 py-1 bg-yellow-200 rounded-full text-xs"><?php echo $stats['Chờ xử lý']; ?></span>
                    </a>
                    <a href="?page=refund_requests&filter=Đã hoàn tiền" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'Đã hoàn tiền' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-600 hover:text-gray-800'; ?>">
                        <i class="fas fa-check-circle mr-2"></i>Đã hoàn tiền
                        <span class="ml-2 px-2 py-1 bg-green-200 rounded-full text-xs"><?php echo $stats['Đã hoàn tiền']; ?></span>
                    </a>
                    <a href="?page=refund_requests&filter=Từ chối" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'Từ chối' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-600 hover:text-gray-800'; ?>">
                        <i class="fas fa-times-circle mr-2"></i>Từ chối
                        <span class="ml-2 px-2 py-1 bg-red-200 rounded-full text-xs"><?php echo $stats['Từ chối']; ?></span>
                    </a>
                </div>
            </div>

            <!-- Refund Requests Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <?php if (empty($refunds)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>Chưa có yêu cầu hoàn tiền nào</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Mã YC</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Đơn hàng</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Khách hàng</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Số tiền</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Thông tin TK</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Trạng thái</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($refunds as $refund): ?>
                            <?php
                            $statusColors = [
                                'Chờ xử lý' => 'bg-yellow-100 text-yellow-800',
                                'Đã hoàn tiền' => 'bg-green-100 text-green-800',
                                'Từ chối' => 'bg-red-100 text-red-800'
                            ];
                            $statusColor = $statusColors[$refund['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-blue-600">#<?php echo $refund['refundID']; ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($refund['createdAt'])); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">#<?php echo $refund['orderID']; ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($refund['orderDate'])); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium"><?php echo htmlspecialchars($refund['customerName']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($refund['phone']); ?></div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="font-bold text-red-600">
                                        <?php echo number_format($refund['amount'], 0, ',', '.'); ?>đ
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        <div><strong>STK:</strong> <?php echo htmlspecialchars($refund['bankAccount']); ?></div>
                                        <div><strong>NH:</strong> <?php echo htmlspecialchars($refund['bankName']); ?></div>
                                        <div><strong>Tên:</strong> <?php echo htmlspecialchars($refund['accountHolder']); ?></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusColor; ?>">
                                        <?php echo $refund['status']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <!-- Xem chi tiết -->
                                        <button onclick="viewRefundDetail(<?php echo htmlspecialchars(json_encode($refund)); ?>)" 
                                                class="text-blue-600 hover:text-blue-800 text-lg" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <!-- Xác nhận hoàn tiền - Chỉ OWNER và SALES -->
                                        <?php if ($refund['status'] === 'Chờ xử lý' && in_array($_SESSION['role_id'], [ROLE_OWNER, ROLE_SALES])): ?>
                                        <button onclick="updateRefundStatus(<?php echo $refund['refundID']; ?>)" 
                                                class="text-green-600 hover:text-green-800 text-lg" title="Xác nhận hoàn tiền">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-lg" title="Đã xử lý">
                                            <i class="fas fa-check-double"></i>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi tiết Yêu cầu Hoàn tiền -->
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white z-10">
            <h3 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                Chi tiết Yêu cầu Hoàn tiền
            </h3>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <div class="p-6" id="detailContent">
            <!-- Content will be loaded by JavaScript -->
        </div>
    </div>
</div>

<!-- Modal Xác nhận Hoàn tiền -->
<div id="updateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full">
        <div class="p-6 border-b flex justify-between items-center bg-gradient-to-r from-blue-50 to-purple-50">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-hand-holding-usd text-blue-600 mr-2"></i>
                Xử lý Yêu cầu Hoàn tiền
            </h3>
            <button onclick="closeUpdateModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" id="refundForm" class="p-6">
            <input type="hidden" name="update_refund_status" value="1">
            <input type="hidden" name="refundID" id="update_refundID">
            <input type="hidden" name="action" id="update_action">
            
            <!-- Thông báo hướng dẫn -->
            <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex">
                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                    <div class="text-sm text-gray-700">
                        <p class="font-semibold mb-1">Hướng dẫn xử lý:</p>
                        <ul class="list-disc ml-4 space-y-1">
                            <li><strong>Đồng ý:</strong> Xác nhận đã hoàn tiền cho khách. Đơn hàng sẽ chuyển sang trạng thái "Đã hoàn tiền".</li>
                            <li><strong>Từ chối:</strong> Từ chối yêu cầu hoàn tiền (cần ghi rõ lý do).</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Ghi chú của Admin -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-comment-dots mr-1"></i>Ghi chú của Admin
                    <span class="text-gray-500 font-normal">(Khuyến khích)</span>
                </label>
                <textarea name="adminNote" rows="4" 
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="VD: Đã chuyển khoản vào STK khách hàng lúc 14:30 ngày 05/12/2025. Mã giao dịch: ABC123..."></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-lightbulb mr-1"></i>Nên ghi: Thời gian chuyển khoản, mã giao dịch, hoặc lý do từ chối
                </p>
            </div>
            
            <!-- Các nút hành động -->
            <div class="flex gap-3 pt-4 border-t">
                <button type="button" onclick="closeUpdateModal()" 
                        class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Hủy
                </button>
                
                <button type="button" onclick="submitRefundAction('reject')" 
                        class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold shadow-lg hover:shadow-xl">
                    <i class="fas fa-times-circle mr-2"></i>Từ Chối
                </button>
                
                <button type="button" onclick="submitRefundAction('approve')" 
                        class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold shadow-lg hover:shadow-xl">
                    <i class="fas fa-check-circle mr-2"></i>Đồng Ý Hoàn Tiền
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function viewRefundDetail(refund) {
    const content = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Thông tin đơn hàng -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-bold text-gray-800 mb-3">
                    <i class="fas fa-shopping-cart text-blue-600 mr-2"></i>Thông tin Đơn hàng
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mã đơn:</span>
                        <span class="font-semibold">#${refund.orderID}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngày đặt:</span>
                        <span class="font-semibold">${new Date(refund.orderDate).toLocaleDateString('vi-VN')}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phương thức:</span>
                        <span class="font-semibold">${refund.paymentMethod}</span>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin khách hàng -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-bold text-gray-800 mb-3">
                    <i class="fas fa-user text-green-600 mr-2"></i>Thông tin Khách hàng
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Họ tên:</span>
                        <span class="font-semibold">${refund.customerName}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="font-semibold">${refund.email}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">SĐT:</span>
                        <span class="font-semibold">${refund.phone}</span>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin tài khoản -->
            <div class="bg-blue-50 rounded-lg p-4">
                <h4 class="font-bold text-gray-800 mb-3">
                    <i class="fas fa-university text-blue-600 mr-2"></i>Thông tin Tài khoản Nhận tiền
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Số tài khoản:</span>
                        <span class="font-semibold">${refund.bankAccount}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngân hàng:</span>
                        <span class="font-semibold">${refund.bankName}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Chủ tài khoản:</span>
                        <span class="font-semibold">${refund.accountHolder}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2">
                        <span class="text-gray-600">Số tiền hoàn:</span>
                        <span class="font-bold text-red-600 text-lg">${new Intl.NumberFormat('vi-VN').format(refund.amount)}đ</span>
                    </div>
                </div>
            </div>
            
            <!-- Trạng thái xử lý -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-bold text-gray-800 mb-3">
                    <i class="fas fa-tasks text-purple-600 mr-2"></i>Trạng thái Xử lý
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Trạng thái:</span>
                        <span class="font-semibold">${refund.status}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngày yêu cầu:</span>
                        <span class="font-semibold">${new Date(refund.createdAt).toLocaleString('vi-VN')}</span>
                    </div>
                    ${refund.processedAt ? `
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngày xử lý:</span>
                        <span class="font-semibold">${new Date(refund.processedAt).toLocaleString('vi-VN')}</span>
                    </div>
                    ` : ''}
                    ${refund.processedByName ? `
                    <div class="flex justify-between">
                        <span class="text-gray-600">Người xử lý:</span>
                        <span class="font-semibold">${refund.processedByName}</span>
                    </div>
                    ` : ''}
                </div>
            </div>
        </div>
        
        <!-- Ảnh chứng minh -->
        ${refund.proofImage ? `
        <div class="mt-6 bg-gray-50 rounded-lg p-4">
            <h4 class="font-bold text-gray-800 mb-3">
                <i class="fas fa-image text-orange-600 mr-2"></i>Ảnh Chứng minh Chuyển khoản
            </h4>
            <img src="<?php echo BASE_URL; ?>${refund.proofImage}" 
                 class="max-w-full h-auto rounded-lg shadow" 
                 alt="Proof Image">
        </div>
        ` : ''}
        
        <!-- Ghi chú admin -->
        ${refund.adminNote ? `
        <div class="mt-6 bg-yellow-50 rounded-lg p-4">
            <h4 class="font-bold text-gray-800 mb-3">
                <i class="fas fa-sticky-note text-yellow-600 mr-2"></i>Ghi chú của Admin
            </h4>
            <p class="text-sm text-gray-700">${refund.adminNote}</p>
        </div>
        ` : ''}
        
        <!-- Nút xác nhận (chỉ hiện khi chờ xử lý) -->
        ${refund.status === 'Chờ xử lý' ? `
        <div class="mt-6 pt-6 border-t flex justify-end">
            <button onclick="closeDetailModal(); updateRefundStatus(${refund.refundID});" 
                    class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold shadow-lg hover:shadow-xl">
                <i class="fas fa-check-circle mr-2"></i>Xác nhận Hoàn tiền
            </button>
        </div>
        ` : ''}
    `;
    
    document.getElementById('detailContent').innerHTML = content;
    document.getElementById('detailModal').classList.remove('hidden');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

function updateRefundStatus(refundID) {
    document.getElementById('update_refundID').value = refundID;
    document.getElementById('update_action').value = '';
    document.getElementById('updateModal').classList.remove('hidden');
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.add('hidden');
}

function submitRefundAction(action) {
    // Xác nhận trước khi thực hiện
    const actionText = action === 'approve' ? 'ĐỒNG Ý HOÀN TIỀN' : 'TỪ CHỐI';
    const confirmText = action === 'approve' 
        ? 'Bạn xác nhận đã hoàn tiền cho khách hàng?\n\nĐơn hàng sẽ được đánh dấu là "Đã hoàn tiền".' 
        : 'Bạn chắc chắn muốn TỪ CHỐI yêu cầu hoàn tiền này?\n\nVui lòng ghi rõ lý do trong phần ghi chú.';
    
    if (confirm(confirmText)) {
        document.getElementById('update_action').value = action;
        document.getElementById('refundForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
