<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}
require_once '../model/mCart.php';
require_once '../model/mProduct.php';

class CartController {
    private $cartModel;
    private $productModel;
    
    public function __construct() {
        $this->cartModel = new Cart();
        $this->productModel = new Product();
    }
    
    // Khởi tạo giỏ hàng trong session
    private function initCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }
    
    // Thêm sản phẩm vào giỏ hàng (không cần đăng nhập)
    public function addToCart() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->initCart();
            
            $productId = (int)$_POST['productId'];
            $quantity = (int)$_POST['quantity'];
            
            $product = $this->productModel->getProductById($productId);
            
            if (!$product) {
                return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
            }
            
            if ($product['stockQuantity'] < $quantity) {
                return ['success' => false, 'message' => 'Số lượng sản phẩm không đủ'];
            }
            
            // Xác định giá cuối cùng (ưu tiên giá khuyến mãi nếu có)
            $finalPrice = (!empty($product['promotional_price']) && $product['promotional_price'] > 0) 
                ? $product['promotional_price'] 
                : $product['price'];
            
            // Nếu sản phẩm đã có trong giỏ, tăng số lượng
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity'] += $quantity;
                // Cập nhật giá nếu có thay đổi
                $_SESSION['cart'][$productId]['price'] = $finalPrice;
            } else {
                // Thêm sản phẩm mới vào giỏ
                $_SESSION['cart'][$productId] = [
                    'productID' => $productId,
                    'productName' => $product['productName'],
                    'price' => $finalPrice,
                    'quantity' => $quantity,
                    'image' => $product['image']
                ];
            }
            
            $cartCount = count($_SESSION['cart']);
            return ['success' => true, 'message' => 'Thêm vào giỏ hàng thành công', 'cartCount' => $cartCount];
        }
        
        return ['success' => false, 'message' => 'Yêu cầu không hợp lệ'];
    }
    
    // Hiển thị giỏ hàng (không cần đăng nhập)
    public function viewCart() {
        $this->initCart();
        
        $cartItems = [];
        $cartTotal = 0;
        
        foreach ($_SESSION['cart'] as $item) {
            $cartItems[] = $item;
            $cartTotal += $item['price'] * $item['quantity'];
        }
        
        return [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
            'cartCount' => count($cartItems)
        ];
    }
    
    // Cập nhật số lượng (không cần đăng nhập)
    public function updateCart() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->initCart();
            
            $productId = (int)$_POST['productId'];
            $quantity = (int)$_POST['quantity'];
            
            if (isset($_SESSION['cart'][$productId])) {
                // Kiểm tra tồn kho
                $product = $this->productModel->getProductById($productId);
                if ($product && $product['stockQuantity'] >= $quantity) {
                    $_SESSION['cart'][$productId]['quantity'] = $quantity;
                    
                    $cartData = $this->viewCart();
                    return [
                        'success' => true, 
                        'cart' => $_SESSION['cart'],
                        'cartTotal' => $cartData['cartTotal'], 
                        'cartCount' => $cartData['cartCount']
                    ];
                } else {
                    return ['success' => false, 'message' => 'Số lượng không đủ'];
                }
            }
            
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng'];
        }
        
        return ['success' => false, 'message' => 'Yêu cầu không hợp lệ'];
    }
    
    // Xóa sản phẩm khỏi giỏ hàng (không cần đăng nhập)
    public function removeFromCart() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->initCart();
            
            $productId = (int)$_POST['productId'];
            
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
                $cartCount = count($_SESSION['cart']);
                $cartData = $this->viewCart();
                return [
                    'success' => true, 
                    'message' => 'Xóa thành công', 
                    'cart' => $_SESSION['cart'],
                    'cartTotal' => $cartData['cartTotal'],
                    'cartCount' => $cartCount
                ];
            }
            
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng'];
        }
        
        return ['success' => false, 'message' => 'Yêu cầu không hợp lệ'];
    }
    
    // Xóa toàn bộ giỏ hàng (không cần đăng nhập)
    public function clearCart() {
        $_SESSION['cart'] = [];
        return ['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng'];
    }
}

// Xử lý request
if (isset($_GET['action'])) {
    $controller = new CartController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'add':
            $result = $controller->addToCart();
            echo json_encode($result);
            break;
            
        case 'update':
            $result = $controller->updateCart();
            echo json_encode($result);
            break;
            
        case 'remove':
            $result = $controller->removeFromCart();
            echo json_encode($result);
            break;
            
        case 'clear':
            $result = $controller->clearCart();
            echo json_encode($result);
            break;
            
        case 'view':
            $data = $controller->viewCart();
            extract($data);
            include '../view/cart/view.php';
            break;
            
        default:
            header('Location: ../view/product/list.php');
    }
}
?>
