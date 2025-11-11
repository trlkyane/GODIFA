# 🤖 Hướng dẫn Setup Cronjob Auto-Cancel Orders

## 📋 Mục đích
Tự động hủy các đơn hàng có QR code đã hết hạn (chạy mỗi 30 phút)

---

## 🪟 Windows - Task Scheduler

### Bước 1: Mở Task Scheduler
- Nhấn `Win + R`, gõ `taskschd.msc`, Enter

### Bước 2: Create Basic Task
1. Click **Create Basic Task** (bên phải)
2. Name: `GODIFA Auto Cancel Orders`
3. Description: `Tự động hủy đơn hàng QR hết hạn`
4. Click **Next**

### Bước 3: Trigger
1. Chọn **Daily** → Next
2. Start: Chọn hôm nay
3. Recur every: `1` days
4. Click **Next**

### Bước 4: Action
1. Chọn **Start a program** → Next
2. Program/script: `C:\wamp64\bin\php\php8.3.14\php.exe`
   *(Điều chỉnh path PHP nếu khác)*
3. Arguments: `C:\wamp64\www\GODIFA\cron\cancel_expired_orders.php`
4. Click **Next** → **Finish**

### Bước 5: Chỉnh lại chạy mỗi 30 phút
1. Trong Task Scheduler, tìm task `GODIFA Auto Cancel Orders`
2. Click chuột phải → **Properties**
3. Tab **Triggers** → Double click trigger
4. Check ✅ **Repeat task every**: `30 minutes`
5. For a duration of: `Indefinitely`
6. Click **OK** → **OK**

### Test thủ công:
```powershell
cd C:\wamp64\bin\php\php8.3.14
.\php.exe C:\wamp64\www\GODIFA\cron\cancel_expired_orders.php
```

---

## 🐧 Linux - Crontab

### Setup:
```bash
crontab -e
```

### Thêm dòng này:
```bash
# Chạy mỗi 30 phút
*/30 * * * * /usr/bin/php /var/www/GODIFA/cron/cancel_expired_orders.php >> /var/www/GODIFA/cron/cancel_orders.log 2>&1
```

### Test thủ công:
```bash
php /var/www/GODIFA/cron/cancel_expired_orders.php
```

---

## 📊 Kiểm tra Log

File log: `C:\wamp64\www\GODIFA\cron\cancel_orders.log`

Ví dụ output:
```
[2025-11-04 14:30:01] === Starting auto-cancel cronjob ===
[2025-11-04 14:30:01] Found 3 expired orders to cancel
[2025-11-04 14:30:01] ✅ Cancelled order #125 - GODIFA202511040125
[2025-11-04 14:30:01] ✅ Cancelled order #126 - GODIFA202511040126
[2025-11-04 14:30:01] ✅ Cancelled order #127 - GODIFA202511040127
[2025-11-04 14:30:01] === Cronjob completed: 3 succeeded, 0 failed ===
```

---

## ✅ Verify cronjob hoạt động

1. Tạo 1 đơn hàng test với QR expiry = 1 phút
2. Đợi 1 phút cho QR hết hạn
3. Chạy cronjob manually (xem lệnh ở trên)
4. Check database xem đơn hàng đã chuyển thành "Đã hủy" chưa

---

## 🔧 Troubleshooting

### Lỗi: Class 'Database' not found
→ Check đường dẫn `require_once` trong file cancel_expired_orders.php

### Cronjob không chạy trên Windows
→ Kiểm tra User account trong Task Scheduler có quyền chạy PHP không

### Không có log gì
→ Check quyền write vào folder `cron/`

---

## 📝 Notes

- Cronjob chỉ hủy đơn **"Chờ thanh toán"** và **đã hết hạn**
- Đơn "Đã thanh toán" hoặc "Đã hủy" sẽ được bỏ qua
- Log sẽ ghi lại mọi hoạt động để dễ debug
