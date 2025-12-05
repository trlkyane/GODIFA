# ⚠️ CÁC FILE CẦN SỬA THỦ CÔNG KHI DEPLOY LÊN VPS

## 🔴 QUAN TRỌNG: Các file này KHÔNG được sửa bởi script tự động

### **1. model/database.php** - Database credentials

**📍 Vị trí:** Dòng 10-13

**🔧 Cần sửa khi deploy lên VPS:**

```php
// TRÊN LOCAL:
private const DB_HOST = 'localhost';
private const DB_USER = 'root';
private const DB_PASS = '';
private const DB_NAME = 'godifa1';

// TRÊN VPS (phải sửa):
private const DB_HOST = 'localhost';
private const DB_USER = 'godifa_user';
private const DB_PASS = 'Godifa@2025';
private const DB_NAME = 'godifa';
```

---

### **2. model/database.php** - BASE_URL

**📍 Vị trí:** Dòng 111

**🔧 Cần sửa khi deploy lên VPS:**

```php
// TRÊN LOCAL:
define('BASE_URL', 'http://localhost/GODIFA/');

// TRÊN VPS (phải sửa):
define('BASE_URL', 'https://godifa.id.vn/');
```

---

### **3. model/database.php** - UPLOAD_URL

**📍 Vị trí:** Dòng 116

**🔧 Cần sửa khi deploy lên VPS:**

```php
// TRÊN LOCAL:
define('UPLOAD_URL', BASE_URL . 'images/');

// TRÊN VPS (phải sửa):
define('UPLOAD_URL', BASE_URL . 'image/');  // Chú ý: không có 's'
```

---

### **4. model/ChatModel.php** - PDO credentials (nếu có dùng chat)

**📍 Vị trí:** Constructor hoặc connection method

**🔧 Cần sửa:**

```php
// TRÊN LOCAL:
$this->db = new PDO("mysql:host=localhost;dbname=godifa1", "root", "");

// TRÊN VPS:
$this->db = new PDO("mysql:host=localhost;dbname=godifa;charset=utf8mb4", "godifa_user", "Godifa@2025");
```

---

### **5. websocket-server/ChatModel.js** - Node.js DB config

**📍 Vị trí:** dbConfig object

**🔧 Cần sửa:**

```javascript
// TRÊN LOCAL:
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'godifa1'
};

// TRÊN VPS:
const dbConfig = {
    host: 'localhost',
    user: 'godifa_user',
    password: 'Godifa@2025',
    database: 'godifa'
};
```

---

### **6. public/js/chat_client.js & admin_chat_client.js** - Socket.IO URL

**📍 Vị trí:** SOCKET_SERVER_URL

**🔧 Cần sửa:**

```javascript
// TRÊN LOCAL:
const SOCKET_SERVER_URL = 'http://localhost:3000';

// TRÊN VPS:
const SOCKET_SERVER_URL = 'https://godifa.id.vn';
```

---

## 🚀 CÁCH SỬA NHANH TRƯỚC KHI DEPLOY:

### **Option 1: Sửa thủ công (KHUYẾN NGHỊ cho lần đầu)**

1. Mở file `model/database.php`
2. Tìm và sửa 3 chỗ:
   - DB credentials (dòng 10-13)
   - BASE_URL (dòng 111)
   - UPLOAD_URL (dòng 116)
3. Save file
4. Zip và upload

### **Option 2: Dùng script tự động (CHO DEPLOY NHANH)**

Tạo file `prepare_for_deploy.ps1`:

```powershell
# Sửa database.php cho production
$dbFile = "C:\wamp64\www\GODIFA\model\database.php"
$content = Get-Content $dbFile -Raw

# Sửa DB credentials
$content = $content -replace "DB_USER = 'root'", "DB_USER = 'godifa_user'"
$content = $content -replace "DB_PASS = ''", "DB_PASS = 'Godifa@2025'"
$content = $content -replace "DB_NAME = 'godifa1'", "DB_NAME = 'godifa'"

# Sửa BASE_URL
$content = $content -replace "http://localhost/GODIFA/", "https://godifa.id.vn/"

# Sửa UPLOAD_URL
$content = $content -replace "BASE_URL \. 'images/'", "BASE_URL . 'image/'"

Set-Content $dbFile -Value $content

Write-Host "✅ Đã sửa xong database.php cho production!"
```

---

## ⚠️ LƯU Ý QUAN TRỌNG:

### **KHÔNG nên commit những thay đổi này vào Git!**

**Lý do:**
- DB credentials khác nhau giữa local và production
- BASE_URL khác nhau
- Nếu commit sẽ gây conflict khi pull code về

**Giải pháp tốt nhất:**
1. Tạo file `.env` hoặc `config.local.php` (không commit)
2. Hoặc sửa trực tiếp trên server sau khi upload

---

## 📋 CHECKLIST TRƯỚC KHI DEPLOY:

- [ ] Đã chạy `fix_all.ps1`
- [ ] Đã test trên localhost
- [ ] Đã sửa `model/database.php` (3 chỗ)
- [ ] Đã sửa `model/ChatModel.php` (nếu dùng chat)
- [ ] Đã sửa `websocket-server/ChatModel.js` (nếu dùng chat)
- [ ] Đã sửa Socket.IO URLs trong JS files
- [ ] Đã zip code
- [ ] Sẵn sàng upload

---

## 🔄 SAU KHI DEPLOY LÊN VPS:

### **Nếu muốn rollback về config local:**

```powershell
git checkout model/database.php
```

Hoặc sửa lại thủ công về:
- `DB_USER = 'root'`
- `DB_PASS = ''`
- `DB_NAME = 'godifa1'`
- `BASE_URL = 'http://localhost/GODIFA/'`

---

## 💡 TIP: Tạo 2 phiên bản

Để tránh phải sửa mỗi lần deploy, bạn có thể:

1. **Tạo `database.local.php`** với config localhost
2. **Tạo `database.production.php`** với config VPS
3. **Trong `database.php`:**

```php
if (file_exists(__DIR__ . '/database.local.php')) {
    require_once 'database.local.php';
} else {
    require_once 'database.production.php';
}
```

4. **Add vào `.gitignore`:**
```
model/database.local.php
```

Như vậy trên local dùng config local, trên VPS dùng config production!
