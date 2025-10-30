<?php
/**
 * Controller: cCategory (Frontend version)
 * Xử lý logic cho khách hàng - Xem danh mục sản phẩm
 */

require_once __DIR__ . '/../model/mCategory.php';

class cCategory {
    private $categoryModel;
    
    public function __construct() {
        $this->categoryModel = new Category();
    }
    
    /**
     * Lấy tất cả danh mục (Frontend - chỉ hiển thị active)
     */
    public function getAllCategories() {
        return $this->categoryModel->getActiveCategories();
    }
    
    /**
     * Lấy danh mục theo ID
     */
    public function getCategoryById($id) {
        $category = $this->categoryModel->getCategoryById($id);
        
        // Kiểm tra nếu danh mục bị khóa thì không cho khách hàng xem
        if ($category && $category['status'] == 0) {
            return null;
        }
        
        return $category;
    }
    
    /**
     * Lấy sản phẩm theo danh mục
     */
    public function getProductsByCategory($categoryId, $limit = null) {
        require_once __DIR__ . '/../model/mProduct.php';
        $productModel = new Product();
        return $productModel->getProductsByCategory($categoryId, $limit);
    }
}
?>
