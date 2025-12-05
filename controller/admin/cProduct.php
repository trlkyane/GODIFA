<?php
/**
 * Controller: cProduct (Admin version)
 * Xá»­ lÃ½ logic nghiá»‡p vá»¥ vÃ  validation cho quáº£n lÃ½ sáº£n pháº©m (Admin)
 */

require_once __DIR__ . '/../../model/mProduct.php';
require_once __DIR__ . '/../../model/mCategory.php';

class cProduct {
    private $productModel;
    private $categoryModel;
    
    public function __construct() {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }
    
    /**
     * Láº¥y táº¥t cáº£ sáº£n pháº©m
     */
    public function getAllProducts() {
        return $this->productModel->getAllProducts();
    }
    
    /**
     * Láº¥y sáº£n pháº©m theo ID
     */
    public function getProductById($id) {
        return $this->productModel->getProductById($id);
    }
    
    /**
     * Láº¥y táº¥t cáº£ danh má»¥c (cho dropdown)
     */
    public function getAllCategories() {
        return $this->categoryModel->getAllCategories();
    }
    
    /**
     * TÃ¬m kiáº¿m sáº£n pháº©m
     */
    public function searchProducts($keyword) {
        return $this->productModel->searchProducts($keyword);
    }
    
    /**
     * Äáº¿m tá»•ng sá»‘ sáº£n pháº©m
     */
    public function countProducts() {
        return $this->productModel->countProducts();
    }
    
    /**
     * ThÃªm sáº£n pháº©m má»›i (Admin)
     * @param array $data - Dá»¯ liá»‡u sáº£n pháº©m
     * @return array ['success' => bool, 'message' hoáº·c 'errors' => mixed]
     */
    public function addProduct($data) {
        $errors = [];
        
        // Validate: Tên sản phẩm
        if (empty($data['productName'])) {
            $errors[] = "Vui lòng nhập tên sản phẩm!";
        }
        
        // Validate: Giá
        if (!isset($data['price']) || $data['price'] < 0) {
            $errors[] = "Giá sản phẩm phải lớn hơn hoặc bằng 0!";
        }
        
        // Validate: Giá khuyến mãi (nếu có)
        if (isset($data['promotional_price']) && !empty($data['promotional_price'])) {
            if ($data['promotional_price'] < 0) {
                $errors[] = "Giá khuyến mãi phải lớn hơn hoặc bằng 0!";
            } elseif ($data['promotional_price'] >= $data['price']) {
                $errors[] = "Giá khuyến mãi phải nhỏ hơn giá gốc!";
            }
        }
        
        // Validate: Số lượng
        if (!isset($data['stockQuantity']) || $data['stockQuantity'] < 0) {
            $errors[] = "Số lượng tồn kho phải lớn hơn hoặc bằng 0!";
        }
        
        // Validate: Danh mục
        if (empty($data['categoryID'])) {
            $errors[] = "Vui lòng chọn danh mục!";
        } else {
            // Kiểm tra danh mục có tồn tại không
            $category = $this->categoryModel->getCategoryById($data['categoryID']);
            if (!$category) {
                $errors[] = "Danh mục không tồn tại!";
            }
        }
        
        // Validate: SKU (nếu có) - phải unique
        if (!empty($data['SKU'])) {
            if ($this->productModel->skuExists($data['SKU'])) {
                $errors[] = "Mã SKU đã tồn tại trong hệ thống!";
            }
        }
        
        // Náº¿u cÃ³ lá»—i, tráº£ vá» danh sÃ¡ch lá»—i
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Thêm sản phẩm
        $promotional_price = (!empty($data['promotional_price']) && $data['promotional_price'] > 0) ? $data['promotional_price'] : null;
        $result = $this->productModel->addProduct(
            $data['productName'],
            $data['SKU'] ?? '',
            $data['stockQuantity'],
            $data['price'],
            $data['description'] ?? '',
            $data['image'] ?? '',
            $data['categoryID'],
            $promotional_price
        );
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Thêm sản phẩm thành công!'
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Lỗi khi thêm sản phẩm vào database!']
            ];
        }
    }
    
    /**
     * Cáº­p nháº­t sáº£n pháº©m (Admin)
     * @param int $id - ID sản phẩm
     * @param array $data - Dữ liệu cập nhật
     * @return array ['success' => bool, 'message' hoặc 'errors' => mixed]
     */
    public function updateProduct($id, $data) {
        $errors = [];
        
        // Validate: TÃªn sáº£n pháº©m
        if (empty($data['productName'])) {
            $errors[] = "Vui lòng nhập tên sản phẩm!";
        }
        
        // Validate: Giá
        if (!isset($data['price']) || $data['price'] < 0) {
            $errors[] = "Giá sản phẩm phải lớn hơn hoặc bằng 0!";
        }
        
        // Validate: Giá khuyến mãi (nếu có)
        if (isset($data['promotional_price']) && !empty($data['promotional_price'])) {
            if ($data['promotional_price'] < 0) {
                $errors[] = "Giá khuyến mãi phải lớn hơn hoặc bằng 0!";
            } elseif ($data['promotional_price'] >= $data['price']) {
                $errors[] = "Giá khuyến mãi phải nhỏ hơn giá gốc!";
            }
        }
        
        // Validate: Số lượng
        if (!isset($data['stockQuantity']) || $data['stockQuantity'] < 0) {
            $errors[] = "Số lượng tồn kho phải lớn hơn hoặc bằng 0!";
        }
        
        // Validate: Danh mục
        if (empty($data['categoryID'])) {
            $errors[] = "Vui lÃ²ng chá»n danh má»¥c!";
        } else {
            $category = $this->categoryModel->getCategoryById($data['categoryID']);
            if (!$category) {
                $errors[] = "Danh mục không tồn tại!";
            }
        }
        
        // Validate: SKU unique (nếu thay đổi)
        if (!empty($data['SKU'])) {
            $existingProduct = $this->productModel->getProductById($id);
            if ($existingProduct && $existingProduct['SKU_MRK'] !== $data['SKU']) {
                if ($this->productModel->skuExists($data['SKU'])) {
                    $errors[] = "Mã SKU đã tồn tại trong hệ thống!";
                }
            }
        }
        
        // Nếu có lỗi
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Cáº­p nháº­t sáº£n pháº©m
        $promotional_price = (!empty($data['promotional_price']) && $data['promotional_price'] > 0) ? $data['promotional_price'] : null;
        $result = $this->productModel->updateProduct(
            $id,
            $data['productName'],
            $data['SKU'] ?? '',
            $data['stockQuantity'],
            $data['price'],
            $data['description'] ?? '',
            $data['image'] ?? '',
            $data['categoryID'],
            $promotional_price
        );
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công!'
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Lỗi khi cập nhật sản phẩm!']
            ];
        }
    }
    
    /**
     * Xóa sản phẩm
     * @param int $id - ID sản phẩm
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteProduct($id) {
        $result = $this->productModel->deleteProduct($id);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Xóa sản phẩm thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Lỗi khi xóa sản phẩm!'
            ];
        }
    }
    
    /**
     * Bật/tắt trạng thái sản phẩm
     * @param int $id - ID sản phẩm
     * @return array ['success' => bool, 'message' => string]
     */
    public function toggleStatus($id) {
        $product = $this->productModel->getProductById($id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Sản phẩm không tồn tại!'
            ];
        }
        
        $newStatus = $product['status'] == 1 ? 0 : 1;
        $result = $this->productModel->toggleStatus($id, $newStatus);
        
        if ($result) {
            $statusText = $newStatus == 1 ? 'Hiển thị' : 'Ẩn';
            return [
                'success' => true,
                'message' => "Đã chuyển sản phẩm sang trạng thái: {$statusText}"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Lỗi khi thay đổi trạng thái!'
            ];
        }
    }
}
?>
