<?php
/**
 * Blog Controller - Admin
 * File: controller/cBlog.php
 */

require_once __DIR__ . '/../../model/mBlog.php';

class cBlog {
    private $blogModel;
    
    public function __construct() {
        $this->blogModel = new Blog();
    }
    
    // Láº¥y táº¥t cáº£ bÃ i viáº¿t
    public function getAllBlogs() {
        return $this->blogModel->getAllBlogs();
    }
    
    // Láº¥y bÃ i viáº¿t theo ID
    public function getBlogById($id) {
        return $this->blogModel->getBlogById($id);
    }
    
    // ThÃªm bÃ i viáº¿t
    public function addBlog($data) {
        // Validate
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = "Vui lòng nhập tiêu đề bài viết!";
        }
        
        if (empty($data['content'])) {
            $errors[] = "Vui lòng nhập nội dung bài viết!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // ThÃªm bÃ i viáº¿t
        $result = $this->blogModel->addBlog(
            $data['title'],
            $data['content'],
            $data['image'] ?? ''
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Thêm bài viết thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi thêm bài viết!']];
    }
    
    // Cáº­p nháº­t bÃ i viáº¿t
    public function updateBlog($id, $data) {
        // Validate
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = "Vui lòng nhập tiêu đề bài viết!";
        }
        
        if (empty($data['content'])) {
            $errors[] = "Vui lòng nhập nội dung bài viết!";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Cáº­p nháº­t bÃ i viáº¿t
        $image = isset($data['image']) ? $data['image'] : null;
        $result = $this->blogModel->updateBlog(
            $id,
            $data['title'],
            $data['content'],
            $image
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Cập nhật bài viết thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi cập nhật bài viết!']];
    }
    
    // XÃ³a bÃ i viáº¿t
    public function deleteBlog($id) {
        $result = $this->blogModel->deleteBlog($id);
        
        if ($result) {
            return ['success' => true, 'message' => 'Xóa bài viết thành công!'];
        }
        
        return ['success' => false, 'errors' => ['Lỗi khi xóa bài viết!']];
    }
    
    // Toggle trạng thái (Khóa/Mở khóa)
    public function toggleStatus($id) {
        $result = $this->blogModel->toggleStatus($id);
        
        if ($result) {
            // Lấy trạng thái mới
            $blog = $this->blogModel->getBlogById($id);
            $statusText = $blog['status'] == 1 ? 'mở khóa' : 'khóa';
            return ['success' => true, 'message' => "Đã {$statusText} bài viết thành công!"];
        }
        
        return ['success' => false, 'message' => 'Lỗi khi thay đổi trạng thái!'];
    }
    
    // Tìm kiếm bài viết
    public function searchBlogs($keyword) {
        if (empty($keyword)) {
            return $this->getAllBlogs();
        }
        return $this->blogModel->searchBlogs($keyword);
    }
    
    // Äáº¿m tá»•ng sá»‘ bÃ i viáº¿t
    public function countBlogs() {
        return $this->blogModel->countBlogs();
    }
    
    // Láº¥y bÃ i viáº¿t má»›i nháº¥t
    public function getRecentBlogs($limit = 5) {
        return $this->blogModel->getRecentBlogs($limit);
    }
}
?>
