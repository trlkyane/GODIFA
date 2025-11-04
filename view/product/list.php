<?php
/**
 * Trang danh sách sản phẩm
 * MVC Flow: Entry → Controller → View
 */

// Middleware: Chỉ cho phép khách hàng truy cập
require_once __DIR__ . '/../../middleware/customer_only.php';

// Định nghĩa constant để ngăn controller xử lý request
define('CONTROLLER_INCLUDED', true);

// Load Controller
require_once __DIR__ . '/../../controller/cProduct.php';

// Khởi tạo Controller và lấy dữ liệu
$controller = new ProductController();
$data = $controller->index();

// Extract dữ liệu
$products = $data['products'];
$categories = $data['categories'];
$currentPage = $data['currentPage'];
$totalPages = $data['totalPages'];

// Params từ URL
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$keyword = isset($_GET['search']) ? trim($_GET['search']) : null;

include '../layout/header.php';
?>
<main class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-blue-700">Danh sách sản phẩm</h1>
    <form method="get" class="mb-8 flex flex-wrap gap-4 items-center">
        <select name="category" class="px-4 py-2 rounded border border-gray-300">
            <option value="">-- Tất cả danh mục --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['categoryID']; ?>" <?php if ($categoryId == $cat['categoryID']) echo 'selected'; ?>><?php echo $cat['categoryName']; ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" value="<?php echo htmlspecialchars($keyword ?? ''); ?>" placeholder="Tìm kiếm sản phẩm..." class="px-4 py-2 rounded border border-gray-300 w-64">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">Lọc</button>
    </form>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-xl shadow hover:shadow-lg transition overflow-hidden group">
                <div class="relative">
                    <img src="/GODIFA/image/<?php echo $product['image']; ?>" alt="<?php echo $product['productName']; ?>" class="w-full h-56 object-cover group-hover:scale-105 transition duration-500">
                    <?php if ($product['stockQuantity'] < 10): ?>
                        <span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">Sắp hết</span>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <h2 class="font-semibold text-lg mb-2 line-clamp-2 h-12"><?php echo $product['productName']; ?></h2>
                    <p class="text-blue-600 font-bold text-lg mb-3"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</p>
                    <p class="text-gray-500 text-sm mb-2">Danh mục: <?php echo $product['categoryName']; ?></p>
                    <div class="flex gap-2">
                        <a href="/GODIFA/controller/cProduct.php?action=detail&id=<?php echo $product['productID']; ?>" 
                           class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-lg transition text-sm">
                            <i class="fas fa-eye mr-1"></i>Chi tiết
                        </a>
                        <button onclick="addToCart(<?php echo $product['productID']; ?>)" 
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center py-2 rounded-lg transition text-sm">
                            <i class="fas fa-cart-plus mr-1"></i>Thêm
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
function addToCart(productId) {
    if (!confirm('Bạn có muốn thêm sản phẩm này vào giỏ hàng?')) {
        return;
    }
    
    fetch('/GODIFA/controller/cCart.php?action=add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `productId=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Đã thêm sản phẩm vào giỏ hàng!');
            // Cập nhật số lượng giỏ hàng trên header nếu có
            if (data.cartCount) {
                updateCartCount(data.cartCount);
            }
        } else {
            alert('❌ ' + (data.message || 'Có lỗi xảy ra!'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Không thể thêm vào giỏ hàng!');
    });
}

function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-count');
    if (cartBadge) {
        cartBadge.textContent = count;
    }
}
</script>

<?php include '../../view/chat/index.php'; ?>
<?php include '../layout/footer.php'; ?>
