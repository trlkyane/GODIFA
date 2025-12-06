<?php
/**
 * Check Customer Status Column
 * File: check_customer_status_column.php
 */

require_once 'model/database.php';

$db = new clsKetNoi();
$conn = $db->moKetNoi();

echo "<h1>Kiểm tra Cột Status trong bảng Customer</h1>";
echo "<hr>";

// 1. Kiểm tra cấu trúc bảng
echo "<h2>1. Cấu trúc bảng customer</h2>";
$sql = "DESCRIBE customer";
$result = mysqli_query($conn, $sql);

$hasStatusColumn = false;
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Field'] === 'status') {
        $hasStatusColumn = true;
        echo "<tr style='background: lightgreen;'>";
    } else {
        echo "<tr>";
    }
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

if (!$hasStatusColumn) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 2px solid red; margin: 20px 0;'>";
    echo "<h3 style='color: red;'>❌ LỖI: Cột 'status' KHÔNG TỒN TẠI!</h3>";
    echo "<p>Bạn cần thêm cột 'status' vào bảng customer:</p>";
    echo "<pre style='background: #f8f9fa; padding: 10px;'>";
    echo "ALTER TABLE customer \nADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 \nCOMMENT '1=Hoạt động, 0=Đã khóa' \nAFTER groupID;";
    echo "</pre>";
    echo "<button onclick=\"copySQL()\">Copy SQL</button>";
    echo "</div>";
} else {
    echo "<div style='background: #e6ffe6; padding: 15px; border: 2px solid green; margin: 20px 0;'>";
    echo "<h3 style='color: green;'>✅ Cột 'status' tồn tại!</h3>";
    echo "</div>";
}

// 2. Kiểm tra dữ liệu
echo "<h2>2. Dữ liệu khách hàng (10 khách gần nhất)</h2>";
$sql = "SELECT customerID, customerName, email, phone, " . 
       ($hasStatusColumn ? "status" : "'N/A' as status") . 
       " FROM customer ORDER BY customerID DESC LIMIT 10";
$result = mysqli_query($conn, $sql);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Tên</th><th>Email</th><th>Phone</th><th>Status</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $statusColor = $hasStatusColumn && $row['status'] == 0 ? 'lightcoral' : 'lightgreen';
    $statusText = $hasStatusColumn ? ($row['status'] == 1 ? 'Hoạt động' : 'Đã khóa') : 'N/A';
    echo "<tr>";
    echo "<td>" . $row['customerID'] . "</td>";
    echo "<td>" . htmlspecialchars($row['customerName']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
    echo "<td style='background: $statusColor;'>" . $statusText . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Test update status
if ($hasStatusColumn) {
    echo "<h2>3. Test Toggle Status</h2>";
    echo "<form method='post'>";
    echo "<label>Chọn khách hàng: </label>";
    echo "<select name='test_customer_id'>";
    
    $sql = "SELECT customerID, customerName, status FROM customer ORDER BY customerID DESC LIMIT 10";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $statusText = $row['status'] == 1 ? 'Hoạt động' : 'Đã khóa';
        echo "<option value='" . $row['customerID'] . "'>" . 
             $row['customerID'] . " - " . htmlspecialchars($row['customerName']) . 
             " (Hiện: $statusText)</option>";
    }
    echo "</select>";
    echo " <button type='submit' name='test_toggle'>Toggle Status</button>";
    echo "</form>";
    
    if (isset($_POST['test_toggle'])) {
        $testID = intval($_POST['test_customer_id']);
        $sql = "UPDATE customer SET status = 1 - status WHERE customerID = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $testID);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Đã toggle status cho customer #$testID!'); window.location.reload();</script>";
        }
    }
}

$db->dongKetNoi($conn);
?>

<script>
function copySQL() {
    const sql = "ALTER TABLE customer \nADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 \nCOMMENT '1=Hoạt động, 0=Đã khóa' \nAFTER groupID;";
    navigator.clipboard.writeText(sql);
    alert('Đã copy SQL vào clipboard!');
}
</script>
