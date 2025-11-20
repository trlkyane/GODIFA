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

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2 brand-font">
            <?php 
            if($categoryId) {
                foreach($categories as $cat) {
                    if($cat['categoryID'] == $categoryId) echo htmlspecialchars($cat['categoryName']);
                }
            } elseif($keyword) {
                echo 'Tìm kiếm: "' . htmlspecialchars($keyword) . '"';
            } else {
                echo 'Tất cả sản phẩm';
            }
            ?>
        </h1>
        <p class="text-gray-500 text-sm"><?php echo count($products); ?> sản phẩm</p>
    </div>

    <!-- Filter Bar -->
    <form method="get" class="mb-6 pb-4 border-b border-gray-100">
        <div class="flex flex-wrap gap-3 items-center">
            <select name="category" class="px-4 py-2 bg-white border border-gray-200 rounded-sm text-sm focus:outline-none focus:border-black transition">
                <option value="">Tất cả danh mục</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['categoryID']; ?>" <?php if ($categoryId == $cat['categoryID']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['categoryName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="search" value="<?php echo htmlspecialchars($keyword ?? ''); ?>" 
                   placeholder="Tìm kiếm..." 
                   class="px-4 py-2 bg-white border border-gray-200 rounded-sm text-sm focus:outline-none focus:border-black transition w-full md:w-64">
            
            <button type="submit" class="px-6 py-2 bg-black text-white text-sm font-medium rounded-sm hover:bg-gray-800 transition">
                Lọc
            </button>
            
            <?php if($categoryId || $keyword): ?>
                <a href="/GODIFA/view/product/list.php" class="px-4 py-2 text-sm text-gray-500 hover:text-black transition">
                    Xóa bộ lọc
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Product Grid -->
    <?php if(empty($products)): ?>
        <div class="text-center py-20">
            <p class="text-gray-500 text-lg mb-4">Không tìm thấy sản phẩm nào</p>
            <a href="/GODIFA/view/product/list.php" class="text-sm text-black border-b border-black hover:text-gray-600 transition">
                Xem tất cả sản phẩm
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-x-3 gap-y-6">
            <?php foreach ($products as $product): ?>
                <div class="group relative">
                    <div class="aspect-square w-full overflow-hidden rounded-sm bg-gray-100 relative">
                        <img src="/GODIFA/image/<?php echo $product['image']; ?>" 
                             alt="<?php echo htmlspecialchars($product['productName']); ?>" 
                             class="h-full w-full object-cover object-center group-hover:scale-110 transition duration-500">
                        
                        <?php if ($product['stockQuantity'] < 10): ?>
                            <span class="absolute top-2 left-2 bg-black text-white text-xs px-2 py-1 font-medium">
                                Sắp hết
                            </span>
                        <?php endif; ?>
                        
                        <div class="absolute inset-x-0 bottom-0 p-2 opacity-0 group-hover:opacity-100 transition duration-300 hidden md:block">
                            <button onclick="addToCart(<?php echo $product['productID']; ?>)" 
                                    class="w-full bg-white/90 backdrop-blur text-black py-2 text-xs font-bold uppercase tracking-wide hover:bg-black hover:text-white transition shadow-lg">
                                Thêm vào giỏ
                            </button>
                        </div>
                    </div>

                    <div class="mt-2">
                        <h3 class="text-xs text-gray-900 line-clamp-2 min-h-[2rem] leading-tight">
                            <a href="/GODIFA/controller/cProduct.php?action=detail&id=<?php echo $product['productID']; ?>">
                                <span aria-hidden="true" class="absolute inset-0"></span>
                                <?php echo htmlspecialchars($product['productName']); ?>
                            </a>
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500"><?php echo htmlspecialchars($product['categoryName']); ?></p>
                        
                        <!-- Rating stars -->
                        <?php 
                        $rating = isset($product['avgRating']) ? floatval($product['avgRating']) : 0;
                        $reviewCount = isset($product['reviewCount']) ? intval($product['reviewCount']) : 0;
                        ?>
                        <?php if ($reviewCount > 0): ?>
                        <div class="flex items-center gap-0.5 mt-1">
                            <div class="flex text-yellow-400">
                                <?php for($i = 0; $i < 5; $i++): ?>
                                    <svg class="w-2.5 h-2.5 <?php echo $i < floor($rating) ? 'fill-current' : 'fill-gray-200'; ?>" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="text-[10px] text-gray-500">(<?php echo $reviewCount; ?>)</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-1">
                            <?php if (!empty($product['promotional_price']) && $product['promotional_price'] > 0): ?>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400 line-through"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</span>
                                    <span class="text-sm font-bold text-red-600"><?php echo number_format($product['promotional_price'], 0, ',', '.'); ?>₫</span>
                                </div>
                                <span class="inline-block mt-0.5 px-1.5 py-0.5 bg-red-100 text-red-600 text-[10px] font-semibold rounded">
                                    -<?php echo round((($product['price'] - $product['promotional_price']) / $product['price']) * 100); ?>%
                                </span>
                            <?php else: ?>
                                <p class="text-sm font-bold text-red-600">
                                    <?php echo number_format($product['price'], 0, ',', '.'); ?>₫
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Mobile Add to Cart -->
                    <button onclick="addToCart(<?php echo $product['productID']; ?>)" 
                            class="mt-2 w-full md:hidden py-1.5 border border-black text-black text-xs font-medium hover:bg-black hover:text-white transition">
                        Thêm vào giỏ
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
        <div class="mt-8 flex justify-center gap-2">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php if($categoryId) echo '&category='.$categoryId; ?><?php if($keyword) echo '&search='.urlencode($keyword); ?>" 
                   class="px-4 py-2 border <?php echo $i == $currentPage ? 'border-black bg-black text-white' : 'border-gray-200 text-gray-700 hover:border-black'; ?> text-sm transition">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
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
            alert('Đã thêm sản phẩm vào giỏ hàng!');
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

<?php include '../chat/index.php'; ?>
<?php include '../layout/footer.php'; ?>
