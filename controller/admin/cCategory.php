<?php
/**
 * Controller: cCategory (Admin version)
 * Xá»­ lÃ½ logic nghiá»‡p vá»¥ vÃ  validation cho quáº£n lÃ½ danh má»¥c
 */

require_once __DIR__ . '/../../model/mCategory.php';

class cCategory {
    private $categoryModel;
    
    public function __construct() {
        $this->categoryModel = new Category();
    }
    
    /**
     * Láº¥y táº¥t cáº£ danh má»¥c
     */
    public function getAllCategories() {
        return $this->categoryModel->getAllCategories();
    }
    
    /**
     * Láº¥y danh má»¥c theo ID
     */
    public function getCategoryById($id) {
        return $this->categoryModel->getCategoryById($id);
    }
    
    /**
     * ThÃªm danh má»¥c má»›i
     * @param array $data - Dá»¯ liá»‡u danh má»¥c
     * @return array ['success' => bool, 'message' hoáº·c 'errors' => mixed]
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
     * Cáº­p nháº­t danh má»¥c
     * @param int $id - ID danh má»¥c
     * @param array $data - Dá»¯ liá»‡u cáº­p nháº­t
     * @return array ['success' => bool, 'message' hoáº·c 'errors' => mixed]
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
        
        // Cáº­p nháº­t danh má»¥c
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
     * Báº­t/táº¯t tráº¡ng thÃ¡i danh má»¥c
     * @param int $id - ID danh má»¥c
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
     * Äáº¿m sá»‘ sáº£n pháº©m trong danh má»¥c
     */
    public function countProductsInCategory($categoryID) {
        return $this->categoryModel->countProductsInCategory($categoryID);
    }
}
?>
