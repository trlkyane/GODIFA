<?php
/**
 * Admin Page: Quản lý Danh mục sản phẩm
 * File: admin/pages/categories.php
 */

// Check permission - Có thể xem hoặc quản lý danh mục
if (!hasPermission('view_categories') && !hasPermission('manage_categories')) {
    header('Location: ../index.php?error=permission_denied');
    exit();
}

// Include Controller (Admin version)
require_once __DIR__ . '/../../controller/admin/cCategory.php';
$categoryController = new cCategory();

$message = '';
$messageType = '';

// Xử lý các action
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Thêm danh mục mới
    if (isset($_POST['add_category']) && hasPermission('manage_categories')) {
        $data = [
            'categoryName' => trim($_POST['categoryName']),
            'description' => trim($_POST['description'] ?? ''),
            'status' => '1'
        ];
        
        $result = $categoryController->addCategory($data);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = implode('<br>', $result['errors']);
            $messageType = 'error';
        }
    }
    
    // Cập nhật danh mục
    if (isset($_POST['update_category'])) {
        $categoryID = intval($_POST['categoryID']);
        $data = [
            'categoryName' => trim($_POST['categoryName']),
            'description' => trim($_POST['description'] ?? ''),
            'status' => isset($_POST['status']) ? '1' : '0'
        ];
        
        $result = $categoryController->updateCategory($categoryID, $data);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = implode('<br>', $result['errors']);
            $messageType = 'error';
        }
    }
    
    // Toggle trạng thái
    if (isset($_POST['toggle_status'])) {
        $categoryID = intval($_POST['categoryID']);
        
        $result = $categoryController->toggleStatus($categoryID);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

// Lấy danh sách danh mục
$categories = $categoryController->getAllCategories();

// Lấy danh mục để edit (nếu có)
$editCategory = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $editCategory = $categoryController->getCategoryById($_GET['id']);
}

$pageTitle = 'Quản lý Danh mục';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto ml-64">
        <!-- Header -->
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-6 py-4 flex flex-wrap justify-between items-center gap-4">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                    <i class="fas fa-folder text-purple-500 mr-2"></i>
                    Quản lý Danh mục
                </h1>
                <?php if (hasPermission('manage_categories')): ?>
                <button onclick="openAddModal()" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Thêm danh mục
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <!-- Message -->
            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded-lg <?= $messageType == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?>">
                    <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Categories Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th style="width: 35%;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                TÊN DANH MỤC
                            </th>
                            <th style="width: 15%;" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                SỐ SẢN PHẨM
                            </th>
                            <th style="width: 15%;" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                TRẠNG THÁI
                            </th>
                            <th style="width: 35%;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                HÀNH ĐỘNG
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        if (empty($categories)) {
                            echo '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-folder-open text-5xl text-gray-300 mb-3"></i>
                                    <p class="text-lg">Chưa có danh mục nào</p>
                                  </td></tr>';
                        } else {
                            foreach ($categories as $category): 
                                $productCount = $categoryController->countProductsInCategory($category['categoryID']);
                                $status = isset($category['status']) ? $category['status'] : 1;
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900 truncate" title="<?= htmlspecialchars($category['categoryName']) ?>">
                                    <?= htmlspecialchars($category['categoryName']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                    <i class="fas fa-box mr-1"></i>
                                    <?= $productCount ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($status == 1): ?>
                                    <span class="px-3 py-1 inline-flex items-center text-xs font-bold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i> Hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex items-center text-xs font-bold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-lock mr-1"></i> Đã khóa
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <?php if (hasPermission('manage_categories')): ?>
                                <div class="flex items-center justify-start space-x-3">
                                    <!-- Nút Sửa -->
                                    <button onclick='openEditModal(<?= json_encode($category) ?>)' 
                                            class="inline-flex items-center text-blue-600 hover:text-blue-900 font-semibold">
                                        <i class="fas fa-edit mr-1"></i> Sửa
                                    </button>
                                    
                                    <!-- Nút Khóa/Mở khóa -->
                                    <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn thay đổi trạng thái danh mục này?')">
                                        <input type="hidden" name="categoryID" value="<?= $category['categoryID'] ?>">
                                        <button type="submit" name="toggle_status" 
                                                class="inline-flex items-center font-semibold <?= $status == 1 ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' ?>">
                                            <i class="fas <?= $status == 1 ? 'fa-lock' : 'fa-unlock' ?> mr-1"></i>
                                            <?= $status == 1 ? 'Khóa' : 'Mở' ?>
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span class="text-gray-400 text-sm italic">Chỉ xem</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        } // end if/else
                        ?>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm danh mục -->
<div id="addModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">
                <i class="fas fa-plus-circle text-purple-500 mr-2"></i>
                Thêm danh mục mới
            </h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Tên danh mục <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="categoryName" 
                       required
                       placeholder="Nhập tên danh mục"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" 
                        onclick="closeAddModal()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Hủy
                </button>
                <button type="submit" 
                        name="add_category"
                        class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-save mr-1"></i> Thêm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa danh mục -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">
                <i class="fas fa-edit text-blue-500 mr-2"></i>
                Sửa danh mục
            </h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" id="editForm">
            <input type="hidden" name="categoryID" id="edit_categoryID">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Tên danh mục <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="categoryName" 
                       id="edit_categoryName"
                       required
                       placeholder="Nhập tên danh mục"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" 
                        onclick="closeEditModal()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Hủy
                </button>
                <button type="submit" 
                        name="update_category"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-save mr-1"></i> Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Custom styles for categories table */
table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    word-wrap: break-word;
    overflow-wrap: break-word;
    vertical-align: middle;
}

/* Đảm bảo text trong cột hiển thị đầy đủ */
table td:nth-child(2) {
    min-width: 250px;
    max-width: none;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .overflow-x-auto {
        overflow-x: scroll;
    }
    
    table {
        min-width: 1000px;
    }
}

/* Button hover effects */
button:hover, a:hover {
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

/* Modal animation */
#addModal, #editModal {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Scrollbar styling */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<script>
// Modal functions
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.querySelector('[name="categoryName"]').focus();
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.querySelector('[name="categoryName"]').value = '';
}

function openEditModal(category) {
    document.getElementById('edit_categoryID').value = category.categoryID;
    document.getElementById('edit_categoryName').value = category.categoryName;
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('edit_categoryName').focus();
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    
    if (event.target == addModal) {
        closeAddModal();
    }
    if (event.target == editModal) {
        closeEditModal();
    }
}

// Close modal with ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAddModal();
        closeEditModal();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
