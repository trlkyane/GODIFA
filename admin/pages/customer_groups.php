<?php
/**
 * Quản lý Nhóm Khách hàng
 * File: admin/pages/customer_groups.php
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission
if (!hasPermission('manage_customers') && !hasPermission('view_customers')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

// Load Controller
require_once __DIR__ . '/../../controller/admin/cCustomerGroup.php';
$groupController = new cCustomerGroup();

$success = '';
$error = '';

// Xử lý THÊM nhóm
if (isset($_POST['add_group']) && hasPermission('manage_customers')) {
    $data = [
        'groupName' => trim($_POST['groupName']),
        'description' => trim($_POST['description']),
        'minSpent' => floatval($_POST['minSpent']),
        'maxSpent' => !empty($_POST['maxSpent']) ? floatval($_POST['maxSpent']) : null,
        'color' => trim($_POST['color']),
        'status' => intval($_POST['status'])
    ];
    
    $result = $groupController->addGroup($data);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý SỬA nhóm
if (isset($_POST['edit_group']) && hasPermission('manage_customers')) {
    $groupID = intval($_POST['groupID']);
    
    // Kiểm tra xem có phải nhóm hệ thống không
    $checkGroup = $groupController->getGroupById($groupID);
    if ($checkGroup && isset($checkGroup['isSystem']) && $checkGroup['isSystem'] == 1) {
        $error = 'Không thể chỉnh sửa nhóm hệ thống "Khách hàng mới"!';
    } else {
        $data = [
            'groupName' => trim($_POST['groupName']),
            'description' => trim($_POST['description']),
            'minSpent' => floatval($_POST['minSpent']),
            'maxSpent' => !empty($_POST['maxSpent']) ? floatval($_POST['maxSpent']) : null,
            'color' => trim($_POST['color']),
            'status' => intval($_POST['status'])
        ];
        
        $result = $groupController->updateGroup($groupID, $data);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = implode('<br>', $result['errors']);
        }
    }
}

// Xử lý TOGGLE STATUS
if (isset($_GET['toggle']) && hasPermission('manage_customers')) {
    $groupID = intval($_GET['toggle']);
    
    // Kiểm tra xem có phải nhóm hệ thống không
    $checkGroup = $groupController->getGroupById($groupID);
    if ($checkGroup && isset($checkGroup['isSystem']) && $checkGroup['isSystem'] == 1) {
        $error = 'Không thể thay đổi trạng thái nhóm hệ thống "Khách hàng mới"!';
    } else {
        $result = $groupController->toggleStatus($groupID);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Xử lý XÓA nhóm
if (isset($_GET['delete']) && hasPermission('manage_customers')) {
    $groupID = intval($_GET['delete']);
    
    // Kiểm tra xem có phải nhóm hệ thống không
    $checkGroup = $groupController->getGroupById($groupID);
    if ($checkGroup && isset($checkGroup['isSystem']) && $checkGroup['isSystem'] == 1) {
        $error = 'Không thể xóa nhóm hệ thống "Khách hàng mới"!';
    } else {
        $result = $groupController->deleteGroup($groupID);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = implode('<br>', $result['errors']);
        }
    }
}

// Xử lý TÌM KIẾM
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchKeyword) {
    $groups = $groupController->searchGroups($searchKeyword);
} else {
    $groups = $groupController->getAllGroups();
}

// Lấy thống kê cho mỗi nhóm
foreach ($groups as &$group) {
    $stats = $groupController->getGroupStats($group['groupID']);
    $group['stats'] = $stats;
}
unset($group);

$totalGroups = $groupController->countGroups();

$pageTitle = 'Quản lý Nhóm Khách hàng';
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
                        <i class="fas fa-users-cog text-indigo-500 mr-2"></i>
                        Quản lý Nhóm Khách hàng
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng số: <strong><?php echo $totalGroups; ?></strong> nhóm
                    </p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Search -->
                    <form method="GET" class="flex items-center">
                        <input type="hidden" name="page" value="customer_groups">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchKeyword); ?>"
                                   placeholder="Tìm tên nhóm..." 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button type="submit" class="ml-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Tìm
                        </button>
                    </form>
                    
                    <?php if (hasPermission('manage_customers')): ?>
                    <button onclick="openAddModal()" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Thêm nhóm
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

            <!-- Groups Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($groups)): ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-users-cog text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">Chưa có nhóm khách hàng nào</p>
                </div>
                <?php else: ?>
                    <?php foreach ($groups as $group): ?>
                    <?php
                    $groupID = $group['groupID'];
                    $groupName = $group['groupName'];
                    $description = $group['description'] ?? '';
                    $color = $group['color'] ?? '#6366f1';
                    $status = $group['status'];
                    $stats = $group['stats'];
                    $totalCustomers = $stats['totalCustomers'] ?? 0;
                    $totalRevenue = $stats['totalRevenue'] ?? 0;
                    $totalOrders = $stats['totalOrders'] ?? 0;
                    
                    // Lấy range chi tiêu
                    $minSpent = $group['minSpent'] ?? 0;
                    $maxSpent = $group['maxSpent'] ?? null;
                    $spentRange = number_format($minSpent/1000000, 0) . 'M';
                    if ($maxSpent) {
                        $spentRange .= ' - ' . number_format($maxSpent/1000000, 0) . 'M';
                    } else {
                        $spentRange .= '+';
                    }
                    ?>
                    
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border-t-4" style="border-top-color: <?php echo $color; ?>">
                        <!-- Header -->
                        <div class="p-6 bg-gradient-to-br to-white" style="background: linear-gradient(to bottom right, <?php echo $color; ?>15, white);">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($groupName); ?></h3>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($description); ?></p>
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white" style="background-color: <?php echo $color; ?>">
                                        <i class="fas fa-wallet mr-1"></i>
                                        Chi tiêu: <?php echo $spentRange; ?>
                                    </span>
                                </div>
                                <?php if ($status == 1): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle"></i> Hoạt động
                                </span>
                                <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-pause-circle"></i> Tạm dừng
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Stats -->
                        <div class="p-6 bg-gray-50 border-t border-gray-200">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-indigo-600"><?php echo $totalCustomers; ?></div>
                                    <div class="text-xs text-gray-600 mt-1">Khách hàng</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-blue-600"><?php echo $totalOrders; ?></div>
                                    <div class="text-xs text-gray-600 mt-1">Đơn hàng</div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-green-600"><?php echo number_format($totalRevenue/1000, 0); ?>K</div>
                                    <div class="text-xs text-gray-600 mt-1">Doanh thu</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <?php if (hasPermission('manage_customers')): ?>
                        <div class="p-4 bg-white border-t border-gray-200 flex justify-center space-x-2">
                            <?php if (isset($group['isSystem']) && $group['isSystem'] == 1): ?>
                                <!-- Nhóm hệ thống - không cho chỉnh sửa -->
                                <div class="flex-1 px-4 py-2 bg-gray-300 text-gray-600 rounded-lg text-sm text-center cursor-not-allowed">
                                    <i class="fas fa-lock mr-1"></i> Nhóm hệ thống
                                </div>
                            <?php else: ?>
                                <!-- Nhóm thường - cho phép chỉnh sửa -->
                                <button onclick='openEditModal(<?php echo json_encode($group, JSON_HEX_APOS); ?>)' 
                                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                    <i class="fas fa-edit mr-1"></i> Sửa
                                </button>
                                
                                <button onclick="toggleStatus(<?php echo $groupID; ?>)" 
                                        class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors text-sm" 
                                        title="<?php echo $status == 1 ? 'Tạm dừng' : 'Kích hoạt'; ?>">
                                    <i class="fas fa-<?php echo $status == 1 ? 'pause' : 'play'; ?>"></i>
                                </button>
                                
                                <button onclick="deleteGroup(<?php echo $groupID; ?>, '<?php echo htmlspecialchars($groupName); ?>', <?php echo $totalCustomers; ?>)" 
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm" 
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Thêm nhóm -->
<div id="addModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-3 border-b border-indigo-200">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-plus text-indigo-500 mr-2"></i>
                Thêm nhóm khách hàng
            </h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4" id="addGroupForm" onsubmit="return validateAddForm()">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Tên nhóm <span class="text-red-500">*</span>
                </label>
                <input type="text" name="groupName" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="VD: Khách hàng VIP">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Mô tả
                </label>
                <textarea name="description" rows="3"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="Mô tả về nhóm khách hàng..."></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Màu sắc hiển thị
                </label>
                <div class="flex items-center space-x-2">
                    <input type="color" name="color" value="#6366f1"
                           class="w-16 h-10 border border-gray-300 rounded cursor-pointer">
                    <span class="text-sm text-gray-600">Chọn màu cho nhóm khách hàng</span>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Trạng thái
                </label>
                <select name="status" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="1">Hoạt động</option>
                    <option value="0">Tạm dừng</option>
                </select>
            </div>
            
            <!-- Điều kiện chi tiêu -->
            <div class="mb-4 p-4 bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg">
                <h4 class="font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-wallet text-green-600 mr-2"></i>
                    Điều kiện chi tiêu
                </h4>
                
                <div class="mb-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                        Chi tiêu tối thiểu (VNĐ)
                    </label>
                    <input type="number" name="minSpent" value="0" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="VD: 5000000">
                    <p class="text-xs text-gray-500 mt-1">Khách hàng phải chi tiêu ≥ số tiền này</p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-arrow-down text-blue-500 mr-1"></i>
                        Chi tiêu tối đa (VNĐ) <span class="text-gray-400 text-xs">(Để trống = không giới hạn)</span>
                    </label>
                    <input type="number" name="maxSpent" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="VD: 10000000 (hoặc để trống)">
                    <p class="text-xs text-gray-500 mt-1">Khách hàng phải chi tiêu ≤ số tiền này (không bắt buộc)</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
                <button type="submit" name="add_group"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-check mr-1"></i> Thêm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Sửa nhóm -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-3 border-b border-blue-200">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-edit text-blue-500 mr-2"></i>
                Sửa nhóm khách hàng
            </h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4" id="editGroupForm" onsubmit="return validateEditForm()">
            <input type="hidden" name="groupID" id="edit_groupID">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Tên nhóm <span class="text-red-500">*</span>
                </label>
                <input type="text" name="groupName" id="edit_groupName" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Mô tả
                </label>
                <textarea name="description" id="edit_description" rows="3"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Màu sắc hiển thị
                </label>
                <div class="flex items-center space-x-2">
                    <input type="color" name="color" id="edit_color"
                           class="w-16 h-10 border border-gray-300 rounded cursor-pointer">
                    <span class="text-sm text-gray-600">Chọn màu cho nhóm khách hàng</span>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Trạng thái
                </label>
                <select name="status" id="edit_status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1">Hoạt động</option>
                    <option value="0">Tạm dừng</option>
                </select>
            </div>
            
            <!-- Điều kiện chi tiêu -->
            <div class="mb-4 p-4 bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg">
                <h4 class="font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-wallet text-green-600 mr-2"></i>
                    Điều kiện chi tiêu
                </h4>
                
                <div class="mb-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                        Chi tiêu tối thiểu (VNĐ)
                    </label>
                    <input type="number" name="minSpent" id="edit_minSpent" value="0" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="VD: 5000000">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-arrow-down text-blue-500 mr-1"></i>
                        Chi tiêu tối đa (VNĐ) <span class="text-gray-400 text-xs">(Để trống = không giới hạn)</span>
                    </label>
                    <input type="number" name="maxSpent" id="edit_maxSpent" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="VD: 10000000 (hoặc để trống)">
                </div>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
                <button type="submit" name="edit_group"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-check mr-1"></i> Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Add modal
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

// Edit modal
function openEditModal(group) {
    document.getElementById('edit_groupID').value = group.groupID;
    document.getElementById('edit_groupName').value = group.groupName;
    document.getElementById('edit_description').value = group.description || '';
    document.getElementById('edit_color').value = group.color || '#6366f1';
    document.getElementById('edit_status').value = group.status;
    document.getElementById('edit_minSpent').value = group.minSpent || 0;
    document.getElementById('edit_maxSpent').value = group.maxSpent || '';
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Toggle status
function toggleStatus(groupID) {
    if (confirm('Bạn có chắc muốn thay đổi trạng thái nhóm này?')) {
        window.location.href = `?page=customer_groups&toggle=${groupID}`;
    }
}

// Delete group
function deleteGroup(groupID, groupName, totalCustomers) {
    if (totalCustomers > 0) {
        alert(`Không thể xóa nhóm "${groupName}"!\n\nNhóm này đang có ${totalCustomers} khách hàng. Vui lòng chuyển khách hàng sang nhóm khác trước khi xóa.`);
        return;
    }
    
    if (confirm(`Bạn có chắc muốn XÓA nhóm "${groupName}"?\n\nHành động này không thể hoàn tác!`)) {
        window.location.href = `?page=customer_groups&delete=${groupID}`;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    
    if (event.target == addModal) closeAddModal();
    if (event.target == editModal) closeEditModal();
}
</script>

<style>
/* Modal animations */
#addModal, #editModal {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Card hover effect */
.grid > div {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.grid > div:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>

<script>
// Validation cho form Add
function validateAddForm() {
    const minSpent = parseFloat(document.querySelector('#addGroupForm input[name="minSpent"]').value) || 0;
    const maxSpentInput = document.querySelector('#addGroupForm input[name="maxSpent"]');
    const maxSpent = parseFloat(maxSpentInput.value);
    
    if (maxSpent && maxSpent <= minSpent) {
        alert('Chi tiêu tối đa phải lớn hơn chi tiêu tối thiểu!');
        maxSpentInput.focus();
        return false;
    }
    
    return true;
}

// Validation cho form Edit
function validateEditForm() {
    const minSpent = parseFloat(document.getElementById('edit_minSpent').value) || 0;
    const maxSpentInput = document.getElementById('edit_maxSpent');
    const maxSpent = parseFloat(maxSpentInput.value);
    
    if (maxSpent && maxSpent <= minSpent) {
        alert('Chi tiêu tối đa phải lớn hơn chi tiêu tối thiểu!');
        maxSpentInput.focus();
        return false;
    }
    
    return true;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
