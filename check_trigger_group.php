<?php
/**
 * Check Auto Assign Customer Group Trigger
 * File: check_trigger_group.php
 */

require_once 'model/database.php';

$db = new clsKetNoi();
$conn = $db->moKetNoi();

echo "<h1>Kiểm tra Trigger Auto Assign Group</h1>";
echo "<hr>";

// 1. Kiểm tra trigger có tồn tại không
echo "<h2>1. Danh sách Triggers</h2>";
$sql = "SHOW TRIGGERS WHERE `Trigger` LIKE '%group%' OR `Trigger` LIKE '%assign%'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Trigger Name</th><th>Event</th><th>Table</th><th>Timing</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Trigger'] . "</td>";
        echo "<td>" . $row['Event'] . "</td>";
        echo "<td>" . $row['Table'] . "</td>";
        echo "<td>" . $row['Timing'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ KHÔNG TÌM THẤY TRIGGER auto_assign!</p>";
    echo "<p>👉 Cần chạy lại file SQL để tạo trigger.</p>";
}

// 2. Xem định nghĩa trigger
echo "<h2>2. Định nghĩa Trigger 'after_order_update_assign_group'</h2>";
$sql = "SHOW CREATE TRIGGER after_order_update_assign_group";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "<pre>" . htmlspecialchars($row['SQL Original Statement']) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Trigger 'after_order_update_assign_group' KHÔNG TỒN TẠI!</p>";
    echo "<h3>Tạo trigger:</h3>";
    echo "<pre>";
    echo "DELIMITER $$
DROP TRIGGER IF EXISTS after_order_update_assign_group$$
CREATE TRIGGER after_order_update_assign_group AFTER UPDATE ON `order` FOR EACH ROW 
BEGIN
    DECLARE customer_total_spent DECIMAL(15,2);
    DECLARE best_group_id INT;
    
    -- Chỉ chạy khi payment status thay đổi
    IF NEW.paymentStatus != OLD.paymentStatus THEN
        -- Tính tổng chi tiêu của customer (không tính đơn hủy)
        SELECT COALESCE(SUM(totalAmount), 0) INTO customer_total_spent
        FROM `order`
        WHERE customerID = NEW.customerID
          AND paymentStatus = 'Đã thanh toán';
        
        -- Tìm nhóm phù hợp nhất
        SELECT groupID INTO best_group_id
        FROM customer_group
        WHERE customer_total_spent >= minSpent
          AND (maxSpent IS NULL OR customer_total_spent <= maxSpent)
        ORDER BY minSpent DESC
        LIMIT 1;
        
        -- Cập nhật nhóm cho customer
        IF best_group_id IS NOT NULL THEN
            UPDATE customer
            SET groupID = best_group_id
            WHERE customerID = NEW.customerID;
        END IF;
    END IF;
END$$
DELIMITER ;
";
    echo "</pre>";
    echo "<p>Copy đoạn SQL trên vào phpMyAdmin > SQL và chạy để tạo trigger.</p>";
}

// 3. Test logic phân nhóm
echo "<h2>3. Test Logic Phân Nhóm</h2>";
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
    echo "<tr><th>ID</th><th>Tên</th><th>Tổng chi tiêu</th><th>Nhóm hiện tại</th><th>Nhóm đúng</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $totalSpent = $row['totalSpent'];
        
        // Tìm nhóm đúng dựa trên chi tiêu
        $sqlGroup = "SELECT groupID, groupName FROM customer_group 
                     WHERE $totalSpent >= minSpent 
                     AND (maxSpent IS NULL OR $totalSpent <= maxSpent)
                     ORDER BY minSpent DESC LIMIT 1";
        $resultGroup = mysqli_query($conn, $sqlGroup);
        $correctGroup = mysqli_fetch_assoc($resultGroup);
        
        $isCorrect = ($row['currentGroupID'] == ($correctGroup['groupID'] ?? null));
        $color = $isCorrect ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>" . $row['customerID'] . "</td>";
        echo "<td>" . $row['customerName'] . "</td>";
        echo "<td>" . number_format($totalSpent) . " VNĐ</td>";
        echo "<td>" . ($row['currentGroupName'] ?? 'NULL') . " (ID: " . ($row['currentGroupID'] ?? 'NULL') . ")</td>";
        echo "<td style='color: $color;'>" . ($correctGroup['groupName'] ?? 'N/A') . " (ID: " . ($correctGroup['groupID'] ?? 'N/A') . ")</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không có khách hàng nào có đơn hàng.</p>";
}

// 4. Hướng dẫn test
echo "<h2>4. Hướng dẫn Test</h2>";
echo "<ol>";
echo "<li>Nếu trigger chưa tồn tại → Copy SQL ở mục 2 và chạy trong phpMyAdmin</li>";
echo "<li>Tạo đơn hàng test và đổi trạng thái sang 'Đã thanh toán'</li>";
echo "<li>Reload trang này để xem nhóm có tự động cập nhật không</li>";
echo "<li>Hoặc chạy stored procedure thủ công: <code>CALL auto_assign_customer_groups_by_spending();</code></li>";
echo "</ol>";

$db->dongKetNoi($conn);
?>
