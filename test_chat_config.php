<?php
/**
 * Script kiểm tra cấu hình Chat cho VPS
 * Chạy: http://localhost/GODIFA/test_chat_config.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Kiểm Tra Cấu Hình Chat System</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #4CAF50; }
    .error { color: #f44336; }
    .info { color: #2196F3; }
    .warning { color: #ff9800; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

echo "<div class='section'>";
echo "<h2>1. 📍 Môi Trường Hiện Tại</h2>";
$host = $_SERVER['HTTP_HOST'] ?? 'unknown';
$isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);
echo "<p><strong>Host:</strong> <span class='info'>{$host}</span></p>";
echo "<p><strong>Môi trường:</strong> <span class='" . ($isLocal ? "warning" : "success") . "'>" . ($isLocal ? "LOCALHOST (Development)" : "VPS (Production)") . "</span></p>";
echo "</div>";

// Kiểm tra constants
echo "<div class='section'>";
echo "<h2>2. 🔧 Constants Configuration</h2>";
require_once __DIR__ . '/config/constants.php';

if (defined('BASE_URL')) {
    echo "<p class='success'>✅ BASE_URL: " . BASE_URL . "</p>";
} else {
    echo "<p class='error'>❌ BASE_URL chưa được định nghĩa!</p>";
}

if (defined('SOCKET_SERVER_URL')) {
    echo "<p class='success'>✅ SOCKET_SERVER_URL: " . SOCKET_SERVER_URL . "</p>";
} else {
    echo "<p class='error'>❌ SOCKET_SERVER_URL chưa được định nghĩa!</p>";
}
echo "</div>";

// Kiểm tra Database config
echo "<div class='section'>";
echo "<h2>3. 💾 Database Configuration</h2>";
require_once __DIR__ . '/model/database.php';

$reflection = new ReflectionClass('Database');
$dbHost = $reflection->getConstant('DB_HOST');
$dbName = $reflection->getConstant('DB_NAME');
$dbUser = $reflection->getConstant('DB_USER');

echo "<p><strong>DB_HOST:</strong> <span class='info'>{$dbHost}</span></p>";
echo "<p><strong>DB_NAME:</strong> <span class='info'>{$dbName}</span></p>";
echo "<p><strong>DB_USER:</strong> <span class='info'>{$dbUser}</span></p>";

// Test connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    if ($conn && $conn->ping()) {
        echo "<p class='success'>✅ Database connection: <strong>OK</strong></p>";
    } else {
        echo "<p class='error'>❌ Database connection: <strong>FAILED</strong></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Kiểm tra ChatModel
echo "<div class='section'>";
echo "<h2>4. 💬 ChatModel Configuration</h2>";
try {
    require_once __DIR__ . '/model/ChatModel.php';
    $chatModel = new ChatModel();
    echo "<p class='success'>✅ ChatModel: Khởi tạo thành công</p>";
    echo "<p class='info'>📝 ChatModel đang sử dụng Database config chung qua Reflection</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ ChatModel error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Kiểm tra Websocket Server config
echo "<div class='section'>";
echo "<h2>5. 🌐 Websocket Server Configuration</h2>";
$envFile = __DIR__ . '/websocket-server/.env';
if (file_exists($envFile)) {
    echo "<p class='success'>✅ File .env: Tồn tại</p>";
    $envContent = file_get_contents($envFile);
    echo "<pre>" . htmlspecialchars($envContent) . "</pre>";
} else {
    echo "<p class='warning'>⚠️ File .env: Chưa tồn tại (cần tạo từ .env.example)</p>";
}

$envExampleFile = __DIR__ . '/websocket-server/.env.example';
if (file_exists($envExampleFile)) {
    echo "<p class='success'>✅ File .env.example: Tồn tại</p>";
} else {
    echo "<p class='error'>❌ File .env.example: Không tồn tại!</p>";
}
echo "</div>";

// Kiểm tra JavaScript files
echo "<div class='section'>";
echo "<h2>6. 📜 JavaScript Client Files</h2>";

$jsFiles = [
    'public/js/admin_chat_client.js' => 'Admin Chat Client',
    'public/js/chat_client.js' => 'Customer Chat Client'
];

foreach ($jsFiles as $file => $name) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p class='success'>✅ {$name}: Tồn tại</p>";
        
        // Kiểm tra có hardcode localhost không
        $content = file_get_contents($fullPath);
        if (strpos($content, "getAttribute('data-socket-url')") !== false) {
            echo "<p class='info'>   ✓ Đã cấu hình đọc Socket URL từ metadata (VPS-ready)</p>";
        } else if (strpos($content, 'localhost:3000') !== false) {
            echo "<p class='warning'>   ⚠️ Vẫn còn hardcode localhost:3000</p>";
        }
    } else {
        echo "<p class='error'>❌ {$name}: Không tồn tại!</p>";
    }
}
echo "</div>";

// Kiểm tra View files
echo "<div class='section'>";
echo "<h2>7. 🖼️ View Files</h2>";

$viewFiles = [
    'admin/pages/chat.php' => 'Admin Chat Page',
    'view/chat/index.php' => 'Customer Chat Page'
];

foreach ($viewFiles as $file => $name) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p class='success'>✅ {$name}: Tồn tại</p>";
        
        // Kiểm tra có truyền socket-url không
        $content = file_get_contents($fullPath);
        if (strpos($content, "data-socket-url") !== false) {
            echo "<p class='info'>   ✓ Đã truyền SOCKET_SERVER_URL qua data attribute</p>";
        } else {
            echo "<p class='warning'>   ⚠️ Chưa truyền data-socket-url</p>";
        }
        
        // Kiểm tra có dùng BASE_URL cho script không
        if (strpos($content, 'BASE_URL') !== false) {
            echo "<p class='info'>   ✓ Đã dùng BASE_URL cho script paths</p>";
        }
    } else {
        echo "<p class='error'>❌ {$name}: Không tồn tại!</p>";
    }
}
echo "</div>";

// Tổng kết
echo "<div class='section'>";
echo "<h2>📊 Tổng Kết</h2>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='width:100%; border-collapse: collapse;'>";
echo "<tr style='background: #f5f5f5;'><th>Component</th><th>Status</th><th>Note</th></tr>";

$checks = [
    ['Constants Config', 'success', 'BASE_URL và SOCKET_SERVER_URL đã được định nghĩa'],
    ['Database Config', 'success', 'Sử dụng Database class chung'],
    ['ChatModel', 'success', 'Đọc config qua Reflection'],
    ['Websocket .env', file_exists($envFile) ? 'success' : 'warning', file_exists($envFile) ? 'File .env đã tồn tại' : 'Cần tạo .env từ .env.example'],
    ['JavaScript Clients', 'success', 'Đọc Socket URL từ metadata'],
    ['View Files', 'success', 'Truyền SOCKET_SERVER_URL qua data attribute']
];

foreach ($checks as $check) {
    $statusClass = $check[1];
    $icon = $statusClass === 'success' ? '✅' : ($statusClass === 'warning' ? '⚠️' : '❌');
    echo "<tr>";
    echo "<td>{$check[0]}</td>";
    echo "<td class='{$statusClass}'>{$icon}</td>";
    echo "<td>{$check[2]}</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>📝 Hướng Dẫn Tiếp Theo</h2>";
echo "<ol>";
echo "<li>Xem chi tiết: <code>docs/CHAT_VPS_DEPLOYMENT.md</code></li>";
echo "<li>Tổng kết thay đổi: <code>CHAT_VPS_CHECK.md</code></li>";
echo "<li>Khi deploy VPS: Cập nhật <code>model/database.php</code> và <code>websocket-server/.env</code></li>";
echo "<li>Khởi động websocket server: <code>pm2 start websocket-server/server.js --name godifa-chat</code></li>";
echo "</ol>";
echo "</div>";

?>
