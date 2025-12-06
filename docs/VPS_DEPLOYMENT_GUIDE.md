    # 🚀 HƯỚNG DẪN DEPLOY GODIFA LÊN VPS - CHI TIẾT TỪNG BƯỚC

    ## 📋 Mục Lục
    1. [Chuẩn Bị](#chuẩn-bị)
    2. [Bước 1: Upload Code Lên VPS](#bước-1-upload-code-lên-vps)
    3. [Bước 2: Cấu Hình Database](#bước-2-cấu-hình-database)
    4. [Bước 3: Cấu Hình PHP & Web Server](#bước-3-cấu-hình-php--web-server)
    5. [Bước 4: Cấu Hình Domain & SSL](#bước-4-cấu-hình-domain--ssl)
    6. [Bước 5: Setup Websocket Server (Chat)](#bước-5-setup-websocket-server-chat)
    7. [Bước 6: Cấu Hình Cronjob](#bước-6-cấu-hình-cronjob)
    8. [Bước 7: Kiểm Tra & Test](#bước-7-kiểm-tra--test)

    ---

    ## 🎯 Chuẩn Bị

    ### Yêu Cầu VPS:
    - **OS:** Ubuntu 20.04/22.04 hoặc CentOS 7/8
    - **RAM:** Tối thiểu 2GB (Khuyến nghị 4GB)
    - **CPU:** 2 cores
    - **Disk:** 20GB SSD
    - **PHP:** 8.0 trở lên
    - **MySQL:** 5.7 hoặc 8.0
    - **Node.js:** v16 trở lên (cho chat)

    ### Domain Cần Chuẩn Bị:
    - **Domain chính:** `godifa.id.vn` → Website
    - **Subdomain chat:** `chat.godifa.id.vn` → Websocket server

    ### Thông Tin Cần Có:
    - [ ] IP VPS
    - [ ] SSH username & password/key
    - [ ] Domain đã trỏ về IP VPS
    - [ ] Database credentials (user, password)

    ---

    ## 📦 Bước 1: Upload Code Lên VPS

    ### Option 1: Dùng Git (Khuyến nghị)

    ```bash
    # SSH vào VPS
    ssh root@your_vps_ip

    # Cài Git nếu chưa có
    apt update && apt install git -y

    # Clone repo
    cd /var/www
    git clone https://github.com/trlkyane/GODIFA.git godifa.id.vn
    cd godifa.id.vn

    # Checkout branch
    git checkout Hieu
    ```

    ### Option 2: Upload qua FTP/SFTP

    ```bash
    # Dùng FileZilla hoặc WinSCP
    # Upload toàn bộ folder GODIFA lên /var/www/godifa.id.vn
    ```

    ### Phân Quyền Folder

    ```bash
    # Set owner
    chown -R www-data:www-data /var/www/godifa.id.vn

    # Set permissions
    find /var/www/godifa.id.vn -type d -exec chmod 755 {} \;
    find /var/www/godifa.id.vn -type f -exec chmod 644 {} \;

    # Chmod đặc biệt cho upload folders
    chmod -R 775 /var/www/godifa.id.vn/image
    chmod -R 775 /var/www/godifa.id.vn/logs
    ```

    ---

    ## 💾 Bước 2: Cấu Hình Database

    ### 2.1. Tạo Database & User

    ```bash
    # Đăng nhập MySQL
    mysql -u root -p

    # Tạo database
    CREATE DATABASE godifa_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

    # Tạo user
    CREATE USER 'godifa_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';

    # Cấp quyền
    GRANT ALL PRIVILEGES ON godifa_production.* TO 'godifa_user'@'localhost';
    FLUSH PRIVILEGES;

    # Thoát
    EXIT;
    ```

    ### 2.2. Import Database

    ```bash
    # Upload file godifa1.sql lên VPS
    cd /var/www/godifa.id.vn/data

    # Import database
    mysql -u godifa_user -p godifa_production < godifa1.sql

    # Hoặc nếu có nhiều file migration
    mysql -u godifa_user -p godifa_production < data/migrations/*.sql
    ```

    ### 2.3. Cập Nhật Database Config

    **File:** `/var/www/godifa.id.vn/model/database.php`

    ```bash
    nano /var/www/godifa.id.vn/model/database.php
    ```

    Sửa các dòng sau:

    ```php
    class Database {
        // THAY ĐỔI CÁC GIÁ TRỊ SAU:
        private const DB_HOST = 'localhost';              // Giữ nguyên nếu DB cùng server
        private const DB_USER = 'godifa_user';            // User vừa tạo
        private const DB_PASS = 'your_secure_password';   // Password vừa tạo
        private const DB_NAME = 'godifa_production';      // Database vừa tạo
        private const DB_CHARSET = 'utf8mb4';
    ```

    **Lưu file:** Ctrl+O, Enter, Ctrl+X

    ---

    ## ⚙️ Bước 3: Cấu Hình PHP & Web Server

    ### 3.1. Cài Đặt PHP 8.x & Extensions

    ```bash
    # Update packages
    apt update

    # Cài PHP 8.1 và extensions
    apt install -y php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml \
                php8.1-curl php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath

    # Kiểm tra version
    php -v
    ```

    ### 3.2. Cấu Hình Nginx

    ```bash
    # Cài Nginx nếu chưa có
    apt install nginx -y

    # Tạo config file
    nano /etc/nginx/sites-available/godifa.id.vn
    ```

    **Nội dung file:**

    ```nginx
    server {
        listen 80;
        server_name godifa.id.vn www.godifa.id.vn;
        root /var/www/godifa.id.vn;
        index index.php index.html;

        # Logs
        access_log /var/log/nginx/godifa_access.log;
        error_log /var/log/nginx/godifa_error.log;

        # Max upload size
        client_max_body_size 50M;

        # Main location
        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        # PHP processing
        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }

        # Admin area
        location /admin {
            try_files $uri $uri/ /admin/index.php?$query_string;
        }

        # Deny access to sensitive files
        location ~ /\. {
            deny all;
        }

        location ~ /\.env {
            deny all;
        }

        # Static files caching
        location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
            expires 30d;
            add_header Cache-Control "public, immutable";
        }
    }
    ```

    **Enable site:**

    ```bash
    # Tạo symbolic link
    ln -s /etc/nginx/sites-available/godifa.id.vn /etc/nginx/sites-enabled/

    # Xóa default site nếu có
    rm /etc/nginx/sites-enabled/default

    # Test config
    nginx -t

    # Restart Nginx
    systemctl restart nginx
    ```

    ---

    ## 🔒 Bước 4: Cấu Hình Domain & SSL

    ### 4.1. Trỏ Domain về VPS

    Vào nhà cung cấp domain, thêm DNS records:

    ```
    Type    Name    Value           TTL
    A       @       your_vps_ip     3600
    A       www     your_vps_ip     3600
    A       chat    your_vps_ip     3600
    ```

    ### 4.2. Cài SSL Certificate (Let's Encrypt)

    ```bash
    # Cài Certbot
    apt install certbot python3-certbot-nginx -y

    # Tạo SSL cho domain chính
    certbot --nginx -d godifa.id.vn -d www.godifa.id.vn

    # Tạo SSL cho chat subdomain
    certbot --nginx -d chat.godifa.id.vn

    # Auto-renew (Certbot tự động cài)
    certbot renew --dry-run
    ```

    ### 4.3. Cập Nhật Nginx Config Sau Khi Có SSL

    Nginx config sẽ tự động update, nhưng kiểm tra lại:

    ```bash
    nano /etc/nginx/sites-available/godifa.id.vn
    ```

    Đảm bảo có redirect HTTP → HTTPS:

    ```nginx
    server {
        listen 80;
        server_name godifa.id.vn www.godifa.id.vn;
        return 301 https://$server_name$request_uri;
    }

    server {
        listen 443 ssl http2;
        server_name godifa.id.vn www.godifa.id.vn;
        
        ssl_certificate /etc/letsencrypt/live/godifa.id.vn/fullchain.pem;
        ssl_certificate_key /etc/letsencrypt/live/godifa.id.vn/privkey.pem;
        
        # ... rest of config
    }
    ```

    ---

    ## 💬 Bước 5: Setup Websocket Server (Chat)

    ### 5.1. Cài Node.js & PM2

    ```bash
    # Cài Node.js v18 LTS
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt install -y nodejs

    # Kiểm tra version
    node -v
    npm -v

    # Cài PM2 (Process Manager)
    npm install -g pm2
    ```

    ### 5.2. Cấu Hình Websocket Server

    ```bash
    # Di chuyển vào folder websocket
    cd /var/www/godifa.id.vn/websocket-server

    # Cài dependencies
    npm install

    # Tạo file .env
    nano .env
    ```

    **Nội dung `.env`:**

    ```env
    DB_HOST=localhost
    DB_USER=godifa_user
    DB_PASS=your_secure_password
    DB_NAME=godifa_production
    PORT=3000
    ```

    **Lưu file:** Ctrl+O, Enter, Ctrl+X

    ### 5.3. Khởi Động Websocket Server

    ```bash
    # Start với PM2
    pm2 start server.js --name "godifa-chat"

    # Xem logs
    pm2 logs godifa-chat

    # Lưu config để auto-start khi reboot
    pm2 save
    pm2 startup

    # Kiểm tra status
    pm2 status
    ```

    ### 5.4. Cấu Hình Nginx cho Chat Subdomain

    ```bash
    nano /etc/nginx/sites-available/chat.godifa.id.vn
    ```

    **Nội dung:**

    ```nginx
    server {
        listen 80;
        server_name chat.godifa.id.vn;
        return 301 https://$server_name$request_uri;
    }

    server {
        listen 443 ssl http2;
        server_name chat.godifa.id.vn;

        ssl_certificate /etc/letsencrypt/live/chat.godifa.id.vn/fullchain.pem;
        ssl_certificate_key /etc/letsencrypt/live/chat.godifa.id.vn/privkey.pem;

        # Logs
        access_log /var/log/nginx/chat_access.log;
        error_log /var/log/nginx/chat_error.log;

        location / {
            proxy_pass http://localhost:3000;
            proxy_http_version 1.1;
            
            # WebSocket support
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection "upgrade";
            
            # Headers
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

    **Enable & reload:**

    ```bash
    ln -s /etc/nginx/sites-available/chat.godifa.id.vn /etc/nginx/sites-enabled/
    nginx -t
    systemctl reload nginx
    ```

    ---

    ## ⏰ Bước 6: Cấu Hình Cronjob

    ### 6.1. Tạo Cronjob Hủy Đơn Hết Hạn

    ```bash
    # Mở crontab
    crontab -e
    ```

    **Thêm dòng sau (chạy mỗi 5 phút):**

    ```bash
    */5 * * * * /usr/bin/php /var/www/godifa.id.vn/cron/cancel_expired_orders.php >> /var/www/godifa.id.vn/logs/cron.log 2>&1
    ```

    **Lưu:** Ctrl+O, Enter, Ctrl+X

    ### 6.2. Kiểm Tra Cronjob

    ```bash
    # Xem danh sách cronjob
    crontab -l

    # Test chạy manual
    /usr/bin/php /var/www/godifa.id.vn/cron/cancel_expired_orders.php

    # Xem log
    tail -f /var/www/godifa.id.vn/logs/cron.log
    ```

    ---

    ## 🧪 Bước 7: Kiểm Tra & Test

    ### 7.1. Kiểm Tra Website

    ```bash
    # Test từ terminal
    curl -I https://godifa.id.vn

    # Mở trình duyệt
    https://godifa.id.vn
    ```

    **Checklist:**
    - [ ] Trang chủ load được
    - [ ] CSS/JS load đúng
    - [ ] Hình ảnh hiển thị
    - [ ] Đăng nhập admin được: `https://godifa.id.vn/admin/login.php`
    - [ ] Đăng nhập khách hàng được
    - [ ] Thêm sản phẩm vào giỏ hàng
    - [ ] Checkout được

    ### 7.2. Kiểm Tra Chat

    ```bash
    # Test websocket server
    curl -I https://chat.godifa.id.vn

    # Xem logs
    pm2 logs godifa-chat
    ```

    **Checklist:**
    - [ ] Chat button hiện ở trang chủ
    - [ ] Gửi tin nhắn được
    - [ ] Admin nhận được tin nhắn
    - [ ] Bot trả lời FAQ

    ### 7.3. Kiểm Tra Database Connection

    ```bash
    # Test connection
    cd /var/www/godifa.id.vn
    php -r "require 'model/database.php'; \$db = Database::getInstance(); echo 'DB OK';"
    ```

    ### 7.4. Kiểm Tra File Permissions

    ```bash
    # Test upload ảnh
    ls -la /var/www/godifa.id.vn/image

    # Test logs
    ls -la /var/www/godifa.id.vn/logs
    ```

    ---

    ## 🔧 Troubleshooting

    ### Lỗi: 502 Bad Gateway

    ```bash
    # Kiểm tra PHP-FPM
    systemctl status php8.1-fpm
    systemctl restart php8.1-fpm

    # Xem log Nginx
    tail -f /var/log/nginx/godifa_error.log
    ```

    ### Lỗi: Database Connection Failed

    ```bash
    # Kiểm tra MySQL
    systemctl status mysql

    # Test login
    mysql -u godifa_user -p godifa_production

    # Kiểm tra file config
    cat /var/www/godifa.id.vn/model/database.php
    ```

    ### Lỗi: Chat Không Kết Nối

    ```bash
    # Kiểm tra PM2
    pm2 status

    # Xem logs
    pm2 logs godifa-chat

    # Restart
    pm2 restart godifa-chat

    # Kiểm tra port
    netstat -tulpn | grep 3000
    ```

    ### Lỗi: Permission Denied

    ```bash
    # Fix ownership
    chown -R www-data:www-data /var/www/godifa.id.vn

    # Fix permissions
    chmod -R 775 /var/www/godifa.id.vn/image
    chmod -R 775 /var/www/godifa.id.vn/logs
    ```

    ---

    ## 📊 Các Lệnh Hữu Ích

    ### Nginx

    ```bash
    nginx -t                    # Test config
    systemctl restart nginx     # Restart
    systemctl status nginx      # Check status
    tail -f /var/log/nginx/godifa_error.log  # View logs
    ```

    ### PHP-FPM

    ```bash
    systemctl restart php8.1-fpm
    systemctl status php8.1-fpm
    tail -f /var/log/php8.1-fpm.log
    ```

    ### MySQL

    ```bash
    systemctl restart mysql
    systemctl status mysql
    mysql -u godifa_user -p godifa_production
    ```

    ### PM2 (Chat)

    ```bash
    pm2 list                    # List all processes
    pm2 logs godifa-chat        # View logs
    pm2 restart godifa-chat     # Restart
    pm2 stop godifa-chat        # Stop
    pm2 delete godifa-chat      # Delete
    pm2 monit                   # Monitor
    ```

    ### Git Updates

    ```bash
    cd /var/www/godifa.id.vn
    git pull origin Hieu
    pm2 restart godifa-chat     # Restart chat nếu có update
    ```

    ---

    ## 🔒 Bảo Mật

    ### Firewall (UFW)

    ```bash
    # Enable firewall
    ufw enable

    # Allow SSH
    ufw allow 22/tcp

    # Allow HTTP & HTTPS
    ufw allow 80/tcp
    ufw allow 443/tcp

    # Deny port 3000 từ bên ngoài (chỉ allow local)
    ufw deny 3000/tcp

    # Check status
    ufw status
    ```

    ### Fail2Ban (Chống brute force)

    ```bash
    # Cài đặt
    apt install fail2ban -y

    # Enable
    systemctl enable fail2ban
    systemctl start fail2ban
    ```

    ---

    ## 📝 Checklist Deploy Hoàn Chỉnh

    ### Trước Khi Deploy
    - [ ] Backup database local
    - [ ] Backup code
    - [ ] Test tất cả chức năng trên local
    - [ ] Xóa file test/debug

    ### Deploy
    - [ ] Upload code lên VPS
    - [ ] Import database
    - [ ] Cấu hình `database.php`
    - [ ] Cấu hình Nginx
    - [ ] Setup SSL certificate
    - [ ] Setup websocket server
    - [ ] Cấu hình cronjob
    - [ ] Test tất cả chức năng

    ### Sau Deploy
    - [ ] Test website trên mobile
    - [ ] Test checkout & payment
    - [ ] Test chat system
    - [ ] Setup monitoring (optional)
    - [ ] Setup backup tự động (optional)

    ---

    ## 🆘 Liên Hệ & Support

    Nếu gặp vấn đề:
    1. Xem logs: Nginx, PHP-FPM, PM2
    2. Kiểm tra file permissions
    3. Kiểm tra database connection
    4. Kiểm tra firewall

    ---

    **Chúc bạn deploy thành công! 🎉**
