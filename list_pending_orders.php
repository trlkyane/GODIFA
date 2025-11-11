<?php
/**
 * Lấy danh sách đơn hàng chưa thanh toán (để test webhook)
 */
require_once __DIR__ . '/model/database.php';

$db = Database::getInstance();
$conn = $db->connect();

$stmt = $conn->query("
    SELECT 
        orderID,
        transactionCode,
        totalAmount,
        paymentStatus,
        deliveryStatus,
        orderDate,
        recipientName,
        recipientPhone
    FROM `order`
    WHERE paymentStatus = 'Chờ thanh toán'
    ORDER BY orderID DESC
    LIMIT 10
");

$orders = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng Chờ Thanh Toán</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .code {
            font-family: 'Courier New', monospace;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
        }
        .code:hover {
            background: #667eea;
            color: white;
        }
        .amount {
            color: #28a745;
            font-weight: 600;
        }
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            background: #ffc107;
            color: #856404;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 13px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .actions {
            margin-bottom: 20px;
        }
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .toast.show {
            display: block;
            animation: slideIn 0.3s;
        }
        @keyframes slideIn {
            from { transform: translateX(400px); }
            to { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Đơn Hàng Chờ Thanh Toán</h1>
        
        <div class="actions">
            <a href="test_sepay_webhook.php" class="btn">🧪 Test Webhook SePay</a>
            <a href="view/cart/checkout.php" class="btn" style="background: #28a745;">➕ Tạo Đơn Mới</a>
        </div>

        <?php if (empty($orders)): ?>
        <div class="empty">
            <h3>📭 Không có đơn hàng chờ thanh toán</h3>
            <p style="margin-top: 10px;">Tạo đơn hàng mới qua trang checkout</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Transaction Code</th>
                    <th>Khách Hàng</th>
                    <th>Số Tiền</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= $order['orderID'] ?></td>
                    <td>
                        <span class="code" onclick="copyCode('<?= $order['transactionCode'] ?>')" title="Click để copy">
                            <?= $order['transactionCode'] ?>
                        </span>
                    </td>
                    <td>
                        <?= htmlspecialchars($order['recipientName'] ?? 'Chưa cập nhật') ?><br>
                        <small style="color: #666;"><?= htmlspecialchars($order['recipientPhone'] ?? '') ?></small>
                    </td>
                    <td class="amount"><?= number_format($order['totalAmount']) ?>₫</td>
                    <td><span class="status"><?= $order['paymentStatus'] ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($order['orderDate'])) ?></td>
                    <td>
                        <a href="test_sepay_webhook.php?code=<?= $order['transactionCode'] ?>&amount=<?= $order['totalAmount'] ?>" 
                           class="btn" style="font-size: 12px;">
                            🧪 Test
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div id="toast" class="toast">✅ Đã copy: <span id="toastText"></span></div>

    <script>
        function copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                const toast = document.getElementById('toast');
                document.getElementById('toastText').textContent = code;
                toast.classList.add('show');
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 2000);
            });
        }

        // Auto-fill nếu có param từ URL
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');
        if (code) {
            copyCode(code);
        }
    </script>
</body>
</html>
