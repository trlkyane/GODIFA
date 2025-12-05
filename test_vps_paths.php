<?php
/**
 * Test Toàn Bộ Đường Dẫn VPS
 * Run: http://localhost/GODIFA/test_vps_paths.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test VPS Paths - GODIFA</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .section { background: white; padding: 25px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; color: #374151; }
        tr:hover { background: #f9fafb; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
        .icon-success::before { content: '✅ '; }
        .icon-error::before { content: '❌ '; }
        .icon-warning::before { content: '⚠️ '; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .stat-card { background: #f9fafb; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat-label { color: #6b7280; font-size: 14px; margin-top: 8px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<div class='header'>
    <h1 style='font-size: 28px; margin-bottom: 10px;'>🔍 Kiểm Tra Đường Dẫn VPS - GODIFA</h1>
    <p style='opacity: 0.9;'>Tự động kiểm tra tất cả đường dẫn trong hệ thống</p>
</div>";

// 1. Environment Check
echo "<div class='section'>
    <h2 style='margin-bottom: 15px; color: #1f2937;'>1. 🌍 Môi Trường Hiện Tại</h2>";

$host = $_SERVER['HTTP_HOST'] ?? 'unknown';
$isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

echo "<table>
    <tr><td width='200'><strong>Host</strong></td><td class='info'>{$host}</td></tr>
    <tr><td><strong>Môi trường</strong></td><td>" . ($isLocal ? "<span class='badge badge-warning'>LOCALHOST (Development)</span>" : "<span class='badge badge-success'>VPS (Production)</span>") . "</td></tr>
    <tr><td><strong>BASE_URL</strong></td><td class='info'>" . BASE_URL . "</td></tr>
    <tr><td><strong>SOCKET_SERVER_URL</strong></td><td class='info'>" . (defined('SOCKET_SERVER_URL') ? SOCKET_SERVER_URL : '<span class="error">Chưa định nghĩa</span>') . "</td></tr>
</table>
</div>";

// 2. Files Check
echo "<div class='section'>
    <h2 style='margin-bottom: 15px; color: #1f2937;'>2. 📂 Kiểm Tra Files Đã Sửa</h2>";

$filesToCheck = [
    'admin/pages/statistics.php' => 'Xuất Excel',
    'view/payment/thankyou.php' => 'Thank You Page',
    'view/cart/checkout_qr.php' => 'Checkout QR',
    'view/cart/checkout.php' => 'Checkout Page',
    'admin/pages/product_detail.php' => 'Admin Product Detail',
    'admin/login.php' => 'Admin Login',
    'admin/pages/chat.php' => 'Admin Chat',
    'view/chat/index.php' => 'Customer Chat',
    'public/js/admin_chat_client.js' => 'Admin Chat JS',
    'public/js/chat_client.js' => 'Customer Chat JS',
    'model/ChatModel.php' => 'Chat Model',
    'config/constants.php' => 'Constants Config',
];

$totalFiles = 0;
$checkedFiles = 0;
$errorFiles = 0;

echo "<table>
    <thead>
        <tr>
            <th>File</th>
            <th>Mô tả</th>
            <th>Trạng thái</th>
        </tr>
    </thead>
    <tbody>";

foreach ($filesToCheck as $file => $description) {
    $totalFiles++;
    $fullPath = __DIR__ . '/' . $file;
    $exists = file_exists($fullPath);
    
    if ($exists) {
        $checkedFiles++;
        echo "<tr>
            <td><code>{$file}</code></td>
            <td>{$description}</td>
            <td><span class='badge badge-success icon-success'>Tồn tại</span></td>
        </tr>";
    } else {
        $errorFiles++;
        echo "<tr>
            <td><code>{$file}</code></td>
            <td>{$description}</td>
            <td><span class='badge badge-error icon-error'>Không tìm thấy</span></td>
        </tr>";
    }
}

echo "</tbody></table>";
echo "</div>";

// 3. Path Analysis
echo "<div class='section'>
    <h2 style='margin-bottom: 15px; color: #1f2937;'>3. 🔎 Phân Tích Đường Dẫn</h2>";

$pathChecks = [];

// Check statistics.php
$statsFile = __DIR__ . '/admin/pages/statistics.php';
if (file_exists($statsFile)) {
    $content = file_get_contents($statsFile);
    
    // Check export function
    if (strpos($content, "const baseUrl = '<?php echo BASE_URL; ?>';") !== false) {
        $pathChecks[] = ['Xuất Excel', 'Dùng BASE_URL trong JavaScript', true];
    } else {
        $pathChecks[] = ['Xuất Excel', 'Hardcode /admin/export_statistics.php', false];
    }
    
    // Check image fallback
    if (strpos($content, "onerror=\"this.src='<?php echo BASE_URL; ?>image/no-image.png'\"") !== false) {
        $pathChecks[] = ['Fallback Image', 'Dùng BASE_URL', true];
    } else if (strpos($content, "onerror=\"this.src='/image/no-image.png'\"") !== false) {
        $pathChecks[] = ['Fallback Image', 'Hardcode /image/no-image.png', false];
    }
}

// Check thankyou.php
$thankyouFile = __DIR__ . '/view/payment/thankyou.php';
if (file_exists($thankyouFile)) {
    $content = file_get_contents($thankyouFile);
    if (strpos($content, 'href="<?php echo BASE_URL; ?>"') !== false) {
        $pathChecks[] = ['Thank You Links', 'Dùng BASE_URL', true];
    } else if (strpos($content, 'href="/GODIFA"') !== false) {
        $pathChecks[] = ['Thank You Links', 'Hardcode /GODIFA', false];
    }
}

// Check checkout_qr.php
$checkoutQrFile = __DIR__ . '/view/cart/checkout_qr.php';
if (file_exists($checkoutQrFile)) {
    $content = file_get_contents($checkoutQrFile);
    if (strpos($content, 'href="<?php echo BASE_URL; ?>"') !== false) {
        $pathChecks[] = ['Checkout QR Header', 'Dùng BASE_URL', true];
    } else if (strpos($content, 'href="/GODIFA"') !== false) {
        $pathChecks[] = ['Checkout QR Header', 'Hardcode /GODIFA', false];
    }
}

// Check admin login
$adminLoginFile = __DIR__ . '/admin/login.php';
if (file_exists($adminLoginFile)) {
    $content = file_get_contents($adminLoginFile);
    $hasBaseUrl = strpos($content, '<?php echo BASE_URL; ?>view/auth/customer-login.php') !== false;
    $hasHardcode = strpos($content, 'href="/view/auth/customer-login.php"') !== false;
    
    if ($hasBaseUrl && !$hasHardcode) {
        $pathChecks[] = ['Admin Login Links', 'Dùng BASE_URL', true];
    } else {
        $pathChecks[] = ['Admin Login Links', 'Còn hardcode', false];
    }
}

// Check chat JS files
$adminChatJs = __DIR__ . '/public/js/admin_chat_client.js';
if (file_exists($adminChatJs)) {
    $content = file_get_contents($adminChatJs);
    if (strpos($content, "getAttribute('data-socket-url')") !== false) {
        $pathChecks[] = ['Admin Chat Socket', 'Đọc từ metadata', true];
    } else if (strpos($content, 'localhost:3000') !== false) {
        $pathChecks[] = ['Admin Chat Socket', 'Hardcode localhost:3000', false];
    }
}

$customerChatJs = __DIR__ . '/public/js/chat_client.js';
if (file_exists($customerChatJs)) {
    $content = file_get_contents($customerChatJs);
    if (strpos($content, "getAttribute('data-socket-url')") !== false) {
        $pathChecks[] = ['Customer Chat Socket', 'Đọc từ metadata', true];
    } else if (strpos($content, 'localhost:3000') !== false) {
        $pathChecks[] = ['Customer Chat Socket', 'Hardcode localhost:3000', false];
    }
}

echo "<table>
    <thead>
        <tr>
            <th>Component</th>
            <th>Mô tả</th>
            <th>Trạng thái</th>
        </tr>
    </thead>
    <tbody>";

$passedChecks = 0;
$failedChecks = 0;

foreach ($pathChecks as $check) {
    list($component, $description, $passed) = $check;
    
    if ($passed) {
        $passedChecks++;
        echo "<tr>
            <td><strong>{$component}</strong></td>
            <td>{$description}</td>
            <td><span class='badge badge-success icon-success'>Đúng</span></td>
        </tr>";
    } else {
        $failedChecks++;
        echo "<tr>
            <td><strong>{$component}</strong></td>
            <td>{$description}</td>
            <td><span class='badge badge-error icon-error'>Cần sửa</span></td>
        </tr>";
    }
}

echo "</tbody></table>";
echo "</div>";

// 4. Statistics
echo "<div class='section'>
    <h2 style='margin-bottom: 15px; color: #1f2937;'>4. 📊 Thống Kê</h2>
    <div class='stats'>
        <div class='stat-card'>
            <div class='stat-number'>{$totalFiles}</div>
            <div class='stat-label'>Tổng Files</div>
        </div>
        <div class='stat-card'>
            <div class='stat-number' style='color: #10b981;'>{$checkedFiles}</div>
            <div class='stat-label'>Files Hợp Lệ</div>
        </div>
        <div class='stat-card'>
            <div class='stat-number' style='color: #10b981;'>{$passedChecks}</div>
            <div class='stat-label'>Checks Passed</div>
        </div>
        <div class='stat-card'>
            <div class='stat-number' style='color: #ef4444;'>{$failedChecks}</div>
            <div class='stat-label'>Checks Failed</div>
        </div>
    </div>
</div>";

// 5. Final Result
$allPassed = ($errorFiles === 0 && $failedChecks === 0);

echo "<div class='section' style='background: " . ($allPassed ? "#d1fae5" : "#fef3c7") . ";'>
    <h2 style='margin-bottom: 15px; color: #1f2937;'>5. ✅ Kết Quả Cuối Cùng</h2>";

if ($allPassed) {
    echo "<p class='icon-success' style='font-size: 18px; font-weight: bold; color: #065f46;'>
        Tất cả đường dẫn đã được cấu hình đúng! Hệ thống sẵn sàng deploy lên VPS.
    </p>";
} else {
    echo "<p class='icon-warning' style='font-size: 18px; font-weight: bold; color: #92400e;'>
        Có {$failedChecks} vấn đề cần sửa. Vui lòng kiểm tra lại các file được đánh dấu màu đỏ.
    </p>";
}

echo "<div style='margin-top: 20px; padding: 15px; background: white; border-radius: 6px;'>
    <h3 style='margin-bottom: 10px;'>📚 Tài liệu tham khảo:</h3>
    <ul style='margin-left: 20px; line-height: 1.8;'>
        <li><code>VPS_PATH_CHECK_COMPLETE.md</code> - Tổng kết chi tiết</li>
        <li><code>CHAT_VPS_CHECK.md</code> - Kiểm tra chat system</li>
        <li><code>docs/CHAT_VPS_DEPLOYMENT.md</code> - Hướng dẫn deploy</li>
    </ul>
</div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
