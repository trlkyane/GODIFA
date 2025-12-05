<?php
/**
 * Helper: Gửi email thông báo yêu cầu hoàn trả
 * File: includes/send_return_notification.php
 * VPS Compatible
 * 
 * NOTE: Để gửi email thành công trên VPS, cần cấu hình SMTP trong php.ini:
 * - SMTP = smtp.gmail.com (hoặc SMTP server của bạn)
 * - smtp_port = 587
 * - Hoặc sử dụng PHPMailer library cho advanced features
 * 
 * Development: Email sẽ fail nhưng không gây crash hệ thống
 */

require_once __DIR__ . '/../config/constants.php';

/**
 * Gửi email thông báo trạng thái yêu cầu hoàn trả
 * 
 * @param string $customerEmail Email khách hàng
 * @param string $customerName Tên khách hàng
 * @param int $orderID Mã đơn hàng
 * @param string $status Trạng thái mới (Đã chấp nhận, Đã từ chối, Đã hoàn tiền)
 * @param string $adminNote Ghi chú từ admin
 * @return bool
 */
function sendReturnNotification($customerEmail, $customerName, $orderID, $status, $adminNote = '') {
    $subject = '';
    $message = '';
    
    switch ($status) {
        case 'Đã chấp nhận':
            $subject = "✅ Yêu cầu hoàn trả đơn hàng #$orderID đã được chấp nhận - GODIFA";
            $message = "
                <h2 style='color: #16a34a;'>Yêu cầu hoàn trả đã được chấp nhận</h2>
                <p>Xin chào <strong>$customerName</strong>,</p>
                <p>Yêu cầu hoàn trả đơn hàng <strong>#$orderID</strong> của bạn đã được chấp nhận.</p>
                <p><strong>Hướng dẫn tiếp theo:</strong></p>
                <ol>
                    <li>Vui lòng đóng gói sản phẩm cẩn thận</li>
                    <li>Chờ shipper đến lấy hàng hoàn trả</li>
                    <li>Sau khi nhận được hàng và kiểm tra, chúng tôi sẽ hoàn tiền cho bạn</li>
                </ol>
                " . ($adminNote ? "<div style='background: #f0f9ff; padding: 15px; border-left: 4px solid #3b82f6; margin: 15px 0;'><strong>Ghi chú từ GODIFA:</strong><br>$adminNote</div>" : "") . "
                <p>Cảm ơn bạn đã tin tưởng GODIFA!</p>
            ";
            break;
            
        case 'Đã từ chối':
            $subject = "❌ Yêu cầu hoàn trả đơn hàng #$orderID đã bị từ chối - GODIFA";
            $message = "
                <h2 style='color: #dc2626;'>Yêu cầu hoàn trả đã bị từ chối</h2>
                <p>Xin chào <strong>$customerName</strong>,</p>
                <p>Rất tiếc, yêu cầu hoàn trả đơn hàng <strong>#$orderID</strong> của bạn không được chấp nhận.</p>
                " . ($adminNote ? "<div style='background: #fef2f2; padding: 15px; border-left: 4px solid #ef4444; margin: 15px 0;'><strong>Lý do từ chối:</strong><br>$adminNote</div>" : "") . "
                <p>Nếu bạn có thắc mắc, vui lòng liên hệ hotline: <strong>0123456789</strong></p>
                <p>Xin cảm ơn!</p>
            ";
            break;
            
        case 'Đã hoàn tiền':
        case 'Đã hoàn trả':
            $subject = "💰 Đã hoàn tiền đơn hàng #$orderID - GODIFA";
            $message = "
                <h2 style='color: #9333ea;'>Hoàn tiền thành công</h2>
                <p>Xin chào <strong>$customerName</strong>,</p>
                <p>Chúng tôi đã hoàn tiền cho đơn hàng <strong>#$orderID</strong> của bạn.</p>
                " . ($adminNote ? "<div style='background: #faf5ff; padding: 15px; border-left: 4px solid #9333ea; margin: 15px 0;'><strong>Thông tin giao dịch:</strong><br>$adminNote</div>" : "") . "
                <p><strong>Lưu ý:</strong> Tiền sẽ được hoàn về tài khoản của bạn trong vòng 1-3 ngày làm việc.</p>
                <p>Cảm ơn bạn đã mua sắm tại GODIFA!</p>
            ";
            break;
            
        default:
            return false;
    }
    
    // HTML Email Template
    $htmlMessage = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
            .footer { background: #f9fafb; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; color: #6b7280; }
            ol { padding-left: 20px; }
            ol li { margin: 8px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0; font-size: 28px;'>🍫 GODIFA Chocolate</h1>
            </div>
            <div class='content'>
                $message
            </div>
            <div class='footer'>
                <p><strong>GODIFA Chocolate</strong></p>
                <p>Hotline: 0123456789 | Email: support@godifa.vn</p>
                <p>&copy; " . date('Y') . " GODIFA. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: GODIFA Chocolate <noreply@godifa.vn>" . "\r\n";
    
    // Gửi email (suppress error nếu SMTP chưa config)
    // Trong môi trường development, hàm này sẽ trả về false nếu không có mail server
    $result = @mail($customerEmail, $subject, $htmlMessage, $headers);
    
    // Log email content nếu gửi thất bại (cho debug)
    if (!$result) {
        error_log("Email notification failed for: $customerEmail (Order #$orderID, Status: $status)");
    }
    
    return $result;
}
