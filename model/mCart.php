<?php
require_once __DIR__ . '/database.php';

class Cart {
    private $conn;
    
    public function __construct() {
        $db = new clsKetNoi();
        $this->conn = $db->moKetNoi();
    }
    
    // Tạo giỏ hàng mới cho khách hàng
    public function createCart($customerId) {
        $sql = "INSERT INTO cart (cartID, customerID) VALUES (?, ?)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $customerId, $customerId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Kiểm tra giỏ hàng đã tồn tại
    public function cartExists($customerId) {
        $sql = "SELECT cartID FROM cart WHERE customerID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    // Thêm sản phẩm vào giỏ hàng
    public function addToCart($customerId, $productId, $quantity, $price) {
        // Tạo giỏ hàng nếu chưa có
        if (!$this->cartExists($customerId)) {
            $this->createCart($customerId);
        }
        
        // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
        $sql = "SELECT quantity FROM cart_items WHERE cartID = ? AND productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $customerId, $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // Cập nhật số lượng
            $row = mysqli_fetch_assoc($result);
            $newQuantity = $row['quantity'] + $quantity;
            $sql = "UPDATE cart_items SET quantity = ?, price = ? WHERE cartID = ? AND productID = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "idii", $newQuantity, $price, $customerId, $productId);
        } else {
            // Thêm mới
            $sql = "INSERT INTO cart_items (cartID, productID, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiid", $customerId, $productId, $quantity, $price);
        }
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Lấy danh sách sản phẩm trong giỏ hàng
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
    
    // Cập nhật số lượng sản phẩm trong giỏ hàng
    public function updateCartItem($customerId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($customerId, $productId);
        }
        
        $sql = "UPDATE cart_items SET quantity = ? WHERE cartID = ? AND productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $quantity, $customerId, $productId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Xóa sản phẩm khỏi giỏ hàng
    public function removeFromCart($customerId, $productId) {
        $sql = "DELETE FROM cart_items WHERE cartID = ? AND productID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $customerId, $productId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Xóa toàn bộ giỏ hàng
    public function clearCart($customerId) {
        $sql = "DELETE FROM cart_items WHERE cartID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        return mysqli_stmt_execute($stmt);
    }
    
    // Tính tổng giá trị giỏ hàng
    public function getCartTotal($customerId) {
        $sql = "SELECT SUM(quantity * price) as total FROM cart_items WHERE cartID = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }
    
    // Đếm số sản phẩm trong giỏ hàng
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
