# WEBSOCKET CONFIGURATION - LOCAL & VPS

## 📋 Tổng Quan Kiến Trúc

### Local (Development)
```
Browser → http://localhost:3000 (Direct) → Node.js WebSocket Server
```

### VPS (Production)
```
Browser → https://godifaproject.id.vn/ws → Nginx Reverse Proxy → http://localhost:3000 → Node.js WebSocket Server
```

---

## ⚙️ Cấu Hình Theo Môi Trường

### 1. Local Environment (.env)
```env
SOCKET_SERVER_URL=http://localhost:3000
```
- **Không có `/ws`** → Kết nối trực tiếp
- Client tự động detect và bỏ qua custom path

### 2. Production Environment (.env.production)
```env
SOCKET_SERVER_URL=https://godifaproject.id.vn/ws
```
- **Có `/ws`** → Đi qua Nginx proxy
- Client tự động detect và thêm `path: '/ws/socket.io'`

---

## 🔧 Nginx Configuration

File: `/etc/nginx/sites-available/godifaproject.id.vn`

```nginx
server {
    listen 443 ssl;
    server_name godifaproject.id.vn www.godifaproject.id.vn;
    root /var/www/godifaproject.id.vn;
    
    # WebSocket Proxy
    location /ws/ {
        rewrite ^/ws/(.*) /$1 break;  # Loại bỏ /ws prefix
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

        # Timeouts for long-lived connections
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }
    
    # PHP & other locations...
}
```

### Giải Thích:
1. **`rewrite ^/ws/(.*) /$1 break;`** 
   - Request: `/ws/socket.io/` 
   - Proxy đến: `/socket.io/` (bỏ `/ws`)

2. **`proxy_pass http://localhost:3000;`**
   - Forward đến Node.js server local

3. **WebSocket Headers**
   - `Upgrade` & `Connection` cho WebSocket handshake
   - Timeouts 7 days cho long-polling

---

## 💻 Client JavaScript Logic

### Auto-Detection

```javascript
// Lấy URL từ PHP
const SOCKET_SERVER_URL_RAW = window.SOCKET_SERVER_URL; 
// Local: "http://localhost:3000"
// VPS:   "https://godifaproject.id.vn/ws"

// Kiểm tra có /ws không
const needsCustomPath = SOCKET_SERVER_URL_RAW.includes('/ws');

// Bỏ /ws khỏi URL (nếu có)
const SOCKET_SERVER_URL = SOCKET_SERVER_URL_RAW.replace('/ws', '');
// Local: "http://localhost:3000"
// VPS:   "https://godifaproject.id.vn"

// Config Socket.IO
const socketConfig = needsCustomPath 
    ? { path: '/ws/socket.io' }  // Production
    : {};                         // Local

const socket = io(SOCKET_SERVER_URL, socketConfig);
```

### Kết Quả:

| Môi Trường | URL Kết Nối | Path | Endpoint Cuối |
|-----------|-------------|------|---------------|
| **Local** | `http://localhost:3000` | (default) | `http://localhost:3000/socket.io/` |
| **VPS** | `https://godifaproject.id.vn` | `/ws/socket.io` | `https://godifaproject.id.vn/ws/socket.io/` → Nginx proxy → `http://localhost:3000/socket.io/` |

---

## 🚀 Deploy Workflow

### Bước 1: Local Test
```powershell
# Khởi động server
cd c:\wamp64\www\godifaproject.id.vn\websocket-server
node server.js

# Test browser
http://localhost/godifaproject.id.vn/admin/pages/chat.php
```

**Console Log Expected:**
```
Customer Chat - SOCKET_SERVER_URL_RAW: http://localhost:3000
Customer Chat - needsCustomPath: false
[SOCKET] Connected, authenticating...
```

### Bước 2: Commit & Push
```bash
git add .
git commit -m "Update WebSocket config for local & production"
git push origin Hieu
```

### Bước 3: Deploy to VPS
```bash
# SSH vào VPS
ssh root@your-vps-ip

# Pull code mới
cd /var/www/godifaproject.id.vn
git pull origin Hieu

# Đảm bảo có .env production
# NẾU LẦN ĐẦU: cp .env.production .env
# NẾU ĐÃ CÓ: Giữ nguyên .env cũ (đã có API keys)

# Restart PM2
cd websocket-server
pm2 restart godifa-chat

# Restart Nginx
sudo systemctl restart nginx

# Check logs
pm2 logs godifa-chat --lines 50
```

**Console Log Expected:**
```
Customer Chat - SOCKET_SERVER_URL_RAW: https://godifaproject.id.vn/ws
Customer Chat - needsCustomPath: true
[SOCKET] Connected, authenticating...
```

### Bước 4: Test Production
```
https://godifaproject.id.vn/admin/pages/chat.php
```

---

## 🐛 Troubleshooting

### Lỗi: ERR_CONNECTION_REFUSED (Local)
```
Nguyên nhân: Node.js server chưa chạy
Giải pháp: node server.js trong thư mục websocket-server
```

### Lỗi: 404 Not Found /ws/socket.io/ (VPS)
```
Nguyên nhân: PM2 chưa chạy hoặc Nginx config sai
Giải pháp: 
1. pm2 list → Check godifa-chat status
2. pm2 restart godifa-chat
3. sudo nginx -t → Test nginx config
4. sudo systemctl restart nginx
```

### Lỗi: net::ERR_CERT_AUTHORITY_INVALID (VPS)
```
Nguyên nhân: SSL certificate chưa có hoặc hết hạn
Giải pháp: sudo certbot renew
```

### Lỗi: CORS Policy (VPS)
```
Nguyên nhân: SOCKET_SERVER_URL sai protocol (http vs https)
Giải pháp: Đảm bảo .env có https://godifaproject.id.vn/ws
```

---

## 📊 Port & Process Management

### Local (Windows)
```powershell
# Kiểm tra port 3000
netstat -ano | findstr :3000

# Kill process
taskkill /F /PID <PID>

# Start server
cd websocket-server
node server.js
```

### VPS (Ubuntu)
```bash
# Kiểm tra port 3000
sudo netstat -tulpn | grep :3000
# Hoặc
sudo lsof -i :3000

# PM2 management
pm2 list                    # List all processes
pm2 logs godifa-chat        # View logs
pm2 restart godifa-chat     # Restart
pm2 stop godifa-chat        # Stop
pm2 delete godifa-chat      # Remove

# Nginx management
sudo systemctl status nginx
sudo systemctl restart nginx
sudo nginx -t               # Test config
```

---

## 🔍 Debug Checklist

### Local
- [ ] `.env` có `SOCKET_SERVER_URL=http://localhost:3000` (không có /ws)
- [ ] `node server.js` đang chạy
- [ ] Browser console log: `needsCustomPath: false`
- [ ] Kết nối: `http://localhost:3000/socket.io/`

### VPS
- [ ] `.env` có `SOCKET_SERVER_URL=https://godifaproject.id.vn/ws` (có /ws)
- [ ] `pm2 list` show godifa-chat online
- [ ] Nginx có location /ws/ config
- [ ] Browser console log: `needsCustomPath: true`
- [ ] Kết nối: `https://godifaproject.id.vn/ws/socket.io/`
- [ ] SSL certificate valid

---

## 📝 Files Modified

1. ✅ `public/js/admin_chat_client.js` - Auto-detect path
2. ✅ `public/js/chat_client.js` - Auto-detect path
3. ✅ `.env` - Local config (no /ws)
4. ✅ `.env.production` - VPS config (with /ws)
5. ✅ `etc/nginx/sites-available/godifaproject.id.vn` - Nginx reverse proxy

---

**Ngày cập nhật:** 2025-12-09  
**Version:** 1.0  
**Tác giả:** GitHub Copilot
