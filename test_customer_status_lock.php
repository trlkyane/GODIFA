<?php
/**
 * Test Customer Status Lock
 * File: test_customer_status_lock.php
 */

session_name('GODIFA_USER_SESSION');
session_start();

require_once 'model/database.php';
require_once 'model/mCustomer.php';

$db = new clsKetNoi();
$conn = $db->moKetNoi();

echo "<h1>Test Tính năng Khóa Tài khoản Khách hàng</h1>";
echo "<hr>";

// 1. Danh sách khách hàng
echo "<h2>1. Danh sách khách hàng</h2>";
$sql = "SELECT customerID, customerName, email, status FROM customer ORDER BY customerID DESC LIMIT 10";
$result = mysqli_query($conn, $sql);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Tên</th><th>Email</th><th>Status</th><th>Action</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $statusText = $row['status'] == 1 ? '✅ Hoạt động' : '🔒 Đã khóa';
    $statusColor = $row['status'] == 1 ? 'lightgreen' : 'lightcoral';
    $btnText = $row['status'] == 1 ? 'Khóa' : 'Mở khóa';
    
    echo "<tr style='background: $statusColor;'>";
    echo "<td>" . $row['customerID'] . "</td>";
    echo "<td>" . htmlspecialchars($row['customerName']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . $statusText . "</td>";
    echo "<td>";
    echo "<form method='post' style='display:inline;'>";
    echo "<input type='hidden' name='toggle_id' value='" . $row['customerID'] . "'>";
    echo "<input type='hidden' name='current_status' value='" . $row['status'] . "'>";
    echo "<button type='submit'>$btnText</button>";
    echo "</form>";
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// Xử lý toggle
if (isset($_POST['toggle_id'])) {
    $customerID = intval($_POST['toggle_id']);
    $currentStatus = intval($_POST['current_status']);
    $newStatus = $currentStatus == 1 ? 0 : 1;
    
    $sql = "UPDATE customer SET status = ? WHERE customerID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $newStatus, $customerID);
    
    if (mysqli_stmt_execute($stmt)) {
        $statusText = $newStatus == 1 ? 'Mở khóa' : 'Khóa';
        echo "<script>alert('Đã $statusText khách hàng #$customerID!'); window.location.href='test_customer_status_lock.php';</script>";
    }
}

// 2. Test đăng nhập
echo "<h2>2. Test Đăng nhập (Khách hàng bị khóa)</h2>";
echo "<form method='post'>";
echo "<label>Email: </label>";
echo "<input type='email' name='test_email' required>";
echo " <label>Password: </label>";
echo "<input type='password' name='test_password' required>";
echo " <button type='submit' name='test_login'>Test Login</button>";
echo "</form>";

if (isset($_POST['test_login'])) {
    $email = $_POST['test_email'];
    $password = $_POST['test_password'];
    
    $customerModel = new Customer();
    $customer = $customerModel->login($email, $password);
    
    echo "<div style='padding: 15px; margin: 10px 0; border: 2px solid;'>";
    if ($customer) {
        // Kiểm tra status
        if (isset($customer['status']) && $customer['status'] == 0) {
            echo "<h3 style='color: red;'>❌ ĐĂNG NHẬP THẤT BẠI</h3>";
            echo "<p>Tài khoản <strong>" . htmlspecialchars($customer['customerName']) . "</strong> đã bị khóa!</p>";
            echo "<p>Logic hoạt động đúng: Khách hàng bị khóa không thể đăng nhập ✅</p>";
        } else {
            echo "<h3 style='color: green;'>✅ ĐĂNG NHẬP THÀNH CÔNG</h3>";
            echo "<p>Chào mừng <strong>" . htmlspecialchars($customer['customerName']) . "</strong>!</p>";
            echo "<p>Status: " . ($customer['status'] == 1 ? 'Hoạt động' : 'Đã khóa') . "</p>";
        }
    } else {
        echo "<h3 style='color: red;'>❌ SAI MẬT KHẨU</h3>";
        echo "<p>Email hoặc mật khẩu không đúng!</p>";
    }
    echo "</div>";
}

// 3. Hướng dẫn
echo "<h2>3. Hướng dẫn Test</h2>";
echo "<ol>";
echo "<li>Chọn 1 khách hàng ở bảng trên và click <strong>Khóa</strong></li>";
echo "<li>Thử đăng nhập bằng email/password của khách đó ở form test</li>";
echo "<li>Kết quả mong đợi: <strong>❌ Tài khoản đã bị khóa!</strong></li>";
echo "<li>Click <strong>Mở khóa</strong> và thử lại</li>";
echo "<li>Kết quả mong đợi: <strong>✅ Đăng nhập thành công!</strong></li>";
echo "</ol>";

echo "<hr>";
echo "<h2>4. Test trên trang thật</h2>";
echo "<ol>";
echo "<li>Vào <a href='/GODIFA/admin/index.php?page=customers' target='_blank'>Admin > Customers</a></li>";
echo "<li>Click nút 🔒 (khóa) hoặc 🔓 (mở khóa) trên bảng</li>";
echo "<li>Thử đăng nhập tại <a href='/GODIFA/view/auth/customer-login.php' target='_blank'>Customer Login</a></li>";
echo "</ol>";

$db->dongKetNoi($conn);
?>
