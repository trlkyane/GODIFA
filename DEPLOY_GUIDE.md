# 🚀 HƯỚNG DẪN DEPLOY LÊN VPS

## 📋 Quy trình Deploy

### 1️⃣ **Chuẩn bị trên Local**

```bash
# Commit tất cả thay đổi
git add .
git commit -m "Update code"
git push origin Hieu
```

### 2️⃣ **Trên VPS - Pull Code Mới**

```bash
# SSH vào VPS
ssh root@103.173.226.191

# Di chuyển đến thư mục project
cd /var/www/godifaproject.id.vn

# Pull code mới
git pull origin Hieu

# Hoặc nếu có conflict, reset về commit mới nhất
git fetch origin
git reset --hard origin/Hieu
```

### 3️⃣ **Cấu hình Environment trên VPS**

```bash
# Copy file .env.production thành .env
cp .env.production .env

# Chỉnh sửa .env nếu cần
nano .env
```

**Nội dung file `.env` trên VPS:**
```env
APP_ENV=production
DB_HOST=localhost
DB_NAME=godifa_production
DB_USER=godifa_user
DB_PASS=Godifa@2025
BASE_URL=https://godifaproject.id.vn/
SOCKET_SERVER_URL=https://godifaproject.id.vn/ws
SEPAY_API_TOKEN=your_real_token
SEPAY_ACCOUNT_NUMBER=your_real_account
SEPAY_ACCOUNT_NAME=YOUR REAL NAME
GHN_API_TOKEN=your_real_token
GHN_SHOP_ID=your_real_shop_id
```

### 4️⃣ **Set Permissions**

```bash
# Đảm bảo các thư mục có quyền ghi
chmod -R 755 /var/www/godifaproject.id.vn
chmod -R 777 /var/www/godifaproject.id.vn/logs
chmod -R 777 /var/www/godifaproject.id.vn/image/refund_proofs
chmod -R 777 /var/www/godifaproject.id.vn/image/return_proofs

# Đảm bảo .env không public
chmod 600 /var/www/godifaproject.id.vn/.env
```

### 5️⃣ **Restart Services (nếu cần)**

```bash
# Restart Nginx
sudo systemctl restart nginx

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Restart WebSocket (nếu có thay đổi)
pm2 restart godifa-chat
```

### 6️⃣ **Kiểm tra**

- Truy cập: https://godifaproject.id.vn
- Kiểm tra chức năng: Login, Order, Payment, Chat
- Xem logs nếu có lỗi: `tail -f /var/www/godifaproject.id.vn/logs/error.log`

---

## 🔄 Workflow Làm Việc Hàng Ngày

### Trên Local (Windows)

1. **Code trên folder `godifaproject.id.vn`**
   ```
   c:\wamp64\www\godifaproject.id.vn
   ```

2. **Chỉnh sửa `.env` cho local**
   ```env
   APP_ENV=local
   BASE_URL=http://localhost/godifaproject.id.vn/
   SOCKET_SERVER_URL=http://localhost:3000
   ```

3. **Test trên local:**
   - URL: http://localhost/godifaproject.id.vn
   - Database: Local MySQL
   - WebSocket: localhost:3000

4. **Commit & Push khi hoàn thành:**
   ```bash
   git add .
   git commit -m "Mô tả thay đổi"
   git push origin Hieu
   ```

### Trên VPS

1. **Pull code mới:**
   ```bash
   cd /var/www/godifaproject.id.vn
   git pull origin Hieu
   ```

2. **Kiểm tra `.env` đã đúng chưa:**
   ```bash
   cat .env | grep APP_ENV
   # Phải là: APP_ENV=production
   ```

3. **Restart nếu cần:**
   ```bash
   sudo systemctl restart nginx
   pm2 restart godifa-chat
   ```

---

## 📝 Lưu Ý Quan Trọng

### ✅ Điều CẦN làm:

1. **KHÔNG commit file `.env` lên Git** - Đã có trong `.gitignore`
2. **Luôn có file `.env.production`** - Template cho VPS
3. **Luôn có file `.env.example`** - Template cho người khác
4. **Test kỹ trên local** trước khi deploy
5. **Backup database** trước khi deploy bản lớn

### ❌ Điều KHÔNG NÊN làm:

1. ❌ Không hard-code thông tin nhạy cảm trong code
2. ❌ Không edit code trực tiếp trên VPS
3. ❌ Không dùng `git reset --hard` nếu không chắc chắn
4. ❌ Không quên pull code trước khi deploy

---

## 🔧 Troubleshooting

### Lỗi: Class 'Env' not found

**Nguyên nhân:** File `config/env.php` chưa được load

**Giải pháp:**
```bash
# Kiểm tra file có tồn tại
ls -la /var/www/godifaproject.id.vn/config/env.php

# Pull lại code
git pull origin Hieu
```

### Lỗi: Database connection failed

**Nguyên nhân:** Thông tin database trong `.env` sai

**Giải pháp:**
```bash
# Kiểm tra .env
cat .env | grep DB_

# Sửa lại nếu sai
nano .env
```

### Lỗi: Permission denied

**Nguyên nhân:** Thư mục không có quyền ghi

**Giải pháp:**
```bash
chmod -R 777 /var/www/godifaproject.id.vn/logs
chmod -R 777 /var/www/godifaproject.id.vn/image
```

---

## 📞 Hỗ Trợ

Nếu gặp vấn đề, kiểm tra:
1. Logs: `/var/www/godifaproject.id.vn/logs/`
2. Nginx logs: `/var/log/nginx/error.log`
3. PHP logs: `/var/log/php8.1-fpm.log`
