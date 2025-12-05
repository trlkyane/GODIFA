<?php
session_name('GODIFA_USER_SESSION');
session_start();

require_once __DIR__ . '/model/database.php';
$conn = Database::getInstance()->getConnection();

echo "<h2>Tìm đơn hàng có thể yêu cầu hoàn trả</h2>";

if (!isset($_SESSION['customer_id'])) {
    echo "<p style='color: red'>Vui lòng đăng nhập</p>";
    exit;
}

$customerID = $_SESSION['customer_id'];

// Tìm các đơn hàng đã hoàn thành và chưa có yêu cầu hoàn trả
$sql = "SELECT orderID, orderDate, deliveryStatus, paymentStatus, returnStatus, totalAmount 
        FROM `order` 
        WHERE customerID = ? 
        AND deliveryStatus = 'Hoàn thành'
        AND (returnStatus = 'Không' OR returnStatus IS NULL)
        ORDER BY orderDate DESC
        LIMIT 10";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $customerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green'>✅ Tìm thấy " . mysqli_num_rows($result) . " đơn hàng có thể yêu cầu hoàn trả:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>OrderID</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Delivery Status</th><th>Return Status</th><th>Hành động</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>#" . $row['orderID'] . "</td>";
        echo "<td>" . $row['orderDate'] . "</td>";
        echo "<td>" . number_format($row['totalAmount']) . " đ</td>";
        echo "<td>" . $row['deliveryStatus'] . "</td>";
        echo "<td>" . ($row['returnStatus'] ?? 'Không') . "</td>";
        echo "<td><a href='view/account/order_history.php' target='_blank'>Xem đơn hàng</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Bạn có thể test chức năng hoàn trả với các đơn hàng trên.</strong></p>";
} else {
    echo "<p style='color: orange'>⚠️ Không có đơn hàng nào đã hoàn thành để test.</p>";
    
    // Hiển thị tất cả đơn hàng
    echo "<h3>Tất cả đơn hàng của bạn:</h3>";
    $sqlAll = "SELECT orderID, orderDate, deliveryStatus, paymentStatus, returnStatus, totalAmount 
               FROM `order` 
               WHERE customerID = ? 
               ORDER BY orderDate DESC 
               LIMIT 10";
    
    $stmtAll = mysqli_prepare($conn, $sqlAll);
    mysqli_stmt_bind_param($stmtAll, "i", $customerID);
    mysqli_stmt_execute($stmtAll);
    $resultAll = mysqli_stmt_get_result($stmtAll);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>OrderID</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Delivery Status</th><th>Return Status</th></tr>";
    
    while ($row = mysqli_fetch_assoc($resultAll)) {
        $canReturn = ($row['deliveryStatus'] === 'Hoàn thành' && ($row['returnStatus'] === 'Không' || $row['returnStatus'] === null));
        $style = $canReturn ? "background: #d4edda" : "";
        
        echo "<tr style='$style'>";
        echo "<td>#" . $row['orderID'] . "</td>";
        echo "<td>" . $row['orderDate'] . "</td>";
        echo "<td>" . number_format($row['totalAmount']) . " đ</td>";
        echo "<td>" . $row['deliveryStatus'] . "</td>";
        echo "<td>" . ($row['returnStatus'] ?? 'Không') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Gợi ý:</strong> Để test, bạn cần đổi deliveryStatus của một đơn hàng thành 'Hoàn thành'</p>";
    echo "<form method='POST'>";
    echo "Chọn orderID: <select name='orderID'>";
    
    mysqli_data_seek($resultAll, 0);
    while ($row = mysqli_fetch_assoc($resultAll)) {
        echo "<option value='" . $row['orderID'] . "'>Order #" . $row['orderID'] . "</option>";
    }
    echo "</select> ";
    echo "<button type='submit' name='update_status'>Đổi thành 'Hoàn thành'</button>";
    echo "</form>";
}

// Xử lý update status để test
if (isset($_POST['update_status'])) {
    $orderID = intval($_POST['orderID']);
    $sqlUpdate = "UPDATE `order` SET deliveryStatus = 'Hoàn thành' WHERE orderID = ? AND customerID = ?";
    $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "ii", $orderID, $customerID);
    
    if (mysqli_stmt_execute($stmtUpdate)) {
        echo "<p style='color: green'>✅ Đã cập nhật đơn #$orderID thành 'Hoàn thành'. Refresh trang để xem.</p>";
        echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
    }
}
?>