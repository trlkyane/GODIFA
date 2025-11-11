<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SePay Webhook</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            font-family: inherit;
        }
        textarea {
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }
        .result.show { display: block; }
        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            margin-top: 10px;
        }
        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #2196F3;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test SePay Webhook</h1>
        <p class="subtitle">Giả lập SePay gửi webhook về server để test thanh toán</p>
        
        <div class="info-box">
            <strong>📋 Hướng dẫn:</strong><br>
            1. Tạo đơn hàng qua checkout (hoặc dùng transactionCode có sẵn)<br>
            2. Nhập thông tin bên dưới<br>
            3. Click "Gửi Webhook Test"<br>
            4. Check database: <code>SELECT * FROM `order` WHERE transactionCode='...'</code>
        </div>

        <div class="section">
            <h2>📝 Thông Tin Webhook</h2>
            
            <label>Transaction Code (Mã giao dịch):</label>
            <input type="text" id="transactionCode" placeholder="Ví dụ: GODIFA202511070001" value="">
            
            <label>Số tiền (VNĐ):</label>
            <input type="number" id="amount" placeholder="Ví dụ: 272000" value="10000">
            
            <label>Gateway (Ngân hàng):</label>
            <input type="text" id="gateway" value="VietinBank">
            
            <label>Bank Transaction ID:</label>
            <input type="text" id="bankId" placeholder="Tự động generate nếu để trống" value="">
        </div>

        <div class="section">
            <h2>🔧 Payload JSON (Preview)</h2>
            <textarea id="payloadPreview" rows="15" readonly></textarea>
        </div>

        <button class="btn" onclick="sendWebhook()">🚀 Gửi Webhook Test</button>
        
        <div id="result" class="result"></div>
    </div>

    <script>
        // Auto-fill từ URL params
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('code')) {
            document.getElementById('transactionCode').value = urlParams.get('code');
        }
        if (urlParams.get('amount')) {
            document.getElementById('amount').value = urlParams.get('amount');
        }

        // Auto-generate payload preview
        function updatePayload() {
            const transactionCode = document.getElementById('transactionCode').value || 'GODIFA202511070001';
            const amount = parseInt(document.getElementById('amount').value) || 10000;
            const gateway = document.getElementById('gateway').value || 'VietinBank';
            const bankId = document.getElementById('bankId').value || Math.floor(Math.random() * 1000000);
            
            const payload = {
                "gateway": gateway,
                "transactionDate": new Date().toISOString().slice(0, 19).replace('T', ' '),
                "accountNumber": "105875539922",
                "subAccount": "155",
                "code": null,
                "content": `106587008893-0978848500-SEVQR TKP155 ${transactionCode}`,
                "transferType": "in",
                "description": `BankAPINotify 106587008893-0978848500-SEVQR TKP155 ${transactionCode}`,
                "transferAmount": amount,
                "referenceCode": Math.random().toString(36).substr(2, 9),
                "accumulated": 4340083,
                "id": bankId
            };
            
            document.getElementById('payloadPreview').value = JSON.stringify(payload, null, 2);
        }
        
        // Update preview khi input thay đổi
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', updatePayload);
        });
        
        // Initial preview
        updatePayload();
        
        async function sendWebhook() {
            const resultDiv = document.getElementById('result');
            resultDiv.className = 'result show';
            resultDiv.innerHTML = '<p>⏳ Đang gửi webhook...</p>';
            
            const payload = JSON.parse(document.getElementById('payloadPreview').value);
            
            try {
                const response = await fetch('/GODIFA/webhook/sepay.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    resultDiv.className = 'result success show';
                    resultDiv.innerHTML = `
                        <h3>✅ Webhook gửi thành công!</h3>
                        <p><strong>Message:</strong> ${data.message || 'Payment confirmed'}</p>
                        ${data.orderID ? `<p><strong>Order ID:</strong> ${data.orderID}</p>` : ''}
                        <p style="margin-top: 15px;"><strong>Kiểm tra database:</strong></p>
                        <pre>SELECT * FROM \`order\` WHERE transactionCode='${payload.content.match(/GODIFA\d+/)[0]}';</pre>
                        <p style="margin-top: 15px;"><strong>Response:</strong></p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } else {
                    throw new Error(data.error || 'Unknown error');
                }
            } catch (error) {
                resultDiv.className = 'result error show';
                resultDiv.innerHTML = `
                    <h3>❌ Lỗi gửi webhook</h3>
                    <p><strong>Chi tiết:</strong> ${error.message}</p>
                    <p style="margin-top: 15px;">Kiểm tra log tại: <code>logs/sepay_webhook.log</code></p>
                `;
            }
        }
    </script>
</body>
</html>
