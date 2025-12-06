# 🚀 Hướng Dẫn Deploy Chat System Lên VPS

## 📋 Tổng Quan
Hệ thống chat sử dụng Socket.IO với Node.js backend và PHP frontend. Các đường dẫn đã được cấu hình tự động phát hiện môi trường (localhost/VPS).

---

## ✅ Các File Đã Được Cấu Hình VPS-Ready

### 1. **PHP Backend** (`model/ChatModel.php`)
- ✅ Sử dụng `Database` class chung qua Reflection
- ✅ Tự động lấy config từ `model/database.php`

### 2. **Constants Config** (`config/constants.php`)
- ✅ Thêm constant `SOCKET_SERVER_URL`
- ✅ Tự động phát hiện:
  - Local: `http://localhost:3000`
  - VPS: `https://chat.godifa.id.vn`

### 3. **Admin Chat View** (`admin/pages/chat.php`)
- ✅ Script path dùng `BASE_URL`
- ✅ Truyền `SOCKET_SERVER_URL` qua data attribute

### 4. **JavaScript Client** (`public/js/admin_chat_client.js`)
- ✅ Đọc Socket URL từ metadata (tự động theo môi trường)

### 5. **Node.js Websocket Server** (`websocket-server/`)
- ✅ `ChatModel.js`: Đọc DB config từ `.env`
- ✅ `server.js`: Đọc PORT từ `.env`
- ✅ `.env.example`: Template cấu hình

---

## 🔧 Cấu Hình VPS

### Bước 1: Cập Nhật Database Config
Chỉnh sửa file `model/database.php`:

```php
class Database {
    // Thay đổi các giá trị sau khi deploy
    private const DB_HOST = 'localhost';        // VPS: 'localhost' hoặc IP database
    private const DB_USER = 'root';             // VPS: tên user database
    private const DB_PASS = '';                 // VPS: password database
    private const DB_NAME = 'godifa1';          // VPS: tên database production
    private const DB_CHARSET = 'utf8mb4';
```

### Bước 2: Cập Nhật Socket Server URL
File `config/constants.php` tự động phát hiện, nhưng có thể điều chỉnh:

```php
// Nếu domain chat khác, sửa dòng này:
define('SOCKET_SERVER_URL', $isLocal ? 'http://localhost:3000' : 'https://chat.godifa.id.vn');
```

### Bước 3: Cấu Hình Websocket Server

1. **Tạo file `.env`** trong thư mục `websocket-server/`:
```bash
cd websocket-server
cp .env.example .env
```

2. **Chỉnh sửa `.env`**:
```env
DB_HOST=localhost
DB_USER=godifa_user
DB_PASS=your_secure_password_here
DB_NAME=godifa_production
PORT=3000
```

3. **Cài đặt dependencies**:
```bash
npm install
```

### Bước 4: Khởi Động Websocket Server Trên VPS

**Sử dụng PM2** (Khuyến nghị - auto restart):
```bash
# Cài PM2 (nếu chưa có)
npm install -g pm2

# Khởi động server
cd /path/to/GODIFA/websocket-server
pm2 start server.js --name "godifa-chat"

# Lưu cấu hình PM2
pm2 save
pm2 startup
```

**Hoặc sử dụng screen/tmux**:
```bash
screen -S chat-server
cd /path/to/GODIFA/websocket-server
node server.js
# Ctrl+A+D để detach
```

### Bước 5: Cấu Hình Nginx (Reverse Proxy)

Thêm vào Nginx config cho domain chat:

```nginx
# Reverse proxy cho Socket.IO
server {
    listen 443 ssl;
    server_name chat.godifa.id.vn;

    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        
        # WebSocket headers
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Standard proxy headers
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
```

Reload Nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🧪 Kiểm Tra Sau Khi Deploy

### 1. Kiểm tra Database Connection
```bash
cd /path/to/GODIFA/websocket-server
node -e "const ChatModel = require('./ChatModel'); const cm = new ChatModel(); console.log('DB OK');"
```

### 2. Kiểm tra Websocket Server
```bash
# Xem logs PM2
pm2 logs godifa-chat

# Hoặc test port
curl http://localhost:3000
```

### 3. Kiểm tra từ Frontend
- Mở trang admin chat: `https://godifa.id.vn/admin/pages/chat.php`
- Mở DevTools Console (F12)
- Xem kết nối Socket.IO:
  ```
  Socket.IO connected to: https://chat.godifa.id.vn
  ```

---

## 🐛 Troubleshooting

### Lỗi: "EADDRINUSE: address already in use"
```bash
# Tìm process đang dùng port 3000
lsof -i :3000
# Hoặc
netstat -tulpn | grep :3000

# Kill process
kill -9 <PID>

# Restart PM2
pm2 restart godifa-chat
```

### Lỗi: Database connection failed
```bash
# Kiểm tra .env file
cat websocket-server/.env

# Test MySQL connection
mysql -u godifa_user -p godifa_production
```

### Lỗi: Socket.IO không kết nối được
1. Kiểm tra Nginx config và SSL certificate
2. Xem logs Nginx: `sudo tail -f /var/log/nginx/error.log`
3. Kiểm tra firewall: `sudo ufw status`
4. Đảm bảo port 3000 không bị block

---

## 📊 Monitoring

### PM2 Dashboard
```bash
pm2 monit
```

### Xem Logs Real-time
```bash
pm2 logs godifa-chat --lines 100
```

### Restart Server
```bash
pm2 restart godifa-chat
```

---

## 🔒 Bảo Mật

1. **Đổi password database** trong `.env`
2. **Sử dụng SSL** cho chat subdomain
3. **Cấu hình CORS** chặt chẽ hơn trong `server.js` nếu cần:
   ```javascript
   const io = new Server(server, {
       cors: {
           origin: ["https://godifa.id.vn", "https://www.godifa.id.vn"],
           methods: ["GET", "POST"]
       }
   });
   ```

---

## 📝 Checklist Deploy

- [ ] Cập nhật `model/database.php` với thông tin VPS
- [ ] Tạo file `.env` trong `websocket-server/`
- [ ] Cài đặt Node.js dependencies (`npm install`)
- [ ] Cài đặt PM2 và khởi động server
- [ ] Cấu hình Nginx reverse proxy
- [ ] Cấu hình SSL certificate cho chat subdomain
- [ ] Test kết nối Socket.IO từ frontend
- [ ] Kiểm tra logs PM2 không có lỗi
- [ ] Test gửi/nhận tin nhắn

---

## 🆘 Support

Nếu gặp vấn đề, kiểm tra:
1. Logs PM2: `pm2 logs godifa-chat`
2. Logs Nginx: `sudo tail -f /var/log/nginx/error.log`
3. Browser Console (F12)
4. Database connection test

---

**Lưu ý**: File này được tạo tự động khi fix đường dẫn VPS cho hệ thống chat.
