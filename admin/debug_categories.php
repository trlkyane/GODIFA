<?php
/**
 * DEBUG: Kiểm tra dữ liệu danh mục
 * Chạy file này để debug: http://localhost/GODIFA/admin/debug_categories.php
 */

require_once __DIR__ . '/../model/mCategory.php';

echo "<h1>🔍 Debug Categories</h1>";
echo "<hr>";

// Test kết nối database
echo "<h2>1. Kiểm tra kết nối Database</h2>";
$db = new clsKetNoi();
$conn = $db->moKetNoi();
if ($conn) {
    echo "✅ Kết nối thành công!<br>";
} else {
    echo "❌ Lỗi kết nối database!<br>";
    exit;
}

// Test Model
echo "<h2>2. Kiểm tra Model Category</h2>";
$categoryModel = new Category();
echo "✅ Model Category được khởi tạo thành công!<br>";

// Kiểm tra cấu trúc bảng
echo "<h2>3. Cấu trúc bảng category</h2>";
$sql = "DESCRIBE category";
$result = mysqli_query($conn, $sql);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Kiểm tra có cột status không
$hasStatus = false;
$result = mysqli_query($conn, "DESCRIBE category");
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Field'] == 'status') {
        $hasStatus = true;
        break;
    }
}

if ($hasStatus) {
    echo "<p style='color: green;'>✅ Bảng category ĐÃ CÓ cột 'status'</p>";
} else {
    echo "<p style='color: red;'>❌ Bảng category CHƯA CÓ cột 'status'</p>";
    echo "<p><strong>⚠️ Bạn cần import file: data/IMPORT_THIS_FIRST.sql</strong></p>";
}

// Lấy danh sách danh mục
echo "<h2>4. Danh sách danh mục hiện tại</h2>";
$categories = $categoryModel->getAllCategories();
echo "<p>Số lượng danh mục: <strong>" . count($categories) . "</strong></p>";

if (empty($categories)) {
    echo "<p style='color: orange;'>⚠️ Chưa có danh mục nào trong database!</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Tên danh mục</th>";
    if ($hasStatus) echo "<th>Status</th>";
    echo "<th>Số sản phẩm</th></tr>";
    
    foreach ($categories as $category) {
        $productCount = $categoryModel->countProductsInCategory($category['categoryID']);
        echo "<tr>";
        echo "<td>" . $category['categoryID'] . "</td>";
        echo "<td>" . htmlspecialchars($category['categoryName']) . "</td>";
        if ($hasStatus) {
            $statusText = isset($category['status']) && $category['status'] == 1 ? '✅ Hoạt động' : '🔒 Đã khóa';
            echo "<td>" . $statusText . "</td>";
        }
        echo "<td>" . $productCount . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Đóng kết nối
$db->dongKetNoi($conn);

echo "<hr>";
echo "<h2>5. Kết luận</h2>";
if ($hasStatus && !empty($categories)) {
    echo "<p style='color: green; font-weight: bold;'>✅ Mọi thứ hoạt động bình thường!</p>";
    echo "<p>Bạn có thể truy cập trang quản lý danh mục tại: <a href='index.php?page=categories'>Admin Categories</a></p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Cần khắc phục các vấn đề trên!</p>";
    if (!$hasStatus) {
        echo "<p>👉 Import file: <code>data/IMPORT_THIS_FIRST.sql</code> vào phpMyAdmin</p>";
    }
    if (empty($categories)) {
        echo "<p>👉 Thêm danh mục vào database</p>";
    }
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 { color: #333; }
    h2 { 
        color: #555; 
        margin-top: 30px;
        background: #e3f2fd;
        padding: 10px;
        border-left: 4px solid #2196F3;
    }
    table {
        background: white;
        margin: 10px 0;
    }
    code {
        background: #f0f0f0;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: monospace;
    }
</style>
