<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

// Đường dẫn động tùy context (frontend/admin)
$basePath = file_exists(__DIR__ . '/../model/mOrder.php') ? __DIR__ . '/..' : __DIR__ . '/..';
require_once $basePath . '/model/mOrder.php';
require_once $basePath . '/model/mCart.php';
require_once $basePath . '/model/mProduct.php';

class OrderController {
    private $orderModel;
    private $cartModel;
    private $productModel;
    
    public function __construct() {
        $this->orderModel = new Order();
        $this->cartModel = new Cart();
        $this->productModel = new Product();
    }
    
    // Kiểm tra đăng nhập
    private function checkLogin() {
        if (!isset($_SESSION['customer_id'])) {
            return false;
        }
        return true;
    }
    
    // Hiển thị trang thanh toán
    public function checkout() {
        if (!$this->checkLogin()) {
            header('Location: vLogin.php');
            exit();
        }
        
        $customerId = $_SESSION['customer_id'];
        $cartItems = $this->cartModel->getCartItems($customerId);
        $cartTotal = $this->cartModel->getCartTotal($customerId);
        
        if (empty($cartItems)) {
            header('Location: /GODIFA/view/cart/viewcart.php');
            exit();
        }
        
        return [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal
        ];
    }
    
    // Xử lý đặt hàng
    public function placeOrder() {
        if (!$this->checkLogin()) {
            return ['success' => false, 'message' => 'Vui lòng đăng nhập'];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $customerId = $_SESSION['customer_id'];
            $paymentMethod = $_POST['payment_method'];
            
            $cartItems = $this->cartModel->getCartItems($customerId);
            
            if (empty($cartItems)) {
                return ['success' => false, 'message' => 'Giỏ hàng trống'];
            }
            
            // Kiểm tra số lượng tồn kho
            foreach ($cartItems as $item) {
                if ($item['stockQuantity'] < $item['quantity']) {
                    return ['success' => false, 'message' => 'Sản phẩm ' . $item['productName'] . ' không đủ số lượng'];
                }
            }
            
            $totalAmount = $this->cartModel->getCartTotal($customerId);
            
            // Tạo đơn hàng
            $orderId = $this->orderModel->createOrder($customerId, $totalAmount, $paymentMethod);
            
            if ($orderId) {
                // Thêm chi tiết đơn hàng
                foreach ($cartItems as $item) {
                    $this->orderModel->addOrderDetails($orderId, $item['productID'], $item['quantity'], $item['price']);
                    // Cập nhật số lượng tồn kho
                    $this->productModel->updateStock($item['productID'], $item['quantity']);
                }
                
                // Xóa giỏ hàng
                $this->cartModel->clearCart($customerId);
                
                return ['success' => true, 'message' => 'Đặt hàng thành công', 'orderId' => $orderId];
            }
            
            return ['success' => false, 'message' => 'Đặt hàng thất bại'];
        }
    }
    
    // Xem chi tiết đơn hàng
    public function orderDetail($orderId) {
        if (!$this->checkLogin()) {
            header('Location: vLogin.php');
            exit();
        }
        
        $order = $this->orderModel->getOrderById($orderId);
        
        if (!$order || $order['customerID'] != $_SESSION['customer_id']) {
            return null;
        }
        
        $orderDetails = $this->orderModel->getOrderDetails($orderId);
        
        return [
            'order' => $order,
            'orderDetails' => $orderDetails
        ];
    }
    
    // Xem lịch sử đơn hàng
    public function orderHistory() {
        if (!$this->checkLogin()) {
            header('Location: vLogin.php');
            exit();
        }
        
        $customerId = $_SESSION['customer_id'];
        $orders = $this->orderModel->getOrdersByCustomer($customerId);
        
        return ['orders' => $orders];
    }
}

// Xử lý request
if (isset($_GET['action'])) {
    $controller = new OrderController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'checkout':
            $data = $controller->checkout();
            extract($data);
            include '../view/cart/checkout.php';
            break;
            
        case 'place_order':
            $result = $controller->placeOrder();
            if ($result['success']) {
                header('Location: ../view/payment/thankyou.php?order_id=' . $result['orderId']);
            } else {
                header('Location: ../view/cart/checkout.php?error=' . urlencode($result['message']));
            }
            break;
            
        case 'detail':
            if (isset($_GET['id'])) {
                $data = $controller->orderDetail((int)$_GET['id']);
                if ($data) {
                    extract($data);
                    include '../view/order_detail.php';
                } else {
                    header('Location: ../view/404.php');
                }
            }
            break;
            
        case 'history':
            $data = $controller->orderHistory();
            extract($data);
            include '../view/order_history.php';
            break;
            
        default:
            header('Location: ../index.php');
    }
}
?>
