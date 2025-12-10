# Hướng dẫn Update trên VPS

## Bước 1: SSH vào VPS
```bash
ssh root@your-vps-ip
```

## Bước 2: Di chuyển vào thư mục project
```bash
cd /var/www/godifaproject.id.vn
```

## Bước 3: Pull code mới
```bash
git pull origin Hieu
```

## Bước 4: Xóa vendor và composer.lock cũ
```bash
rm -rf vendor/
rm -f composer.lock
```

## Bước 5: Cài lại dependencies
```bash
composer install --no-dev --optimize-autoloader
```

## Bước 6: Kiểm tra PHP version
```bash
php -v
# Phải hiển thị PHP >= 7.4
```

## Bước 7: Test export Excel
- Vào trang Admin → Thống kê
- Click nút "Xuất Excel"
- Kiểm tra file có download được không

## Nếu vẫn lỗi "PHP >= 8.3.0":

### Option A: Ignore platform check (NHANH)
```bash
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
```

### Option B: Sửa composer.json thêm config
```bash
composer config platform.php 8.1.33
composer install --no-dev --optimize-autoloader
```

### Option C: Upgrade PHP trên VPS (NẾU CÓ QUYỀN ROOT)
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-zip php8.3-gd

# Chuyển đổi sang PHP 8.3
sudo update-alternatives --set php /usr/bin/php8.3
php -v
```

## Kiểm tra lại
```bash
php -v
composer --version
cd /var/www/godifaproject.id.vn
php -r "require 'vendor/autoload.php'; echo 'OK';"
```
