<?php
require 'config.php';

file_put_contents(__DIR__ . '/log_webhook.txt', date('Y-m-d H:i:s') . "\n" . file_get_contents('php://input') . "\n", FILE_APPEND); 
// Nhận dữ liệu JSON từ SePay
$data = file_get_contents('php://input');
file_put_contents('webhook_raw.txt', $data . "\n", FILE_APPEND); // Ghi log raw data

$payload = json_decode($data, true);

// Kiểm tra nếu dữ liệu hợp lệ
if ($payload && isset($payload['description'])) {
    $description = $payload['description'];
    file_put_contents('webhook_log.txt', "Description: $description\n", FILE_APPEND);

    // Lấy order_code từ mô tả, ví dụ: "BankAPINotify ... DH1748265325498"
    if (preg_match('/DH\d{13,}/', $description, $matches)) {
        $orderCode = $matches[0];

        $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE order_code = ?");
        if (!$stmt) {
            file_put_contents('webhook_log.txt', "Prepare failed: " . $conn->error . "\n", FILE_APPEND);
        } else {
            $stmt->bind_param("s", $orderCode);
            if (!$stmt->execute()) {
                file_put_contents('webhook_log.txt', "Execute failed: " . $stmt->error . "\n", FILE_APPEND);
            } else {
                file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Đã cập nhật đơn hàng: $orderCode\n", FILE_APPEND);
            }
        }

        http_response_code(200);
        echo "OK - Order updated";
    } else {
        file_put_contents('webhook_log.txt', "Không tìm thấy mã đơn hàng trong description.\n", FILE_APPEND);
        http_response_code(400);
        echo "Không tìm thấy mã đơn hàng trong description.";
    }
} else {
    file_put_contents('webhook_log.txt', "Dữ liệu webhook không hợp lệ hoặc thiếu description.\n", FILE_APPEND);
    http_response_code(400);
    echo "Dữ liệu webhook không hợp lệ.";
}
