<?php
/**
 * Admin: Quản lý yêu cầu hoàn trả đơn hàng
 * File: admin/pages/return_requests.php
 * VPS Compatible: Sử dụng ADMIN_BASE_URL
 */

// Start output buffering để tránh headers already sent
ob_start();

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Giới hạn quyền: Chỉ Chủ doanh nghiệp (1) và Nhân viên bán hàng (3)
if (!in_array($_SESSION['role_id'], [ROLE_OWNER, ROLE_SALES])) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">
        <i class="fas fa-lock mr-2"></i>
        Chỉ Chủ doanh nghiệp và Nhân viên bán hàng mới có quyền truy cập trang này!
    </div></div>');
}

if (!hasPermission('view_orders')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

require_once __DIR__ . '/../../model/database.php';
$conn = Database::getInstance()->getConnection();

$success = '';
$error = '';

// Xử lý POST actions - Đã kiểm tra role ở đầu file (OWNER và SALES)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnID = intval($_POST['returnID'] ?? 0);
    $action = $_POST['action'] ?? '';
    $adminNote = trim($_POST['adminNote'] ?? '');
    
    if ($returnID > 0 && in_array($action, ['approve', 'reject', 'refund'])) {
        $adminID = $_SESSION['user_id'] ?? 0;
        
        $newStatus = '';
        switch ($action) {
            case 'approve': $newStatus = 'Đã chấp nhận'; break;
            case 'reject': $newStatus = 'Đã từ chối'; break;
            case 'refund': $newStatus = 'Đã hoàn tiền'; break;
        }
        
        // Escape để tránh SQL injection
        $newStatusEscaped = mysqli_real_escape_string($conn, $newStatus);
        $adminNoteEscaped = mysqli_real_escape_string($conn, $adminNote);
        
        // Cập nhật trạng thái (dùng mysqli_query thay vì prepare để tương thích MyISAM)
        $sqlUpdate = "UPDATE return_requests 
                     SET status = '$newStatusEscaped', 
                         processedAt = NOW(), 
                         processedBy = $adminID, 
                         adminNote = '$adminNoteEscaped' 
                     WHERE returnID = $returnID";
        
        if (mysqli_query($conn, $sqlUpdate)) {
            // Lấy orderID từ return_requests trước
            $sqlGetOrder = "SELECT orderID FROM return_requests WHERE returnID = $returnID";
            $resultGetOrder = mysqli_query($conn, $sqlGetOrder);
            $orderData = mysqli_fetch_assoc($resultGetOrder);
            
            if ($orderData) {
                $orderID = intval($orderData['orderID']);
                
                // Cập nhật paymentStatus và deliveryStatus theo action
                if ($action === 'approve') {
                    // Chấp nhận → Giữ nguyên paymentStatus = "Đã thanh toán", không update gì
                    // (Vì đơn đã thanh toán rồi, chỉ chấp nhận yêu cầu hoàn trả thôi)
                } elseif ($action === 'refund') {
                    // ĐƠN HOÀN TRẢ: Xác nhận hoàn tiền → deliveryStatus = "Đã hoàn trả", paymentStatus = "Đã hoàn tiền"
                    $sqlOrder = "UPDATE `order` 
                                SET deliveryStatus = 'Đã hoàn trả',
                                    paymentStatus = 'Đã hoàn tiền' 
                                WHERE orderID = $orderID";
                    mysqli_query($conn, $sqlOrder);
                }
                // Từ chối → Không update
            }
            
            $actionText = '';
            switch ($action) {
                case 'approve': $actionText = 'chấp nhận'; break;
                case 'reject': $actionText = 'từ chối'; break;
                case 'refund': $actionText = 'hoàn tiền'; break;
            }
            $_SESSION['success_message'] = "Đã {$actionText} yêu cầu hoàn trả #$returnID";
            header('Location: ' . $_SERVER['PHP_SELF'] . '?page=return_requests');
            exit;
        } else {
            $error = "Lỗi khi cập nhật: " . mysqli_error($conn);
        }
    }
}

// Lấy thông báo từ session
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Lấy danh sách yêu cầu hoàn trả
$sql = "SELECT 
            rr.*,
            o.orderID, o.orderDate, o.totalAmount, o.paymentMethod, o.deliveryStatus,
            c.customerName, c.phone, c.email,
            u.userName as adminName
        FROM return_requests rr
        INNER JOIN `order` o ON rr.orderID = o.orderID
        INNER JOIN customer c ON rr.customerID = c.customerID
        LEFT JOIN user u ON rr.processedBy = u.userID
        ORDER BY 
            CASE rr.status 
                WHEN 'Chờ xử lý' THEN 1
                WHEN 'Đã chấp nhận' THEN 2
                WHEN 'Đã từ chối' THEN 3
                WHEN 'Đã hoàn tiền' THEN 4
            END,
            rr.createdAt DESC";

$result = mysqli_query($conn, $sql);
$returnRequests = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $returnRequests[] = $row;
    }
}

// Thống kê
$stats = [
    'total' => count($returnRequests),
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'refunded' => 0
];

foreach ($returnRequests as $req) {
    switch ($req['status']) {
        case 'Chờ xử lý':
            $stats['pending']++;
            break;
        case 'Đã chấp nhận':
            $stats['approved']++;
            break;
        case 'Đã từ chối':
            $stats['rejected']++;
            break;
        case 'Đã hoàn tiền':
            $stats['refunded']++;
            break;
    }
}

$pageTitle = 'Yêu cầu hoàn trả đơn hàng';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto ml-64">
        <!-- Header -->
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-4 md:px-6 py-4">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                    <i class="fas fa-undo text-orange-500 mr-2"></i>
                    Yêu cầu hoàn trả đơn hàng
                </h1>
                <p class="text-sm text-gray-600 mt-1">
                    Tổng số: <strong><?php echo $stats['total']; ?></strong> yêu cầu
                    <span class="mx-2">|</span>
                    Chờ xử lý: <span class="text-yellow-600 font-semibold"><?php echo $stats['pending']; ?></span>
                    <span class="mx-2">|</span>
                    Đã chấp nhận: <span class="text-green-600 font-semibold"><?php echo $stats['approved']; ?></span>
                    <span class="mx-2">|</span>
                    Đã từ chối: <span class="text-red-600 font-semibold"><?php echo $stats['rejected']; ?></span>
                </p>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 md:p-6">
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="bg-white rounded-lg shadow-sm mb-4">
                <div class="flex border-b overflow-x-auto">
                    <button class="filter-tab px-6 py-3 font-medium text-gray-700 hover:text-blue-600 border-b-2 border-transparent hover:border-blue-600 transition-colors active" data-status="all">
                        Tất cả <span class="ml-1 text-sm text-gray-500">(<?php echo $stats['total']; ?>)</span>
                    </button>
                    <button class="filter-tab px-6 py-3 font-medium text-yellow-600 hover:text-yellow-700 border-b-2 border-transparent hover:border-yellow-600 transition-colors" data-status="pending">
                        Chờ xử lý <span class="ml-1 text-sm text-gray-500">(<?php echo $stats['pending']; ?>)</span>
                    </button>
                    <button class="filter-tab px-6 py-3 font-medium text-green-600 hover:text-green-700 border-b-2 border-transparent hover:border-green-600 transition-colors" data-status="approved">
                        Đã chấp nhận <span class="ml-1 text-sm text-gray-500">(<?php echo $stats['approved']; ?>)</span>
                    </button>
                    <button class="filter-tab px-6 py-3 font-medium text-red-600 hover:text-red-700 border-b-2 border-transparent hover:border-red-600 transition-colors" data-status="rejected">
                        Đã từ chối <span class="ml-1 text-sm text-gray-500">(<?php echo $stats['rejected']; ?>)</span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã YC</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn hàng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày yêu cầu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lý do</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($returnRequests)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Chưa có yêu cầu hoàn trả nào</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($returnRequests as $req): 
                                $statusClass = [
                                    'Chờ xử lý' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    'Đã chấp nhận' => 'bg-green-100 text-green-800 border-green-300',
                                    'Đã từ chối' => 'bg-red-100 text-red-800 border-red-300',
                                    'Đã hoàn tiền' => 'bg-purple-100 text-purple-800 border-purple-300'
                                ];
                                
                                $filterStatus = [
                                    'Chờ xử lý' => 'pending',
                                    'Đã chấp nhận' => 'approved',
                                    'Đã từ chối' => 'rejected',
                                    'Đã hoàn tiền' => 'refunded'
                                ];
                            ?>
                            <tr class="hover:bg-gray-50 return-row" data-status="<?php echo $filterStatus[$req['status']]; ?>">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-blue-600">#<?php echo $req['returnID']; ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">#<?php echo $req['orderID']; ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo number_format($req['totalAmount'], 0, ',', '.'); ?> đ
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium"><?php echo htmlspecialchars($req['customerName']); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($req['phone']); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm"><?php echo date('d/m/Y H:i', strtotime($req['createdAt'])); ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm max-w-xs truncate" title="<?php echo htmlspecialchars($req['reason']); ?>">
                                        <?php echo htmlspecialchars($req['reason']); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-3 py-1 <?php echo $statusClass[$req['status']]; ?> border rounded-full text-xs font-medium">
                                        <?php echo $req['status']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button onclick='viewReturnDetail(<?php echo json_encode($req, JSON_UNESCAPED_UNICODE); ?>)' 
                                            class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                                        <i class="fas fa-eye mr-1"></i>Xem
                                    </button>
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

<!-- Modal xem chi tiết -->
<div id="detailModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Chi tiết yêu cầu hoàn trả</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div id="modalContent"></div>
        </div>
    </div>
</div>

<script>
// Filter tabs
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const status = this.dataset.status;
        
        // Update active tab
        document.querySelectorAll('.filter-tab').forEach(t => {
            t.classList.remove('active', 'border-blue-600', 'text-blue-600');
        });
        this.classList.add('active', 'border-blue-600', 'text-blue-600');
        
        // Filter rows
        document.querySelectorAll('.return-row').forEach(row => {
            if (status === 'all' || row.dataset.status === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

function viewReturnDetail(data) {
    const images = JSON.parse(data.images || '[]');
    const baseUrl = '<?php echo BASE_URL; ?>';
    
    let imagesHtml = '';
    if (images.length > 0) {
        imagesHtml = '<div class="grid grid-cols-3 gap-2 mt-2">';
        images.forEach(img => {
            imagesHtml += `<a href="${baseUrl}/${img}" target="_blank" class="block">
                <img src="${baseUrl}/${img}" class="w-full h-32 object-cover rounded border hover:opacity-75">
            </a>`;
        });
        imagesHtml += '</div>';
    } else {
        imagesHtml = '<p class="text-gray-500 italic mt-2">Không có ảnh đính kèm</p>';
    }
    
    const statusColor = {
        'Chờ xử lý': 'yellow',
        'Đã chấp nhận': 'green',
        'Đã từ chối': 'red',
        'Đã hoàn tiền': 'purple'
    };
    
    const color = statusColor[data.status] || 'gray';
    
    let actionsHtml = '';
    // Kiểm tra role (OWNER hoặc SALES)
    const canUpdate = <?php echo in_array($_SESSION['role_id'], [ROLE_OWNER, ROLE_SALES]) ? 'true' : 'false'; ?>;

    // Hiển thị ghi chú cũ nếu có
    if (data.adminNote) {
        actionsHtml += `
            <div class="mt-4 p-3 bg-gray-50 rounded border border-gray-200">
                <p class="text-sm font-medium text-gray-700">Ghi chú xử lý:</p>
                <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">${data.adminNote}</p>
                ${data.processedAt ? `<p class="text-xs text-gray-500 mt-1">Cập nhật lúc: ${new Date(data.processedAt).toLocaleString('vi-VN')}</p>` : ''}
                ${data.adminName ? `<p class="text-xs text-gray-500">Bởi: ${data.adminName}</p>` : ''}
            </div>
        `;
    }
    
    if (canUpdate) {
        if (data.status === 'Chờ xử lý') {
            actionsHtml = `
                <div class="mt-6 pt-6 border-t">
                    <h4 class="font-semibold mb-3">Xử lý yêu cầu:</h4>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="returnID" value="${data.returnID}">
                        <div>
                            <label class="block text-sm font-medium mb-1">Ghi chú của admin:</label>
                            <textarea name="adminNote" rows="3" class="w-full border rounded px-3 py-2" placeholder="Nhập ghi chú (tùy chọn)"></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" name="action" value="approve" 
                                    class="flex-1 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                                    onclick="return confirm('Xác nhận CHẤP NHẬN yêu cầu hoàn trả này?')">
                                <i class="fas fa-check mr-1"></i>Chấp nhận
                            </button>
                            <button type="submit" name="action" value="reject" 
                                    class="flex-1 bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                                    onclick="return confirm('Xác nhận TỪ CHỐI yêu cầu hoàn trả này?')">
                                <i class="fas fa-times mr-1"></i>Từ chối
                            </button>
                        </div>
                    </form>
                </div>
            `;
        } else if (data.status === 'Đã chấp nhận') {
            actionsHtml += `
                <div class="mt-6 pt-6 border-t">
                    <h4 class="font-semibold mb-3">Xử lý hoàn tiền:</h4>
                    <div class="bg-blue-50 p-3 rounded mb-3 text-sm text-blue-700 border border-blue-200">
                        <i class="fas fa-info-circle mr-1"></i>
                        Vui lòng kiểm tra hàng hoàn trả trước khi xác nhận hoàn tiền.
                    </div>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="returnID" value="${data.returnID}">
                        <div>
                            <label class="block text-sm font-medium mb-1">Ghi chú hoàn tiền:</label>
                            <textarea name="adminNote" rows="3" class="w-full border rounded px-3 py-2" placeholder="Nhập ghi chú giao dịch hoàn tiền (Mã GD, Ngân hàng...)"></textarea>
                        </div>
                        <button type="submit" name="action" value="refund" 
                                class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700"
                                onclick="return confirm('Xác nhận ĐÃ HOÀN TIỀN cho khách hàng?')">
                            <i class="fas fa-money-bill-wave mr-1"></i>Xác nhận đã hoàn tiền
                        </button>
                    </form>
                </div>
            `;
        }
    }
    
    const html = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Mã yêu cầu:</p>
                    <p class="font-semibold">#${data.returnID}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Mã đơn hàng:</p>
                    <p class="font-semibold">#${data.orderID}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Khách hàng:</p>
                    <p class="font-semibold">${data.customerName}</p>
                    <p class="text-sm text-gray-500">${data.phone}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Tổng tiền:</p>
                    <p class="font-semibold text-red-600">${parseInt(data.totalAmount).toLocaleString('vi-VN')} đ</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Ngày yêu cầu:</p>
                    <p class="font-semibold">${new Date(data.createdAt).toLocaleString('vi-VN')}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Trạng thái:</p>
                    <span class="inline-block px-3 py-1 bg-${color}-100 text-${color}-800 border border-${color}-300 rounded-full text-sm font-medium">
                        ${data.status}
                    </span>
                </div>
            </div>
            
            <!-- Thông tin tài khoản hoàn tiền -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
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
                <p class="text-sm text-gray-600 mb-1">Lý do hoàn trả:</p>
                <div class="bg-gray-50 p-3 rounded">
                    <p class="text-sm">${data.reason}</p>
                </div>
            </div>
            
            <div>
                <p class="text-sm text-gray-600 mb-1">Ảnh chứng minh:</p>
                ${imagesHtml}
            </div>
            
            ${actionsHtml}
        </div>
    `;
    
    document.getElementById('modalContent').innerHTML = html;
    document.getElementById('detailModal').classList.remove('hidden');
    document.getElementById('detailModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('detailModal').classList.add('hidden');
    document.getElementById('detailModal').classList.remove('flex');
}

// Close modal when clicking outside
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
