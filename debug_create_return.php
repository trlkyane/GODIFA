<?php
/**
 * Debug API create_return_request.php
 */
session_name('GODIFA_USER_SESSION');
session_start();

echo "<h2>Debug: Create Return Request API</h2>";

// 1. Kiểm tra session
echo "<h3>1. Session hiện tại:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (!isset($_SESSION['customer_id'])) {
    echo "<p style='color: red'>❌ Session customer_id KHÔNG TỒN TẠI!</p>";
    echo "<p>Hãy đăng nhập tại: <a href='view/auth/customer-login.php'>Login</a></p>";
} else {
    echo "<p style='color: green'>✅ customer_id: " . $_SESSION['customer_id'] . "</p>";
    
    // 2. Test với dữ liệu giả
    echo "<h3>2. Test API với dữ liệu mẫu:</h3>";
    
    require_once __DIR__ . '/model/database.php';
    $conn = Database::getInstance()->getConnection();
    
    $customerID = $_SESSION['customer_id'];
    $testOrderID = 191; // Thay bằng orderID thực tế
    $testReason = "Test yêu cầu hoàn trả từ debug";
    
    // Kiểm tra đơn hàng
    $sqlCheck = "SELECT o.orderID, o.deliveryStatus, o.paymentStatus, o.returnStatus 
                 FROM `order` o 
                 WHERE o.orderID = ? AND o.customerID = ?";
    $stmt = mysqli_prepare($conn, $sqlCheck);
    mysqli_stmt_bind_param($stmt, "ii", $testOrderID, $customerID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    
    if (!$order) {
        echo "<p style='color: red'>❌ Không tìm thấy đơn hàng #$testOrderID thuộc về customer #$customerID</p>";
        
        // Hiển thị các đơn hàng của customer
        $sqlOrders = "SELECT orderID, deliveryStatus, returnStatus FROM `order` WHERE customerID = ?";
        $stmt2 = mysqli_prepare($conn, $sqlOrders);
        mysqli_stmt_bind_param($stmt2, "i", $customerID);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);
        
        echo "<p>Các đơn hàng của customer #$customerID:</p>";
        echo "<ul>";
        while ($row = mysqli_fetch_assoc($result2)) {
            echo "<li>Order #" . $row['orderID'] . " - Delivery: " . $row['deliveryStatus'] . " - Return: " . ($row['returnStatus'] ?? 'Không') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green'>✅ Tìm thấy đơn hàng:</p>";
        echo "<pre>";
        print_r($order);
        echo "</pre>";
        
        // Kiểm tra điều kiện
        if ($order['deliveryStatus'] !== 'Hoàn thành') {
            echo "<p style='color: orange'>⚠️ Đơn hàng chưa hoàn thành (deliveryStatus = '" . $order['deliveryStatus'] . "')</p>";
        }
        
        if (isset($order['returnStatus']) && $order['returnStatus'] !== 'Không') {
            echo "<p style='color: orange'>⚠️ Đơn hàng đã có yêu cầu hoàn trả (returnStatus = '" . $order['returnStatus'] . "')</p>";
        }
        
        // Test INSERT
        if ($order['deliveryStatus'] === 'Hoàn thành' && (!isset($order['returnStatus']) || $order['returnStatus'] === 'Không')) {
            echo "<h3>3. Thực hiện INSERT:</h3>";
            
            $imagesJson = json_encode([]);
            $sqlInsert = "INSERT INTO return_requests (orderID, customerID, reason, images) 
                          VALUES (?, ?, ?, ?)";
            $stmtInsert = mysqli_prepare($conn, $sqlInsert);
            mysqli_stmt_bind_param($stmtInsert, "iiss", $testOrderID, $customerID, $testReason, $imagesJson);
            
            if (mysqli_stmt_execute($stmtInsert)) {
                $returnID = mysqli_insert_id($conn);
                echo "<p style='color: green'>✅ INSERT thành công! returnID: $returnID</p>";
                
                // Kiểm tra trigger đã chạy chưa
                $sqlCheckReturn = "SELECT returnStatus FROM `order` WHERE orderID = ?";
                $stmtCheck = mysqli_prepare($conn, $sqlCheckReturn);
                mysqli_stmt_bind_param($stmtCheck, "i", $testOrderID);
                mysqli_stmt_execute($stmtCheck);
                $resultCheck = mysqli_stmt_get_result($stmtCheck);
                $orderAfter = mysqli_fetch_assoc($resultCheck);
                
                echo "<p>returnStatus sau INSERT: '" . ($orderAfter['returnStatus'] ?? 'NULL') . "'</p>";
                
                if ($orderAfter['returnStatus'] === 'Đang yêu cầu') {
                    echo "<p style='color: green'>✅ Trigger đã chạy thành công!</p>";
                } else {
                    echo "<p style='color: red'>❌ Trigger không chạy hoặc chưa cập nhật returnStatus</p>";
                }
            } else {
                echo "<p style='color: red'>❌ INSERT thất bại: " . mysqli_error($conn) . "</p>";
            }
        }
    }
}
?>