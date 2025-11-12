-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 12, 2025 at 06:32 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `godifa1`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `auto_assign_customer_groups`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `auto_assign_customer_groups` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE cust_id INT;
    DECLARE cust_age INT;
    DECLARE cust_gender VARCHAR(10);
    DECLARE cust_orders INT;
    DECLARE cust_spent DECIMAL(15,2);
    DECLARE best_group_id INT;
    DECLARE best_priority INT;
    
    DECLARE customer_cursor CURSOR FOR 
        SELECT 
            c.customerID,
            TIMESTAMPDIFF(YEAR, c.birthdate, CURDATE()) as age,
            c.gender,
            COUNT(o.orderID) as totalOrders,
            COALESCE(SUM(o.totalAmount), 0) as totalSpent
        FROM customer c
        LEFT JOIN `order` o ON c.customerID = o.customerID AND o.paymentStatus != 'Đã hủy'
        WHERE c.birthdate IS NOT NULL AND c.gender IS NOT NULL
        GROUP BY c.customerID, c.birthdate, c.gender;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN customer_cursor;
    
    read_loop: LOOP
        FETCH customer_cursor INTO cust_id, cust_age, cust_gender, cust_orders, cust_spent;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Tìm nhóm phù hợp nhất (priority cao nhất)
        SELECT groupID, priority INTO best_group_id, best_priority
        FROM customer_group
        WHERE status = 1 
          AND autoAssign = 1
          AND (gender = 'Tất cả' OR gender = cust_gender)
          AND (minAge IS NULL OR cust_age >= minAge)
          AND (maxAge IS NULL OR cust_age <= maxAge)
          AND (minOrders <= cust_orders)
          AND (minSpent <= cust_spent)
        ORDER BY priority DESC, discountPercent DESC
        LIMIT 1;
        
        -- Cập nhật nhóm cho khách hàng
        IF best_group_id IS NOT NULL THEN
            UPDATE customer SET groupID = best_group_id WHERE customerID = cust_id;
        END IF;
        
        SET best_group_id = NULL;
        SET best_priority = NULL;
    END LOOP;
    
    CLOSE customer_cursor;
END$$

DROP PROCEDURE IF EXISTS `auto_assign_customer_groups_by_spending`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `auto_assign_customer_groups_by_spending` ()   BEGIN
    UPDATE customer c
    LEFT JOIN (
        SELECT 
            o.customerID,
            COALESCE(SUM(CASE WHEN o.paymentStatus != 'Đã hủy' THEN o.totalAmount ELSE 0 END), 0) as totalSpent
        FROM `order` o
        GROUP BY o.customerID
    ) spending ON c.customerID = spending.customerID
    SET c.groupID = (
        SELECT cg2.groupID
        FROM customer_group cg2
        WHERE cg2.status = 1
          AND COALESCE(spending.totalSpent, 0) >= cg2.minSpent
          AND (cg2.maxSpent IS NULL OR COALESCE(spending.totalSpent, 0) <= cg2.maxSpent)
        ORDER BY cg2.minSpent DESC
        LIMIT 1
    );
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `blog`
--

DROP TABLE IF EXISTS `blog`;
CREATE TABLE IF NOT EXISTS `blog` (
  `blogID` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `date` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 1 = Hoạt động, 0 = Đã khóa',
  PRIMARY KEY (`blogID`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `blog`
--

INSERT INTO `blog` (`blogID`, `title`, `content`, `date`, `status`) VALUES
(1, 'Top 5 sản phẩm bổ gan bán chạy nhất tháng 10', 'Trong tháng 10 này, các sản phẩm bổ gan Orihiro đã nhận được rất nhiều sự quan tâm từ khách hàng. Đặc biệt là viên uống bổ gan Shijimi với chiết xuất từ nghêu Nhật Bản...', '2025-10-10 10:00:00', 1),
(2, 'Hướng dẫn chăm sóc da mùa hanh khô', 'Mùa hanh khô đã đến, làn da của bạn cần được chăm sóc đặc biệt. Dưới đây là 5 tips giúp da bạn luôn mềm mại, mịn màng...', '2025-10-12 14:30:00', 1),
(3, 'Bí quyết giảm cân an toàn và hiệu quả', 'Giảm cân không chỉ là ăn kiêng mà còn cần có chế độ sinh hoạt và bổ sung thực phẩm chức năng phù hợp. Viên uống giảm cán Minami Diet...', '2025-10-14 09:00:00', 1),
(4, 'Sản phẩm mới về: Kem dưỡng trắng Transino', 'Chúng tôi vừa nhập khẩu về lô hàng mới với kem dưỡng trắng da Transino từ Nhật Bản. Đây là dòng sản phẩm chuyên trị nám, tàn nhang.......', '2025-10-15 16:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `cartID` int NOT NULL,
  `customerID` int NOT NULL,
  PRIMARY KEY (`cartID`),
  KEY `fk_cart_user` (`customerID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cartID`, `customerID`) VALUES
(1, 1),
(2, 2),
(3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE IF NOT EXISTS `cart_items` (
  `cartID` int NOT NULL,
  `productID` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,0) NOT NULL,
  PRIMARY KEY (`cartID`,`productID`),
  KEY `fk_cartItems_product` (`productID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cartID`, `productID`, `quantity`, `price`) VALUES
(1, 1, 2, 700000),
(1, 7, 1, 70000),
(2, 2, 1, 440000),
(2, 13, 3, 185000),
(3, 18, 1, 270000);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `categoryID` int NOT NULL AUTO_INCREMENT,
  `categoryName` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  PRIMARY KEY (`categoryID`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`categoryID`, `categoryName`, `status`) VALUES
(1, 'Thực phẩm chức năng', 1),
(2, 'Mỹ phẩm', 1),
(3, 'Phụ kiện - Làm đẹp', 1),
(4, 'Mẹ & Bé', 1),
(5, 'Gia dụng', 1),
(8, 'ádasd', 1);

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

DROP TABLE IF EXISTS `chat`;
CREATE TABLE IF NOT EXISTS `chat` (
  `chatID` int NOT NULL AUTO_INCREMENT,
  `conversation_ID` int NOT NULL,
  `chatContent` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sender_ID` int NOT NULL,
  `senderType` enum('customer','user') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `isRead` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`chatID`),
  KEY `fk_chat_conversation` (`conversation_ID`)
) ENGINE=MyISAM AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `chat`
--

INSERT INTO `chat` (`chatID`, `conversation_ID`, `chatContent`, `date`, `sender_ID`, `senderType`, `isRead`) VALUES
(6, 1, 'sdas', '2025-11-01 15:58:56', 1, 'customer', 0),
(7, 1, 'ádas', '2025-11-01 15:58:56', 1, 'customer', 0),
(8, 1, 'chào bạn', '2025-11-01 15:58:56', 3, 'user', 0),
(9, 2, 'xin chào', '2025-11-01 15:58:56', 1223, 'customer', 0),
(10, 2, 'chào', '2025-11-01 15:58:56', 1223, 'customer', 0),
(11, 2, 'chào khải', '2025-11-01 15:58:56', 3, 'user', 0),
(12, 2, 'tôi cần giúp đỡ', '2025-11-01 15:58:56', 1223, 'customer', 0),
(13, 2, 'bạn cần gì', '2025-11-01 15:58:56', 3, 'user', 0),
(14, 2, 'sdas', '2025-11-01 15:58:56', 1223, 'customer', 0),
(15, 2, 'ádas', '2025-11-01 15:58:56', 1223, 'customer', 0),
(16, 2, 'ádasdsa', '2025-11-01 15:58:56', 3, 'user', 0),
(17, 2, 'hở', '2025-11-01 15:58:56', 1223, 'customer', 0),
(18, 2, 'hở', '2025-11-01 15:58:56', 1223, 'customer', 0),
(19, 2, 'sao', '2025-11-01 15:58:56', 3, 'user', 0),
(20, 2, 'ádas', '2025-11-01 15:58:56', 1223, 'customer', 0),
(21, 2, 'ádas', '2025-11-01 15:58:56', 3, 'user', 0),
(22, 2, 'sadsa', '2025-11-01 15:58:56', 3, 'user', 0),
(23, 2, '2', '2025-11-01 15:58:56', 1223, 'customer', 0),
(24, 2, '3', '2025-11-01 15:58:56', 1223, 'customer', 0),
(25, 2, '4', '2025-11-01 15:58:56', 3, 'user', 0),
(26, 2, 'xin chào', '2025-11-01 15:58:56', 1223, 'customer', 0),
(27, 2, 'chào Khải', '2025-11-01 15:58:56', 3, 'user', 0),
(28, 3, 'hello ạ', '2025-11-02 01:19:38', 2, 'customer', 0),
(29, 3, 'hello ạ', '2025-11-02 01:19:45', 2, 'customer', 0),
(30, 3, 'Chào bạn nha', '2025-11-02 01:20:36', 3, 'user', 0),
(31, 3, 'Sao tôi không thấy được tin nhắn cũ nhỉ', '2025-11-02 01:21:04', 2, 'customer', 0),
(32, 3, 'À thấy rồi nè', '2025-11-02 01:21:08', 2, 'customer', 0),
(33, 3, 'Hahaa', '2025-11-02 01:21:12', 3, 'user', 0),
(34, 3, 'Chào lại nhé', '2025-11-02 01:21:32', 2, 'customer', 0),
(35, 3, 'aaaa', '2025-11-02 01:21:43', 2, 'customer', 0),
(36, 3, 'aaaa', '2025-11-02 01:21:45', 2, 'customer', 0),
(37, 3, 'aaaa', '2025-11-02 01:21:49', 2, 'customer', 0),
(38, 3, 'Hello', '2025-11-02 01:21:56', 2, 'customer', 0),
(39, 3, 'Hello bạn', '2025-11-02 01:22:11', 2, 'customer', 0),
(40, 3, 'Alo', '2025-11-02 01:22:19', 2, 'customer', 0),
(41, 3, 'Chào bạn nha', '2025-11-03 00:08:03', 1, 'user', 0);

-- --------------------------------------------------------

--
-- Table structure for table `chatbot`
--

DROP TABLE IF EXISTS `chatbot`;
CREATE TABLE IF NOT EXISTS `chatbot` (
  `faq_id` int NOT NULL AUTO_INCREMENT,
  `keywords` varchar(255) NOT NULL,
  `response_text` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`faq_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `chatbot`
--

INSERT INTO `chatbot` (`faq_id`, `keywords`, `response_text`, `is_active`, `created_at`) VALUES
(1, 'thời gian giao hàng', 'Thời gian giao hàng tiêu chuẩn của Godifa là từ 3-5 ngày sau khi xác nhận đơn hàng !', 1, '2025-11-05 05:44:05'),
(2, 'xin chào', 'Godifa xin chào bạn! Tôi là Chatbot hỗ trợ trả lời tự động, tôi có thể giúp gì cho bạn ?', 1, '2025-11-05 06:12:47'),
(3, 'nhân viên', 'Tôi sẽ kết nối bạn với nhân viên chăm sóc khách hàng của chúng tôi! Vui lòng đợi tôi một chút...', 1, '2025-11-06 06:47:06'),
(4, 'nguồn gốc, nhập khẩu', 'Các sản phẩm của chúng tôi nhập khẩu chính hãng từ Nhật Bản được biết đến là các sản phẩm chất lượng !', 1, '2025-11-06 07:43:21');

-- --------------------------------------------------------

--
-- Table structure for table `conversation`
--

DROP TABLE IF EXISTS `conversation`;
CREATE TABLE IF NOT EXISTS `conversation` (
  `conversationID` int NOT NULL AUTO_INCREMENT,
  `customerID` int NOT NULL,
  `userID` int NOT NULL,
  `last_message_at` datetime NOT NULL,
  `customer_unread_count` int NOT NULL DEFAULT '0',
  `user_unread_count` int NOT NULL DEFAULT '0',
  `status` enum('open','closed','pending') NOT NULL,
  PRIMARY KEY (`conversationID`),
  KEY `fk_conversation_customer` (`customerID`),
  KEY `fk_conversation_user` (`userID`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `conversation`
--

INSERT INTO `conversation` (`conversationID`, `customerID`, `userID`, `last_message_at`, `customer_unread_count`, `user_unread_count`, `status`) VALUES
(1, 1, 0, '2025-11-01 14:34:23', 0, 2, 'open'),
(2, 1223, 0, '2025-11-01 15:00:18', 0, 11, 'open'),
(3, 2, 0, '2025-11-03 00:08:03', 0, 11, 'open');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
CREATE TABLE IF NOT EXISTS `customer` (
  `customerID` int NOT NULL AUTO_INCREMENT,
  `customerName` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `phone` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Hoạt động, 0=Đã khóa',
  `groupID` int DEFAULT NULL COMMENT 'ID nhóm khách hàng',
  PRIMARY KEY (`customerID`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_groupID` (`groupID`)
) ENGINE=MyISAM AUTO_INCREMENT=1224 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customerID`, `customerName`, `phone`, `email`, `password`, `status`, `groupID`) VALUES
(1, 'Ngô Hoàng Khải', '0817574722', 'ngok1708@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1),
(2, 'Lê Trung Hiếu', '0978848500', 'trunghieu@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1),
(3, 'Nguyễn Trung Trực', '0812412573', 'trungtruc@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1),
(4, 'nguyễn  thanh tùng', '0313212356', 'tungnguyen@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1),
(5, 'lê hồng minh', '0123111444', 'hongminh@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1),
(1223, 'quốc khải', '0949123123', 'quockhai@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer_group`
--

DROP TABLE IF EXISTS `customer_group`;
CREATE TABLE IF NOT EXISTS `customer_group` (
  `groupID` int NOT NULL AUTO_INCREMENT,
  `groupName` varchar(100) NOT NULL COMMENT 'Tên nhóm',
  `description` text COMMENT 'Mô tả nhóm',
  `minSpent` decimal(10,0) DEFAULT '0' COMMENT 'Chi tiêu tối thiểu để vào nhóm',
  `maxSpent` decimal(10,0) DEFAULT NULL COMMENT 'Chi tiêu tối đa (NULL = không giới hạn)',
  `color` varchar(7) DEFAULT '#6366f1' COMMENT 'Màu sắc hiển thị (hex color)',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`groupID`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Nhóm khách hàng theo chi tiêu';

--
-- Dumping data for table `customer_group`
--

INSERT INTO `customer_group` (`groupID`, `groupName`, `description`, `minSpent`, `maxSpent`, `color`, `createdAt`) VALUES
(1, 'Broze', 'Chi tieu 0-5tr', NULL, NULL, '#fa8500', '2025-10-30 05:13:38'),
(2, 'Sliver', 'Chi tieu 5-15tr', 5000000, 14999999, '#99a6b8', '2025-10-29 10:21:19'),
(3, 'Gold', 'Chi tieu 15-30tr', 15000000, 29999999, '#fbbf24', '2025-10-29 10:21:19'),
(4, 'Platinum', 'Chi tieu 30-50tr', 30000000, 49999999, '#42e9ff', '2025-10-29 10:21:19'),
(5, 'Diamon', 'Chi tieu hon 50tr', 50000000, NULL, '#2042ee', '2025-10-29 10:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
CREATE TABLE IF NOT EXISTS `order` (
  `orderID` int NOT NULL AUTO_INCREMENT,
  `orderDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paymentStatus` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL COMMENT 'Chờ thanh toán, Đã thanh toán, Đã hủy',
  `totalAmount` decimal(10,0) NOT NULL,
  `paymentMethod` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `customerID` int NOT NULL,
  `note` text COLLATE utf8mb3_unicode_520_ci COMMENT 'Ghi chú đơn hàng',
  `voucherID` int DEFAULT NULL,
  `deliveryStatus` enum('Chờ xác nhận','Đang tiến hành vận chuyển','Hoàn thành','Đã hủy') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci DEFAULT 'Chờ xác nhận',
  `shippingProvider` varchar(50) COLLATE utf8mb3_unicode_520_ci DEFAULT 'GHN' COMMENT 'Đơn vị vận chuyển',
  `shippingFee` decimal(10,2) DEFAULT '0.00' COMMENT 'Phí vận chuyển',
  `cancelReason` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci COMMENT 'Lý do hủy đơn hàng',
  `transactionCode` varchar(50) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL COMMENT 'Mã giao dịch duy nhất (GODIFA202511040001)',
  PRIMARY KEY (`orderID`),
  KEY `fk_order_user` (`customerID`),
  KEY `fk_order_voucher` (`voucherID`),
  KEY `idx_transaction_code` (`transactionCode`)
) ENGINE=MyISAM AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`orderID`, `orderDate`, `paymentStatus`, `totalAmount`, `paymentMethod`, `customerID`, `note`, `voucherID`, `deliveryStatus`, `shippingProvider`, `shippingFee`, `cancelReason`, `transactionCode`) VALUES
(1, '2025-11-01 15:58:56', 'Đã thanh toán', 700000, 'QR', 1, NULL, 0, '', 'GHN', 0.00, NULL, NULL),
(2, '2025-11-01 15:58:56', 'Đã thanh toán', 440000, 'QR', 2, NULL, 0, '', 'GHN', 0.00, NULL, NULL),
(3, '2025-11-01 15:58:56', 'Đã hủy', 570000, 'QR', 3, NULL, 0, 'Đã hủy', 'GHN', 0.00, 'll', NULL),
(4, '2025-11-09 01:10:53', 'Đã thanh toán', 750000, 'COD', 1, NULL, 0, '', 'GHN', 0.00, NULL, NULL),
(5, '2025-11-01 15:58:56', 'Đã hủy', 650000, 'QR', 2, NULL, 0, 'Đã hủy', 'GHN', 0.00, NULL, NULL),
(6, '2025-11-01 15:58:56', 'Đã hủy', 185000, 'QR', 3, NULL, 0, 'Đã hủy', 'GHN', 0.00, NULL, NULL),
(7, '2025-11-01 15:58:56', 'Đã hủy', 270000, 'QR', 1, NULL, 0, 'Đã hủy', 'GHN', 0.00, 'hết hàng', NULL),
(8, '2025-11-01 15:58:56', 'Đã hủy', 95000, 'COD', 2, NULL, 0, 'Đã hủy', 'GHN', 0.00, NULL, NULL),
(112, '2025-11-01 15:58:56', 'Đã hủy', 120000000, 'QR', 1, NULL, NULL, 'Đã hủy', 'GHN', 0.00, 'Nháp', NULL),
(113, '2025-11-08 00:43:18', 'Đã hủy', 90000, 'QR', 2, NULL, NULL, '', 'GHN', 0.00, 'QR code hết hạn - Tự động hủy', 'GODIFA202511050113'),
(114, '2025-11-09 01:09:36', 'Đã thanh toán', 90000, 'QR', 2, NULL, NULL, '', 'GHN', 0.00, NULL, 'GODIFA202511050114'),
(115, '2025-11-08 00:43:18', 'Đã hủy', 90000, 'QR', 2, NULL, NULL, '', 'GHN', 0.00, 'QR code hết hạn - Tự động hủy', 'GODIFA202511050115'),
(116, '2025-11-08 01:50:07', 'Đã hủy', 500000, 'QR', 1, NULL, NULL, 'Đã hủy', 'GHN', 22000.00, 'Nhap', NULL),
(117, '2025-11-09 01:09:36', 'Đã thanh toán', 500000, 'QR', 1, NULL, NULL, '', 'GHN', 22000.00, NULL, NULL),
(118, '2025-11-08 01:50:12', 'Đã hủy', 500000, 'QR', 1, NULL, NULL, 'Đã hủy', 'GHN', 22000.00, 'Nhap', NULL),
(120, '2025-11-09 01:09:36', 'Đã thanh toán', 10000, 'QR', 2, NULL, NULL, '', 'GHN', 0.00, NULL, 'GODIFA202511070120'),
(121, '2025-11-09 01:09:36', 'Đã thanh toán', 10000, 'QR', 2, NULL, NULL, '', 'GHN', 0.00, NULL, 'GODIFA202511070121'),
(122, '2025-11-09 01:09:36', 'Đã thanh toán', 10000, 'QR', 2, NULL, NULL, '', 'GHN', 0.00, NULL, 'GODIFA202511070122'),
(123, '2025-11-09 01:09:36', 'Đã thanh toán', 10000, 'QR', 2, NULL, NULL, '', 'GHN', 0.00, NULL, 'GODIFA202511070123'),
(124, '2025-11-09 01:09:36', 'Đã thanh toán', 10000, 'QR', 2, NULL, NULL, '', 'GHN', 0.00, NULL, 'GODIFA202511070124'),
(125, '2025-11-08 00:43:18', 'Đã hủy', 10000, 'QR', 2, NULL, NULL, '', 'GHN', 0.00, 'QR code hết hạn - Tự động hủy', 'GODIFA202511070125'),
(126, '2025-11-09 02:00:11', 'Đã thanh toán', 90000, 'COD', 2, '', NULL, 'Hoàn thành', 'GHN', 0.00, NULL, 'GODIFA202511070126'),
(127, '2025-11-09 02:02:41', 'Chờ thanh toán (COD)', 10000, 'COD', 3, '', NULL, 'Đang tiến hành vận chuyển', 'GHN', 0.00, NULL, 'GODIFA202511080127'),
(128, '2025-11-09 01:56:08', 'Chờ thanh toán', 10000, 'COD', 3, '', NULL, '', 'GHN', 0.00, NULL, 'GODIFA202511080128'),
(129, '2025-11-10 03:10:19', 'Chờ thanh toán (COD)', 10000, 'COD', 2, 'aaaaaa', NULL, 'Chờ xác nhận', 'GHN', 0.00, NULL, 'GODIFA202511090129'),
(130, '2025-11-10 03:10:53', 'Chờ thanh toán (COD)', 10000, 'COD', 2, 'test lan n', NULL, 'Chờ xác nhận', 'GHN', 0.00, NULL, 'GODIFA202511090130'),
(131, '2025-11-10 03:12:06', 'Chờ thanh toán (COD)', 10000, 'COD', 2, 'aaaa', NULL, 'Chờ xác nhận', 'GHN', 0.00, NULL, 'GODIFA202511090131'),
(132, '2025-11-10 03:12:59', 'Chờ thanh toán (COD)', 30000, 'COD', 2, 'test', NULL, 'Chờ xác nhận', 'GHN', 0.00, NULL, 'GODIFA202511090132'),
(133, '2025-11-10 03:28:25', 'Chờ thanh toán (COD)', 10000, 'COD', 2, 'aaaa1', NULL, 'Chờ xác nhận', 'GHN', 0.00, NULL, 'GODIFA202511090133'),
(134, '2025-11-10 03:31:30', 'Chờ thanh toán (COD)', 10000, 'COD', 2, 'aaaa', NULL, 'Chờ xác nhận', 'GHN', 0.00, NULL, 'GODIFA202511090134'),
(135, '2025-11-10 03:31:57', 'Chờ thanh toán (COD)', 30000, 'COD', 2, '111111', NULL, 'Chờ xác nhận', 'GHN', 0.00, NULL, 'GODIFA202511090135'),
(136, '2025-11-10 17:25:16', 'Chờ thanh toán (COD)', 40000, 'COD', 3, 'Đừng lỗi nữa', NULL, 'Chờ xác nhận', 'GHN', 0.00, NULL, 'GODIFA202511100136'),
(137, '2025-11-11 08:18:33', 'Chờ thanh toán (COD)', 34900, 'COD', 3, '111', 10, 'Đang tiến hành vận chuyển', 'GHN', 24900.00, NULL, 'GODIFA202511110137'),
(138, '2025-11-11 09:07:51', 'Chờ thanh toán (COD)', -15100, 'COD', 3, 'new1', 2, 'Chờ xác nhận', 'GHN', 24900.00, NULL, 'GODIFA202511110138'),
(139, '2025-11-11 09:09:53', 'Chờ thanh toán (COD)', 30500, 'COD', 3, '', NULL, 'Chờ xác nhận', 'GHN', 20500.00, NULL, 'GODIFA202511110139'),
(140, '2025-11-11 09:20:03', 'Chờ thanh toán (COD)', 22900, 'COD', 3, '', 7, 'Chờ xác nhận', 'GHN', 24900.00, NULL, 'GODIFA202511110140'),
(141, '2025-11-11 09:22:06', 'Chờ thanh toán (COD)', 18500, 'COD', 3, '', 7, 'Chờ xác nhận', 'GHN', 20500.00, NULL, 'GODIFA202511110141');

--
-- Triggers `order`
--
DROP TRIGGER IF EXISTS `after_order_update_assign_group`;
DELIMITER $$
CREATE TRIGGER `after_order_update_assign_group` AFTER UPDATE ON `order` FOR EACH ROW BEGIN
    DECLARE customer_total_spent DECIMAL(15,2);DECLARE best_group_id INT;-- Chỉ chạy khi payment status thay đổi
    IF NEW.paymentStatus != OLD.paymentStatus THEN
        -- Tính tổng chi tiêu của customer (không tính đơn hủy)
        SELECT COALESCE(SUM(totalAmount), 0) INTO customer_total_spent
        FROM `order`
        WHERE customerID = NEW.customerID 
          AND paymentStatus = 'Đã thanh toán';-- Tìm nhóm phù hợp nhất
        SELECT groupID INTO best_group_id
        FROM customer_group
        WHERE status = 1
          AND customer_total_spent >= minSpent
          AND (maxSpent IS NULL OR customer_total_spent <= maxSpent)
        ORDER BY minSpent DESC
        LIMIT 1;-- Cập nhật nhóm cho customer
        IF best_group_id IS NOT NULL THEN
            UPDATE customer 
            SET groupID = best_group_id 
            WHERE customerID = NEW.customerID;END IF;END IF;END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_delivery`
--

DROP TABLE IF EXISTS `order_delivery`;
CREATE TABLE IF NOT EXISTS `order_delivery` (
  `deliveryID` int NOT NULL AUTO_INCREMENT,
  `orderID` int NOT NULL,
  `recipientName` varchar(100) NOT NULL,
  `recipientEmail` varchar(100) DEFAULT NULL,
  `recipientPhone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `district` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `provinceId` int DEFAULT NULL,
  `districtId` int DEFAULT NULL,
  `wardCode` varchar(20) DEFAULT NULL,
  `fullAddress` varchar(500) GENERATED ALWAYS AS (concat_ws(_utf8mb4', ',`address`,`ward`,`district`,`city`)) STORED,
  `deliveryNotes` text,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`deliveryID`),
  UNIQUE KEY `orderID` (`orderID`),
  KEY `idx_orderID` (`orderID`),
  KEY `idx_province_district` (`provinceId`,`districtId`),
  KEY `idx_ward` (`wardCode`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_delivery`
--

INSERT INTO `order_delivery` (`deliveryID`, `orderID`, `recipientName`, `recipientEmail`, `recipientPhone`, `address`, `ward`, `district`, `city`, `provinceId`, `districtId`, `wardCode`, `deliveryNotes`, `createdAt`) VALUES
(1, 115, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'asdas', '46034', '1863', '240', NULL, NULL, NULL, 'aaa', '2025-11-05 20:07:27'),
(2, 116, 'Nguyễn Văn Test', 'test@gmail.com', '0987654321', '42/5-7 Hồ Đắc Di', 'Phường Tây Thạnh', 'Quận Tân Phú', 'Hồ Chí Minh', 202, 1456, '21511', NULL, '2025-11-05 20:17:54'),
(3, 117, 'Nguyễn Văn Test', 'test@gmail.com', '0987654321', '42/5-7 Hồ Đắc Di', 'Phường Tây Thạnh', 'Quận Tân Phú', 'Hồ Chí Minh', 202, 1456, '21511', NULL, '2025-11-05 20:19:26'),
(4, 118, 'Nguyễn Văn Test', 'test@gmail.com', '0987654321', '42/5-7 Hồ Đắc Di', 'Phường Tây Thạnh', 'Quận Tân Phú', 'Hồ Chí Minh', 202, 1456, '21511', NULL, '2025-11-05 20:19:34'),
(5, 119, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', '!@#', '46034', '1863', '240', NULL, NULL, NULL, '111', '2025-11-07 16:14:16'),
(6, 120, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'qqq', '80213', '2264', '269', NULL, NULL, NULL, '11', '2025-11-07 16:23:02'),
(7, 121, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'asdas', '450706', '3302', '261', NULL, NULL, NULL, '', '2025-11-07 16:31:08'),
(8, 122, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', '11', '470503', '1776', '258', NULL, NULL, NULL, '', '2025-11-07 16:34:25'),
(9, 123, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'aaa', '80212', '2264', '269', NULL, NULL, NULL, '', '2025-11-07 16:43:11'),
(10, 124, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'aa', '600507', '1998', '253', NULL, NULL, NULL, '', '2025-11-07 16:59:48'),
(11, 125, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', '12', '600507', '1998', '253', NULL, NULL, NULL, '', '2025-11-07 17:06:11'),
(12, 126, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', '!@#', '140813', '2267', '266', 266, 2267, '140813', '', '2025-11-07 18:42:21'),
(13, 127, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'asdas', '360703', '1835', '259', 259, 1835, '360703', '', '2025-11-08 17:50:14'),
(14, 128, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '!@#', '390302', '1856', '260', 260, 1856, '390302', '', '2025-11-08 18:11:27'),
(15, 129, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'asdas', '800050', '2163', '267', 267, 2163, '800050', 'aaaaaa', '2025-11-09 20:10:19'),
(16, 130, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'asdas', '190105', '1644', '249', 249, 1644, '190105', 'test lan n', '2025-11-09 20:10:53'),
(17, 131, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'asdas', '620414', '2022', '265', 265, 2022, '620414', 'aaaa', '2025-11-09 20:12:06'),
(18, 132, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'asdas', '390204', '3186', '260', 260, 3186, '390204', 'test', '2025-11-09 20:12:59'),
(19, 133, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'aaa', '640403', '1824', '250', 250, 1824, '640403', 'aaaa1', '2025-11-09 20:28:25'),
(20, 134, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', 'asdas', '640704', '1823', '250', 250, 1823, '640704', 'aaaa', '2025-11-09 20:31:30'),
(21, 135, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0123222531', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '140813', '2267', '266', 266, 2267, '140813', '111111', '2025-11-09 20:31:57'),
(22, 136, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'Đừng lỗi nữa', '190105', '1644', '249', 249, 1644, '190105', 'Đừng lỗi nữa', '2025-11-10 10:25:16'),
(23, 137, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'asdas', '140909', '2007', '266', 266, 2007, '140909', '111', '2025-11-11 01:15:03'),
(24, 138, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'asdas', '620712', '2123', '265', 265, 2123, '620712', 'new1', '2025-11-11 02:07:51'),
(25, 139, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'asdas', '470802', '2012', '258', 258, 2012, '470802', '', '2025-11-11 02:09:53'),
(26, 140, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '12', '70509', '1980', '264', 264, 1980, '70509', '', '2025-11-11 02:20:03'),
(27, 141, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '370310', '2140', '262', 262, 2140, '370310', '', '2025-11-11 02:22:06');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

DROP TABLE IF EXISTS `order_details`;
CREATE TABLE IF NOT EXISTS `order_details` (
  `orderID` int NOT NULL,
  `productID` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,0) NOT NULL,
  PRIMARY KEY (`orderID`,`productID`),
  KEY `fk_orderDetails_product` (`productID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`orderID`, `productID`, `quantity`, `price`) VALUES
(1, 1, 1, 700000),
(2, 2, 1, 440000),
(3, 3, 1, 570000),
(4, 4, 1, 750000),
(5, 5, 1, 650000),
(6, 13, 1, 185000),
(7, 18, 1, 270000),
(8, 14, 1, 95000),
(113, 30, 1, 90000),
(114, 30, 1, 90000),
(115, 30, 1, 90000),
(119, 16, 1, 59000),
(120, 36, 1, 10000),
(121, 36, 1, 10000),
(122, 36, 1, 10000),
(123, 36, 1, 10000),
(124, 36, 1, 10000),
(125, 36, 1, 10000),
(126, 30, 1, 90000),
(127, 36, 1, 10000),
(128, 36, 1, 10000),
(129, 36, 1, 10000),
(130, 36, 1, 10000),
(131, 36, 1, 10000),
(132, 36, 3, 10000),
(133, 36, 1, 10000),
(134, 36, 1, 10000),
(135, 36, 3, 10000),
(136, 36, 4, 10000),
(137, 36, 2, 10000),
(138, 36, 1, 10000),
(139, 36, 1, 10000),
(140, 36, 1, 10000),
(141, 36, 1, 10000);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
CREATE TABLE IF NOT EXISTS `product` (
  `productID` int NOT NULL AUTO_INCREMENT,
  `productName` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `SKU_MRK` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `stockQuantity` int NOT NULL,
  `price` float NOT NULL,
  `description` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `image` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `categoryID` int NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Hoạt động, 0=Đã khóa',
  PRIMARY KEY (`productID`),
  KEY `fk_product_category` (`categoryID`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`productID`, `productName`, `SKU_MRK`, `stockQuantity`, `price`, `description`, `image`, `categoryID`, `status`) VALUES
(1, 'Viên Uống Bổ Gan Shijimi Orihiro 70 Viên - Nhật Bản\r\n', '4571157257624', 5, 700000, 'Viên uống bổ gan Orihiro đã được nghiên cứu kỹ lưỡng, thành phần chính có trong viên này là chiết xuất gan lợn, bột hàu, bột ngao và tinh chất nghệ. ...', '4571157257624.jpg', 1, 1),
(2, 'Viên uống bổ não Orihiro Ginkgo Biloba 240 viên\r\n', '4971493101597', 8, 440000, 'Viên Uống Bổ Não Orihiro Ginkgo Biloba chiết xuất lá cây bạch quả chứa hơn 20 loại Flavonoid cùng một số vi chất giúp hoạt huyết, dưỡng não, tăng cường trí nhớ, giảm nguy cơ sa sút trí tuệ, lú lẫn, stress, suy nhược thần kinh,....', '4971493101597.jpg', 1, 1),
(3, 'Viên uống tinh bột nghệ mùa thu Orihiro 520 viên\r\n', '4971493102426', 15, 570000, 'Tinh bột nghệ – Curcumin được chứng minh có các công dụng tốt cho chức năng của lá gan:\r\nNghệ giúp hỗ trợ điều trị bệnh gan nhiễm mỡ. Trong một phân tích của tác giả Goodrarzi và các cộng sự (2019), thử nghiệm cho bệnh nhân gan nhiễm mỡ không do rượu dùng Curcumin trong vòng 8 tuần đã giảm được các chỉ số men gan (ALT, AST) tốt hơn nhóm bệnh nhân không sử dụng.\r\nNghệ giúp hỗ trợ điều trị virus viêm gan. Trong một mô hình thí nghiệm cho thấy Curcumin ức chế sự nhân lên của virus viêm gan B (HBV) ', '4971493102426.jpg', 1, 1),
(4, 'Viên uống giảm cân Minami Diet Deruderu giảm 15kg +25% mỡ thừa 540 viên\r\n', '4945904018965', 3, 750000, 'Thông tin sản phẩm: Viên uống giảm 15kg và 25% mỡ bụng Minami \r\nSản phẩm \"Giảm cân 15kg 540 viên\" là một giải pháp giảm cân được thiết kế để hỗ trợ quá trình giảm cân một cách hiệu quả và an toàn.\r\nThành Phần:\r\nChiết xuất lưới Salacia, bột trái cây lên men (từ quả táo, dâu, berry), thành phần Enzyme, Acid Lactic, muồng trâu, bột cacao, Galactooligosacarit (GOS), Chiết xuất cây Gymnema Sylvestre, Canxi từ vỏ trứng, Hàm lượng vitamin B1, B2, B6, E, D.', '4945904018965.jpg', 1, 1),
(5, 'Viên Uống Hỗ Trợ Điều Trị Bệnh Gout Anserine Minami  lọ 240 viên\r\n', '4945904016138', 0, 650000, 'Viên hỗ trợ điều trị gout Anserine Minami sử dụng các thành phần có nguồn gốc tự nhiên, vitamin và khoáng chất mà cơ thể cần để loại bỏ các acid uric một cách tự nhiên, lành tính và an toàn cho người sử dụng. Công dụng chính của sản phẩm giúp làm giảm và đào thải lượng axit uric tại các khớp xương, giảm các triệu chứng đau nhức, mệt mỏi do bệnh gout gây ra.', '4945904016138.jpg', 1, 1),
(6, 'Viên uống chống ung thư Fucoidan Kanehide Bio 180 viên\r\n', '4958349250135', 2, 2615000, 'Chiết xuất từ 100% tảo nâu Mozuku vùng biển nắng, trong sạch Okinawa Nhật Bản\r\nGiúp tăng cường hệ miễn dịch, ngăn chặn sự phát triển của tế bào ung thư\r\nGiảm tác dụng phụ của hóa xạ trị và hóa trị', '4958349250135.jpg', 1, 1),
(7, 'Viên Uống Bổ Sung Kẽm DHC Zin C Cải Thiện Hệ Miễn Dịch  Gói 15v (15 ngày sử dụng)\r\n', '4511413405116', 25, 70000, 'Thực Phẩm Bảo Vệ Sức Khoẻ DHC Zinc là sản phẩm thực phẩm chức năng hỗ trợ sức khỏe từ thương hiệu DHC Nhật Bản, giúp bổ sung hiệu quả lượng khoáng kẽm cần thiết cho cơ thể. Ngoài ra, sản phẩm còn được bổ sung thêm thành phần selen và crom cũng là những khoáng chất thiết yếu, giúp hỗ trợ duy trì sức khỏe dẻo dai, cho cơ thể tràn đầy năng lượng.', '4511413405116.jpg', 1, 1),
(8, 'Viên Uống Bổ Sung Vitamin E DHC Natural Vitamin E Soybean Giúp Cải Thiện Làn Da, Sức Khoẻ  Gói 30 viên (30 ngày sử dụng)\r\n', '4511413621394', 0, 165000, 'Vitamin \"trẻ hóa\" giúp ngăn ngừa lão hóa da, cải thiện tình trạng da khô hiệu quả\r\nCải thiện da khô, cho da căng mềm tràn đầy sức sống\r\nHỗ trợ duy trì cơ thể trẻ trung, sức khỏe dẻo dai', '4511413621394.jpg', 1, 1),
(9, 'Set 2 gói Băng Vệ Sinh Ngày LAURIER Nội Địa Nhật Siêu Thấm Không Cánh 20.5cm (gói 28 miếng)\r\n', '4901301392404', 50, 129000, 'Tên sản phẩm :Băng vệ sinh siêu thấm\r\nThương hiệu :KAO\r\nXuất xứ :Nhật Bản\r\nChất liệu/ Thành phần :Chất liệu: Polyetylen, polypropylene, polyester', '4901301392404.jpg', 2, 1),
(10, 'DUNG DỊCH VỆ SINH PHỤ NỮ PH CARE HƯƠNG BẠC HÀ CỦA NHẬT CHAI 150ML - HÀNG NHẬT NỘI ĐỊA nước rửa phụ khoa làm sạch vùng kín cân bằng độ PH\r\n', '4582372213388', 15, 209000, 'Dung dịch vệ sinh phụ nữ PH Japan Premium Shower Splash 150ml đến từ thương hiệu mỹ phẩm chăm sóc cơ thể PH JAPAN Premium có khả năng làm sạch và chăm sóc vùng da nhạy cảm của phái nữ.\r\n\r\n', '4582372213388.jpg', 2, 1),
(11, 'Dầu gội Salonlink Extra Treatment siêu dưỡng 1000ml (Màu Xanh)\r\n', '4513574022812', 20, 269000, 'Dầu Gội Đầu Kumano Salon Link Extra Treatment 1000ml là sản phẩm được thiết kế chuyên biệt cho những mái tóc hư tổn và gãy rụng, không chỉ giúp làm sạch tóc hiệu quả mà còn giúp cung cấp một lượng lớn protein và các axit amin giúp nuôi dưỡng mái tóc, dưỡng ẩm và sửa chữa các tổn thương do hóa chất tạo kiểu gây ra, phục hồi lại mái tóc chắc khỏe vốn có.', '4513574022812.jpg', 2, 1),
(12, 'Kem dưỡng trắng trị nám Transino Whitening Repair Cream EX 35g\r\n', '4987107626530', 10, 1110000, 'Kem dưỡng trắng da đặc trị nám Medicinal Whitening Repair Cream EX của Transino là dòng sản phẩm dưỡng da ban đêm, giúp tái tạo, phục hồi những hư tổn trên da. Tăng cường nuôi dưỡng và chăm sóc da trắng mịn, tươi trẻ.', '4987107626530.jpg', 2, 1),
(13, 'Sữa Rửa Mặt Tạo Bọt ROHTO HADA LABO Dưỡng Ẩm Cho Mọi Loại Da 160ml\r\n', '4987241145614', 10, 185000, 'Sữa Rửa Mặt Tạo Bọt Hadalabo Nhật Bản Trắng là một trong những sản phẩm đang được yêu thích nhất của Hada Labo tại thị trường Việt Nam, được nhập khẩu trực tiếp từ Nhật Bản. Với dạng bọt tiện lợi, mềm mịn kết hợp cùng các thành phần giàu dưỡng chất, sản phẩm đem lại hiệu quả làm sạch vô cùng vượt trội, giúp loại bỏ tận gốc bụi bẩn, bã nhờn và vi khuẩn tích tụ trên da, giúp da khô thoáng, căng mịn. ', '4987241145614.jpg', 2, 1),
(14, 'Sữa Rửa Mặt Kumano Deve Men Than Hoạt Tính Cho Nam 130g\r\n', '4513574031449', 10, 95000, 'ữa Rửa Mặt Kumano Deve Men Than Hoạt Tính Cho Nam 130g là dòng sữa rửa mặt cho nam đến từ thương hiệu mỹ phẩm Kumano của Nhật Bản, với thành phần than hoạt tính đem lại công dụng 2 trong 1 vừa làm sữa rửa mặt vừa tẩy da chết nhẹ nhàng giúp loại sạch bụi bẩn, dầu thừa, bã nhờn và thông thoáng lỗ chân lông đồng thời hỗ trợ ngăn ngừa mụn hiệu quả.', '4513574031449.jpg', 2, 1),
(15, 'Xà Bông Beauty Soap Cow 90g\r\n', '4901525010900', 10, 39000, 'Với chiết xuất từ sữa bò tươi, kết hợp cùng Squalane dưỡng ẩm da mềm mại\r\nTạo nhiều bọt kem mịn, tắm sạch hoàn hảo, cho da thông thoáng\r\nLàn da sạch mịn, mềm mại, không khô ráp', '4901525010900.jpg', 2, 1),
(16, 'Xà phòng tắm chiết xuất từ sữa và dâu tây 80g\r\n', '4976631477589', 15, 59000, 'Xà Phòng Tắm Pelican Chiết Xuất Sữa Và Dâu Tây 80g là dòng xà phòng tắm đến từ thương hiệu Pelican của Nhật Bản, chiết xuất thành phần dâu tây và sữa tắm giúp làm sạch bụi bẩn, vi khuẩn trên da đồng thời cung cấp dưỡng chất và vitamin nuôi dưỡng làn da mịn màng, sáng bóng.', '4976631477589.jpg', 2, 1),
(17, 'Bộ cắt móng, kéo du lịch Seiwapro\r\n', '4982790408784', 50, 75000, 'Chất liệu inox bền chắc, khó gỉ sét\r\nGồm: Kéo tỉa lông, đồ cắt móng, dũa móng, móc lỗ tai, nhíp.\r\nThiết kế nhỏ gọn, tiện mang theo đi du lịch, công tác', '4982790408784.jpg', 3, 1),
(18, 'Mặt nạ Keana chiết xuất từ gạo dưỡng ẩm se khít lỗ chân lông 10 miếng\r\n', '4992440034713', 25, 270000, 'Chiết xuất 100% từ gạo quốc sản Nhật Bản, cung cấp độ ẩm cho da mịn màng\r\nKết hợp cùng ceramide tạo lớp màng bảo vệ độ ẩm, duy trì làn da mềm mại dài lâu\r\nGiàu vitamin E dưỡng da căng mịn, se khít lỗ chân lông, da sáng mịn, trong suốt tự nhiên', '4992440034713.jpg', 3, 1),
(19, 'Miếng rửa mặt Seiwapro Loven sillicon\r\n', '4982790188631', 30, 45000, 'Làm sạch là bước đầu tiên và quan trọng nhất trong quá trình chăm sóc da. Chỉ rửa mặt bằng tay cùng sữa rửa mặt thôi là chưa đủ bởi nhiều nghiên cứu chỉ ra rằng, rửa mặt bằng tay không thể làm sạch hoàn toàn da mặt. Đừng quá lo lắng vì Miếng Rửa Mặt Silicon Seiwapro Loven Make Cleansing Pad đến từ Nhật Bản sẽ giúp bạn giải quyết vấn đề này.', '4982790188631.jpg', 3, 1),
(20, 'Set 10 dao cạo cho nữ KAI\r\n', '4901331007439', 10, 85000, 'Bộ 10 Dao Cạo Lông Mày, Lông Mặt KAI là sản phẩm dao cạo đến từ thương hiệu KAI của Nhật Bản. Sản phẩm có thiết kế nhỏ gọn và tiện lợi, với lưỡi dao làm từ thép không gỉ có độ bền cao, đảm bảo an toàn trong quá trình sử dụng và không gây ra đau rát hay tổn thương da.', '4901331007439.jpg', 3, 1),
(21, 'Miếng thấm mồ hôi nách Kyowa 10 chiếc\r\n', '4969757106143', 10, 65000, 'Chất liệu thấm hút tốt giữ cho vùng nách áo luôn sạch sẽ, khô thoáng, không ố vàng\r\nBề mặt tiếp xúc êm, dễ chịu với da\r\nSản phẩm không mùi, không làm lấn át mùi nước hoa', '4969757106143.jpg', 3, 1),
(22, 'Set 3 dao cạo lông mày Pretty KAI\r\n', '4901331012860', 100, 115000, '- Chất liệu:  Lưỡi dao được làm từ thép không gỉ, thân dao được làm từ nhựa dẻo cao cấp và được kháng khuẩn\r\n- Quy cách: gồm 3 cây dao cạo kèm lưỡi.\r\n- HDSD: Dao được thiết kế chuyên dụng dành cho phụ nữ giúp cạo lông mày. Phần tay cầm thiết kế dày dặn giúp cầm nắm dễ dàng. Không thay thế được lưỡi, bỏ đi sau khi lưỡi dao đã cùn.\r\n- Xuất xứ: Nhật Bản. Nhập khẩu trực tiếp từ Nhật', '4901331012860.jpg', 3, 1),
(23, 'Set 5 dao cạo lông mày KAI\r\n', '4901331010781', 19, 55000, 'MÔ TẢ SẢN PHẨM\r\nCombo 5 dao cạo lông mày KAI Nhật Bản là phụ kiện hỗ trợ tốt cho việc làm đẹp chân mày. Với dao cạo lông mày KAI, bạn có thể thỏa thích tạo đường cong chân mày sắc nét và nổi bật.\r\nDao cạo lông mày KAI​​​ - Mày xinh, mặt càng thêm xinh\r\nThông tin sản phẩm:\r\n- Chất liệu: Thép không gỉ, nhựa cao cấp', '4901331010781.jpg', 3, 1),
(24, 'Xịt Chống Muỗi Và Côn Trùng Cho Bé SKIN VAPE 200ml Nội Địa Nhật (Chai Màu Hồng - Hương Đào) Dùng Cho Bé Từ 6 Tháng Tuổi Trở Lên\r\n', '4902424433081', 20, 225000, 'Xịt chống muỗi Skin Vape hương mơ đào của Nhật Bản thích hợp cho cả người lớn lẫn trẻ nhỏ.\r\nDùng để xịt lên da vùng tay, chân và cổ, hiệu quả trong việc xua đuổi muỗi và nhiều loại côn trùng khác.', '4902424433081.jpg', 4, 1),
(25, 'Set 3 gói giấy ướt 80 tờ cho bé (100% tinh khiết)\n', '4589506153282', 15, 145000, 'Chứa đến 99% nước tinh khiết, dịu nhẹ và an toàn cho làn da bé nhỏ\r\nKết hợp thêm thành phần dưỡng ẩm từ collagen, hyaluronic acid giữ cho làn da bé luôn mềm mại, mịn màng\r\nKhăn giấy không chứa cồn, paraben, hương liệu', '4589506153282.jpg', 4, 1),
(26, 'Kem Đánh Răng Cho Trẻ Em KAO KIDS Hương Dâu 70g Hàng Nội Địa Nhật Bản Cho Bé Từ 3 Tuổi\r\n', '4901301281623', 27, 78000, 'Kem đánh răng trẻ em KAO Clear Clean Kid\'s 70g là thương hiệu nổi tiếng của Nhật Bản, sản phẩm được thiết kế với hình dáng những con vật ngộ nghĩnh trên bao bì sản phẩm nhằm thu hút sự chú ý và tò mò của bé. Giúp chống sâu răng, tăng độ chắc khỏe cho răng, giúp men răng trắng sáng.', '4901301281623.jpg', 4, 1),
(27, 'Lăn Bôi Trị Muỗi Và Côn Trùng Đốt MUHI 50ml  Nội Địa Nhật Chim Cánh Cụt Cho Bé Từ 6 Tháng Tuổi\r\n', '4987426002091', 35, 176000, 'Lăn trị muỗi đốt Muhi từ Nhật Bản giúp làm xẹp, làm dịu nhanh cơn ngứa, vết sưng tấy do muỗi, các loại côn trùng cắn tức thì và không để lại sẹo. Sản phẩm không chứa cồn hay bất kỳ chất phụ gia độc hại, an toàn cho da nhạy cảm, giúp chống hăm da, rôm sẩy, viêm da, đỏ da, nổi mề đay, chàm, phát ban nhiệt ở cả trẻ em và người lớn.', '4987426002091.jpg', 5, 1),
(28, 'Hộp Đựng Thuốc 2 Ngăn Cao Cấp Inomata Nhật Bản\r\n', '4973228171516', 16, 95000, 'Mô tả sản phẩm Hộp đựng thuốc Inomata chia 2 ngăn Nhật Bản\r\n- Chất liệu: nhựa PP cao cấp\r\n- Kích thước: đường kính 7cm * độ dày 2cm\r\n- Công dụng: Chia 2 ngăn, dùng để đựng th. Thiết kế nhỏ gọn dễ dàng mang theo người. Kiểu dáng đẹp, sang trọng. \r\n- Hàng nhập khẩu từ Nhật, sản xuất tại Nhật Bản ', '4973228171516.jpg', 5, 1),
(29, 'Kem Đánh Răng Muối SunStar tuýp 170g Hàng Nội Địa Nhật Bản\r\n', '4901616005266', 19, 70000, 'Kem Đánh Răng Muối SunStar là sản phẩm đến từ Nhật Bản, với khả năng chăm sóc răng miệng 1 cách toàn diện. Kem có chứa thành phần chính là muối kết hợp với canxi carbonate, vitamin E, tinh thể muối, sorbitol giúp đánh bật các mảng bám ố vàng trên răng và trong từng kẽ răng, đồng thời còn giúp ngăn chặn các bệnh về nha chu, sâu răng, chảy máu chân răng hiệu quả.', '4901616005266.jpg', 5, 1),
(30, 'Hộp 180 bông ngoáy tai cao cấp cho người lớn\r\n', '4936613072331', 20, 90000, 'Xuất xứ: Hàng nội địa Nhật Bản, sản xuất tại Nhật Bản.\r\n- Chất liệu: tay cầm bằng nhựa, 2 đầu bằng bông.\r\n- Công dụng: vệ sinh, làm sạch tai. Đầu bông chất liệu cotton cao cấp, không gây đau rát, an toàn khi ngoáy tai.', '4936613072331.jpg', 5, 1),
(31, 'Bàn Chải Chà Gót Chân Sanada Seiko (Đá San Hô)', '4973430023672', 10, 60000, 'Bàn Chải Chà Gót Chân Sanada Seiko (Đá San Hô)\r\n\r\nBàn chải chà gót chân bằng đá san hô dùng cọ gót chân giúp làm mềm, mịn gót chân, loại bỏ các vết chai sần, xơ cứng phần gót chân bạn.\r\nĐặc điểm\r\nChất liệu an toàn\r\nVới thành phần từ đá thiên nhiên nên bạn sẽ hoàn toàn yên tâm khi sử dụng để chăm sóc cho đôi chân của mình và gia đình\r\nSản phẩm với kích thước vừa tay cầm, dễ dàng cất gọn, giúp tiết kiệm không gian nhà tắm', '4973430023672.jpg', 5, 1),
(36, 'aaa', '12', 15, 10000, '', '690e1ccdd4ff9.jpg', 8, 1);

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

DROP TABLE IF EXISTS `review`;
CREATE TABLE IF NOT EXISTS `review` (
  `reviewID` int NOT NULL AUTO_INCREMENT,
  `rating` int NOT NULL,
  `comment` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `dateReview` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `orderID` int NOT NULL,
  `productID` int NOT NULL,
  `customerID` int NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`reviewID`),
  KEY `fk_review_user` (`customerID`),
  KEY `fk_review_product` (`productID`),
  KEY `fk_review_order` (`orderID`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`reviewID`, `rating`, `comment`, `dateReview`, `orderID`, `productID`, `customerID`, `status`) VALUES
(1, 5, 'Sản phẩm rất tốt, đúng như mô tả. Sẽ ủng hộ shop lâu dài!', '2025-10-15 11:00:00', 0, 1, 1, 1),
(2, 4, 'Chất lượng oke, giao hàng nhanh. Giá hơi cao nhưng chấp nhận được.', '2025-10-15 15:30:00', 0, 2, 2, 1),
(3, 5, 'Viên uống bổ não này dùng rất hiệu quả, cảm thấy tỉnh táo hơn hẳn!', '2025-10-16 10:00:00', 0, 2, 3, 1),
(4, 3, 'Sản phẩm tạm ổn, nhưng chưa thấy hiệu quả rõ rệt.', '2025-10-16 12:00:00', 0, 4, 1, 1),
(5, 5, 'Xà bông thơm, dùng da mịn màng. Rất hài lòng!', '2025-10-14 09:00:00', 0, 13, 3, 1),
(6, 5, 'Sản phẩm tốt', '2025-11-10 14:50:51', 1, 1, 1, 1),
(7, 5, 'Tuyệt vời', '2025-11-10 15:00:07', 2, 2, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE IF NOT EXISTS `role` (
  `roleID` int NOT NULL AUTO_INCREMENT,
  `roleName` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  PRIMARY KEY (`roleID`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`roleID`, `roleName`) VALUES
(1, 'Chủ Doanh Nghiệp'),
(2, 'Nhân Viên Quản Trị'),
(3, 'Nhân Viên Bán Hàng'),
(4, 'Nhân Viên CSKH');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `userID` int NOT NULL AUTO_INCREMENT,
  `userName` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `phone` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `status` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `roleID` int NOT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_user_role` (`roleID`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `userName`, `email`, `password`, `phone`, `status`, `roleID`) VALUES
(1, 'Lê Văn A', 'chudoanhnghiep@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', '0815111032', '1', 1),
(2, 'Đặng Văn Lương', 'nhanvienbanhang@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', '0917333821', '1', 3),
(3, 'Nguyễn Như Ý', 'nhanviencskh@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', '0913222315', '1', 4),
(4, 'Lương Sơn Trường', 'nhanvienquantri@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', '0915333216', '1', 2),
(8, 'aaa', 'letrunghieu2513@gmail.com', '4297f44b13955235245b2497399d7a93', '0978848500', '1', 3),
(6, 'aaa', 'anpha15@outlook.com', '00c66aaf5f2c3f49946f15c1ad2ea0d3', '121231232', '1', 3),
(7, 'HHH', 'abc@gmail.com', 'fcea920f7412b5da7be0cf42b8c93759', '078787', '1', 2);

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

DROP TABLE IF EXISTS `voucher`;
CREATE TABLE IF NOT EXISTS `voucher` (
  `voucherID` int NOT NULL AUTO_INCREMENT,
  `voucherName` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `value` decimal(10,0) NOT NULL,
  `quantity` int NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `requirement` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=active, 0=locked',
  PRIMARY KEY (`voucherID`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `voucher`
--

INSERT INTO `voucher` (`voucherID`, `voucherName`, `value`, `quantity`, `startDate`, `endDate`, `requirement`, `status`) VALUES
(1, 'GODIFA100K', 100000, 50, '2025-10-01', '2025-10-31', 'Áp dụng cho đơn hàng từ 500,000đ', 1),
(2, 'NEWCUSTOMER50K', 50000, 99, '2025-10-01', '2025-12-31', 'Khách hàng mới, đơn từ 300,000đ', 1),
(3, 'FREESHIP', 30000, 200, '2025-10-01', '2026-03-07', 'Miễn phí ship cho đơn từ 200,000đ', 1),
(4, 'SALE20PERCENT', 10000, 30, '2025-10-17', '2025-10-25', 'Giảm 20% tối đa 150,000đ', 0),
(5, 'HAPPYDAY', 75000, 80, '2025-10-10', '2025-10-30', 'Mừng khai trương, đơn từ 400,000đ', 1),
(6, 'Test voucher', 12000, 10, '2025-10-06', '2025-11-16', '1223123123123123', 0),
(7, 'Test voucher 2', 12000, 8, '0001-01-01', '2025-11-16', '1223123123123123', 1),
(8, 'Test voucher KHTT', 150000, 1, '2025-10-17', '2025-11-28', '', 1),
(9, 'Test voucher 2', 12000, 1, '2025-10-08', '2025-11-28', '', 1),
(10, 'Bronze', 10000, 10, '2025-11-11', '2025-11-27', '', 1),
(11, 'Test voucher 3', 20000, 20, '2025-11-11', '2025-12-26', 'Khách Bạc', 1),
(12, 'Test voucher KHTT', 11000, 1, '2025-11-11', '2025-12-11', '', 1),
(13, 'FREESHIP', 50000, 100, '0000-00-00', '2025-12-11', '', 1),
(14, 'Test voucher', 50000, 1, '0000-00-00', '2025-12-11', '', 1),
(15, 'Bronze', 10000, 10, '0000-00-00', '2025-12-11', 'aaa', 1),
(16, '11', 10000, 10, '0000-00-00', '2025-12-11', '', 1),
(17, 'Test voucher 3', 10000, 2, '0000-00-00', '2025-12-11', '', 1),
(18, 'Bronze5', 11000, 1, '0000-00-00', '2025-12-11', '', 1),
(19, '13', 10000, 12, '0000-00-00', '2025-12-11', '', 1),
(20, '10', 15000, 10, '0000-00-00', '2025-12-11', '', 1),
(21, 'Test voucher', 10000, 1, '2025-11-11', '2025-12-11', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `voucher_group`
--

DROP TABLE IF EXISTS `voucher_group`;
CREATE TABLE IF NOT EXISTS `voucher_group` (
  `voucherGroupID` int NOT NULL AUTO_INCREMENT,
  `voucherID` int NOT NULL COMMENT 'ID voucher',
  `groupID` int NOT NULL COMMENT 'ID nhóm khách hàng',
  PRIMARY KEY (`voucherGroupID`),
  KEY `idx_voucher` (`voucherID`),
  KEY `idx_group` (`groupID`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Ánh xạ voucher - nhóm khách hàng';

--
-- Dumping data for table `voucher_group`
--

INSERT INTO `voucher_group` (`voucherGroupID`, `voucherID`, `groupID`) VALUES
(2, 8, 6),
(4, 9, 3),
(7, 10, 1),
(8, 11, 2),
(10, 12, 5),
(11, 13, 3),
(12, 14, 1),
(13, 15, 4),
(14, 16, 4),
(15, 17, 1),
(16, 18, 1),
(17, 19, 1),
(18, 20, 5),
(19, 21, 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_customer_group_stats`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `v_customer_group_stats`;
CREATE TABLE IF NOT EXISTS `v_customer_group_stats` (
);

-- --------------------------------------------------------

--
-- Structure for view `v_customer_group_stats`
--
DROP TABLE IF EXISTS `v_customer_group_stats`;

DROP VIEW IF EXISTS `v_customer_group_stats`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_customer_group_stats`  AS SELECT `cg`.`groupID` AS `groupID`, `cg`.`groupName` AS `groupName`, `cg`.`description` AS `description`, `cg`.`minSpent` AS `minSpent`, `cg`.`maxSpent` AS `maxSpent`, `cg`.`status` AS `status`, count(distinct `c`.`customerID`) AS `totalCustomers`, count(distinct `o`.`orderID`) AS `totalOrders`, coalesce(sum((case when (`o`.`paymentStatus` <> 'Đã hủy') then `o`.`totalAmount` else 0 end)),0) AS `totalRevenue` FROM ((`customer_group` `cg` left join `customer` `c` on((`cg`.`groupID` = `c`.`groupID`))) left join `order` `o` on((`c`.`customerID` = `o`.`customerID`))) GROUP BY `cg`.`groupID`, `cg`.`groupName`, `cg`.`description`, `cg`.`minSpent`, `cg`.`maxSpent`, `cg`.`status` ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
