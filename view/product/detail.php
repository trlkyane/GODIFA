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

<main class="container mx-auto px-4 py-8">
    <nav class="mb-6 text-gray-600 text-sm">
        <a href="../index.php" class="hover:text-blue-600">Trang chủ</a>
        <span class="mx-2">/</span>
        <a href="product.php" class="hover:text-blue-600">Sản phẩm</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900"><?php echo htmlspecialchars($product['productName']); ?></span>
    </nav>

    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="grid md:grid-cols-2 gap-8 p-8">
            <div class="space-y-4">
                <div class="relative overflow-hidden rounded-xl bg-gray-100">
                    <img src="/GODIFA/image/<?php echo $product['image']; ?>" 
                            alt="<?php echo htmlspecialchars($product['productName']); ?>" 
                            class="w-full h-[500px] object-contain">
                    <?php if ($product['stockQuantity'] < 10 && $product['stockQuantity'] > 0): ?>
                        <span class="absolute top-4 left-4 bg-red-500 text-white text-sm px-3 py-1 rounded-full font-semibold">
                            <i class="fas fa-exclamation-circle mr-1"></i>Sắp hết hàng
                        </span>
                    <?php elseif ($product['stockQuantity'] == 0): ?>
                        <span class="absolute top-4 left-4 bg-gray-800 text-white text-sm px-3 py-1 rounded-full font-semibold">
                            <i class="fas fa-times-circle mr-1"></i>Hết hàng
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-3">
                        <?php echo htmlspecialchars($product['productName']); ?>
                    </h1>
                    <div class="flex items-center gap-4 mb-4">
                        <?php 
                        $ratingValue = round($avgRating['avgRating'] ?? 0);
                        $totalReviews = $avgRating['totalReviews'] ?? 0;
                        ?>
                        <div class="flex items-center text-yellow-400">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i > $ratingValue ? 'text-gray-300' : ''; ?>"></i>
                            <?php endfor; ?>
                            <span class="text-gray-600 ml-2 text-sm">(<?php echo number_format($avgRating['avgRating'] ?? 0, 1); ?>/5)</span>
                        </div>
                        <span class="text-gray-400">|</span>
                        <span class="text-gray-600 text-sm">
                            <i class="fas fa-comment-alt mr-1"></i>Đã bán: <strong class="text-gray-900"><?php echo $totalReviews; ?></strong>
                        </span>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-xl p-6">
                    <span class="text-4xl font-bold text-blue-600">
                        <?php echo number_format($product['price'], 0, ',', '.'); ?>đ
                    </span>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-gray-700">
                        <i class="fas fa-barcode w-6 text-gray-500"></i>
                        <span class="font-medium">Mã SKU:</span>
                        <span class="text-gray-600"><?php echo htmlspecialchars($product['SKU_MRK']); ?></span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-700">
                        <i class="fas fa-tag w-6 text-gray-500"></i>
                        <span class="font-medium">Danh mục:</span>
                        <span class="text-blue-600 font-medium"><?php echo htmlspecialchars($product['categoryName']); ?></span>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6 space-y-4">
                    <div class="flex items-center gap-4">
                        <label class="font-medium text-gray-700">Số lượng:</label>
                        <div class="flex items-center border border-gray-300 rounded-lg">
                            <button onclick="decreaseQty()" class="px-4 py-2 hover:bg-gray-100">
                                <i class="fas fa-minus text-gray-600"></i>
                            </button>
                            <input type="number" id="qty" value="1" min="1" max="<?php echo $product['stockQuantity']; ?>" 
                                    class="w-20 text-center border-x py-2 focus:outline-none">
                            <button onclick="increaseQty()" class="px-4 py-2 hover:bg-gray-100">
                                <i class="fas fa-plus text-gray-600"></i>
                            </button>
                        </div>
                        <span class="text-gray-500 text-sm">(Tối đa: <?php echo $product['stockQuantity']; ?>)</span>
                    </div>

                    <?php if ($product['stockQuantity'] > 0): ?>
                        <div class="flex gap-3">
                            <button onclick="addCart(<?php echo $product['productID']; ?>)" 
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-4 px-6 rounded-xl">
                                <i class="fas fa-cart-plus mr-2"></i>Thêm vào giỏ hàng
                            </button>
                            <button onclick="buyNow(<?php echo $product['productID']; ?>)" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-xl">
                                <i class="fas fa-bolt mr-2"></i>Mua ngay
                            </button>
                        </div>
                    <?php else: ?>
                        <button disabled class="w-full bg-gray-400 text-white font-semibold py-4 px-6 rounded-xl cursor-not-allowed">
                            <i class="fas fa-times-circle mr-2"></i>Hết hàng
                        </button>
                    <?php endif; ?>
                </div>

                <div class="bg-gray-50 rounded-xl p-6 space-y-3">
                    <h3 class="font-semibold text-gray-900 mb-3">
                        <i class="fas fa-shield-alt text-blue-600 mr-2"></i>Chính sách bán hàng
                    </h3>
                    <div class="flex items-center gap-3 text-sm text-gray-700">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Miễn phí giao hàng cho đơn từ 500.000đ</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-700">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Đổi trả trong vòng 7 ngày</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-700">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Hỗ trợ khách hàng 24/7</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-700">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Cam kết hàng chính hãng 100%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Mô tả sản phẩm
            </h2>
            <div class="prose max-w-none text-gray-700 leading-relaxed">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
        </div>
        
        <div class="border-t p-8 bg-white">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-star text-yellow-500 mr-2"></i>Đánh giá sản phẩm (<?php echo $totalReviews; ?>)
            </h2>

            <?php if ($totalReviews > 0): ?>
                <div class="flex items-center mb-6 p-4 border rounded-xl bg-yellow-50">
                    <div class="flex-shrink-0 text-center mr-6">
                        <p class="text-5xl font-bold text-yellow-600">
                            <?php echo number_format($avgRating['avgRating'] ?? 0, 1); ?>
                        </p>
                        <div class="flex justify-center text-yellow-400 mt-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i > $ratingValue ? 'text-gray-300' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="text-gray-600 text-lg"><?php echo $totalReviews; ?> lượt đánh giá</p>
                </div>
                
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b pb-4">
                            <div class="flex items-center mb-2">
                                <span class="font-semibold text-gray-700 mr-3">
                                    <?php echo htmlspecialchars($review['customerName']); ?>
                                </span>
                                
                                <div class="flex text-sm text-yellow-400">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i > $review['rating'] ? 'text-gray-300' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-gray-800 mb-2">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </p>
                            <span class="text-xs text-gray-500">
                                Ngày đánh giá: <?php echo date('d/m/Y', strtotime($review['dateReview'])); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 border rounded-xl bg-gray-50">
                    <p class="text-gray-600 font-medium">Chưa có đánh giá nào cho sản phẩm này.</p>
                    <p class="text-sm text-gray-500 mt-1">Hãy là người đầu tiên đánh giá!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="border-t p-8 bg-gray-50">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-boxes text-blue-600 mr-2"></i>Sản phẩm liên quan
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php
                if (isset($relatedProducts) && count($relatedProducts) > 0):
                    foreach ($relatedProducts as $r):
                        if ($r['productID'] == $product['productID']) continue;
                ?>
                    <a href="/GODIFA/controller/cProduct.php?action=detail&id=<?php echo $r['productID']; ?>" 
                       class="bg-white rounded-xl shadow hover:shadow-lg transition overflow-hidden group">
                        <img src="/GODIFA/image/<?php echo $r['image']; ?>" 
                             alt="<?php echo htmlspecialchars($r['productName']); ?>" 
                             class="w-full h-48 object-cover group-hover:scale-105 transition duration-300">
                        <div class="p-4">
                            <h3 class="font-semibold text-sm mb-2 line-clamp-2 h-10">
                                <?php echo htmlspecialchars($r['productName']); ?>
                            </h3>
                            <p class="text-blue-600 font-bold">
                                <?php echo number_format($r['price'], 0, ',', '.'); ?>đ
                            </p>
                        </div>
                    </a>
                <?php 
                    endforeach;
                else: 
                ?>
                    <p class="col-span-4 text-gray-500 text-center py-8">Không có sản phẩm liên quan</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
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
    .then(d => { if(d.success) window.location.href = '../cart/view.php'; else alert('❌ Lỗi!'); })
    .catch(() => alert('❌ Lỗi kết nối!'));
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>