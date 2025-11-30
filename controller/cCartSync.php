<?php
/**
 * Cart Synchronization Helper
 * File: controller/cCartSync.php
 * 
 * Xử lý đồng bộ giỏ hàng giữa SESSION và DATABASE
 */

require_once __DIR__ . '/../model/mCart.php';
require_once __DIR__ . '/../model/mProduct.php';

class CartSync {
    private $cartModel;
    private $productModel;
    
    public function __construct() {
        $this->cartModel = new Cart();
        $this->productModel = new Product();
    }
    
    /**
     * Đồng bộ giỏ hàng khi khách đăng nhập
     * - Load giỏ hàng từ database
     * - Merge với giỏ hàng trong session (nếu có)
     * - Lưu lại vào database
     */
    public function syncCartOnLogin($customerID) {
        try {
            // 1. Lấy giỏ hàng từ database
            $dbCartItems = $this->cartModel->getCartItems($customerID);
            
            // Debug log
            error_log("CartSync: Loading cart for customer $customerID, found " . count($dbCartItems) . " items in DB");
            
            // 2. Chuyển database cart thành array với key là productID
            $dbCart = [];
            foreach ($dbCartItems as $item) {
                $dbCart[$item['productID']] = [
                    'productID' => $item['productID'],
                    'productName' => $item['productName'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'image' => $item['image']
                ];
            }
            
            // 3. Nếu có giỏ hàng trong session (từ trước khi đăng nhập)
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                $sessionCart = $_SESSION['cart'];
                error_log("CartSync: Found " . count($sessionCart) . " items in session cart");
                
                // Merge: Cộng số lượng nếu sản phẩm đã có trong database
                foreach ($sessionCart as $productID => $item) {
                    if (isset($dbCart[$productID])) {
                        // Sản phẩm đã có trong DB, cộng số lượng
                        $dbCart[$productID]['quantity'] += $item['quantity'];
                    } else {
                        // Sản phẩm mới, thêm vào
                        $dbCart[$productID] = $item;
                    }
                }
            }
            
            // 4. Lưu giỏ hàng đã merge vào session
            $_SESSION['cart'] = $dbCart;
            
            // 5. Lưu giỏ hàng vào database
            $result = $this->saveCartToDatabase($customerID);
            
            error_log("CartSync: Sync completed for customer $customerID, " . count($_SESSION['cart']) . " items total");
            
            return $result;
        } catch (Exception $e) {
            error_log("CartSync Error in syncCartOnLogin: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lưu giỏ hàng từ SESSION vào DATABASE
     * Dùng khi: Khách đã đăng nhập thêm/sửa/xóa sản phẩm
     */
    public function saveCartToDatabase($customerID) {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return false;
        }
        
        try {
            // Xóa toàn bộ giỏ hàng cũ trong database
            $this->cartModel->clearCart($customerID);
            
            // Thêm lại từng sản phẩm từ session vào database
            foreach ($_SESSION['cart'] as $item) {
                $result = $this->cartModel->addToCart(
                    $customerID,
                    $item['productID'],
                    $item['quantity'],
                    $item['price']
                );
                
                // Debug log
                if (!$result) {
                    error_log("CartSync: Failed to add product {$item['productID']} to database for customer $customerID");
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("CartSync Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load giỏ hàng từ DATABASE vào SESSION
     * Dùng khi: Khách đăng nhập vào từ thiết bị khác
     */
    public function loadCartFromDatabase($customerID) {
        $dbCartItems = $this->cartModel->getCartItems($customerID);
        
        $_SESSION['cart'] = [];
        
        foreach ($dbCartItems as $item) {
            $_SESSION['cart'][$item['productID']] = [
                'productID' => $item['productID'],
                'productName' => $item['productName'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'image' => $item['image']
            ];
        }
        
        return true;
    }
    
    /**
     * Xóa giỏ hàng trong DATABASE
     * Dùng khi: Đơn hàng đã thanh toán thành công
     */
    public function clearDatabaseCart($customerID) {
        return $this->cartModel->clearCart($customerID);
    }
}
?>
