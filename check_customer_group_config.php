<?php
/**
 * Check Customer Group Configuration
 * File: check_customer_group_config.php
 */

require_once 'model/database.php';

$db = new clsKetNoi();
$conn = $db->moKetNoi();

echo "<h1>Kiểm tra Cấu hình Nhóm Khách Hàng</h1>";
echo "<hr>";

// 1. Hiển thị cấu hình nhóm
echo "<h2>1. Cấu hình Nhóm (customer_group)</h2>";
$sql = "SELECT * FROM customer_group ORDER BY minSpent ASC";
$result = mysqli_query($conn, $sql);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>groupID</th><th>groupName</th><th>minSpent</th><th>maxSpent</th><th>description</th><th>color</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $minSpent = $row['minSpent'] !== null ? number_format($row['minSpent']) : '<span style="color:red">NULL ❌</span>';
    $maxSpent = $row['maxSpent'] ? number_format($row['maxSpent']) : 'NULL';
    echo "<tr>";
    echo "<td>" . $row['groupID'] . "</td>";
    echo "<td>" . $row['groupName'] . "</td>";
    echo "<td>" . $minSpent . "</td>";
    echo "<td>" . $maxSpent . "</td>";
    echo "<td>" . $row['description'] . "</td>";
    echo "<td style='background: " . $row['color'] . "'>" . $row['color'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Kiểm tra payment status values
echo "<h2>2. Các giá trị paymentStatus trong database</h2>";
$sql = "SELECT DISTINCT paymentStatus, COUNT(*) as count FROM `order` GROUP BY paymentStatus";
$result = mysqli_query($conn, $sql);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>paymentStatus</th><th>Số đơn</th><th>Hex</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $hex = bin2hex($row['paymentStatus']);
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['paymentStatus']) . "</td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "<td><code>" . $hex . "</code></td>";
    echo "</tr>";
}
echo "</table>";

// 3. Test query cho từng khách hàng
echo "<h2>3. Chi tiết phân nhóm từng khách hàng</h2>";
$sql = "SELECT 
    c.customerID,
    c.customerName,
    c.groupID as currentGroupID,
    cg.groupName as currentGroupName,
    COALESCE(SUM(CASE WHEN o.paymentStatus = 'Đã thanh toán' THEN o.totalAmount ELSE 0 END), 0) as totalSpent
FROM customer c
LEFT JOIN `order` o ON c.customerID = o.customerID
LEFT JOIN customer_group cg ON c.groupID = cg.groupID
GROUP BY c.customerID, c.customerName, c.groupID, cg.groupName
HAVING totalSpent > 0
ORDER BY totalSpent DESC
LIMIT 10";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Tên</th><th>Tổng chi tiêu</th><th>Nhóm hiện tại</th><th>Nhóm đúng (query)</th><th>Action</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $customerID = $row['customerID'];
        $totalSpent = $row['totalSpent'];
        
        // Query tìm nhóm đúng với CAST về DECIMAL
        $sqlGroup = "SELECT groupID, groupName, minSpent, maxSpent 
                     FROM customer_group 
                     WHERE CAST($totalSpent AS DECIMAL(15,2)) >= CAST(minSpent AS DECIMAL(15,2))
                     AND (maxSpent IS NULL OR CAST($totalSpent AS DECIMAL(15,2)) <= CAST(maxSpent AS DECIMAL(15,2)))
                     ORDER BY CAST(minSpent AS DECIMAL(15,2)) DESC 
                     LIMIT 1";
        $resultGroup = mysqli_query($conn, $sqlGroup);
        $correctGroup = mysqli_fetch_assoc($resultGroup);
        
        $isCorrect = ($row['currentGroupID'] == ($correctGroup['groupID'] ?? null));
        $color = $isCorrect ? 'lightgreen' : 'lightcoral';
        
        echo "<tr style='background: $color;'>";
        echo "<td>" . $customerID . "</td>";
        echo "<td>" . htmlspecialchars($row['customerName']) . "</td>";
        echo "<td>" . number_format($totalSpent) . " VNĐ</td>";
        echo "<td>" . ($row['currentGroupName'] ?? 'NULL') . " (ID: " . ($row['currentGroupID'] ?? 'NULL') . ")</td>";
        
        if ($correctGroup) {
            echo "<td>" . $correctGroup['groupName'] . " (ID: " . $correctGroup['groupID'] . ")<br>";
            echo "<small>Range: " . number_format($correctGroup['minSpent']) . " - " . ($correctGroup['maxSpent'] ? number_format($correctGroup['maxSpent']) : '∞') . "</small></td>";
        } else {
            echo "<td style='color: red;'>KHÔNG TÌM THẤY NHÓM!</td>";
        }
        
        echo "<td>";
        if (!$isCorrect && $correctGroup) {
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='fix_customer' value='$customerID'>";
            echo "<input type='hidden' name='new_group' value='" . $correctGroup['groupID'] . "'>";
            echo "<button type='submit'>Fix ngay</button>";
            echo "</form>";
        } else {
            echo "OK ✓";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Xử lý form fix
if (isset($_POST['fix_customer']) && isset($_POST['new_group'])) {
    $customerID = intval($_POST['fix_customer']);
    $newGroup = intval($_POST['new_group']);
    
    $sql = "UPDATE customer SET groupID = ? WHERE customerID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $newGroup, $customerID);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Đã cập nhật nhóm cho khách hàng #$customerID!'); window.location.reload();</script>";
    }
}

// 4. Test trigger với hex comparison
echo "<h2>4. Khuyến nghị FIX</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107;'>";
echo "<h3>🔧 Cách fix trigger encoding:</h3>";
echo "<ol>";
echo "<li><strong>Vấn đề:</strong> Trigger đang so sánh với string lỗi encoding <code>'???? thanh to??n'</code></li>";
echo "<li><strong>Giải pháp 1 (Khuyến nghị):</strong> Drop và tạo lại trigger với UTF-8:<br>";
echo "<pre style='background: #f8f9fa; padding: 10px;'>";
echo "DELIMITER \$\$
DROP TRIGGER IF EXISTS after_order_update_assign_group\$\$
CREATE TRIGGER after_order_update_assign_group 
AFTER UPDATE ON \`order\` 
FOR EACH ROW 
BEGIN
    DECLARE customer_total_spent DECIMAL(15,2);
    DECLARE best_group_id INT;
    
    IF NEW.paymentStatus != OLD.paymentStatus THEN
        SELECT COALESCE(SUM(totalAmount), 0) INTO customer_total_spent
        FROM \`order\`
        WHERE customerID = NEW.customerID
          AND paymentStatus = 'Đã thanh toán';
        
        SELECT groupID INTO best_group_id
        FROM customer_group
        WHERE CAST(customer_total_spent AS DECIMAL(15,2)) >= CAST(minSpent AS DECIMAL(15,2))
          AND (maxSpent IS NULL OR CAST(customer_total_spent AS DECIMAL(15,2)) <= CAST(maxSpent AS DECIMAL(15,2)))
        ORDER BY CAST(minSpent AS DECIMAL(15,2)) DESC
        LIMIT 1;
        
        IF best_group_id IS NOT NULL THEN
            UPDATE customer SET groupID = best_group_id WHERE customerID = NEW.customerID;
        END IF;
    END IF;
END\$\$
DELIMITER ;
</pre>";
echo "</li>";
echo "<li><strong>Giải pháp 2:</strong> Chạy stored procedure thủ công:<br>";
echo "<code>CALL auto_assign_customer_groups_by_spending();</code></li>";
echo "</ol>";
echo "</div>";

$db->dongKetNoi($conn);
?>
