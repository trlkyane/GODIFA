<?php
// Middleware: Chỉ cho phép khách hàng truy cập trang này
require_once 'middleware/customer_only.php';

require_once 'model/mProduct.php';
require_once 'model/mCategory.php';
require_once 'model/mVoucher.php';
require_once 'controller/admin/cBlog.php';

$pageTitle = "Trang chủ";
include 'view/layout/header.php'; // Đường dẫn header mới sửa
?>

<?php
// Lấy dữ liệu
$productModel = new Product();
$featuredProducts = $productModel->getProductsWithRatings(14); // Lấy 14 sp cho 2 hàng (7x2)
$categoryModel = new Category();
$categories = $categoryModel->getActiveCategories();
$blogController = new cBlog();
$recentBlogs = $blogController->getRecentBlogs(3); // Lấy 3 bài cho cân đối
$voucherModel = new Voucher();
$activeVouchers = $voucherModel->getActiveVouchers();
?>

<!-- Categories Sidebar + Banner Carousel -->
<section class="py-4 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            
            <!-- Categories Sidebar -->
            <div class="lg:col-span-2">
                <h2 class="text-sm font-bold text-gray-900 mb-2 brand-font">Danh mục</h2>
                <div class="space-y-0.5">
                    <?php foreach ($categories as $cat): ?>
                        <a href="view/product/list.php?category=<?php echo $cat['categoryID']; ?>" 
                           class="flex items-center gap-1.5 p-1.5 rounded-sm hover:bg-gray-50 transition group">
                            <div class="w-8 h-8 flex-shrink-0 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden group-hover:ring-2 ring-black transition">
                                <?php if(!empty($cat['image'])): ?>
                                    <img src="image/<?php echo $cat['image']; ?>" 
                                         alt="<?php echo $cat['categoryName']; ?>" 
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-xs font-bold text-gray-400 group-hover:text-gray-600">
                                        <?php echo substr($cat['categoryName'], 0, 1); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs font-medium text-gray-700 group-hover:text-black">
                                <?php echo $cat['categoryName']; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Banner Carousel -->
            <div class="lg:col-span-10">
                <div class="relative overflow-hidden rounded-sm bg-gray-100" style="height: 300px;">
                    <div class="banner-slider h-full">
                        <!-- Slide 1 -->
                        <div class="banner-slide active h-full">
                            <img src="https://images.unsplash.com/photo-1612817288484-6f916006741a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" 
                                 alt="Banner 1" 
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent flex items-center">
                                <div class="max-w-xl px-4 lg:px-6 text-white">
                                    <span class="text-xs tracking-widest uppercase font-semibold">Bộ sưu tập mới</span>
                                    <h1 class="mt-1.5 text-xl lg:text-2xl font-extrabold brand-font">
                                        Tinh hoa mỹ phẩm<br>Nhật Bản
                                    </h1>
                                    <p class="mt-1.5 text-xs lg:text-sm text-gray-200">
                                        Khám phá vẻ đẹp tự nhiên với những sản phẩm chất lượng cao
                                    </p>
                                    <a href="view/product/list.php" 
                                       class="inline-block mt-2 px-4 py-1.5 bg-white text-black font-bold text-xs uppercase tracking-wide hover:bg-gray-100 transition rounded-sm">
                                        Khám phá ngay
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Slide 2 -->
                        <div class="banner-slide h-full">
                            <img src="https://images.unsplash.com/photo-1556228720-195a672e8a03?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" 
                                 alt="Banner 2" 
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent flex items-center">
                                <div class="max-w-xl px-4 lg:px-6 text-white">
                                    <span class="text-xs tracking-widest uppercase font-semibold">Ưu đãi đặc biệt</span>
                                    <h1 class="mt-1.5 text-xl lg:text-2xl font-extrabold brand-font">
                                        Giảm giá lên đến 50%
                                    </h1>
                                    <p class="mt-1.5 text-xs lg:text-sm text-gray-200">
                                        Cho tất cả sản phẩm chăm sóc da
                                    </p>
                                    <a href="view/product/list.php" 
                                       class="inline-block mt-2 px-4 py-1.5 bg-white text-black font-bold text-xs uppercase tracking-wide hover:bg-gray-100 transition rounded-sm">
                                        Mua ngay
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Slide 3 -->
                        <div class="banner-slide h-full">
                            <img src="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80" 
                                 alt="Banner 3" 
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-transparent flex items-center">
                                <div class="max-w-xl px-4 lg:px-6 text-white">
                                    <span class="text-xs tracking-widest uppercase font-semibold">Xu hướng 2025</span>
                                    <h1 class="mt-1.5 text-xl lg:text-2xl font-extrabold brand-font">
                                        Làn da khỏe đẹp<br>tự nhiên
                                    </h1>
                                    <p class="mt-1.5 text-xs lg:text-sm text-gray-200">
                                        Sản phẩm organic từ thiên nhiên
                                    </p>
                                    <a href="view/product/list.php" 
                                       class="inline-block mt-2 px-4 py-1.5 bg-white text-black font-bold text-xs uppercase tracking-wide hover:bg-gray-100 transition rounded-sm">
                                        Xem thêm
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Arrows -->
                    <button onclick="prevSlide()" 
                            class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full flex items-center justify-center transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button onclick="nextSlide()" 
                            class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/80 hover:bg-white rounded-full flex items-center justify-center transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    <!-- Dots -->
                    <div class="absolute bottom-6 left-0 right-0 flex justify-center gap-2">
                        <button onclick="goToSlide(0)" class="banner-dot w-2 h-2 rounded-full bg-white/50 hover:bg-white transition"></button>
                        <button onclick="goToSlide(1)" class="banner-dot w-2 h-2 rounded-full bg-white/50 hover:bg-white transition"></button>
                        <button onclick="goToSlide(2)" class="banner-dot w-2 h-2 rounded-full bg-white/50 hover:bg-white transition"></button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Products Section - More compact -->
<section class="py-4 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-bold text-gray-900 brand-font">Sản phẩm nổi bật</h2>
            <a href="view/product/list.php" class="text-xs font-medium text-gray-500 hover:text-black border-b border-transparent hover:border-black transition pb-1">
                Xem tất cả &rarr;
            </a>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-7 gap-2">
            <?php foreach ($featuredProducts as $product): 
                $imgSrc = !empty($product['image']) ? 'image/' . $product['image'] : 'https://via.placeholder.com/400x500';
                
                // Xác định giá hiển thị (ưu tiên giá khuyến mãi)
                $hasPromotion = !empty($product['promotional_price']) && $product['promotional_price'] > 0;
                $displayPrice = $hasPromotion ? $product['promotional_price'] : $product['price'];
                $price = number_format($displayPrice, 0, ',', '.');
                $originalPrice = number_format($product['price'], 0, ',', '.');
                $discount = $hasPromotion ? round((($product['price'] - $product['promotional_price']) / $product['price']) * 100) : 0;
                
                // Lấy đánh giá và số lượng bán thật từ database
                $rating = floatval($product['avgRating']);
                $soldCount = intval($product['soldCount']);
                $reviewCount = intval($product['reviewCount']);
            ?>
            <div class="group relative bg-white">
                <div class="aspect-square w-full overflow-hidden rounded bg-gray-100 relative">
                    <a href="controller/cProduct.php?action=detail&id=<?php echo $product['productID']; ?>">
                        <img src="<?php echo $imgSrc; ?>" 
                             alt="<?php echo $product['productName']; ?>" 
                             class="h-full w-full object-cover object-center group-hover:scale-110 transition duration-500">
                    </a>
                    
                    <!-- Quick add button on hover -->
                    <button onclick="addToCart(<?php echo $product['productID']; ?>)" 
                            class="absolute bottom-0.5 left-0.5 right-0.5 bg-black/80 text-white py-1 text-xs font-bold uppercase opacity-0 group-hover:opacity-100 transition">
                        Thêm vào giỏ
                    </button>
                    
                    <!-- Sale badge if applicable -->
                    <?php if ($hasPromotion): ?>
                    <span class="absolute top-0.5 left-0.5 bg-red-600 text-white text-xs px-1 py-0.5 font-bold">
                        -<?php echo $discount; ?>%
                    </span>
                    <?php endif; ?>
                </div>

                <div class="mt-1">
                    <h3 class="text-xs text-gray-900 line-clamp-2 min-h-[1.75rem] leading-tight">
                        <a href="controller/cProduct.php?action=detail&id=<?php echo $product['productID']; ?>">
                            <?php echo $product['productName']; ?>
                        </a>
                    </h3>
                    
                    <!-- Rating -->
                    <div class="flex items-center gap-0.5 mt-0.5">
                        <div class="flex text-yellow-400">
                            <?php for($i = 0; $i < 5; $i++): ?>
                                <svg class="w-2 h-2 <?php echo $i < floor($rating) ? 'fill-current' : 'fill-gray-200'; ?>" viewBox="0 0 20 20">
                                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <span class="text-xs text-gray-500 ml-2">
                            Bán <?php echo isset($soldCount) && $soldCount > 0 ? $soldCount : 0; ?>
                        </span>
                    </div>
                    
                    <!-- Price -->
                    <div class="mt-0.5">
                        <?php if ($hasPromotion): ?>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-gray-400 line-through text-xs"><?php echo $originalPrice; ?>₫</span>
                                <span class="text-red-600 font-bold text-xs"><?php echo $price; ?>₫</span>
                            </div>
                        <?php else: ?>
                            <span class="text-red-600 font-bold text-xs"><?php echo $price; ?>₫</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Blog Section - More compact -->
<section class="py-4 bg-gray-50 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-lg font-bold mb-3 brand-font">📰 Tin tức làm đẹp</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <?php foreach ($recentBlogs as $blog): ?>
                <article class="group bg-white rounded overflow-hidden shadow-sm hover:shadow-md transition">
                    <a href="view/news/detail.php?id=<?php echo $blog['blogID']; ?>">
                        <div class="aspect-video overflow-hidden bg-gray-100">
                            <img src="<?php echo !empty($blog['image']) ? 'image/'.$blog['image'] : 'https://via.placeholder.com/600x400'; ?>" 
                                 class="object-cover w-full h-full group-hover:scale-105 transition duration-500">
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 mb-1"><?php echo date('d/m/Y', strtotime($blog['date'] ?? 'now')); ?></p>
                            <h3 class="text-xs font-bold text-gray-900 mb-1 line-clamp-2 group-hover:text-gray-600 transition">
                                <?php echo htmlspecialchars($blog['title']); ?>
                            </h3>
                            <p class="text-xs text-gray-500 line-clamp-2"><?php echo htmlspecialchars(strip_tags(substr($blog['content'], 0, 80))); ?>...</p>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
function addToCart(productId) {
    if (!confirm('Bạn có muốn thêm sản phẩm này vào giỏ hàng?')) return;
    
    fetch('controller/cCart.php?action=add', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `productId=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => { 
        if (data.success) {
            alert('Đã thêm vào giỏ!');
            location.reload();
        } else {
            alert('❌ ' + (data.message || 'Lỗi!'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Không thể thêm vào giỏ hàng!');
    });
}

function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        showToast('Đã sao chép mã: ' + code, 'success');
    }).catch(() => {
        showToast('❌ Không thể sao chép mã!', 'error');
    });
}

function showToast(message, type = 'success') {
    // Tạo toast element
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 px-6 py-3 rounded-sm shadow-lg text-white text-sm font-medium z-50 transform transition-all duration-300 translate-y-0 opacity-100';
    toast.style.backgroundColor = type === 'success' ? '#000000' : '#EF4444';
    toast.textContent = message;
    
    // Thêm vào body
    document.body.appendChild(toast);
    
    // Animation
    setTimeout(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    }, 10);
    
    // Xóa sau 3 giây
    setTimeout(() => {
        toast.style.transform = 'translateY(100px)';
        toast.style.opacity = '0';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Banner Carousel
let currentSlide = 0;
const slides = document.querySelectorAll('.banner-slide');
const dots = document.querySelectorAll('.banner-dot');

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (i === index) {
            slide.classList.add('active');
        }
    });
    
    dots.forEach((dot, i) => {
        if (i === index) {
            dot.classList.remove('bg-white/50');
            dot.classList.add('bg-white');
        } else {
            dot.classList.remove('bg-white');
            dot.classList.add('bg-white/50');
        }
    });
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(currentSlide);
}

function goToSlide(index) {
    currentSlide = index;
    showSlide(currentSlide);
}

// Auto slide every 5 seconds
setInterval(nextSlide, 5000);

// Initialize first slide
showSlide(0);
</script>

<style>
.banner-slide {
    display: none;
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
}

.banner-slide.active {
    display: block;
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.banner-slider {
    position: relative;
}
</style>

<?php include 'view/chat/index.php'; ?>
<?php include 'view/layout/footer.php'; ?>