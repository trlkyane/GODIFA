<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

// Đường dẫn động tùy context (frontend/admin)
$basePath = file_exists(__DIR__ . '/../model/mProduct.php') ? __DIR__ . '/..' : __DIR__ . '/..';
require_once $basePath . '/model/mProduct.php';
require_once $basePath . '/model/mCategory.php';
require_once $basePath . '/model/mReview.php';

class ProductController {
    protected $productModel;
    protected $categoryModel;
    protected $reviewModel;
    
    public function __construct() {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->reviewModel = new Review();
    }
    
    // Hiển thị danh sách sản phẩm
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 12;
        $offset = ($page - 1) * $limit;
        
        $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $keyword = isset($_GET['search']) ? trim($_GET['search']) : null;
        
        if ($categoryId) {
            $products = $this->productModel->getProductsByCategory($categoryId);
        } elseif ($keyword) {
            $products = $this->productModel->searchProducts($keyword);
        } else {
            $products = $this->productModel->getActiveProducts($limit, $offset);
        }
        
        // Lấy rating và review count cho từng sản phẩm
        foreach ($products as &$product) {
            $ratingData = $this->reviewModel->getAverageRating($product['productID']);
            $product['avgRating'] = $ratingData['avgRating'] ?? 0;
            $product['reviewCount'] = $ratingData['totalReviews'] ?? 0;
        }
        unset($product);
        
        $categories = $this->categoryModel->getActiveCategories();
        $totalProducts = $this->productModel->countProducts();
        $totalPages = ceil($totalProducts / $limit);
        
        return [
            'products' => $products,
            'categories' => $categories,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ];
    }
    
    // Hiển thị chi tiết sản phẩm
    public function detail($productId) {
        $product = $this->productModel->getProductById($productId);
        
        if (!$product) {
            return null;
        }
        
        // Kiểm tra nếu sản phẩm bị khóa thì không cho khách hàng xem
        if ($product['status'] == 0) {
            return null;
        }
        
        $reviews = $this->reviewModel->getReviewsByProduct($productId);
        $avgRating = $this->reviewModel->getAverageRating($productId);
        $relatedProducts = $this->productModel->getProductsByCategory($product['categoryID'], 4);
        
        return [
            'product' => $product,
            'reviews' => $reviews,
            'avgRating' => $avgRating,
            'relatedProducts' => $relatedProducts
        ];
    }
    
    // Thêm đánh giá
    public function addReview() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['customer_id'])) {
                return ['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá'];
            }
            
            $productId = (int)$_POST['product_id'];
            $customerId = $_SESSION['customer_id'];
            $rating = (int)$_POST['rating'];
            $comment = trim($_POST['comment']);
            
            if ($this->reviewModel->hasReviewed($productId, $customerId)) {
                return ['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi'];
            }
            
            if ($this->reviewModel->addReview($productId, $customerId, $rating, $comment)) {
                return ['success' => true, 'message' => 'Đánh giá thành công'];
            }
            
            return ['success' => false, 'message' => 'Đánh giá thất bại'];
        }
    }
}

// Alias class để tương thích với code cũ
class cProduct extends ProductController {
    public function getProductById($id) {
        return $this->productModel->getProductById($id);
    }
    
    public function getProductsByCategory($categoryId, $limit = null) {
        return $this->productModel->getProductsByCategory($categoryId, $limit);
    }
}

// Xử lý request CHỈ KHI được gọi trực tiếp (không phải require/include)
if (!defined('CONTROLLER_INCLUDED')) {
    define('CONTROLLER_INCLUDED', true);
    
    if (isset($_GET['action'])) {
        $controller = new ProductController();
        $action = $_GET['action'];
    
    // DEBUG
    error_log("cProduct.php - Action: " . $action);
    if (isset($_GET['id'])) {
        error_log("cProduct.php - ID: " . $_GET['id']);
    }
    
    switch ($action) {
        case 'detail':
            if (isset($_GET['id'])) {
                $data = $controller->detail((int)$_GET['id']);
                error_log("cProduct.php - Data: " . ($data ? 'OK' : 'NULL'));
                if ($data) {
                    extract($data);
                    include '../view/product/detail.php';
                    exit(); // Dừng lại sau khi include view
                } else {
                    error_log("cProduct.php - Product not found, redirect to 404");
                    header('Location: /GODIFA/view/404.php');
                    exit();
                }
            } else {
                error_log("cProduct.php - No ID provided, redirect to home");
                header('Location: /GODIFA/index.php');
                exit();
            }
            break;
            
        case 'add_review':
            $result = $controller->addReview();
            echo json_encode($result);
            exit(); // Dừng lại sau khi return JSON
            break;
            
        default:
            // Không có action hợp lệ, redirect về trang chủ
            header('Location: /GODIFA/index.php');
            exit();
    }
    }
    // Nếu truy cập trực tiếp không có action, redirect về trang chủ
    else {
        header('Location: /GODIFA/index.php');
        exit();
    }
}
?>
