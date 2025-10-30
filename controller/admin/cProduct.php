<?php
/**
 * Controller: cProduct (Admin version)
 * Xử lý logic nghiệp vụ và validation cho quản lý sản phẩm (Admin)
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
     * Lấy tất cả sản phẩm
     */
    public function getAllProducts() {
        return $this->productModel->getAllProducts();
    }
    
    /**
     * Lấy sản phẩm theo ID
     */
    public function getProductById($id) {
        return $this->productModel->getProductById($id);
    }
    
    /**
     * Lấy tất cả danh mục (cho dropdown)
     */
    public function getAllCategories() {
        return $this->categoryModel->getAllCategories();
    }
    
    /**
     * Tìm kiếm sản phẩm
     */
    public function searchProducts($keyword) {
        return $this->productModel->searchProducts($keyword);
    }
    
    /**
     * Đếm tổng số sản phẩm
     */
    public function countProducts() {
        return $this->productModel->countProducts();
    }
    
    /**
     * Thêm sản phẩm mới (Admin)
     * @param array $data - Dữ liệu sản phẩm
     * @return array ['success' => bool, 'message' hoặc 'errors' => mixed]
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
        
        // Nếu có lỗi, trả về danh sách lỗi
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Thêm sản phẩm
        $result = $this->productModel->addProduct(
            $data['productName'],
            $data['SKU'] ?? '',
            $data['stockQuantity'],
            $data['price'],
            $data['description'] ?? '',
            $data['image'] ?? '',
            $data['categoryID']
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
     * Cập nhật sản phẩm (Admin)
     * @param int $id - ID sản phẩm
     * @param array $data - Dữ liệu cập nhật
     * @return array ['success' => bool, 'message' hoặc 'errors' => mixed]
     */
    public function updateProduct($id, $data) {
        $errors = [];
        
        // Validate: Tên sản phẩm
        if (empty($data['productName'])) {
            $errors[] = "Vui lòng nhập tên sản phẩm!";
        }
        
        // Validate: Giá
        if (!isset($data['price']) || $data['price'] < 0) {
            $errors[] = "Giá sản phẩm phải lớn hơn hoặc bằng 0!";
        }
        
        // Validate: Số lượng
        if (!isset($data['stockQuantity']) || $data['stockQuantity'] < 0) {
            $errors[] = "Số lượng tồn kho phải lớn hơn hoặc bằng 0!";
        }
        
        // Validate: Danh mục
        if (empty($data['categoryID'])) {
            $errors[] = "Vui lòng chọn danh mục!";
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
        
        // Cập nhật sản phẩm
        $result = $this->productModel->updateProduct(
            $id,
            $data['productName'],
            $data['SKU'] ?? '',
            $data['stockQuantity'],
            $data['price'],
            $data['description'] ?? '',
            $data['image'] ?? '',
            $data['categoryID']
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
