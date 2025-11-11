<?php
require_once __DIR__ . '/../../controller/admin/cBlog.php';
$blogController = new cBlog();
$blogs = $blogController->getAllBlogs();

$pageTitle = "Tất cả bài viết - Godifa";
include_once __DIR__ . '/../layout/header.php';
?>

<section class="bg-gray-50 py-16">
  <div class="max-w-7xl mx-auto px-4">
    <div class="text-center mb-12">
      <h1 class="text-4xl font-bold text-gray-800 mb-4">
        <i class="fas fa-newspaper text-blue-600 mr-2"></i>Tất cả bài viết
      </h1>
      <p class="text-gray-600 max-w-2xl mx-auto">
        Khám phá các bài viết mới nhất về sức khỏe, mẹ & bé, và các sản phẩm gia dụng từ Godifa.
      </p>
    </div>

    <?php if (!empty($blogs)): ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($blogs as $blog): ?>
          <?php 
            // Kiểm tra ảnh — nếu trống thì dùng ảnh mặc định
            $imagePath = (!empty($blog['image']) && file_exists(__DIR__ . '/../../image/' . $blog['image']))
              ? 'image/' . $blog['image']
              : 'image/blog.jpg';
          ?>
          <article class="bg-white rounded-xl shadow-md hover:shadow-xl transition overflow-hidden group">
            <div class="overflow-hidden">
              <img src="/GODIFA/<?php echo $imagePath; ?>" 
                   alt="<?php echo htmlspecialchars($blog['title']); ?>"
                   class="w-full h-56 object-cover group-hover:scale-110 transition-transform duration-300">
            </div>
            <div class="p-5">
              <div class="text-sm text-gray-500 mb-2 flex items-center gap-2">
                <i class="far fa-calendar-alt"></i>
                <!-- Đã sửa: Sử dụng cột 'date' từ Model -->
                <span><?php echo date('d/m/Y', strtotime($blog['date'] ?? 'now')); ?></span>
              </div>
              <h3 class="font-semibold text-xl text-gray-800 mb-2 group-hover:text-blue-600 transition line-clamp-2">
                <?php echo htmlspecialchars($blog['title']); ?>
              </h3>
              <p class="text-gray-600 text-sm mb-4 line-clamp-3 h-16">
                <?php echo htmlspecialchars(strip_tags(substr($blog['content'], 0, 150))); ?>...
              </p>
              <!-- Đã sửa: Sử dụng blogID cho liên kết chi tiết -->
              <a href="detail.php?id=<?php echo $blog['blogID']; ?>" 
                 class="text-blue-600 font-semibold hover:underline">
                Đọc thêm →
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-center text-gray-600">Hiện chưa có bài viết nào.</p>
    <?php endif; ?>
  </div>
</section>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>