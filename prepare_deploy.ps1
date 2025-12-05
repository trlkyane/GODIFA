# Script Chuẩn Bị Deploy - Windows PowerShell
# Chạy: .\prepare_deploy.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  GODIFA - CHUẨN BỊ DEPLOY LÊN VPS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Kiểm tra Git status
Write-Host "[1/6] Kiểm tra Git status..." -ForegroundColor Yellow
$gitStatus = git status --porcelain
if ($gitStatus) {
    Write-Host "   ⚠️  Có file chưa commit:" -ForegroundColor Red
    git status -s
    $commit = Read-Host "   Commit changes? (y/n)"
    if ($commit -eq "y") {
        $message = Read-Host "   Commit message"
        git add .
        git commit -m $message
        Write-Host "   ✅ Committed!" -ForegroundColor Green
    }
} else {
    Write-Host "   ✅ Git clean" -ForegroundColor Green
}

# 2. Push lên GitHub
Write-Host ""
Write-Host "[2/6] Push lên GitHub..." -ForegroundColor Yellow
$push = Read-Host "   Push to GitHub? (y/n)"
if ($push -eq "y") {
    git push origin Hieu
    Write-Host "   ✅ Pushed!" -ForegroundColor Green
} else {
    Write-Host "   ⏭️  Skipped" -ForegroundColor Gray
}

# 3. Xóa các file test/debug
Write-Host ""
Write-Host "[3/6] Kiểm tra file test/debug..." -ForegroundColor Yellow
$testFiles = @(
    "test_*.php",
    "debug_*.php",
    "check_*.php",
    "demo_*.html"
)

$foundFiles = @()
foreach ($pattern in $testFiles) {
    $files = Get-ChildItem -Path . -Filter $pattern -Recurse -File
    if ($files) {
        $foundFiles += $files
    }
}

if ($foundFiles.Count -gt 0) {
    Write-Host "   ⚠️  Tìm thấy $($foundFiles.Count) file test/debug:" -ForegroundColor Yellow
    $foundFiles | ForEach-Object { Write-Host "      - $($_.FullName)" -ForegroundColor Gray }
    Write-Host "   💡 Nên xóa hoặc không upload những file này lên VPS" -ForegroundColor Cyan
} else {
    Write-Host "   ✅ Không có file test/debug" -ForegroundColor Green
}

# 4. Backup database
Write-Host ""
Write-Host "[4/6] Export database..." -ForegroundColor Yellow
$backup = Read-Host "   Export database local? (y/n)"
if ($backup -eq "y") {
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupFile = "data\backup_$timestamp.sql"
    
    Write-Host "   Exporting database..." -ForegroundColor Gray
    # Giả sử dùng mysqldump
    & "C:\wamp64\bin\mysql\mysql8.0.31\bin\mysqldump.exe" -u root godifa1 | Out-File -FilePath $backupFile -Encoding utf8
    
    if (Test-Path $backupFile) {
        Write-Host "   ✅ Database exported: $backupFile" -ForegroundColor Green
    } else {
        Write-Host "   ❌ Export failed!" -ForegroundColor Red
    }
} else {
    Write-Host "   ⏭️  Skipped" -ForegroundColor Gray
}

# 5. Tạo .gitignore nếu chưa có
Write-Host ""
Write-Host "[5/6] Kiểm tra .gitignore..." -ForegroundColor Yellow
if (!(Test-Path ".gitignore")) {
    $gitignoreContent = @"
# Environment files
.env
websocket-server/.env

# Test/Debug files
test_*.php
debug_*.php
check_*.php
demo_*.html

# Logs
logs/*.log
*.log

# OS files
.DS_Store
Thumbs.db
desktop.ini

# IDE
.vscode/
.idea/
*.swp
*.swo

# Vendor (nếu dùng Composer)
/vendor/

# Node modules
node_modules/
websocket-server/node_modules/

# Uploads (tùy chọn - có thể muốn commit hoặc không)
# image/uploads/
"@
    $gitignoreContent | Out-File -FilePath ".gitignore" -Encoding utf8
    Write-Host "   ✅ Created .gitignore" -ForegroundColor Green
} else {
    Write-Host "   ✅ .gitignore exists" -ForegroundColor Green
}

# 6. Checklist cuối
Write-Host ""
Write-Host "[6/6] Deployment Checklist" -ForegroundColor Yellow
Write-Host ""
Write-Host "   📋 TRƯỚC KHI DEPLOY, KIỂM TRA:" -ForegroundColor Cyan
Write-Host "   [ ] Code đã push lên GitHub" -ForegroundColor White
Write-Host "   [ ] Database đã backup" -ForegroundColor White
Write-Host "   [ ] File .env.example có đầy đủ" -ForegroundColor White
Write-Host "   [ ] Xóa file test/debug" -ForegroundColor White
Write-Host "   [ ] Domain đã trỏ về VPS" -ForegroundColor White
Write-Host "   [ ] VPS đã cài: PHP 8.1, MySQL, Nginx, Node.js" -ForegroundColor White
Write-Host ""
Write-Host "   📚 TÀI LIỆU DEPLOY:" -ForegroundColor Cyan
Write-Host "   - Chi tiết: docs\VPS_DEPLOYMENT_GUIDE.md" -ForegroundColor White
Write-Host "   - Checklist nhanh: DEPLOY_CHECKLIST.md" -ForegroundColor White
Write-Host ""

Write-Host "========================================" -ForegroundColor Green
Write-Host "  ✅ SẴN SÀNG DEPLOY!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Các bước tiếp theo:" -ForegroundColor Yellow
Write-Host "1. SSH vào VPS: ssh root@your_vps_ip" -ForegroundColor White
Write-Host "2. Clone repo: git clone https://github.com/trlkyane/GODIFA.git" -ForegroundColor White
Write-Host "3. Làm theo: DEPLOY_CHECKLIST.md" -ForegroundColor White
Write-Host ""

# Mở file hướng dẫn
$open = Read-Host "Mở file DEPLOY_CHECKLIST.md? (y/n)"
if ($open -eq "y") {
    Start-Process "DEPLOY_CHECKLIST.md"
}
