<?php
// Middleware: Chỉ cho phép khách hàng truy cập
require_once __DIR__ . '/../../middleware/customer_only.php';

// Session đã được start ở controller
// Dữ liệu $product, $reviews, $avgRating, $relatedProducts đã được truyền từ controller

// Nếu truy cập trực tiếp (không qua controller), redirect về trang sản phẩm
if (!isset($product)) {
    header("Location: list.php");
    exit();
}

include __DIR__ . '/../layout/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Breadcrumb -->
    <nav class="mb-6 text-xs text-gray-500">
        <a href="/GODIFA/index.php" class="hover:text-black transition">Trang chủ</a>
        <span class="mx-2">/</span>
        <a href="/GODIFA/view/product/list.php" class="hover:text-black transition">Sản phẩm</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900"><?php echo htmlspecialchars($product['productName']); ?></span>
    </nav>

    <!-- Product Detail -->
    <div class="grid md:grid-cols-2 gap-8 lg:gap-12 mb-12">
        <!-- Product Image -->
        <div class="relative">
            <div class="aspect-[4/5] w-full overflow-hidden bg-gray-50 rounded-sm">
                <img src="/GODIFA/image/<?php echo $product['image']; ?>" 
                     alt="<?php echo htmlspecialchars($product['productName']); ?>" 
                     class="w-full h-full object-cover">
                
                <?php if ($product['stockQuantity'] < 10 && $product['stockQuantity'] > 0): ?>
                    <span class="absolute top-4 left-4 bg-red-500 text-white text-xs px-3 py-1 font-medium">
                        Chỉ còn <?php echo $product['stockQuantity']; ?> sản phẩm
                    </span>
                <?php elseif ($product['stockQuantity'] == 0): ?>
                    <span class="absolute top-4 left-4 bg-black text-white text-xs px-3 py-1 font-medium">
                        Hết hàng
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info -->
        <div class="flex flex-col">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3 brand-font leading-tight">
                <?php echo htmlspecialchars($product['productName']); ?>
            </h1>

            <?php 
            $ratingValue = round($avgRating['avgRating'] ?? 0);
            $totalReviews = $avgRating['totalReviews'] ?? 0;
            ?>
            
            <?php if ($totalReviews > 0): ?>
            <div class="flex items-center gap-2 mb-4 pb-4 border-b border-gray-100">
                <div class="flex text-yellow-400">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg class="w-3.5 h-3.5 <?php echo $i > $ratingValue ? 'text-gray-200' : ''; ?> fill-current" viewBox="0 0 20 20">
                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                        </svg>
                    <?php endfor; ?>
                </div>
                <span class="text-xs text-gray-500">
                    <?php echo number_format($avgRating['avgRating'] ?? 0, 1); ?>/5 
                    <span class="mx-1">·</span>
                    <?php echo $totalReviews; ?> đánh giá
                </span>
            </div>
            <?php else: ?>
            <div class="mb-4 pb-4 border-b border-gray-100"></div>
            <?php endif; ?>

            <div class="mb-6">
                <?php if (!empty($product['promotional_price']) && $product['promotional_price'] > 0): ?>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-2xl font-bold text-red-600">
                            <?php echo number_format($product['promotional_price'], 0, ',', '.'); ?>₫
                        </span>
                        <span class="text-base font-medium text-gray-400 line-through">
                            <?php echo number_format($product['price'], 0, ',', '.'); ?>₫
                        </span>
                        <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs font-semibold rounded">
                            -<?php echo round((($product['price'] - $product['promotional_price']) / $product['price']) * 100); ?>%
                        </span>
                    </div>
                <?php else: ?>
                    <span class="text-2xl font-bold text-gray-900">
                        <?php echo number_format($product['price'], 0, ',', '.'); ?>₫
                    </span>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-6 text-xs bg-gray-50 p-4 rounded-sm">
                <div>
                    <p class="text-gray-500 mb-1">SKU</p>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($product['SKU_MRK']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 mb-1">Danh mục</p>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($product['categoryName']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 mb-1">Tình trạng</p>
                    <p class="text-gray-900 font-medium">
                        <?php echo $product['stockQuantity'] > 0 ? 'Còn hàng' : 'Hết hàng'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 mb-1">Kho</p>
                    <p class="text-gray-900 font-medium"><?php echo $product['stockQuantity']; ?> sản phẩm</p>
                </div>
            </div>

            <?php if ($product['stockQuantity'] > 0): ?>
            <div class="mb-6">
                <label class="text-xs font-medium text-gray-700 mb-2 block">Số lượng</label>
                <div class="flex items-center gap-3">
                    <div class="flex items-center border border-gray-300 rounded-sm">
                        <button onclick="decreaseQty()" class="px-3 py-2 hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </button>
                        <input type="number" id="qty" value="1" min="1" max="<?php echo $product['stockQuantity']; ?>" 
                               class="w-14 text-center py-2 border-x border-gray-300 focus:outline-none text-sm">
                        <button onclick="increaseQty()" class="px-3 py-2 hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-2 mb-6">
                <button onclick="addCart(<?php echo $product['productID']; ?>)" 
                        class="w-full bg-black text-white py-3 text-xs font-bold uppercase tracking-wide hover:bg-gray-800 transition rounded-sm">
                    Thêm vào giỏ hàng
                </button>
                <button onclick="buyNow(<?php echo $product['productID']; ?>)" 
                        class="w-full border border-black text-black py-3 text-xs font-bold uppercase tracking-wide hover:bg-black hover:text-white transition rounded-sm">
                    Mua ngay
                </button>
            </div>
            <?php else: ?>
            <div class="mb-6">
                <button disabled class="w-full bg-gray-200 text-gray-500 py-3 text-xs font-bold uppercase tracking-wide cursor-not-allowed rounded-sm">
                    Hết hàng
                </button>
            </div>
            <?php endif; ?>

            <div class="space-y-2 text-xs text-gray-600 bg-gray-50 p-4 rounded-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Miễn phí ship từ 500K</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Đổi trả 7 ngày</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Hàng chính hãng 100%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs: Description & Reviews -->
    <div class="mb-12">
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex gap-8">
                <button onclick="showTab('description')" id="tab-description" 
                        class="tab-button pb-4 text-sm font-medium border-b-2 border-black text-black">
                    Mô tả sản phẩm
                </button>
                <button onclick="showTab('reviews')" id="tab-reviews" 
                        class="tab-button pb-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-black">
                    Đánh giá <?php if($totalReviews > 0): ?>(<?php echo $totalReviews; ?>)<?php endif; ?>
                </button>
            </nav>
        </div>

        <!-- Description Tab -->
        <div id="content-description" class="tab-content">
            <div class="prose max-w-none text-gray-700 text-sm leading-relaxed">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
        </div>

        <!-- Reviews Tab -->
        <div id="content-reviews" class="tab-content hidden">
            <?php if ($totalReviews > 0): ?>
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
                    <div class="text-center bg-gray-50 p-4 rounded-sm">
                        <p class="text-3xl font-bold text-gray-900 mb-1">
                            <?php echo number_format($avgRating['avgRating'] ?? 0, 1); ?>
                        </p>
                        <div class="flex justify-center text-yellow-400 mb-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-4 h-4 <?php echo $i > $ratingValue ? 'text-gray-200' : ''; ?> fill-current" viewBox="0 0 20 20">
                                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <p class="text-xs text-gray-500"><?php echo $totalReviews; ?> đánh giá</p>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-gray-100 pb-6 last:border-0">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="font-semibold text-gray-900 text-sm">
                                    <?php echo htmlspecialchars($review['customerName']); ?>
                                </span>
                                <span class="text-gray-300">·</span>
                                <div class="flex text-yellow-400">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-3.5 h-3.5 <?php echo $i > $review['rating'] ? 'text-gray-200' : ''; ?> fill-current" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-gray-300">·</span>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($review['dateReview'])); ?>
                                </span>
                            </div>
                            <p class="text-gray-700 text-sm leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 bg-gray-50 rounded-sm">
                    <p class="text-gray-500 text-sm">Chưa có đánh giá nào cho sản phẩm này.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (isset($relatedProducts) && count($relatedProducts) > 0): ?>
    <div class="mb-12">
        <h2 class="text-xl font-bold text-gray-900 mb-6 brand-font">Sản phẩm tương tự</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php 
            $count = 0;
            foreach ($relatedProducts as $r):
                if ($r['productID'] == $product['productID']) continue;
                if ($count >= 4) break;
                $count++;
            ?>
                <div class="group relative">
                    <div class="aspect-[3/4] w-full overflow-hidden rounded-sm bg-gray-100">
                        <img src="/GODIFA/image/<?php echo $r['image']; ?>" 
                             alt="<?php echo htmlspecialchars($r['productName']); ?>" 
                             class="h-full w-full object-cover object-center group-hover:scale-105 transition duration-500 ease-in-out">
                    </div>
                    <div class="mt-3">
                        <h3 class="text-xs text-gray-700 mb-1 line-clamp-2">
                            <a href="/GODIFA/controller/cProduct.php?action=detail&id=<?php echo $r['productID']; ?>">
                                <span aria-hidden="true" class="absolute inset-0"></span>
                                <?php echo htmlspecialchars($r['productName']); ?>
                            </a>
                        </h3>
                        <p class="text-sm font-semibold text-gray-900">
                            <?php echo number_format($r['price'], 0, ',', '.'); ?>₫
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
// Tab switching
function showTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    // Remove active styles from all buttons
    document.querySelectorAll('.tab-button').forEach(el => {
        el.classList.remove('border-black', 'text-black');
        el.classList.add('border-transparent', 'text-gray-500');
    });
    // Show selected content
    document.getElementById('content-' + tabName).classList.remove('hidden');
    // Add active styles to selected button
    const activeBtn = document.getElementById('tab-' + tabName);
    activeBtn.classList.remove('border-transparent', 'text-gray-500');
    activeBtn.classList.add('border-black', 'text-black');
}

function decreaseQty() {
    const input = document.getElementById('qty');
    if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
}
function increaseQty() {
    const input = document.getElementById('qty');
    if (parseInt(input.value) < parseInt(input.max)) input.value = parseInt(input.value) + 1;
}
function addCart(id) {
    const qty = document.getElementById('qty').value;
    fetch('../controller/cCart.php?action=add', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `productId=${id}&quantity=${qty}`
    })
    .then(r => r.json())
    .then(d => { alert(d.success ? '✅ Đã thêm vào giỏ hàng!' : '❌ ' + (d.message || 'Lỗi!')); })
    .catch(() => alert('❌ Lỗi kết nối!'));
}
function buyNow(id) {
    const qty = document.getElementById('qty').value;
    fetch('../controller/cCart.php?action=add', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `productId=${id}&quantity=${qty}`
    })
    .then(r => r.json())
    .then(d => { if(d.success) window.location.href = '../view/cart/checkout.php'; else alert('❌ Lỗi!'); })
    .catch(() => alert('❌ Lỗi kết nối!'));
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>