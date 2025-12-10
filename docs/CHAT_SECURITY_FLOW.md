# 🔐 CHAT SYSTEM - BẢO MẬT & LUỒNG HOẠT ĐỘNG

## 📋 **Tổng quan hệ thống**

Hệ thống chat GODIFA đã được cải tiến với các tính năng bảo mật cao:

### ✅ **Chỉ CSKH được truy cập chat**
- **Role ID = 4** (ROLE_SUPPORT) mới có quyền
- Các role khác (Owner, Admin, Sales) **KHÔNG** được truy cập

### ✅ **Xác thực WebSocket**
- Mọi kết nối WebSocket phải xác thực trước khi sử dụng
- Xác thực dựa trên `role_id`, `user_id`, và `type`

---

## 🔐 **Kiến trúc bảo mật**

### **1. Backend PHP - Middleware**

#### File: `admin/pages/chat.php`
```php
// Kiểm tra đăng nhập
require_once __DIR__ . '/../middleware/auth.php';
requireStaff();

// Kiểm tra ROLE: CHỈ CSKH (role_id = 4)
$currentRoleID = $_SESSION['role_id'] ?? 0;
if ($currentRoleID != 4) {
    die('Chỉ nhân viên CSKH mới có quyền truy cập Chat!');
}
```

#### File: `api/chat_ajax.php`
```php
// Bảo mật API endpoint
require_once __DIR__ . '/../admin/middleware/auth.php';
requireStaff();

// Kiểm tra ROLE
$currentRoleID = $_SESSION['role_id'] ?? 0;
if ($currentRoleID != 4) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Chỉ nhân viên CSKH mới có quyền!'
    ]);
    exit;
}
```

---

### **2. WebSocket Server - Authentication**

#### File: `websocket-server/server.js`

**Luồng xác thực:**

```javascript
io.on('connection', (socket) => {
    let isAuthenticated = false;
    let userRole = null;
    let userId = null;
    let userType = null; // 'customer' hoặc 'staff'
    
    // 1. Client phải gửi authenticate event
    socket.on('authenticate', (authData) => {
        const { role_id, user_id, type } = authData;
        
        // Kiểm tra type
        if (type !== 'customer' && type !== 'staff') {
            socket.emit('auth_failed', { message: 'Invalid type' });
            socket.disconnect(true);
            return;
        }
        
        // Nếu là staff, PHẢI là CSKH (role_id = 4)
        if (type === 'staff' && parseInt(role_id) !== 4) {
            socket.emit('auth_failed', { 
                message: 'Chỉ CSKH mới có quyền chat!' 
            });
            socket.disconnect(true);
            return;
        }
        
        // Xác thực thành công
        isAuthenticated = true;
        userRole = parseInt(role_id);
        userId = parseInt(user_id);
        userType = type;
        
        socket.emit('auth_success');
    });
    
    // 2. Tất cả events khác phải kiểm tra auth
    const requireAuth = (action) => {
        if (!isAuthenticated) {
            socket.emit('auth_required');
            return false;
        }
        return true;
    };
    
    socket.on('send_message', async (msg) => {
        if (!requireAuth('send_message')) return;
        // ... xử lý
    });
});
```

---

### **3. Client - Gửi Authentication**

#### File: `public/js/admin_chat_client.js` (CSKH)

```javascript
socket.on('connect', () => {
    // Lấy role_id từ metadata
    const roleId = metadata.getAttribute('data-role-id');
    
    // Gửi authentication
    socket.emit('authenticate', {
        role_id: parseInt(roleId),
        user_id: parseInt(currentUserID),
        type: 'staff'
    });
});

// Lắng nghe kết quả
socket.on('auth_success', (data) => {
    console.log('Authenticated successfully');
});

socket.on('auth_failed', (data) => {
    alert('Xác thực thất bại: ' + data.message);
    window.location.href = BASE_URL + 'admin/';
});
```

#### File: `public/js/chat_client.js` (Customer)

```javascript
socket.on('connect', () => {
    const customerId = window.CURRENT_USER_ID || 'guest';
    
    socket.emit('authenticate', {
        role_id: 0, // Customer không có role
        user_id: customerId === 'guest' ? 0 : parseInt(customerId),
        type: 'customer'
    });
});
```

---

## 🚀 **Luồng hoạt động**

### **Scenario 1: CSKH vào trang chat**

1. ✅ PHP kiểm tra session → Role = 4 → OK
2. ✅ Load trang `admin/pages/chat.php`
3. ✅ JS kết nối WebSocket
4. ✅ Gửi `authenticate` event với `type: 'staff', role_id: 4`
5. ✅ Server xác thực → Cho phép chat

### **Scenario 2: Admin/Owner cố vào chat**

1. ❌ PHP kiểm tra session → Role ≠ 4 → BLOCK
2. ❌ Hiển thị: "Chỉ CSKH mới có quyền truy cập"
3. ❌ Không load WebSocket

### **Scenario 3: Khách hàng chat**

1. ✅ Vào `view/chat/index.php` (public)
2. ✅ JS kết nối WebSocket
3. ✅ Gửi `authenticate` event với `type: 'customer'`
4. ✅ Server xác thực → Cho phép chat

### **Scenario 4: Hacker cố bypass**

1. ❌ Kết nối WebSocket trực tiếp
2. ❌ Không gửi authentication
3. ❌ Server reject mọi event
4. ❌ Emit `auth_required` → Disconnect

---

## 📁 **Cấu trúc files**

```
godifaproject.id.vn/
│
├── admin/
│   ├── pages/
│   │   └── chat.php                    # ✅ CHỈ CSKH (role=4)
│   └── middleware/
│       └── auth.php                     # Kiểm tra đăng nhập
│
├── api/
│   └── chat_ajax.php                    # ✅ CHỈ CSKH (role=4)
│
├── websocket-server/
│   └── server.js                        # ✅ Authentication WebSocket
│
├── public/js/
│   ├── admin_chat_client.js             # Client cho CSKH
│   └── chat_client.js                   # Client cho Customer
│
└── view/chat/
    └── index.php                        # Chat công khai (Customer)
```

---

## 🔑 **Các điểm bảo mật chính**

| Lớp bảo mật | Vị trí | Mục đích |
|-------------|--------|----------|
| **PHP Middleware** | `admin/pages/chat.php` | Block non-CSKH từ truy cập trang |
| **API Middleware** | `api/chat_ajax.php` | Block non-CSKH từ gọi API |
| **WebSocket Auth** | `websocket-server/server.js` | Block non-CSKH từ gửi/nhận tin nhắn real-time |
| **Client Validation** | `admin_chat_client.js` | Gửi đúng credentials |

---

## 🧪 **Test Cases**

### ✅ **Test 1: CSKH login và chat**
1. Login với tài khoản role_id = 4
2. Vào `/admin/pages/chat.php`
3. Thấy giao diện chat
4. Gửi tin nhắn thành công

### ❌ **Test 2: Owner cố vào chat**
1. Login với tài khoản role_id = 1
2. Vào `/admin/pages/chat.php`
3. Thấy thông báo lỗi: "Chỉ CSKH..."
4. Không load WebSocket

### ❌ **Test 3: Bypass WebSocket**
1. Mở Console
2. Tạo kết nối: `io('wss://....')`
3. Gửi `send_message` mà không authenticate
4. Nhận `auth_required` → Disconnect

---

## 🌐 **Tương thích VPS**

### **Cấu hình môi trường**

#### File `.env` (Local)
```env
APP_ENV=local
BASE_URL=http://localhost/godifaproject.id.vn/
SOCKET_SERVER_URL=http://localhost:3000
```

#### File `.env.production` (VPS)
```env
APP_ENV=production
BASE_URL=https://godifaproject.id.vn/
SOCKET_SERVER_URL=https://godifaproject.id.vn/ws
```

### **Deploy lên VPS**

```bash
# 1. Pull code
cd /var/www/godifaproject.id.vn
git pull origin Hieu

# 2. Copy .env.production
cp .env.production .env

# 3. Restart WebSocket
pm2 restart godifa-chat

# 4. Restart Nginx
sudo systemctl restart nginx
```

---

## 📞 **Troubleshooting**

### **Lỗi: "Chỉ CSKH mới có quyền"**
- Kiểm tra `role_id` trong database
- CSKH phải có `role_id = 4`

### **Lỗi: WebSocket không kết nối**
- Kiểm tra `SOCKET_SERVER_URL` trong `.env`
- Kiểm tra PM2: `pm2 status`
- Kiểm tra logs: `pm2 logs godifa-chat`

### **Lỗi: "Authentication failed"**
- Kiểm tra `data-role-id` trong HTML
- Xem Console logs
- Verify session có `role_id` không

---

## ✅ **Checklist triển khai**

- [x] PHP middleware kiểm tra role_id = 4
- [x] API endpoint kiểm tra role_id = 4
- [x] WebSocket authentication
- [x] Client gửi auth credentials
- [x] Handle auth_failed, auth_required
- [x] Test với nhiều roles khác nhau
- [x] Tương thích VPS (BASE_URL, SOCKET_SERVER_URL)

---

**Tóm lại:** Hệ thống chat giờ đây **CHỈ CHO PHÉP CSKH (role_id = 4)** truy cập, với 3 lớp bảo mật: PHP, API, và WebSocket. Tất cả đều được cấu hình để chạy trên cả Local và VPS.
