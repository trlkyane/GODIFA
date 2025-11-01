<?php
// FILE: GODIFA/view/chat/index.php

// ------------------------------------------------------------------
// LOGIC KHỞI TẠO VÀ PHÂN LOẠI NGƯỜI DÙNG (Fix Undefined Variable)
// ------------------------------------------------------------------

// Khởi động Session nếu chưa được khởi động
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Gán giá trị mặc định (Guest/Customer)
$user_id = 'guest';
$user_type = 'customer'; 
$currentConversationID = 'null'; 
$currentPartnerName = 'Hỗ trợ Khách hàng';
$unread_count_total = 0;
$conversations = []; 

// 2. KIỂM TRA ĐĂNG NHẬP VÀ GÁN VAI TRÒ CHÍNH XÁC

// Ưu tiên kiểm tra Staff/Admin (User)
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Đã đăng nhập là User/Staff (từ bảng user)
    $user_id = $_SESSION['user_id'];
    $user_type = 'user'; // <--- QUAN TRỌNG: Gán SENDERTYPE LÀ 'user'
} 
// Sau đó kiểm tra Customer
else if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
    // Đã đăng nhập là Customer (từ bảng customer)
    $user_id = $_SESSION['customer_id'];
    $user_type = 'customer'; // SENDERTYPE LÀ 'customer'
}


// LƯU Ý: Nếu $currentConversationID và $unread_count_total được gán 
// từ Controller/Model PHP, bạn cần giữ nguyên logic đó ở đây.
// Nếu không, chúng sẽ giữ nguyên giá trị mặc định là 'null' và 0.

// ------------------------------------------------------------------
// BẮT ĐẦU CẤU TRÚC HTML
// ------------------------------------------------------------------
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat Support</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* -------------------- Định vị Nút Kích hoạt -------------------- */
        .chat-toggle-btn {
            position: fixed; 
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #ee4d2d; 
            color: white;
            border: none;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            cursor: pointer;
            z-index: 1000;
            transition: transform 0.2s;
        }

        .chat-toggle-btn:hover {
            transform: scale(1.05);
        }

        /* Badge thông báo */
        .chat-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            font-size: 12px;
            font-weight: bold;
            border-radius: 50%;
            padding: 3px 6px;
            line-height: 1;
        }

        /* -------------------- Cửa sổ Chat Popup -------------------- */
        .chat-popup-container {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 380px;
            height: 500px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            z-index: 999;
            transform-origin: bottom right; 
            transition: all 0.3s ease-in-out;
        }

        .chat-popup-container.hidden {
            transform: scale(0);
            opacity: 0;
            pointer-events: none;
        }

        /* Header Popup */
        .popup-header {
            background-color: #ee4d2d;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .popup-header .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            line-height: 1;
        }

        /* -------------------- Cấu trúc Chat Nội bộ -------------------- */
        .shopee-chat-container {
            display: flex;
            flex: 1; 
            width: 100%;
            overflow: hidden;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        /* Khu vực Tin nhắn */
        .messages-display {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* Bong bóng Chat */
        .message-bubble {
            max-width: 75%;
            padding: 8px 12px;
            border-radius: 12px;
            margin-bottom: 10px;
            line-height: 1.4;
            font-size: 14px;
            position: relative;
        }
        .message-bubble.received {
            align-self: flex-start;
            background-color: #f1f1f1;
            color: #333;
            border-top-left-radius: 2px;
        }
        .message-bubble.sent {
            align-self: flex-end;
            background-color: #ff5733; 
            color: white;
            border-top-right-radius: 2px;
        }

        .timestamp {
            display: block;
            font-size: 10px;
            margin-top: 5px;
            opacity: 0.7;
            text-align: right;
        }

        .message-bubble.sent .timestamp {
            color: white;
        }
        
        /* Khu vực Input */
        .chat-input-area {
            padding: 10px 15px;
            border-top: 1px solid #e0e0e0;
            flex-shrink: 0;
        }

        .message-form {
            display: flex;
        }

        .message-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
            font-size: 14px;
        }

        .send-button {
            background-color: #ee4d2d; 
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        
        .send-button:hover {
            background-color: #ff6633;
        }
    </style>
    </head>
<body>
    
    <button id="chat-toggle-btn" class="chat-toggle-btn">
        <i class="fas fa-comment-dots"></i>
        
        <span class="chat-badge" id="chat-badge" 
              data-initial-count="<?php echo $unread_count_total; ?>"
              style="<?php echo $unread_count_total > 0 ? 'display: block;' : 'display: none;'; ?>">
            <?php echo $unread_count_total; ?>
        </span> 
    </button>

    <div id="chat-popup-container" class="chat-popup-container hidden">
        
        <div class="popup-header">
            <?php echo htmlspecialchars($currentPartnerName); ?>
            <button id="chat-close-btn" class="close-btn">&times;</button>
        </div>

        <div class="shopee-chat-container">
            <div class="chat-main">
                
                <div id="messages-display" class="messages-display">
                    </div>

                <div class="chat-input-area">
                    <form id="message-form" class="message-form">
                        <input type="text" id="message-input" placeholder="Nhập tin nhắn..." class="message-input">
                        <button type="submit" class="send-button">Gửi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="chat-metadata" 
        data-user-id="<?php echo htmlspecialchars($user_id); ?>" 
        data-user-type="<?php echo htmlspecialchars($user_type); ?>"
        data-conversation-id="<?php echo $currentConversationID; ?>"
        style="display: none;">
    </div>
    
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    
    <script src="/GODIFA/public/js/chat_client.js"></script> 
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('chat-toggle-btn');
            const closeBtn = document.getElementById('chat-close-btn');
            const popupContainer = document.getElementById('chat-popup-container');
            
            if (toggleBtn && popupContainer) {
                 // Mở/Đóng Popup
                 toggleBtn.addEventListener('click', function() {
                     popupContainer.classList.toggle('hidden');
                 });
            }

            if (closeBtn && popupContainer) {
                closeBtn.addEventListener('click', function() {
                     popupContainer.classList.add('hidden');
                 });
            }
            
            // Hiển thị popup ngay khi tải nếu có tin nhắn chưa đọc
            const badgeElement = document.getElementById('chat-badge');
            if (badgeElement && popupContainer) {
                const initialCount = parseInt(badgeElement.dataset.initialCount);
                if (initialCount > 0) {
                     // Nếu có tin nhắn chưa đọc, mở popup
                     popupContainer.classList.remove('hidden');
                }
            }
        });
    </script>
</body>
</html>