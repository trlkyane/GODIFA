<?php
/**
 * SePay Configuration
 * File: config/sepay.php
 * 
 * HƯỚNG DẪN LẤY THÔNG TIN:
 * 1. Đăng ký tài khoản tại: https://my.sepay.vn/
 * 2. Vào Cài đặt → API → Lấy API Key
 * 3. Điền thông tin tài khoản ngân hàng của bạn
 */

return [
    // ⚠️ QUAN TRỌNG: Thay đổi thông tin này
    'account_number' => '0123456789',  // Số tài khoản ngân hàng
    'account_name' => 'CONG TY GODIFA', // Tên chủ tài khoản (IN HOA, không dấu)
    'bank_code' => 'VCB', // Mã ngân hàng: VCB, TCB, MB, ACB, VPB, TPB, etc.
    'api_key' => '', // API Key từ SePay (để trống nếu chưa có)
    
    // Cấu hình webhook
    'webhook_url' => 'https://yourdomain.com/webhook/sepay.php',
    'webhook_secret' => hash('sha256', 'GODIFA_SEPAY_SECRET_' . date('Y')), // Secret key để verify
    
    // Template nội dung chuyển khoản
    'transfer_template' => 'GODIFA{orderID}', // GODIFA000123
    
    // QR Code settings
    'qr_template' => 'compact2', // compact, compact2, qr_only
];
