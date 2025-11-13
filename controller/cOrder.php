<?php
// File: cOrder.php
if (session_status() === PHP_SESSION_NONE) {
    session_name('GODIFA_USER_SESSION');
    session_start();
}

$basePath = __DIR__ . '/..'; 

// REQUIRE MODELS
require_once $basePath . '/model/mOrder.php';
require_once $basePath . '/model/mCart.php';
require_once $basePath . '/model/mProduct.php';
require_once $basePath . '/model/mReview.php'; // Đảm bảo đã có Model Review

class OrderController {
    private $orderModel;
    private $cartModel;
    private $productModel;
    private $reviewModel;
    
    public function __construct() {
        $this->orderModel = new Order();
        $this->cartModel = new Cart();
        $this->productModel = new Product();
        $this->reviewModel = new Review();
    }
    
    // ... (checkLogin(), checkout(), placeOrder() - Giữ nguyên) ...
    private function checkLogin() {
        if (!isset($_SESSION['customer_id'])) {
            return false;
        }
        return true;
    }

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
        return [ 'cartItems' => $cartItems, 'cartTotal' => $cartTotal ];
    }
    
    public function placeOrder() {
        if (!$this->checkLogin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Yêu cầu không hợp lệ.'];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $customerId = $_SESSION['customer_id'];
             $paymentMethod = $_POST['payment_method'] ?? 'COD'; 
             $cartItems = $this->cartModel->getCartItems($customerId);
             
             if (empty($cartItems)) {
                 return ['success' => false, 'message' => 'Giỏ hàng trống'];
             }
             
             foreach ($cartItems as $item) {
                 if ($item['stockQuantity'] < $item['quantity']) {
                     return ['success' => false, 'message' => 'Sản phẩm ' . htmlspecialchars($item['productName']) . ' không đủ số lượng'];
                 }
             }
             
             $totalAmount = $this->cartModel->getCartTotal($customerId);
             $orderId = $this->orderModel->createOrder($customerId, $totalAmount, $paymentMethod);
             
             if ($orderId) {
                 foreach ($cartItems as $item) {
                     $this->orderModel->addOrderDetails($orderId, $item['productID'], $item['quantity'], $item['price']);
                     $this->productModel->updateStock($item['productID'], $item['quantity']);
                 }
                 $this->cartModel->clearCart($customerId);
                 return ['success' => true, 'message' => 'Đặt hàng thành công', 'orderId' => $orderId];
             }
             return ['success' => false, 'message' => 'Đặt hàng thất bại'];
        }
        return ['success' => false, 'message' => 'Phương thức truy cập không hợp lệ.'];
    }

    /**
     * Xem lịch sử đơn hàng. (Hoàn chỉnh logic kiểm tra đánh giá)
     */
    public function orderHistory() {
        if (!$this->checkLogin()) {
            header('Location: vLogin.php');
            exit();
        }
        
        $customerId = $_SESSION['customer_id'];
        $orders = $this->orderModel->getOrdersByCustomer($customerId);
        
        foreach ($orders as &$order) {
            $order['canReviewAny'] = false; 
            $orderCompleted = ($order['deliveryStatus'] == 'Hoàn thành' || $order['deliveryStatus'] == 'Đã giao');

            if ($orderCompleted) {
                $orderDetails = $this->orderModel->getOrderDetails($order['orderID']);
                
                foreach ($orderDetails as $detail) {
                    $isReviewed = $this->reviewModel->hasReviewed(
                        $detail['productID'], 
                        $customerId, 
                        $order['orderID'] 
                    );

                    if (!$isReviewed) {
                        $order['canReviewAny'] = true; 
                        break; 
                    }
                }
            }
        }
        unset($order); 
        
        return ['orders' => $orders];
    }
    
    /**
     * Xem chi tiết đơn hàng. (Hoàn chỉnh logic kiểm tra từng sản phẩm)
     */
    public function orderDetail($orderId) {
        if (!$this->checkLogin()) {
            header('Location: vLogin.php');
            exit();
        }
        
        $customerId = $_SESSION['customer_id'];
        $order = $this->orderModel->getOrderById($orderId);
        
        if (!$order || $order['customerID'] != $customerId) {
            return null;
        }
        
        $orderDetails = $this->orderModel->getOrderDetails($orderId);
        $orderCompleted = ($order['deliveryStatus'] == 'Hoàn thành' || $order['deliveryStatus'] == 'Đã giao');

        foreach ($orderDetails as &$detail) {
            $detail['canReview'] = false;
            $detail['isReviewed'] = false;
            
            if ($orderCompleted) {
                $isReviewed = $this->reviewModel->hasReviewed(
                    $detail['productID'], 
                    $customerId, 
                    $orderId 
                );

                if ($isReviewed) {
                    $detail['isReviewed'] = true;
                } else {
                    $detail['canReview'] = true;
                }
            }
        }
        unset($detail);
        
        return [
            'order' => $order,
            'orderDetails' => $orderDetails
        ];
    }

    /**
     * Xử lý gửi đánh giá (POST).
     */
    public function submitReview() {
        if (!$this->checkLogin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Lỗi xác thực hoặc phương thức không hợp lệ.'];
        }
        
        $customerId = $_SESSION['customer_id'];
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
        
        if (!$productId || !$orderId || $rating < 1 || $rating > 5 || empty($comment)) {
            return ['success' => false, 'message' => 'Dữ liệu đánh giá không hợp lệ. Vui lòng điền đủ điểm và nội dung.'];
        }

        if ($this->reviewModel->hasReviewed($productId, $customerId, $orderId)) {
            return ['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này cho đơn hàng này rồi.'];
        }
        
        $result = $this->reviewModel->addReview($productId, $customerId, $orderId, $rating, $comment);
        
        if ($result) {
            return ['success' => true, 'message' => 'Đánh giá của bạn đã được gửi thành công.'];
        } else {
            return ['success' => false, 'message' => 'Lỗi hệ thống khi lưu đánh giá.'];
        }
    }
    
    /**
     * Lấy lịch sử đánh giá của khách hàng.
     */
    public function reviewHistory() {
        if (!$this->checkLogin()) {
            header('Location: vLogin.php');
            exit();
        }
        
        $customerId = $_SESSION['customer_id'];
        $reviews = $this->reviewModel->getReviewsByCustomer($customerId);
        
        return ['reviews' => $reviews];
    }
}

// ------------------- ĐIỀU PHỐI REQUEST CHÍNH -------------------

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
                $_SESSION['notify_error'] = $result['message']; 
                header('Location: ../view/cart/checkout.php');
            }
            exit(); 
            
        case 'detail':
            if (isset($_GET['id'])) {
                $data = $controller->orderDetail((int)$_GET['id']);
                if ($data) {
                    extract($data);
                    include '../view/order/detail.php';
                } else {
                    header('Location: index.php?action=history'); 
                }
            } else {
                header('Location: index.php?action=history');
            }
            break;
            
        case 'history':
            $data = $controller->orderHistory();
            extract($data);
            include '../view/customer/orderHistory.php';
            break;
            
            case 'submit_review': 
                $result = $controller->submitReview();
                
                // Đặt URL chuyển hướng mặc định về lịch sử đơn hàng
                $redirectUrl = 'cOrder.php?action=history'; 
                
                // Nếu order_id được gửi lên hợp lệ, chuyển hướng về chi tiết đơn hàng đó
                if (isset($_POST['order_id']) && filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT)) {
                    // Sửa: Đảm bảo trỏ về cOrder.php
                    $redirectUrl = 'cOrder.php?action=detail&id=' . (int)$_POST['order_id']; 
                }
    
                // Đặt thông báo vào session
                if ($result['success']) {
                    $_SESSION['notify_success'] = $result['message']; 
                } else {
                    $_SESSION['notify_error'] = $result['message']; 
                }
                
                // Thực hiện chuyển hướng
                header('Location: ' . $redirectUrl);
                exit();
    
            case 'reviews': 
                $data = $controller->reviewHistory();
                extract($data);
                include '../view/order/detail.php'; 
                break;
                
            default:
                // Sửa: Đảm bảo trỏ về cOrder.php
                header('Location: cOrder.php?action=history');
                break;
        }
    }
?>