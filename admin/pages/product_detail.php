<?php
/**
 * Chi tiết Sản phẩm (Admin view)
 * File: admin/pages/product_detail.php
 */
require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

if (!hasPermission('manage_products') && !hasPermission('view_products')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

require_once __DIR__ . '/../../controller/admin/cProduct.php';
$productController = new cProduct();

$productID = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;
if ($productID > 0) {
    $product = $productController->getProductById($productID);
}

$pageTitle = 'Chi tiết sản phẩm';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 overflow-y-auto ml-64">
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-4 md:px-6 py-4 flex justify-between items-center">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-box-open text-blue-500 mr-2"></i> Chi tiết Sản phẩm
                    </h1>
                    <?php if ($product): ?>
                    <p class="text-sm text-gray-600 mt-1">ID: <strong>#<?php echo $product['productID']; ?></strong></p>
                    <?php endif; ?>
                </div>
                <div class="flex gap-2">
                    <a href="?page=products" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Quay lại danh sách
                    </a>
                    
                </div>
            </div>
        </div>

        <div class="p-4 md:p-6">
            <?php if (!$product): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                    <h2 class="text-xl font-bold mb-2">Sản phẩm không tồn tại</h2>
                    <p class="text-gray-600 mb-4">ID không hợp lệ hoặc sản phẩm đã bị xóa.</p>
                    <a href="?page=products" class="inline-block px-6 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-black">
                        Quay lại quản lý sản phẩm
                    </a>
                </div>
            <?php else: ?>
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Left: Image -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="aspect-[4/5] bg-gray-50 flex items-center justify-center">
                            <?php if ($product['image']): ?>
                                <img src="/GODIFA/image/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['productName']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="text-gray-400 text-sm"><i class="fas fa-image text-3xl mb-2"></i><p>Chưa có hình</p></div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4 border-t text-xs text-gray-500 flex justify-between">
                            <span>SKU: <strong class="text-gray-700"><?php echo htmlspecialchars($product['SKU_MRK'] ?: 'N/A'); ?></strong></span>
                            <span>Danh mục: <strong class="text-gray-700"><?php echo htmlspecialchars($product['categoryName'] ?: 'N/A'); ?></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Middle: Info -->
                <div class="lg:col-span-1 space-y-4">
                    <div class="bg-white rounded-lg shadow p-5">
                        <h2 class="text-lg font-bold mb-3 leading-tight text-gray-900"><?php echo htmlspecialchars($product['productName']); ?></h2>
                        <?php if (!empty($product['promotional_price']) && $product['promotional_price'] > 0): ?>
                            <div class="flex items-center gap-3 mb-4">
                                <span class="text-2xl font-bold text-red-600"><?php echo number_format($product['promotional_price'], 0, ',', '.'); ?>₫</span>
                                <span class="text-base line-through text-gray-400"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</span>
                                <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded">-<?php echo round((($product['price'] - $product['promotional_price']) / $product['price']) * 100); ?>%</span>
                            </div>
                        <?php else: ?>
                            <div class="mb-4"><span class="text-2xl font-bold text-gray-800"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</span></div>
                        <?php endif; ?>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="bg-gray-50 p-3 rounded">
                                <p class="text-gray-500 text-xs">Trạng thái</p>
                                <p class="font-medium mt-1">
                                    <span class="px-2 py-1 rounded text-xs <?php echo $product['status']==1?'bg-green-100 text-green-700':'bg-red-100 text-red-700'; ?>">
                                        <?php echo $product['status']==1?'Hoạt động':'Đã khóa'; ?>
                                    </span>
                                </p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <p class="text-gray-500 text-xs">Tồn kho</p>
                                <p class="font-medium mt-1"><?php echo $product['stockQuantity']; ?> sản phẩm</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <p class="text-gray-500 text-xs">Đã bán</p>
                                <p class="font-medium mt-1">—</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded">
                                <p class="text-gray-500 text-xs">Mã nội bộ</p>
                                <p class="font-medium mt-1"><?php echo htmlspecialchars($product['SKU_MRK'] ?: 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold mb-3 text-gray-700">Mô tả</h3>
                        <div class="text-sm leading-relaxed text-gray-700 whitespace-pre-line max-h-60 overflow-auto">
                            <?php echo htmlspecialchars($product['description'] ?: 'Chưa có mô tả.'); ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Actions / Quick edit suggestion -->
                <div class="lg:col-span-1 space-y-4">
                    <div class="bg-white rounded-lg shadow p-5">
                        <h3 class="text-sm font-semibold mb-3 text-gray-700">Liên kết</h3>
                        <a href="/GODIFA/controller/cProduct.php?action=detail&id=<?php echo $product['productID']; ?>" target="_blank" class="w-full block text-center px-4 py-2 bg-gray-800 hover:bg-black text-white rounded text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i> Xem trên website (cửa hàng)
                        </a>
                    </div>
                    <div class="bg-white rounded-lg shadow p-5 text-xs text-gray-500">
                        <p><strong>Lưu ý:</strong> Đây là trang xem chi tiết dành cho Admin. Chỉnh sửa, khóa hoặc xóa chỉ thực hiện ở trang danh sách sản phẩm.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Không nhúng lại products.php để tránh trùng lặp giao diện -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
