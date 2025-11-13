<?php
// Middleware: Chỉ cho phép khách hàng truy cập trang này
require_once 'middleware/customer_only.php';

require_once 'model/mProduct.php';
require_once 'model/mCategory.php';
require_once 'controller/admin/cBlog.php';

$pageTitle = "Trang chủ";
include 'view/layout/header.php';

// Lấy sản phẩm nổi bật
$productModel = new Product();
$featuredProducts = $productModel->getAllProducts(8);
// Lấy danh mục (chỉ hiển thị danh mục đang hoạt động)
$categoryModel = new Category();
$categories = $categoryModel->getActiveCategories();
// Lấy 3 bài viết mới nhất
$blogController = new cBlog();
$recentBlogs = $blogController->getRecentBlogs(3);
?>

<!-- Banner chính -->
<section class="relative bg-cover bg-center h-[500px] md:h-[600px]" style="background-image: url('images/bannerr.jpg');">
  <div class="absolute inset-0 bg-gradient-to-r from-blue-900/70 to-purple-900/70 flex items-center justify-center">
    <div class="text-center text-white px-4">
      <h1 class="text-3xl md:text-5xl font-extrabold mb-4 animate-fade-in">
        <i class="fas fa-shopping-bag mr-3"></i>Chào mừng đến với <span class="text-blue-300">GODIFA</span>
      </h1>
      <p class="text-lg md:text-xl mb-6 max-w-2xl mx-auto">
        Mỹ phẩm & Thực phẩm chức năng chính hãng từ Nhật Bản
      </p>
      <p class="text-md mb-8 text-blue-200">
        <i class="fas fa-shield-alt mr-2"></i>Chính hãng 100% • Giá tốt nhất • Giao hàng toàn quốc
      </p>
      <a href="view/product/list.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-xl shadow-lg hover:shadow-xl transition transform hover:scale-105">
        <i class="fas fa-shopping-cart mr-2"></i>Khám phá ngay
      </a>
    </div>
  </div>
</section>

<!-- Danh mục sản phẩm -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-gray-800 mb-4">
        <i class="fas fa-th-large mr-3 text-blue-600"></i>Danh mục sản phẩm
      </h2>
      <p class="text-gray-600">Khám phá các danh mục sản phẩm đa dạng</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6">
      <?php foreach ($categories as $category): ?>
        <a href="view/product/list.php?category=<?php echo $category['categoryID']; ?>" 
           class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
          <div class="text-4xl mb-3">
            <?php 
              $icons = ['💊', '💄', '✨', '👶', '🏠'];
              echo $icons[($category['categoryID'] - 1) % 5]; 
            ?>
          </div>
          <h3 class="font-semibold text-gray-800"><?php echo $category['categoryName']; ?></h3>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Sản phẩm nổi bật -->
<section class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-gray-800 mb-4">
        <i class="fas fa-star mr-3 text-yellow-500"></i>Sản phẩm nổi bật
      </h2>
      <p class="text-gray-600">Những sản phẩm được khách hàng yêu thích nhất</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <?php foreach ($featuredProducts as $product): ?>
        <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition overflow-hidden group">
          <div class="relative overflow-hidden">
            <img src="image/<?php echo $product['image']; ?>" 
                 alt="<?php echo $product['productName']; ?>" 
                 class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-300">
            <?php if ($product['stockQuantity'] < 10): ?>
              <span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                Sắp hết
              </span>
            <?php endif; ?>
          </div>
          <div class="p-4">
            <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2 h-12">
              <?php echo $product['productName']; ?>
            </h3>
            <p class="text-blue-600 font-bold text-lg mb-3">
              <?php echo number_format($product['price'], 0, ',', '.'); ?>đ
            </p>
            <div class="flex gap-2">
              <a href="controller/cProduct.php?action=detail&id=<?php echo $product['productID']; ?>" 
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
    
    <div class="text-center mt-10">
      <a href="view/product/list.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-xl shadow-lg transition">
        <i class="fas fa-arrow-right mr-2"></i>Xem tất cả sản phẩm
      </a>
    </div>
  </div>
</section>
<!-- News -->
<!-- Tin tức & Bài viết (DYNAMIC) -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-gray-800 mb-4">
        <i class="fas fa-newspaper mr-3 text-blue-600"></i>Tin tức & Bài viết
      </h2>
      <p class="text-gray-600">Cập nhật những thông tin, mẹo chăm sóc sức khỏe & nuôi dạy bé từ Godifa</p>
    </div>

    <?php if (!empty($recentBlogs)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php foreach ($recentBlogs as $blog): ?>
        <?php 
          // Kiểm tra ảnh — nếu trống thì dùng ảnh mặc định
          $imagePath = (!empty($blog['image']) && file_exists(__DIR__ . '/image/' . $blog['image']))
            ? 'image/' . $blog['image']
            : 'image/blog.jpg';
        ?>
        <article class="bg-white rounded-xl shadow-md hover:shadow-xl transition overflow-hidden group">
          <div class="overflow-hidden">
            <img src="<?php echo $imagePath; ?>" 
                 alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                 class="w-full h-56 object-cover group-hover:scale-110 transition-transform duration-300"
                 onerror="this.onerror=null; this.src='image/blog.jpg';">
          </div>
          <div class="p-5">
            <div class="text-sm text-gray-500 mb-2 flex items-center gap-2">
                <i class="far fa-calendar-alt"></i>
                <span><?php echo date('d/m/Y', strtotime($blog['date'] ?? 'now')); ?></span>
            </div>
            <h3 class="font-semibold text-xl text-gray-800 mb-2 group-hover:text-blue-600 transition line-clamp-2 h-[56px]">
              <?php echo htmlspecialchars($blog['title']); ?>
            </h3>
            <p class="text-gray-600 text-sm mb-4 line-clamp-3 h-16">
              <!-- Hiển thị 150 ký tự đầu tiên của nội dung và loại bỏ tag HTML -->
              <?php echo htmlspecialchars(strip_tags(substr($blog['content'], 0, 150))); ?>...
            </p>
            <a href="view/news/detail.php?id=<?php echo $blog['blogID']; ?>" class="text-blue-600 font-semibold hover:underline">
              Đọc thêm →
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="text-center text-gray-600">Hiện chưa có bài viết nào được đăng.</p>
    <?php endif; ?>

    <div class="text-center mt-10">
      <a href="view/news/news.php" 
         class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-xl shadow-lg transition">
        <i class="fas fa-arrow-right mr-2"></i>Xem tất cả bài viết
      </a>
    </div>
  </div>
</section>

<!-- Ưu điểm -->
<section class="py-16 bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
  <div class="max-w-7xl mx-auto px-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
      <div>
        <i class="fas fa-shield-alt text-5xl mb-4"></i>
        <h3 class="font-bold text-xl mb-2">Chính hãng 100%</h3>
        <p class="text-blue-100">Nhập khẩu trực tiếp từ Nhật Bản</p>
      </div>
      <div>
        <i class="fas fa-shipping-fast text-5xl mb-4"></i>
        <h3 class="font-bold text-xl mb-2">Giao hàng nhanh</h3>
        <p class="text-blue-100">Giao hàng toàn quốc trong 2-3 ngày</p>
      </div>
      <div>
        <i class="fas fa-undo-alt text-5xl mb-4"></i>
        <h3 class="font-bold text-xl mb-2">Đổi trả dễ dàng</h3>
        <p class="text-blue-100">Hỗ trợ đổi trả trong 7 ngày</p>
      </div>
      <div>
        <i class="fas fa-headset text-5xl mb-4"></i>
        <h3 class="font-bold text-xl mb-2">Hỗ trợ 24/7</h3>
        <p class="text-blue-100">Tư vấn nhiệt tình, chuyên nghiệp</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-16 bg-white text-center">
  <div class="max-w-4xl mx-auto px-4">
    <h2 class="text-3xl font-bold text-gray-800 mb-4">
      <i class="fas fa-gift mr-3 text-red-500"></i>Nhận ưu đãi ngay hôm nay!
    </h2>
    <p class="text-gray-600 mb-8">
      Đăng ký nhận tin để không bỏ lỡ các chương trình khuyến mãi hấp dẫn
    </p>
    <div class="flex flex-col md:flex-row gap-4 justify-center items-center">
      <input type="email" placeholder="Nhập email của bạn" 
             class="px-6 py-3 rounded-lg border-2 border-gray-300 focus:border-blue-600 outline-none w-full md:w-96">
      <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-3 rounded-lg shadow hover:shadow-lg transition w-full md:w-auto">
        <i class="fas fa-paper-plane mr-2"></i>Đăng ký
      </button>
    </div>
  </div>
</section>

<script>
function addToCart(productId) {
    if (!confirm('Bạn có muốn thêm sản phẩm này vào giỏ hàng?')) {
        return;
    }
    
    fetch('controller/cCart.php?action=add', {
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
<?php include 'view/chat/index.php'; ?>
<?php include 'view/layout/footer.php'; ?>


