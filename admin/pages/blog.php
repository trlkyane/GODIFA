<?php
/**
 * Quản lý Bài viết (Blog)
 * File: admin/pages/blogs.php
 */

require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Check permission - Có thể xem hoặc quản lý bài viết
if (!hasPermission('view_blog') && !hasPermission('manage_blog')) {
    die('<div class="p-8"><div class="bg-red-100 text-red-700 p-4 rounded">Bạn không có quyền truy cập trang này!</div></div>');
}

// Load Controller (Admin version)
require_once __DIR__ . '/../../controller/admin/cBlog.php';
$blogController = new cBlog();

$success = '';
$error = '';

// Xử lý THÊM bài viết
if (isset($_POST['add_blog']) && hasPermission('manage_blog')) {
    $data = [
        'title' => trim($_POST['title']),
        'content' => trim($_POST['content'])
    ];
    
    $result = $blogController->addBlog($data);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý SỬA bài viết
if (isset($_POST['edit_blog'])) {
    $blogID = intval($_POST['blogID']);
    $data = [
        'title' => trim($_POST['title']),
        'content' => trim($_POST['content'])
    ];
    
    $result = $blogController->updateBlog($blogID, $data);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý TOGGLE STATUS (Khóa/Mở khóa)
if (isset($_GET['toggle']) && hasPermission('manage_blog')) {
    $blogID = intval($_GET['toggle']);
    $result = $blogController->toggleStatus($blogID);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Xử lý XÓA bài viết (chỉ Owner và Admin)
if (isset($_GET['delete']) && hasPermission('delete_blog')) {
    $blogID = intval($_GET['delete']);
    $result = $blogController->deleteBlog($blogID);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Xử lý TÌM KIẾM
$searchKeyword = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchKeyword) {
    $blogs = $blogController->searchBlogs($searchKeyword);
} else {
    $blogs = $blogController->getAllBlogs();
}

$totalBlogs = $blogController->countBlogs();

$pageTitle = 'Quản lý Bài viết';
include __DIR__ . '/../includes/header.php';
?>

<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto ml-64">
        <!-- Header -->
        <div class="bg-white shadow sticky top-0 z-10">
            <div class="px-4 md:px-6 py-4 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">
                        <i class="fas fa-newspaper text-green-500 mr-2"></i>
                        Quản lý Bài viết
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng số: <strong><?php echo $totalBlogs; ?></strong> bài viết
                    </p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Search -->
                    <form method="GET" class="flex items-center">
                        <input type="hidden" name="page" value="blogs">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchKeyword); ?>"
                                   placeholder="Tìm tiêu đề, nội dung..." 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 w-64">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <button type="submit" class="ml-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Tìm
                        </button>
                    </form>
                    
                    <?php if (hasPermission('manage_blog')): ?>
                    <button onclick="openAddModal()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Thêm bài viết
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-4 md:p-6">
            <!-- Alerts -->
            <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?php echo $success; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <div><?php echo $error; ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Blogs Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php if (empty($blogs)): ?>
                <div class="col-span-full">
                    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                        <i class="fas fa-newspaper text-4xl mb-2 text-gray-300"></i>
                        <p>Không tìm thấy bài viết nào</p>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($blogs as $blog): ?>
                    <?php
                    // Safe access
                    $blogID = $blog['blogID'] ?? 0;
                    $title = $blog['title'] ?? 'N/A';
                    $content = $blog['content'] ?? '';
                    $date = $blog['date'] ?? '';
                    $authorName = $blog['authorName'] ?? 'N/A';
                    $status = $blog['status'] ?? 1;
                    
                    // Excerpt (100 ký tự đầu)
                    $excerpt = mb_substr(strip_tags($content), 0, 100) . '...';
                    
                    // Màu header theo trạng thái
                    $headerColor = $status == 1 
                        ? 'bg-gradient-to-r from-green-500 to-teal-600' 
                        : 'bg-gradient-to-r from-gray-400 to-gray-500';
                    ?>
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden <?php echo $status == 0 ? 'opacity-75' : ''; ?>">
                        <!-- Header với màu động theo trạng thái -->
                        <div class="<?php echo $headerColor; ?> p-4">
                            <h3 class="text-white font-bold text-lg line-clamp-2">
                                <?php echo htmlspecialchars($title); ?>
                                <?php if ($status == 0): ?>
                                <i class="fas fa-lock ml-2 text-sm" title="Đã khóa"></i>
                                <?php endif; ?>
                            </h3>
                        </div>
                        
                        <!-- Body -->
                        <div class="p-4">
                            <p class="text-gray-600 text-sm mb-3 line-clamp-3">
                                <?php echo htmlspecialchars($excerpt); ?>
                            </p>
                            
                            <!-- Meta -->
                            <div class="flex items-center text-xs text-gray-500 mb-4">
                                <i class="fas fa-user mr-1"></i>
                                <span class="mr-3"><?php echo htmlspecialchars($authorName); ?></span>
                                <i class="fas fa-calendar mr-1"></i>
                                <span><?php echo date('d/m/Y H:i', strtotime($date)); ?></span>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex justify-end space-x-2 pt-3 border-t">
                                <button onclick='viewBlog(<?php echo json_encode($blog, JSON_HEX_APOS); ?>)' 
                                        class="px-3 py-1 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 text-sm">
                                    <i class="fas fa-eye mr-1"></i> Xem
                                </button>
                                <?php if (hasPermission('manage_blog')): ?>
                                <button onclick='openEditModal(<?php echo json_encode($blog, JSON_HEX_APOS); ?>)' 
                                        class="px-3 py-1 bg-green-100 text-green-600 rounded hover:bg-green-200 text-sm">
                                    <i class="fas fa-edit mr-1"></i> Sửa
                                </button>
                                
                                <!-- Khóa/Mở khóa -->
                                <?php if ($status == 1): ?>
                                <button onclick="toggleBlogStatus(<?php echo $blogID; ?>, '<?php echo htmlspecialchars($title); ?>')" 
                                        class="px-3 py-1 bg-orange-100 text-orange-600 rounded hover:bg-orange-200 text-sm" title="Khóa bài viết">
                                    <i class="fas fa-lock mr-1"></i> Khóa
                                </button>
                                <?php else: ?>
                                <button onclick="toggleBlogStatus(<?php echo $blogID; ?>, '<?php echo htmlspecialchars($title); ?>')" 
                                        class="px-3 py-1 bg-green-100 text-green-600 rounded hover:bg-green-200 text-sm" title="Mở khóa bài viết">
                                    <i class="fas fa-unlock mr-1"></i> Mở khóa
                                </button>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (hasPermission('delete_blog')): ?>
                                <button onclick="deleteBlog(<?php echo $blogID; ?>, '<?php echo htmlspecialchars($title); ?>')" 
                                        class="px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200 text-sm">
                                    <i class="fas fa-trash mr-1"></i> Xóa
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Thêm bài viết -->
<div id="addModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-3 border-b border-green-200">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-plus text-green-500 mr-2"></i>
                Thêm bài viết mới
            </h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                    Tiêu đề <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                       placeholder="Nhập tiêu đề bài viết">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="content">
                    Nội dung <span class="text-red-500">*</span>
                </label>
                <textarea name="content" id="content" required rows="10"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                          placeholder="Nhập nội dung bài viết..."></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
                <button type="submit" name="add_blog"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-1"></i> Thêm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Sửa bài viết -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-3 border-b border-blue-200">
            <h3 class="text-xl font-bold text-gray-800">
                <i class="fas fa-edit text-blue-500 mr-2"></i>
                Sửa bài viết
            </h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4">
            <input type="hidden" name="blogID" id="edit_blogID">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_title">
                    Tiêu đề <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="edit_title" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="edit_content">
                    Nội dung <span class="text-red-500">*</span>
                </label>
                <textarea name="content" id="edit_content" required rows="10"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
                <button type="submit" name="edit_blog"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-check mr-1"></i> Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Xem chi tiết -->
<div id="viewModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-lg bg-white mb-10">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-bold text-gray-800" id="view_title"></h3>
            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="mt-4">
            <div class="flex items-center text-sm text-gray-500 mb-4">
                <i class="fas fa-user mr-2"></i>
                <span id="view_author" class="mr-4"></span>
                <i class="fas fa-calendar mr-2"></i>
                <span id="view_date"></span>
            </div>
            
            <div class="prose max-w-none">
                <div id="view_content" class="text-gray-700 whitespace-pre-wrap"></div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Add modal
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

// Edit modal
function openEditModal(blog) {
    document.getElementById('edit_blogID').value = blog.blogID;
    document.getElementById('edit_title').value = blog.title;
    document.getElementById('edit_content').value = blog.content;
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Toggle status
function toggleBlogStatus(id, title) {
    if (confirm(`Bạn có chắc muốn thay đổi trạng thái bài viết "${title}"?`)) {
        window.location.href = `?page=blog&toggle=${id}`;
    }
}

// View modal
function viewBlog(blog) {
    document.getElementById('view_title').textContent = blog.title;
    document.getElementById('view_author').textContent = blog.authorName || 'N/A';
    document.getElementById('view_date').textContent = new Date(blog.date).toLocaleString('vi-VN');
    document.getElementById('view_content').textContent = blog.content;
    
    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

// Delete blog
function deleteBlog(id, title) {
    if (confirm(`Bạn có chắc muốn XÓA bài viết "${title}"?\n\nCảnh báo: Hành động này không thể hoàn tác!`)) {
        window.location.href = `?page=blog&delete=${id}`;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const viewModal = document.getElementById('viewModal');
    
    if (event.target == addModal) closeAddModal();
    if (event.target == editModal) closeEditModal();
    if (event.target == viewModal) closeViewModal();
}
</script>

<style>
/* Card line-clamp */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Modal animations */
#addModal, #editModal, #viewModal {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
