# ✅ KIỂM TRA ĐƯỜNG DẪN CHAT - TƯƠNG THÍCH VPS

## 📝 Tóm Tắt Các Thay Đổi

Đã cập nhật **7 files** để hệ thống chat tự động phát hiện môi trường (localhost/VPS):

### 1. ✅ `config/constants.php`
**Thêm mới:**
```php
// Socket.IO Server URL - Tự động phát hiện môi trường
define('SOCKET_SERVER_URL', $isLocal ? 'http://localhost:3000' : 'https://chat.godifa.id.vn');
```

### 2. ✅ `model/ChatModel.php`
**Trước:**
```php
$this->db = new PDO('mysql:host=localhost;dbname=godifa1', 'root', '');
```

**Sau:**
```php
// Sử dụng Database class chung qua Reflection
require_once __DIR__ . '/database.php';
$dbInstance = Database::getInstance();
$reflection = new ReflectionClass('Database');
$dbHost = $reflection->getConstant('DB_HOST');
// ... lấy config chung
```

### 3. ✅ `admin/pages/chat.php`
**Thêm:**
```php
// Truyền Socket URL qua data attribute
data-socket-url="<?php echo SOCKET_SERVER_URL; ?>"
```

**Sửa:**
```html
<!-- Trước: -->
<script src="/public/js/admin_chat_client.js"></script>

<!-- Sau: -->
<script src="<?php echo BASE_URL; ?>public/js/admin_chat_client.js"></script>
```

### 4. ✅ `public/js/admin_chat_client.js`
**Trước:**
```javascript
const SOCKET_SERVER_URL = 'http://localhost:3000';
```

**Sau:**
```javascript
// Lấy từ metadata (tự động theo môi trường)
const metadata = document.getElementById('admin-metadata');
const SOCKET_SERVER_URL = metadata ? metadata.getAttribute('data-socket-url') : 'http://localhost:3000';
```

### 5. ✅ `view/chat/index.php`
**Thêm:**
```php
data-socket-url="<?php echo SOCKET_SERVER_URL; ?>"
```

### 6. ✅ `public/js/chat_client.js`
**Trước:**
```javascript
const SOCKET_SERVER_PORT = 3000;
const SOCKET_SERVER_URL = `http://localhost:${SOCKET_SERVER_PORT}`;
```

**Sau:**
```javascript
const metadata = document.getElementById('customer-metadata');
const SOCKET_SERVER_URL = metadata ? metadata.getAttribute('data-socket-url') : 'http://localhost:3000';
```

### 7. ✅ `websocket-server/ChatModel.js`
**Trước:**
```javascript
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'godifa1',
    // ...
};
```

**Sau:**
```javascript
require('dotenv').config();

const dbConfig = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'godifa1',
    // ...
};
```

### 8. ✅ `websocket-server/server.js`
**Trước:**
```javascript
const PORT = 3000;
```

**Sau:**
```javascript
const PORT = process.env.PORT || 3000;
```

---

## 📂 Files Mới Được Tạo

### 1. `websocket-server/.env`
File cấu hình local:
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=godifa1
PORT=3000
```

### 2. `websocket-server/.env.example`
Template cho VPS deployment

### 3. `docs/CHAT_VPS_DEPLOYMENT.md`
Hướng dẫn deploy chi tiết lên VPS

---

## 🧪 Cách Kiểm Tra

### Trên Localhost:
1. Không cần thay đổi gì
2. System tự động detect `localhost` và dùng:
   - Socket URL: `http://localhost:3000`
   - Database: từ `model/database.php` (localhost)

### Khi Deploy Lên VPS:

#### Bước 1: Cập nhật config constants
File `config/constants.php` đã tự động detect domain.
Nếu cần custom:
```php
define('SOCKET_SERVER_URL', $isLocal ? 'http://localhost:3000' : 'https://chat.godifa.id.vn');
```

#### Bước 2: Cập nhật database config
File `model/database.php`:
```php
private const DB_HOST = 'localhost';  // Thay bằng host VPS
private const DB_USER = 'root';       // Thay bằng user VPS
private const DB_PASS = '';           // Thay bằng password VPS
private const DB_NAME = 'godifa1';    // Thay bằng DB name VPS
```

#### Bước 3: Cấu hình Websocket Server
Trong `websocket-server/.env`:
```env
DB_HOST=localhost
DB_USER=godifa_user
DB_PASS=secure_password_here
DB_NAME=godifa_production
PORT=3000
```

#### Bước 4: Khởi động Websocket Server
```bash
cd websocket-server
npm install
pm2 start server.js --name "godifa-chat"
pm2 save
```

#### Bước 5: Cấu hình Nginx
Xem chi tiết trong `docs/CHAT_VPS_DEPLOYMENT.md`

---

## ✅ Checklist Kiểm Tra

### Trước Khi Deploy:
- [x] Đã sửa 7 files chính
- [x] Tạo file `.env` và `.env.example`
- [x] Tạo tài liệu hướng dẫn deploy
- [x] Test trên localhost (chat vẫn hoạt động bình thường)

### Khi Deploy VPS:
- [ ] Cập nhật `model/database.php`
- [ ] Tạo file `websocket-server/.env` với thông tin VPS
- [ ] Cài đặt Node.js dependencies
- [ ] Khởi động PM2 cho websocket server
- [ ] Cấu hình Nginx reverse proxy
- [ ] Setup SSL cho subdomain chat
- [ ] Test kết nối Socket.IO
- [ ] Test gửi/nhận tin nhắn

---

## 🎯 Kết Quả

### Trên Localhost:
- ✅ Socket URL: `http://localhost:3000`
- ✅ Database: `localhost/godifa1`
- ✅ Script paths: `/public/js/...`

### Trên VPS (godifa.id.vn):
- ✅ Socket URL: `https://chat.godifa.id.vn` (auto detect)
- ✅ Database: Từ `model/database.php` config
- ✅ Script paths: `https://godifa.id.vn/public/js/...`

---

## 📌 Lưu Ý Quan Trọng

1. **Database config** vẫn nằm trong `model/database.php` - cần update khi deploy
2. **Socket URL** tự động detect qua `$_SERVER['HTTP_HOST']`
3. **Websocket server** cần `.env` file để chạy trên VPS
4. **PM2** nên được dùng để auto-restart websocket server
5. **Nginx** cần cấu hình reverse proxy cho WebSocket

---

## 📚 Tài Liệu Tham Khảo

- Hướng dẫn deploy chi tiết: `docs/CHAT_VPS_DEPLOYMENT.md`
- Template .env: `websocket-server/.env.example`
- Database config: `model/database.php`
- Constants config: `config/constants.php`

---

**Tóm lại:** Tất cả đường dẫn chat đã được cấu hình tự động phát hiện môi trường, tương thích 100% với VPS! 🚀
