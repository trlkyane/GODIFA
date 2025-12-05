<?php
require_once __DIR__ . '/database.php';

class Cart {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Táº¡o giá» hÃ ng má»›i cho khÃ¡ch hÃ ng
    public function createCart($customerId) {
        $sql = "INSERT INTO cart (cartID, customerID) VALUES (?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $customerId, $customerId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Kiá»ƒm tra giá» hÃ ng Ä‘Ã£ tá»“n táº¡i
    public function cartExists($customerId) {
        $sql = "SELECT cartID FROM cart WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    // ThÃªm sáº£n pháº©m vÃ o giá» hÃ ng
    public function addToCart($customerId, $productId, $quantity, $price) {
        // Táº¡o giá» hÃ ng náº¿u chÆ°a cÃ³
        if (!$this->cartExists($customerId)) {
            $this->createCart($customerId);
        }
        
        // Kiá»ƒm tra sáº£n pháº©m Ä‘Ã£ cÃ³ trong giá» hÃ ng chÆ°a
        $sql = "SELECT quantity FROM cart_items WHERE cartID = ? AND productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $customerId, $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // Cáº­p nháº­t sá»‘ lÆ°á»£ng
            $row = mysqli_fetch_assoc($result);
            $newQuantity = $row['quantity'] + $quantity;
            $sql = "UPDATE cart_items SET quantity = ?, price = ? WHERE cartID = ? AND productID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "idii", $newQuantity, $price, $customerId, $productId);
        } else {
            // ThÃªm má»›i
            $sql = "INSERT INTO cart_items (cartID, productID, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiid", $customerId, $productId, $quantity, $price);
        }
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Láº¥y danh sÃ¡ch sáº£n pháº©m trong giá» hÃ ng
    public function getCartItems($customerId) {
        $sql = "SELECT ci.*, p.productName, p.image, p.stockQuantity 
                FROM cart_items ci 
                INNER JOIN product p ON ci.productID = p.productID 
                WHERE ci.cartID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
        return $items;
    }
    
    // Cáº­p nháº­t sá»‘ lÆ°á»£ng sáº£n pháº©m trong giá» hÃ ng
    public function updateCartItem($customerId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($customerId, $productId);
        }
        
        $sql = "UPDATE cart_items SET quantity = ? WHERE cartID = ? AND productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $quantity, $customerId, $productId);
        return mysqli_stmt_execute($stmt);
    }
    
    // XÃ³a sáº£n pháº©m khá»i giá» hÃ ng
    public function removeFromCart($customerId, $productId) {
        $sql = "DELETE FROM cart_items WHERE cartID = ? AND productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $customerId, $productId);
        return mysqli_stmt_execute($stmt);
    }
    
    // XÃ³a toÃ n bá»™ giá» hÃ ng
    public function clearCart($customerId) {
        $sql = "DELETE FROM cart_items WHERE cartID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        return mysqli_stmt_execute($stmt);
    }
    
    // TÃ­nh tá»•ng giÃ¡ trá»‹ giá» hÃ ng
    public function getCartTotal($customerId) {
        $sql = "SELECT SUM(quantity * price) as total FROM cart_items WHERE cartID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }
    
    // Äáº¿m sá»‘ sáº£n pháº©m trong giá» hÃ ng
    public function getCartItemCount($customerId) {
        $sql = "SELECT SUM(quantity) as count FROM cart_items WHERE cartID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['count'] ?? 0;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $db = new clsKetNoi();
            $db->dongKetNoi($this->conn);
        }
    }
}
?>
