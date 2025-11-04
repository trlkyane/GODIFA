<?php
require_once __DIR__ . '/../../controller/admin/cBlog.php';
$blogController = new cBlog();

// Lấy ID bài viết từ URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$blog = $blogController->getBlogById($id);

if (!$blog) {
    // Nếu không tìm thấy bài viết, hiển thị thông báo lỗi
    $pageTitle = "Bài viết không tồn tại";
    include_once __DIR__ . '/../layout/header.php';
    echo "<div class='text-center py-20 text-gray-600 text-lg'>Bài viết không tồn tại hoặc đã bị xóa.</div>";
    include_once __DIR__ . '/../layout/footer.php';
    exit;
}

// Lấy 4 bài viết khác để hiển thị 3 bài viết liên quan (đảm bảo loại trừ bài hiện tại)
$relatedBlogs = $blogController->getRecentBlogs(4); 

$pageTitle = htmlspecialchars($blog['title']);
include_once __DIR__ . '/../layout/header.php';
?>

<section class="bg-gray-50 py-12">
  <div class="max-w-4xl mx-auto px-4 bg-white rounded-xl shadow-md p-8">
    <?php 
      // Ảnh bài viết hoặc ảnh mặc định
      $imagePath = (!empty($blog['image']) && file_exists(__DIR__ . '/../../image/' . $blog['image']))
        ? 'image/' . $blog['image']
        : 'image/blog.jpg';
    ?>

    <!-- Ảnh bìa -->
    <div class="mb-6 overflow-hidden rounded-xl">
      <img src="/GODIFA/<?php echo $imagePath; ?>" 
           alt="<?php echo htmlspecialchars($blog['title']); ?>" 
           class="w-full h-[400px] object-cover">
    </div>

    <!-- Tiêu đề và ngày đăng -->
    <h1 class="text-3xl font-bold text-gray-800 mb-3"><?php echo htmlspecialchars($blog['title']); ?></h1>
    <div class="text-gray-500 text-sm mb-6 flex items-center gap-2">
      <i class="far fa-calendar-alt"></i>
      <!-- Đã sửa: Sử dụng cột 'date' từ Model -->
      <span>Đăng ngày <?php echo date('d/m/Y', strtotime($blog['date'] ?? 'now')); ?></span>
    </div>

    <!-- Nội dung bài viết -->
    <div class="prose prose-blue max-w-none text-gray-800 leading-relaxed">
      <!-- Đã sửa: Loại bỏ nl2br() để giữ nguyên định dạng HTML của nội dung -->
      <?php echo $blog['content']; ?>
    </div>
  </div>

  <!-- Bài viết liên quan -->
  <?php if (!empty($relatedBlogs)): ?>
  <div class="max-w-7xl mx-auto px-4 mt-16">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
      Bài viết liên quan
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php $count = 0; ?>
      <?php foreach ($relatedBlogs as $related): ?>
        <?php 
          // Đã sửa: Kiểm tra blogID để loại trừ chính bài hiện tại
          if ($related['blogID'] == $blog['blogID']) continue; 
          if ($count >= 3) break; // Chỉ hiển thị tối đa 3 bài viết
          $count++;
        ?>
        <?php 
          $imagePath = (!empty($related['image']) && file_exists(__DIR__ . '/../../image/' . $related['image']))
            ? 'image/' . $related['image']
            : 'image/blog.jpg';
        ?>
        <article class="bg-white rounded-xl shadow-md hover:shadow-xl transition overflow-hidden group">
          <div class="overflow-hidden">
            <img src="/GODIFA/<?php echo $imagePath; ?>" 
                 alt="<?php echo htmlspecialchars($related['title']); ?>"
                 class="w-full h-48 object-cover group-hover:scale-110 transition-transform duration-300">
          </div>
          <div class="p-5">
            <h3 class="font-semibold text-lg text-gray-800 mb-2 group-hover:text-blue-600 transition line-clamp-2">
              <?php echo htmlspecialchars($related['title']); ?>
            </h3>
            <p class="text-gray-600 text-sm mb-4 line-clamp-3 h-16">
              <?php echo htmlspecialchars(strip_tags(substr($related['content'], 0, 120))); ?>...
            </p>
            <!-- Đã sửa: Sử dụng blogID cho liên kết chi tiết -->
            <a href="detail.php?id=<?php echo $related['blogID']; ?>" 
               class="text-blue-600 font-semibold hover:underline">
              Đọc thêm →
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>