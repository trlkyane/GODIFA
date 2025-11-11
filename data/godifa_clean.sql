-- ============================================
-- GODIFA DATABASE - CLEAN STRUCTURE
-- Ngày: 09/11/2025
-- Version: 2.0 (Simplified)
-- ============================================

DROP DATABASE IF EXISTS `godifa1`;
CREATE DATABASE `godifa1` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `godifa1`;

-- ============================================
-- 1. BẢNG ROLE (Admin/Staff roles)
-- ============================================
CREATE TABLE `role` (
  `roleID` int NOT NULL AUTO_INCREMENT,
  `roleName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`roleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role` VALUES 
(1, 'Admin', 'Quản trị viên hệ thống'),
(2, 'Staff', 'Nhân viên bán hàng'),
(3, 'Manager', 'Quản lý');

-- ============================================
-- 2. BẢNG USER (Admin/Staff accounts)
-- ============================================
CREATE TABLE `user` (
  `userID` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roleID` int DEFAULT 2,
  `status` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_user_role` (`roleID`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`roleID`) REFERENCES `role` (`roleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. BẢNG CUSTOMER_GROUP (Loyalty tiers)
-- ============================================
CREATE TABLE `customer_group` (
  `groupID` int NOT NULL AUTO_INCREMENT,
  `groupName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `discountPercent` decimal(5,2) DEFAULT 0.00,
  `minSpent` decimal(15,2) DEFAULT 0.00,
  `maxSpent` decimal(15,2) DEFAULT NULL,
  `minOrders` int DEFAULT 0,
  `minAge` int DEFAULT NULL,
  `maxAge` int DEFAULT NULL,
  `gender` enum('Nam','Nữ','Tất cả') COLLATE utf8mb4_unicode_ci DEFAULT 'Tất cả',
  `priority` int DEFAULT 1,
  `autoAssign` tinyint(1) DEFAULT 1,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_group` VALUES
(1, 'Khách hàng mới', 'Khách hàng chưa có đơn hàng', 0.00, 0.00, 500000.00, 0, NULL, NULL, 'Tất cả', 1, 1, 1),
(2, 'Khách hàng thân thiết', 'Chi tiêu từ 500k - 2tr', 5.00, 500000.00, 2000000.00, 1, NULL, NULL, 'Tất cả', 2, 1, 1),
(3, 'Khách hàng VIP', 'Chi tiêu từ 2tr - 5tr', 10.00, 2000000.00, 5000000.00, 3, NULL, NULL, 'Tất cả', 3, 1, 1),
(4, 'Khách hàng VVIP', 'Chi tiêu trên 5tr', 15.00, 5000000.00, NULL, 5, NULL, NULL, 'Tất cả', 4, 1, 1);

-- ============================================
-- 4. BẢNG CUSTOMER (End customers)
-- ============================================
CREATE TABLE `customer` (
  `customerID` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groupID` int DEFAULT 1,
  `status` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customerID`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_customer_group` (`groupID`),
  CONSTRAINT `fk_customer_group` FOREIGN KEY (`groupID`) REFERENCES `customer_group` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. BẢNG CATEGORY (Product categories)
-- ============================================
CREATE TABLE `category` (
  `categoryID` int NOT NULL AUTO_INCREMENT,
  `categoryName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`categoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. BẢNG PRODUCT (Products)
-- ============================================
CREATE TABLE `product` (
  `productID` int NOT NULL AUTO_INCREMENT,
  `productName` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,0) NOT NULL,
  `stock` int NOT NULL DEFAULT 0,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoryID` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`productID`),
  KEY `fk_product_category` (`categoryID`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`categoryID`) REFERENCES `category` (`categoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. BẢNG VOUCHER (Discount vouchers)
-- ============================================
CREATE TABLE `voucher` (
  `voucherID` int NOT NULL AUTO_INCREMENT,
  `voucherCode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucherName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discountType` enum('percent','fixed') COLLATE utf8mb4_unicode_ci DEFAULT 'percent',
  `discountValue` decimal(10,2) NOT NULL,
  `minOrderValue` decimal(10,2) DEFAULT 0.00,
  `maxDiscount` decimal(10,2) DEFAULT NULL,
  `usageLimit` int DEFAULT NULL,
  `usedCount` int DEFAULT 0,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`voucherID`),
  UNIQUE KEY `voucherCode` (`voucherCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. BẢNG VOUCHER_GROUP (Voucher eligibility)
-- ============================================
CREATE TABLE `voucher_group` (
  `voucherGroupID` int NOT NULL AUTO_INCREMENT,
  `voucherID` int NOT NULL,
  `groupID` int NOT NULL,
  PRIMARY KEY (`voucherGroupID`),
  KEY `fk_vg_voucher` (`voucherID`),
  KEY `fk_vg_group` (`groupID`),
  CONSTRAINT `fk_vg_voucher` FOREIGN KEY (`voucherID`) REFERENCES `voucher` (`voucherID`) ON DELETE CASCADE,
  CONSTRAINT `fk_vg_group` FOREIGN KEY (`groupID`) REFERENCES `customer_group` (`groupID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. BẢNG ORDER (Orders - Simplified)
-- ============================================
CREATE TABLE `order` (
  `orderID` int NOT NULL AUTO_INCREMENT,
  `orderDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customerID` int NOT NULL,
  `totalAmount` decimal(10,2) NOT NULL,
  `paymentMethod` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'QR, COD',
  `paymentStatus` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Chờ thanh toán' COMMENT 'Chờ thanh toán, Đã thanh toán, Đã hủy',
  `deliveryStatus` enum('Chờ xác nhận','Đang tiến hành vận chuyển','Hoàn thành','Đã hủy') COLLATE utf8mb4_unicode_ci DEFAULT 'Chờ xác nhận',
  `shippingProvider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'GHN',
  `shippingCode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã vận đơn',
  `shippingFee` decimal(10,2) DEFAULT 0.00,
  `transactionCode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GODIFA202511080001',
  `voucherID` int DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `cancelReason` text COLLATE utf8mb4_unicode_ci,
  `userID` int DEFAULT NULL COMMENT 'Admin xử lý',
  PRIMARY KEY (`orderID`),
  UNIQUE KEY `transactionCode` (`transactionCode`),
  KEY `idx_customer` (`customerID`),
  KEY `idx_shipping_code` (`shippingCode`),
  KEY `idx_transaction_code` (`transactionCode`),
  KEY `fk_order_voucher` (`voucherID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. BẢNG ORDER_DELIVERY (Shipping info)
-- ============================================
CREATE TABLE `order_delivery` (
  `deliveryID` int NOT NULL AUTO_INCREMENT,
  `orderID` int NOT NULL,
  `recipientName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipientEmail` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipientPhone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provinceId` int DEFAULT NULL COMMENT 'GHN province ID',
  `districtId` int DEFAULT NULL COMMENT 'GHN district ID',
  `wardCode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'GHN ward code',
  `fullAddress` varchar(500) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat_ws(', ',`address`,`ward`,`district`,`city`)) STORED,
  `deliveryNotes` text COLLATE utf8mb4_unicode_ci,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`deliveryID`),
  UNIQUE KEY `orderID` (`orderID`),
  KEY `idx_orderID` (`orderID`),
  CONSTRAINT `fk_delivery_order` FOREIGN KEY (`orderID`) REFERENCES `order` (`orderID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. BẢNG ORDER_DETAILS (Order items)
-- ============================================
CREATE TABLE `order_details` (
  `orderDetailID` int NOT NULL AUTO_INCREMENT,
  `orderID` int NOT NULL,
  `productID` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`orderDetailID`),
  KEY `fk_od_order` (`orderID`),
  KEY `fk_od_product` (`productID`),
  CONSTRAINT `fk_od_order` FOREIGN KEY (`orderID`) REFERENCES `order` (`orderID`) ON DELETE CASCADE,
  CONSTRAINT `fk_od_product` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. BẢNG CART (Shopping carts)
-- ============================================
CREATE TABLE `cart` (
  `cartID` int NOT NULL AUTO_INCREMENT,
  `customerID` int DEFAULT NULL,
  `sessionID` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cartID`),
  KEY `idx_customer` (`customerID`),
  KEY `idx_session` (`sessionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. BẢNG CART_ITEMS (Cart items)
-- ============================================
CREATE TABLE `cart_items` (
  `cartItemID` int NOT NULL AUTO_INCREMENT,
  `cartID` int NOT NULL,
  `productID` int NOT NULL,
  `quantity` int NOT NULL DEFAULT 1,
  `addedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cartItemID`),
  KEY `fk_ci_cart` (`cartID`),
  KEY `fk_ci_product` (`productID`),
  CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cartID`) REFERENCES `cart` (`cartID`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_product` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. BẢNG REVIEW (Product reviews)
-- ============================================
CREATE TABLE `review` (
  `reviewID` int NOT NULL AUTO_INCREMENT,
  `productID` int NOT NULL,
  `customerID` int NOT NULL,
  `rating` int NOT NULL CHECK ((`rating` >= 1) AND (`rating` <= 5)),
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reviewID`),
  KEY `fk_review_product` (`productID`),
  KEY `fk_review_customer` (`customerID`),
  CONSTRAINT `fk_review_product` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_customer` FOREIGN KEY (`customerID`) REFERENCES `customer` (`customerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. BẢNG BLOG (Blog posts)
-- ============================================
CREATE TABLE `blog` (
  `blogID` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorID` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`blogID`),
  KEY `fk_blog_author` (`authorID`),
  CONSTRAINT `fk_blog_author` FOREIGN KEY (`authorID`) REFERENCES `user` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 16. BẢNG CONVERSATION (Chat conversations)
-- ============================================
CREATE TABLE `conversation` (
  `conversationID` int NOT NULL AUTO_INCREMENT,
  `customerID` int NOT NULL,
  `lastMessage` text COLLATE utf8mb4_unicode_ci,
  `lastMessageTime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`conversationID`),
  KEY `fk_conv_customer` (`customerID`),
  CONSTRAINT `fk_conv_customer` FOREIGN KEY (`customerID`) REFERENCES `customer` (`customerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 17. BẢNG CHAT (Chat messages)
-- ============================================
CREATE TABLE `chat` (
  `chatID` int NOT NULL AUTO_INCREMENT,
  `conversationID` int NOT NULL,
  `senderType` enum('customer','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `senderID` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `isRead` tinyint(1) DEFAULT 0,
  `sentAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`chatID`),
  KEY `fk_chat_conv` (`conversationID`),
  CONSTRAINT `fk_chat_conv` FOREIGN KEY (`conversationID`) REFERENCES `conversation` (`conversationID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TRIGGERS
-- ============================================

-- Auto-assign customer group when payment status changes
DELIMITER $$
CREATE TRIGGER `after_order_update_assign_group` AFTER UPDATE ON `order` FOR EACH ROW 
BEGIN
    DECLARE customer_total_spent DECIMAL(15,2);
    DECLARE best_group_id INT;
    
    IF NEW.paymentStatus != OLD.paymentStatus THEN
        SELECT COALESCE(SUM(totalAmount), 0) INTO customer_total_spent
        FROM `order`
        WHERE customerID = NEW.customerID 
          AND paymentStatus = 'Đã thanh toán';
        
        SELECT groupID INTO best_group_id
        FROM customer_group
        WHERE status = 1
          AND customer_total_spent >= minSpent
          AND (maxSpent IS NULL OR customer_total_spent <= maxSpent)
        ORDER BY minSpent DESC
        LIMIT 1;
        
        IF best_group_id IS NOT NULL THEN
            UPDATE customer 
            SET groupID = best_group_id 
            WHERE customerID = NEW.customerID;
        END IF;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- STORED PROCEDURES
-- ============================================

DELIMITER $$
CREATE PROCEDURE `auto_assign_customer_groups_by_spending`()
BEGIN
    UPDATE customer c
    LEFT JOIN (
        SELECT 
            o.customerID,
            COALESCE(SUM(CASE WHEN o.paymentStatus = 'Đã thanh toán' THEN o.totalAmount ELSE 0 END), 0) as totalSpent
        FROM `order` o
        GROUP BY o.customerID
    ) spending ON c.customerID = spending.customerID
    SET c.groupID = (
        SELECT cg.groupID
        FROM customer_group cg
        WHERE cg.status = 1
          AND COALESCE(spending.totalSpent, 0) >= cg.minSpent
          AND (cg.maxSpent IS NULL OR COALESCE(spending.totalSpent, 0) <= cg.maxSpent)
        ORDER BY cg.minSpent DESC
        LIMIT 1
    );
END$$
DELIMITER ;

-- ============================================
-- DONE! Clean database structure ready
-- ============================================
