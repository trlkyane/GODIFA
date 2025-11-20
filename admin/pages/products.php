<?php
/**
 * Quản lý Sản phẩm
 * File: admin/pages/products.php
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission
if (!hasPermission('manage_products') && !hasPermission('view_products')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

// Load Controller (Admin version)
require_once __DIR__ . '/../../controller/admin/cProduct.php';
$productController = new cProduct();

$success = '';
$error = '';

// Xử lý THÊM sản phẩm
if (isset($_POST['add_product']) && hasPermission('manage_products')) {
    // Xử lý upload ảnh
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = __DIR__ . "/../../image/";
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $imageFileType;
        $targetFile = $targetDir . $newFileName;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $image = $newFileName;
        }
    }
    
    $data = [
        'productName' => trim($_POST['productName']),
        'SKU' => trim($_POST['SKU_MRK']),
        'stockQuantity' => intval($_POST['stockQuantity']),
        'price' => floatval($_POST['price']),
        'promotional_price' => !empty($_POST['promotional_price']) ? floatval($_POST['promotional_price']) : null,
        'description' => trim($_POST['description']),
        'categoryID' => intval($_POST['categoryID']),
        'image' => $image,
        'status' => '1'
    ];
    
    $result = $productController->addProduct($data);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý SỬA sản phẩm
if (isset($_POST['edit_product']) && hasPermission('manage_products')) {
    $productID = intval($_POST['productID']);
    
    // Lấy ảnh cũ
    $currentProduct = $productController->getProductById($productID);
    $image = $currentProduct['image'];
    
    // Xử lý upload ảnh mới (nếu có)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = __DIR__ . "/../../image/";
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $imageFileType;
        $targetFile = $targetDir . $newFileName;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            // Xóa ảnh cũ nếu có
            if ($image && file_exists($targetDir . $image)) {
                unlink($targetDir . $image);
            }
            $image = $newFileName;
        }
    }
    
    $data = [
        'productName' => trim($_POST['productName']),
        'SKU' => trim($_POST['SKU_MRK']),
        'stockQuantity' => intval($_POST['stockQuantity']),
        'price' => floatval($_POST['price']),
        'promotional_price' => !empty($_POST['promotional_price']) ? floatval($_POST['promotional_price']) : null,
        'description' => trim($_POST['description']),
        'categoryID' => intval($_POST['categoryID']),
        'image' => $image
    ];
    
    $result = $productController->updateProduct($productID, $data);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý TOGGLE STATUS (Khóa/Mở khóa)
if (isset($_GET['toggle']) && hasPermission('manage_products')) {
    $productId = intval($_GET['toggle']);
    
    $result = $productController->toggleStatus($productId);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Xử lý XÓA sản phẩm
if (isset($_GET['delete']) && hasPermission('delete_product')) {
    $productId = intval($_GET['delete']);
    
    // Xóa ảnh trước
    $product = $productController->getProductById($productId);
    if ($product && $product['image']) {
        $imagePath = __DIR__ . "/../../image/" . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    $result = $productController->deleteProduct($productId);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Phân trang
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search) {
    $products = $productController->searchProducts($search);
    $totalRows = count($products);
    $products = array_slice($products, $offset, $limit);
} else {
    $products = $productController->getAllProducts();
    $totalRows = $productController->countProducts();
    $products = array_slice($products, $offset, $limit);
}

$totalPages = ceil($totalRows / $limit);

// Lấy danh sách categories cho dropdown
$categories = $productController->getAllCategories();

$pageTitle = 'Quản lý Sản phẩm';
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
                        <i class="fas fa-box text-blue-500 mr-2"></i>
                        Quản lý Sản phẩm
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng số: <strong><?php echo $totalRows; ?></strong> sản phẩm
                    </p>
                </div>
                <?php if (hasPermission('manage_products')): ?>
                <button onclick="openAddModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg inline-flex items-center text-sm md:text-base">
                    <i class="fas fa-plus mr-2"></i>Thêm sản phẩm
                </button>
                <?php endif; ?>
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

            <!-- Search -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-2">
                    <input type="hidden" name="page" value="products">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Tìm kiếm sản phẩm..." 
                           class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Tìm kiếm
                    </button>
                    <?php if ($search): ?>
                    <a href="?page=products" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-times mr-2"></i>Xóa
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Products Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase" style="width: 80px;">Hình</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase" style="width: 250px;">Tên sản phẩm</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase" style="width: 120px;">Danh mục</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase" style="width: 110px;">Giá</th>
                                <th class="px-2 py-3 text-center text-xs font-semibold text-gray-700 uppercase" style="width: 100px;">Số lượng</th>
                                <th class="px-2 py-3 text-center text-xs font-semibold text-gray-700 uppercase" style="width: 110px;">Trạng thái</th>
                                <th class="px-2 py-3 text-center text-xs font-semibold text-gray-700 uppercase" style="width: 150px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Không có sản phẩm nào</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <?php if ($product['image']): ?>
                                        <img src="/GODIFA/image/<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['productName']); ?>"
                                             class="w-14 h-14 object-cover rounded">
                                        <?php else: ?>
                                        <div class="w-14 h-14 bg-gray-200 rounded flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div onclick='openViewModal(<?php echo json_encode($product, JSON_HEX_APOS); ?>)' class="font-medium text-gray-900 truncate cursor-pointer hover:text-blue-600" style="max-width: 250px;" title="Xem chi tiết: <?php echo htmlspecialchars($product['productName']); ?>">
                                            <?php echo htmlspecialchars($product['productName']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 truncate" style="max-width: 250px;">
                                            <?php echo htmlspecialchars(mb_substr($product['description'], 0, 40)); ?>...
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">
                                            <?php echo htmlspecialchars($product['categoryName'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if (!empty($product['promotional_price']) && $product['promotional_price'] > 0): ?>
                                            <div class="flex flex-col">
                                                <span class="text-gray-400 line-through text-xs"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</span>
                                                <span class="font-semibold text-red-600"><?php echo number_format($product['promotional_price'], 0, ',', '.'); ?>₫</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="font-semibold text-green-600"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-3 text-center text-sm">
                                        <span class="px-2 py-0.5 <?php echo $product['stockQuantity'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded text-xs font-medium">
                                            <?php echo $product['stockQuantity']; ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-center text-sm">
                                        <span class="px-3 py-1 <?php echo $product['status'] == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded-full text-xs font-medium inline-flex items-center">
                                            <i class="fas fa-<?php echo $product['status'] == 1 ? 'check-circle' : 'times-circle'; ?> mr-1"></i>
                                            <?php echo $product['status'] == 1 ? 'Hoạt động' : 'Đã khóa'; ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-center">
                                        <div class="flex justify-center items-center space-x-2">
                                            <!-- Xem nhanh -->
                                            <button onclick='openViewModal(<?php echo json_encode($product, JSON_HEX_APOS); ?>)'
                                                    class="text-gray-600 hover:text-blue-600" title="Xem chi tiết">
                                                <i class="fas fa-eye text-lg"></i>
                                            </button>
                                            <?php if (hasPermission('manage_products')): ?>
                                            <!-- Sửa -->
                                            <button onclick='openEditModal(<?php echo json_encode($product, JSON_HEX_APOS); ?>)' 
                                                    class="text-blue-600 hover:text-blue-800" title="Sửa">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>
                                            
                                            <!-- Khóa/Mở khóa -->
                                            <?php if ($product['status'] == 1): ?>
                                            <button onclick="toggleStatus(<?php echo $product['productID']; ?>)" 
                                                    class="text-orange-600 hover:text-orange-800" title="Khóa sản phẩm">
                                                <i class="fas fa-lock text-lg"></i>
                                            </button>
                                            <?php else: ?>
                                            <button onclick="toggleStatus(<?php echo $product['productID']; ?>)" 
                                                    class="text-green-600 hover:text-green-800" title="Mở khóa sản phẩm">
                                                <i class="fas fa-lock-open text-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <!-- Xóa -->
                                            <?php if (hasPermission('delete_product')): ?>
                                            <button onclick="deleteProduct(<?php echo $product['productID']; ?>)" 
                                                    class="text-red-600 hover:text-red-800" title="Xóa">
                                                <i class="fas fa-trash text-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            <!-- Chỉ xem -->
                                            <span class="text-gray-400 text-sm">Không có quyền</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="px-4 py-3 bg-gray-50 border-t flex flex-wrap justify-between items-center gap-2">
                    <div class="text-sm text-gray-600">
                        Trang <?php echo $page; ?> / <?php echo $totalPages; ?>
                    </div>
                    <div class="flex gap-1">
                        <?php if ($page > 1): ?>
                        <a href="?page=products&p=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                           class="px-3 py-1 bg-white border rounded hover:bg-gray-100 text-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <a href="?page=products&p=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                           class="px-3 py-1 <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-white hover:bg-gray-100'; ?> border rounded text-sm">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=products&p=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                           class="px-3 py-1 bg-white border rounded hover:bg-gray-100 text-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal THÊM sản phẩm -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Thêm sản phẩm mới
            </h3>
            <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên sản phẩm <span class="text-red-500">*</span></label>
                    <input type="text" name="productName" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mã SKU</label>
                    <input type="text" name="SKU_MRK"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Danh mục <span class="text-red-500">*</span></label>
                    <select name="categoryID" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['categoryID']; ?>"><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giá gốc (VNĐ) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" required min="0" step="1000"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giá khuyến mãi (VNĐ)</label>
                    <input type="number" name="promotional_price" min="0" step="1000"
                           placeholder="Để trống nếu không có KM"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Giá khuyến mãi phải nhỏ hơn giá gốc</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng <span class="text-red-500">*</span></label>
                    <input type="number" name="stockQuantity" required min="0"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hình ảnh</label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mô tả</label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeAddModal()" 
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">
                    Hủy
                </button>
                <button type="submit" name="add_product" 
                        class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Thêm sản phẩm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal SỬA sản phẩm -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-edit text-blue-500 mr-2"></i>Sửa sản phẩm
            </h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" name="productID" id="edit_productID">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tên sản phẩm <span class="text-red-500">*</span></label>
                    <input type="text" name="productName" id="edit_productName" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mã SKU</label>
                    <input type="text" name="SKU_MRK" id="edit_SKU_MRK"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Danh mục <span class="text-red-500">*</span></label>
                    <select name="categoryID" id="edit_categoryID" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['categoryID']; ?>"><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giá gốc (VNĐ) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" id="edit_price" required min="0" step="1000"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Giá khuyến mãi (VNĐ)</label>
                    <input type="number" name="promotional_price" id="edit_promotional_price" min="0" step="1000"
                           placeholder="Để trống nếu không có KM"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Giá khuyến mãi phải nhỏ hơn giá gốc</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Số lượng <span class="text-red-500">*</span></label>
                    <input type="number" name="stockQuantity" id="edit_stockQuantity" required min="0"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hình ảnh hiện tại</label>
                    <div id="edit_currentImage" class="mb-2"></div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thay đổi hình ảnh (để trống nếu không đổi)</label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mô tả</label>
                    <textarea name="description" id="edit_description" rows="4"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">
                    Hủy
                </button>
                <button type="submit" name="edit_product" 
                        class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
                    <i class="fas fa-save mr-2"></i>Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal XEM NHANH sản phẩm -->
<div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-lg">
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <i class="fas fa-eye text-blue-500 mr-2"></i>Chi tiết sản phẩm
            </h3>
            <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6" id="viewContent">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <div id="viewImage" class="aspect-[4/5] w-full bg-gray-100 rounded flex items-center justify-center overflow-hidden mb-4"></div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-gray-500">Trạng thái</p>
                            <p id="viewStatus" class="mt-1 font-medium"></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-gray-500">Tồn kho</p>
                            <p id="viewStock" class="mt-1 font-medium"></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-gray-500">SKU</p>
                            <p id="viewSKU" class="mt-1 font-medium"></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="text-gray-500">Danh mục</p>
                            <p id="viewCategory" class="mt-1 font-medium"></p>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 id="viewName" class="text-xl font-bold text-gray-900 mb-3 leading-tight"></h2>
                    <div id="viewPricing" class="mb-4"></div>
                    <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line max-h-56 overflow-auto" id="viewDescription"></div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button onclick="closeViewModal()" class="px-5 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded text-sm">Đóng</button>
                <?php /* Optional quick edit */ ?>
                <?php if (hasPermission('manage_products')): ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// View modal state
let currentViewedProduct = null;

function openViewModal(product) {
    currentViewedProduct = product;
    // Name
    document.getElementById('viewName').textContent = product.productName || 'N/A';
    // Image
    const imgBox = document.getElementById('viewImage');
    if (product.image) {
        imgBox.innerHTML = `<img src="/GODIFA/image/${product.image}" alt="${product.productName}" class="w-full h-full object-cover"/>`;
    } else {
        imgBox.innerHTML = '<div class="text-gray-400 text-sm"><i class="fas fa-image text-3xl mb-2"></i><p>Không có hình</p></div>';
    }
    // Status
    const statusHtml = `<span class='px-2 py-1 rounded text-xs ${product.status==1?'bg-green-100 text-green-700':'bg-red-100 text-red-700'}'>${product.status==1?'Hoạt động':'Đã khóa'}</span>`;
    document.getElementById('viewStatus').innerHTML = statusHtml;
    // Stock
    document.getElementById('viewStock').textContent = (product.stockQuantity || 0) + ' sản phẩm';
    // SKU
    document.getElementById('viewSKU').textContent = product.SKU_MRK || '—';
    // Category
    document.getElementById('viewCategory').textContent = product.categoryName || '—';
    // Pricing
    const pricingDiv = document.getElementById('viewPricing');
    if (product.promotional_price && product.promotional_price > 0) {
        const discount = Math.round(((product.price - product.promotional_price)/product.price)*100);
        pricingDiv.innerHTML = `
            <div class='flex items-center gap-3'>
                <span class='text-2xl font-bold text-red-600'>${Number(product.promotional_price).toLocaleString('vi-VN')}₫</span>
                <span class='text-base line-through text-gray-400'>${Number(product.price).toLocaleString('vi-VN')}₫</span>
                <span class='px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded'>-${discount}%</span>
            </div>`;
    } else {
        pricingDiv.innerHTML = `<span class='text-2xl font-bold text-gray-800'>${Number(product.price).toLocaleString('vi-VN')}₫</span>`;
    }
    // Description
    document.getElementById('viewDescription').textContent = product.description || 'Chưa có mô tả.';
    // Edit button visibility
    const editBtn = document.getElementById('viewEditBtn');
    if (editBtn) editBtn.classList.remove('hidden');
    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
    currentViewedProduct = null;
}
// Modal functions
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(product) {
    document.getElementById('edit_productID').value = product.productID;
    document.getElementById('edit_productName').value = product.productName;
    document.getElementById('edit_SKU_MRK').value = product.SKU_MRK || '';
    document.getElementById('edit_categoryID').value = product.categoryID;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_promotional_price').value = product.promotional_price || '';
    document.getElementById('edit_stockQuantity').value = product.stockQuantity;
    document.getElementById('edit_description').value = product.description || '';
    
    // Hiển thị ảnh hiện tại
    const imageDiv = document.getElementById('edit_currentImage');
    if (product.image) {
        imageDiv.innerHTML = `<img src="/GODIFA/image/${product.image}" alt="Current" class="w-32 h-32 object-cover rounded">`;
    } else {
        imageDiv.innerHTML = '<p class="text-gray-500 text-sm">Chưa có hình ảnh</p>';
    }
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Toggle status
function toggleStatus(id) {
    if (confirm('Bạn có chắc muốn thay đổi trạng thái sản phẩm này?\n\n• Hoạt động → Đã khóa\n• Đã khóa → Hoạt động')) {
        window.location.href = `?page=products&toggle=${id}`;
    }
}

// Delete product
function deleteProduct(id) {
    if (confirm('Bạn có chắc muốn XÓA sản phẩm này?\n\nLưu ý: Hành động này không thể hoàn tác!')) {
        window.location.href = `?page=products&delete=${id}`;
    }
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
</script>

<style>
/* Custom styles for products table - Responsive */
table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

table th, table td {
    word-wrap: break-word;
    overflow-wrap: break-word;
    vertical-align: middle;
}

/* Truncate text */
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Image hover effect */
table img {
    transition: transform 0.2s ease;
}

table img:hover {
    transform: scale(1.15);
}

/* Icon buttons */
table td a, table td button {
    transition: all 0.2s ease;
}

table td a:hover, table td button:hover {
    transform: translateY(-1px);
}

/* Action buttons in table */
table td button {
    padding: 0.25rem;
    transition: all 0.2s ease;
}

table td button:hover {
    transform: scale(1.2);
}

/* Responsive breakpoints */
@media (max-width: 768px) {
    /* Mobile: Allow horizontal scroll */
    .overflow-x-auto {
        overflow-x: auto;
    }
    
    table {
        min-width: 930px;
    }
    
    /* Smaller padding on mobile */
    .px-4 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
}

@media (min-width: 769px) and (max-width: 1400px) {
    /* Tablet: Allow scroll if needed */
    .overflow-x-auto {
        overflow-x: auto;
    }
    
    table {
        min-width: 900px;
    }
}

@media (min-width: 1401px) {
    /* Desktop: No scroll needed */
    table {
        width: 100%;
    }
}

/* Scrollbar styling */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Modal animations */
#addModal, #editModal {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
