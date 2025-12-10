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

// Load environment variables
require_once __DIR__ . '/env.php';

return [
    // Lấy thông tin từ .env file
    'account_number' => Env::get('SEPAY_ACCOUNT_NUMBER', '0123456789'),  // Số tài khoản ngân hàng
    'account_name' => Env::get('SEPAY_ACCOUNT_NAME', 'CONG TY GODIFA'), // Tên chủ tài khoản (IN HOA, không dấu)
    'bank_code' => 'VCB', // Mã ngân hàng: VCB, TCB, MB, ACB, VPB, TPB, etc.
    'api_key' => Env::get('SEPAY_API_TOKEN', ''), // API Key từ SePay (để trống nếu chưa có)
    
    // Cấu hình webhook
    'webhook_url' => 'https://yourdomain.com/webhook/sepay.php',
    'webhook_secret' => hash('sha256', 'GODIFA_SEPAY_SECRET_' . date('Y')), // Secret key để verify
    
    // Template nội dung chuyển khoản
    'transfer_template' => 'GODIFA{orderID}', // GODIFA000123
    
    // QR Code settings
    'qr_template' => 'compact2', // compact, compact2, qr_only
];
