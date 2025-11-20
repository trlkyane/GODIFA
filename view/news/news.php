<?php
require_once __DIR__ . '/../../controller/admin/cBlog.php';
$blogController = new cBlog();
$blogs = $blogController->getAllBlogs();

$pageTitle = "Tin tức";
include_once __DIR__ . '/../layout/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
  
  <!-- Page Header -->
  <div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2 brand-font">Tin tức & Bài viết</h1>
    <p class="text-sm text-gray-500">Cập nhật thông tin mới nhất về làm đẹp và chăm sóc sức khỏe</p>
  </div>

  <?php if (!empty($blogs)): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <?php foreach ($blogs as $blog): ?>
        <?php 
          $imagePath = (!empty($blog['image']) && file_exists(__DIR__ . '/../../image/' . $blog['image']))
            ? 'image/' . $blog['image']
            : 'image/blog.jpg';
        ?>
        <article class="group bg-white rounded-sm overflow-hidden shadow-sm hover:shadow-md transition">
          <a href="detail.php?id=<?php echo $blog['blogID']; ?>">
            <div class="aspect-video overflow-hidden bg-gray-100">
              <img src="/GODIFA/<?php echo $imagePath; ?>" 
                   alt="<?php echo htmlspecialchars($blog['title']); ?>"
                   class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
            </div>
            <div class="p-3">
              <p class="text-xs text-gray-500 mb-1">
                <?php echo date('d/m/Y', strtotime($blog['date'] ?? 'now')); ?>
              </p>
              <h3 class="text-sm font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-gray-600 transition min-h-[2.5rem]">
                <?php echo htmlspecialchars($blog['title']); ?>
              </h3>
              <p class="text-xs text-gray-500 line-clamp-2">
                <?php echo htmlspecialchars(strip_tags(substr($blog['content'], 0, 100))); ?>...
              </p>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center py-20">
      <p class="text-gray-500">Hiện chưa có bài viết nào.</p>
    </div>
  <?php endif; ?>
  
</main>
<?php include '../chat/index.php'; ?>
<?php include_once __DIR__ . '/../layout/footer.php'; ?>