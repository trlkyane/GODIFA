<?php
/**
 * Controller: cCategory (Frontend version)
 * Xá»­ lÃ½ logic cho khÃ¡ch hÃ ng - Xem danh má»¥c sáº£n pháº©m
 */

require_once __DIR__ . '/../model/mCategory.php';

class cCategory {
    private $categoryModel;
    
    public function __construct() {
        $this->categoryModel = new Category();
    }
    
    /**
     * Láº¥y táº¥t cáº£ danh má»¥c (Frontend - chá»‰ hiá»ƒn thá»‹ active)
     */
    public function getAllCategories() {
        return $this->categoryModel->getActiveCategories();
    }
    
    /**
     * Láº¥y danh má»¥c theo ID
     */
    public function getCategoryById($id) {
        $category = $this->categoryModel->getCategoryById($id);
        
        // Kiá»ƒm tra náº¿u danh má»¥c bá»‹ khÃ³a thÃ¬ khÃ´ng cho khÃ¡ch hÃ ng xem
        if ($category && $category['status'] == 0) {
            return null;
        }
        
        return $category;
    }
    
    /**
     * Láº¥y sáº£n pháº©m theo danh má»¥c
     */
    public function getProductsByCategory($categoryId, $limit = null) {
        require_once __DIR__ . '/../model/mProduct.php';
        $productModel = new Product();
        return $productModel->getProductsByCategory($categoryId, $limit);
    }
}
?>
