<?php
/**
 * Controller: cCategory (Admin version)
 * Xử lý logic nghiệp vụ và validation cho quản lý danh mục
 */

require_once __DIR__ . '/../../model/mCategory.php';

class cCategory {
    private $categoryModel;
    
    public function __construct() {
        $this->categoryModel = new Category();
    }
    
    /**
     * Lấy tất cả danh mục
     */
    public function getAllCategories() {
        return $this->categoryModel->getAllCategories();
    }
    
    /**
     * Lấy danh mục theo ID
     */
    public function getCategoryById($id) {
        return $this->categoryModel->getCategoryById($id);
    }
    
    /**
     * Thêm danh mục mới
     * @param array $data - Dữ liệu danh mục
     * @return array ['success' => bool, 'message' hoặc 'errors' => mixed]
     */
    public function addCategory($data) {
        $errors = [];
        
        // Validate: Tên danh mục
        if (empty($data['categoryName'])) {
            $errors[] = "Vui lòng nhập tên danh mục!";
        } else {
            // Kiểm tra trùng tên
            if ($this->categoryModel->categoryNameExists($data['categoryName'])) {
                $errors[] = "Tên danh mục đã tồn tại!";
            }
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Thêm danh mục
        $result = $this->categoryModel->addCategory(
            $data['categoryName'],
            $data['description'] ?? '',
            $data['status'] ?? '1'
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Thêm danh mục thành công!'];
        } else {
            return ['success' => false, 'errors' => ['Lỗi khi thêm danh mục vào database!']];
        }
    }
    
    /**
     * Cập nhật danh mục
     * @param int $id - ID danh mục
     * @param array $data - Dữ liệu cập nhật
     * @return array ['success' => bool, 'message' hoặc 'errors' => mixed]
     */
    public function updateCategory($id, $data) {
        $errors = [];
        
        // Validate: Tên danh mục
        if (empty($data['categoryName'])) {
            $errors[] = "Vui lòng nhập tên danh mục!";
        } else {
            // Kiểm tra trùng tên (ngoại trừ chính nó)
            $existingCategory = $this->categoryModel->getCategoryById($id);
            if ($existingCategory && $existingCategory['categoryName'] !== $data['categoryName']) {
                if ($this->categoryModel->categoryNameExists($data['categoryName'])) {
                    $errors[] = "Tên danh mục đã tồn tại!";
                }
            }
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Cập nhật danh mục
        $result = $this->categoryModel->updateCategory(
            $id,
            $data['categoryName'],
            $data['description'] ?? '',
            $data['status'] ?? '1'
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Cập nhật danh mục thành công!'];
        } else {
            return ['success' => false, 'errors' => ['Lỗi khi cập nhật danh mục!']];
        }
    }
    
    // REMOVED: deleteCategory() - Chỉ dùng khóa/mở (toggleStatus), không xóa
    
    /**
     * Bật/tắt trạng thái danh mục
     * @param int $id - ID danh mục
     * @return array ['success' => bool, 'message' => string]
     */
    public function toggleStatus($id) {
        $category = $this->categoryModel->getCategoryById($id);
        
        if (!$category) {
            return ['success' => false, 'message' => 'Danh mục không tồn tại!'];
        }
        
        $newStatus = $category['status'] == 1 ? 0 : 1;
        $result = $this->categoryModel->toggleStatus($id, $newStatus);
        
        if ($result) {
            $statusText = $newStatus == 1 ? 'Hiển thị' : 'Ẩn';
            $message = "Đã chuyển danh mục sang trạng thái: {$statusText}";
            
            // Cảnh báo khi ẩn danh mục có sản phẩm
            if ($newStatus == 0 && $this->categoryModel->hasProducts($id)) {
                $productCount = $this->categoryModel->countProductsInCategory($id);
                $message .= " (Lưu ý: {$productCount} sản phẩm trong danh mục này sẽ bị ẩn trên website)";
            }
            
            return [
                'success' => true,
                'message' => $message
            ];
        } else {
            return ['success' => false, 'message' => 'Lỗi khi thay đổi trạng thái!'];
        }
    }
    
    /**
     * Đếm tổng số danh mục
     */
    public function countCategories() {
        return $this->categoryModel->countCategories();
    }
    
    /**
     * Đếm số sản phẩm trong danh mục
     */
    public function countProductsInCategory($categoryID) {
        return $this->categoryModel->countProductsInCategory($categoryID);
    }
}
?>
