-- Test: Tạo đơn hàng mẫu để test nút hủy
-- Chạy script này trong phpMyAdmin

-- Lấy customerID của bạn (thay X bằng ID thật)
SET @customerID = 1; -- Thay số này bằng customerID của bạn

-- Tạo đơn hàng test với trạng thái cho phép hủy
INSERT INTO `order` (
    orderDate, 
    paymentStatus, 
    deliveryStatus, 
    totalAmount, 
    paymentMethod, 
    customerID
) VALUES 
-- Đơn 1: COD chưa thanh toán - CHO PHÉP HỦY
(NOW(), 'Chờ thanh toán', 'Chờ xác nhận', 100000, 'COD', @customerID),

-- Đơn 2: QR chưa thanh toán - CHO PHÉP HỦY
(NOW(), 'Chờ thanh toán', 'Chờ xác nhận', 200000, 'QR', @customerID),

-- Đơn 3: QR đã thanh toán nhưng chưa giao - CHO PHÉP HỦY (cần ảnh)
(NOW(), 'Đã thanh toán', 'Chờ xác nhận', 300000, 'QR', @customerID);

-- Lấy ID các đơn vừa tạo
SET @order1 = LAST_INSERT_ID();
SET @order2 = @order1 + 1;
SET @order3 = @order1 + 2;

-- Thêm chi tiết đơn hàng (giả sử productID = 1 tồn tại)
INSERT INTO order_details (orderID, productID, quantity, price) VALUES
(@order1, 1, 1, 100000),
(@order2, 1, 2, 100000),
(@order3, 1, 3, 100000);

-- Thêm thông tin giao hàng
INSERT INTO order_delivery (orderID, recipientName, recipientPhone, fullAddress) VALUES
(@order1, 'Nguyễn Test 1', '0900000001', 'Địa chỉ test 1'),
(@order2, 'Nguyễn Test 2', '0900000002', 'Địa chỉ test 2'),
(@order3, 'Nguyễn Test 3', '0900000003', 'Địa chỉ test 3');

SELECT 'Đã tạo 3 đơn hàng test! Kiểm tra trang order_history.php' AS Message;
