# ✅ CHECKLIST DEPLOY GODIFA LÊN VPS - NHANH

## 🎯 CHUẨN BỊ
```
[ ] VPS: Ubuntu 20.04+, 2GB RAM, 2 CPU
[ ] Domain: godifa.id.vn + chat.godifa.id.vn đã trỏ về IP VPS
[ ] SSH access vào VPS
```

---

## 📋 CÁC BƯỚC (7 BƯỚC CHÍNH)

### 1️⃣ UPLOAD CODE
```bash
cd /var/www
git clone https://github.com/trlkyane/GODIFA.git godifa.id.vn
cd godifa.id.vn && git checkout Hieu
chown -R www-data:www-data /var/www/godifa.id.vn
chmod -R 775 /var/www/godifa.id.vn/image
chmod -R 775 /var/www/godifa.id.vn/logs
```

### 2️⃣ DATABASE
```sql
-- MySQL
CREATE DATABASE godifa_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'godifa_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON godifa_production.* TO 'godifa_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import
mysql -u godifa_user -p godifa_production < /var/www/godifa.id.vn/data/godifa1.sql

# Sửa config
nano /var/www/godifa.id.vn/model/database.php
```

**Sửa trong database.php:**
```php
private const DB_HOST = 'localhost';
private const DB_USER = 'godifa_user';
private const DB_PASS = 'your_password';
private const DB_NAME = 'godifa_production';
```

### 3️⃣ PHP & NGINX
```bash
# Cài PHP 8.1
apt update
apt install -y php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd

# Cài Nginx
apt install nginx -y

# Tạo config
nano /etc/nginx/sites-available/godifa.id.vn
```

**Copy config này vào:** (Xem file VPS_DEPLOYMENT_GUIDE.md section 3.2)

```bash
# Enable site
ln -s /etc/nginx/sites-available/godifa.id.vn /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
```

### 4️⃣ SSL CERTIFICATE
```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d godifa.id.vn -d www.godifa.id.vn
certbot --nginx -d chat.godifa.id.vn
```

### 5️⃣ WEBSOCKET SERVER (CHAT)
```bash
# Cài Node.js & PM2
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs
npm install -g pm2

# Setup chat server
cd /var/www/godifa.id.vn/websocket-server
npm install
nano .env
```

**Nội dung .env:**
```env
DB_HOST=localhost
DB_USER=godifa_user
DB_PASS=your_password
DB_NAME=godifa_production
PORT=3000
```

```bash
# Start với PM2
pm2 start server.js --name "godifa-chat"
pm2 save
pm2 startup
```

**Tạo Nginx config cho chat:**
```bash
nano /etc/nginx/sites-available/chat.godifa.id.vn
```
(Copy config từ VPS_DEPLOYMENT_GUIDE.md section 5.4)

```bash
ln -s /etc/nginx/sites-available/chat.godifa.id.vn /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 6️⃣ CRONJOB
```bash
crontab -e
```

**Thêm dòng này:**
```bash
*/5 * * * * /usr/bin/php /var/www/godifa.id.vn/cron/cancel_expired_orders.php >> /var/www/godifa.id.vn/logs/cron.log 2>&1
```

### 7️⃣ TEST
```bash
# Test website
curl -I https://godifa.id.vn

# Test chat
curl -I https://chat.godifa.id.vn
pm2 logs godifa-chat

# Mở trình duyệt test
https://godifa.id.vn
https://godifa.id.vn/admin/login.php
```

---

## 🔥 QUICK COMMANDS

### Xem Logs
```bash
# Nginx
tail -f /var/log/nginx/godifa_error.log

# PHP
tail -f /var/log/php8.1-fpm.log

# Chat
pm2 logs godifa-chat

# Cron
tail -f /var/www/godifa.id.vn/logs/cron.log
```

### Restart Services
```bash
systemctl restart nginx
systemctl restart php8.1-fpm
systemctl restart mysql
pm2 restart godifa-chat
```

### Check Status
```bash
systemctl status nginx
systemctl status php8.1-fpm
systemctl status mysql
pm2 status
```

---

## 🚨 TROUBLESHOOTING

### Website không load
```bash
# Check Nginx
nginx -t
systemctl status nginx
tail -f /var/log/nginx/godifa_error.log

# Check PHP-FPM
systemctl status php8.1-fpm
```

### Database connection failed
```bash
# Test MySQL
mysql -u godifa_user -p godifa_production

# Check config
cat /var/www/godifa.id.vn/model/database.php
```

### Chat không kết nối
```bash
# Check PM2
pm2 status
pm2 logs godifa-chat

# Check port
netstat -tulpn | grep 3000

# Restart
pm2 restart godifa-chat
```

### Permission denied
```bash
chown -R www-data:www-data /var/www/godifa.id.vn
chmod -R 775 /var/www/godifa.id.vn/image
chmod -R 775 /var/www/godifa.id.vn/logs
```

---

## 🎯 CHECKLIST CUỐI CÙNG

```
[ ] Website load được: https://godifa.id.vn
[ ] Admin login được: https://godifa.id.vn/admin/login.php
[ ] Customer login được
[ ] Thêm sản phẩm vào giỏ
[ ] Checkout & thanh toán
[ ] Chat button hiển thị
[ ] Gửi tin nhắn chat được
[ ] Admin nhận được tin nhắn
[ ] Cronjob chạy (check logs sau 5 phút)
[ ] SSL certificate hoạt động (HTTPS)
[ ] Mobile responsive
```

---

## 📚 TÀI LIỆU CHI TIẾT

Xem thêm: `docs/VPS_DEPLOYMENT_GUIDE.md`

---

**🎉 DONE! Website sẵn sàng hoạt động!**
