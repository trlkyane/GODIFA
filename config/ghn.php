<?php
/**
 * Giao Hàng Nhanh (GHN) Configuration
 * File: config/ghn.php
 * 
 * ⚠️ CHỈ SỬ DỤNG CHO: Lấy địa chỉ và tính phí vận chuyển
 * 
 * HƯỚNG DẪN LẤY THÔNG TIN:
 * 1. Đăng ký tài khoản tại: https://5sao.ghn.dev/
 * 2. Tạo shop → Lấy ShopID
 * 3. Vào Cài đặt → Token → Lấy Token
 * 4. Tài liệu API: https://api.ghn.vn/home/docs/detail
 */

return [
    // API Credentials
    'token' => '05213622-ba7c-11f0-bdfd-7a69b8ccea68',
    'shop_id' => 197971,
    
    // API URLs
    'api_url' => 'https://dev-online-gateway.ghn.vn/shiip/public-api',
    // 'api_url' => 'https://online-gateway.ghn.vn/shiip/public-api', // Production
    
    // Địa chỉ kho hàng (dùng để tính phí ship từ kho → khách)
    'from_province_id' => 202,      // Hồ Chí Minh
    'from_district_id' => 1456,     // Quận Tân Phú
    'from_ward_code' => '21511',    // Phường Tây Thạnh
    
    // Service settings (dùng cho tính phí)
    'service_type_id' => 2,         // 2 = Tiêu chuẩn (Standard) - Mặc định
    
    // Danh sách các loại dịch vụ GHN (theo docs: https://api.ghn.vn/home/docs/detail)
    // ⚠️ Chỉ có 2 service types chính xác trong GHN API
    'services' => [
        2 => ['name' => 'Tiêu chuẩn', 'description' => 'Giao trong 2-3 ngày (E-commerce Standard)', 'icon' => '📦'],
        5 => ['name' => 'Nhanh', 'description' => 'Giao nhanh hơn (Express)', 'icon' => '⚡'],
    ],
    
    // Default package dimensions (dùng cho tính phí)
    // ⚠️ GHN yêu cầu: weight >= 200g, dimensions > 0
    'default_weight' => 500,        // 500 gram (0.5 kg) - An toàn với GHN
    'default_length' => 20,         // 20 cm
    'default_width' => 15,          // 15 cm
    'default_height' => 10,         // 10 cm
];
