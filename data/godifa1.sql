-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 04, 2025 at 07:28 PM
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
  `image` varchar(255) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL,
  `date` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 1 = Hoạt động, 0 = Đã khóa',
  PRIMARY KEY (`blogID`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `blog`
--

INSERT INTO `blog` (`blogID`, `title`, `content`, `image`, `date`, `status`) VALUES
(1, 'Top 5 sản phẩm bổ gan bán chạy nhất tháng 10', 'Trong tháng 10 này, các sản phẩm bổ gan Orihiro đã nhận được rất nhiều sự quan tâm từ khách hàng. Đặc biệt là viên uống bổ gan Shijimi với chiết xuất từ nghêu Nhật Bản...', '691e044c1b7fa.png', '2025-10-10 10:00:00', 1),
(2, 'Hướng dẫn chăm sóc da mùa hanh khô', 'Mùa hanh khô đã đến, làn da của bạn cần được chăm sóc đặc biệt. Dưới đây là 5 tips giúp da bạn luôn mềm mại, mịn màng...', '691e04454b9b4.png', '2025-10-12 14:30:00', 1),
(3, 'Bí quyết giảm cân an toàn và hiệu quả', 'Giảm cân không chỉ là ăn kiêng mà còn cần có chế độ sinh hoạt và bổ sung thực phẩm chức năng phù hợp. Viên uống giảm cán Minami Diet...', '691e043f8be0f.png', '2025-10-14 09:00:00', 1),
(4, 'Sản phẩm mới về: Kem dưỡng trắng Transino', 'aa', '691e041cdac62.png', '2025-10-15 16:00:00', 1);

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
(1, 7, 1, 70000);

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
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`categoryID`, `categoryName`, `status`) VALUES
(1, 'Thực phẩm chức năng', 1),
(2, 'Mỹ phẩm', 1),
(3, 'Phụ kiện - Làm đẹp', 1),
(4, 'Mẹ & Bé', 1),
(5, 'Gia dụng', 1);

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
  `senderType` enum('customer','user','bot') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `isRead` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`chatID`),
  KEY `fk_chat_conversation` (`conversation_ID`)
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

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
(41, 3, 'Chào bạn nha', '2025-11-03 00:08:03', 1, 'user', 0),
(42, 3, 'xin chào', '2025-11-15 14:28:53', 2, 'customer', 0),
(43, 3, 'Godifa xin chào bạn! Tôi là Chatbot hỗ trợ trả lời tự động, tôi có thể giúp gì cho bạn ?', '2025-11-15 14:28:53', 0, 'bot', 0),
(44, 3, 'hi', '2025-11-15 14:29:05', 2, 'customer', 0),
(45, 3, 'chào bạn', '2025-11-15 14:29:31', 3, 'user', 0),
(46, 3, 'lô', '2025-11-18 22:00:35', 2, 'customer', 0),
(47, 3, 'Chào lại nhé', '2025-11-22 14:21:47', 2, 'customer', 0),
(48, 3, 'Vâng ạ', '2025-11-22 14:22:00', 1, 'user', 0),
(49, 3, 'aaaaaaaaaa', '2025-11-22 14:22:17', 1, 'user', 0),
(50, 4, 'xin chào', '2025-11-22 14:33:08', 3, 'customer', 0),
(51, 4, 'Godifa xin chào bạn! Tôi là Chatbot hỗ trợ trả lời tự động, tôi có thể giúp gì cho bạn ?', '2025-11-22 14:33:08', 0, 'bot', 0),
(52, 4, 'xin chào', '2025-11-22 14:42:44', 3, 'customer', 0),
(53, 4, 'Godifa xin chào bạn! Tôi là Chatbot hỗ trợ trả lời tự động, tôi có thể giúp gì cho bạn ?', '2025-11-22 14:42:44', 0, 'bot', 0),
(54, 4, 'Tôi buồn ngủ quá', '2025-11-22 14:43:09', 3, 'customer', 0),
(55, 4, 'Vâng ạ', '2025-11-22 14:43:32', 1, 'user', 0),
(56, 3, 'Chao ban', '2025-12-04 17:43:43', 2, 'customer', 0),
(57, 3, 'Da chao a', '2025-12-04 17:43:53', 1, 'user', 0),
(58, 4, 'xin chào', '2025-12-04 17:57:46', 3, 'customer', 0),
(59, 4, 'Godifa xin chào bạn! Tôi là Chatbot hỗ trợ trả lời tự động, tôi có thể giúp gì cho bạn ?', '2025-12-04 17:57:46', 0, 'bot', 0),
(60, 4, 'chào', '2025-12-04 17:57:50', 3, 'customer', 0),
(61, 4, 'Da chao a', '2025-12-04 17:58:07', 2, 'user', 0),
(62, 3, 'Chào bạn', '2025-12-04 19:05:47', 2, 'customer', 0),
(63, 3, 'Dạ em chào anh', '2025-12-04 19:06:17', 1, 'user', 0),
(64, 3, 'Tôi muốn hủy đơn hàng', '2025-12-04 19:08:17', 2, 'customer', 0),
(65, 3, 'Dạ cho em xin mã đơn hàng cần hủy', '2025-12-04 19:12:14', 1, 'user', 0),
(66, 3, 'Mã Đơn hàng #182', '2025-12-04 19:12:28', 2, 'customer', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `conversation`
--

INSERT INTO `conversation` (`conversationID`, `customerID`, `userID`, `last_message_at`, `customer_unread_count`, `user_unread_count`, `status`) VALUES
(1, 1, 0, '2025-11-01 14:34:23', 0, 2, 'open'),
(2, 1223, 0, '2025-11-01 15:00:18', 0, 11, 'open'),
(3, 2, 0, '2025-12-04 19:12:28', 0, 19, 'open'),
(4, 3, 0, '2025-12-04 17:58:07', 0, 5, 'open');

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
  `note` text COLLATE utf8mb3_unicode_520_ci COMMENT 'Ghi chú thông tin khách hàng',
  PRIMARY KEY (`customerID`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_groupID` (`groupID`)
) ENGINE=MyISAM AUTO_INCREMENT=1225 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customerID`, `customerName`, `phone`, `email`, `password`, `status`, `groupID`, `note`) VALUES
(1, 'Ngô Hoàng Khải', '0817574722', 'ngok1708@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1, NULL),
(2, 'Lê Trung Hiếu', '0978848500', 'trunghieu@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1, NULL),
(3, 'Nguyễn Trung Trực', '0812412573', 'trungtruc@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1, NULL),
(4, 'nguyễn  thanh tùng', '0313212356', 'tungnguyen@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1, 'oke'),
(5, 'lê hồng minh', '0123111444', 'hongminh@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1, NULL),
(1223, 'quốc khải', '0949123123', 'quockhai@gmail.com', '7c6a180b36896a0a8c02787eeafb0e4c', 1, 1, 'khách thường xuyên mua');

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
(1, 'Broze', 'Chi tieu 0-5tr', NULL, NULL, '#412f1b', '2025-10-30 05:13:38'),
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
  `orderDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày tạo đơn (không tự động update)',
  `paymentDate` datetime DEFAULT NULL COMMENT 'Ngày thanh toán thực tế (khi webhook xác nhận thành công)',
  `paymentStatus` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL COMMENT 'Chờ thanh toán, Đã thanh toán, Đã hủy',
  `totalAmount` decimal(10,0) NOT NULL,
  `paymentMethod` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `customerID` int NOT NULL,
  `note` text COLLATE utf8mb3_unicode_520_ci COMMENT 'Ghi chú đơn hàng',
  `voucherID` int DEFAULT NULL,
  `deliveryStatus` enum('Chờ xác nhận','Đang tiến hành vận chuyển','Hoàn thành','Đã hủy','Chờ xử lý hoàn tiền','Đã hoàn tiền','Yêu cầu hoàn trả','Đang hoàn trả','Đã hoàn trả') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci DEFAULT 'Chờ xác nhận',
  `shippingProvider` varchar(50) COLLATE utf8mb3_unicode_520_ci DEFAULT 'GHN' COMMENT 'Đơn vị vận chuyển',
  `shippingFee` decimal(10,2) DEFAULT '0.00' COMMENT 'Phí vận chuyển',
  `cancelReason` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci COMMENT 'Lý do hủy đơn hàng',
  `cancelledAt` datetime DEFAULT NULL COMMENT 'Thời gian hủy đơn',
  `cancelledBy` enum('customer','admin') COLLATE utf8mb3_unicode_520_ci DEFAULT NULL COMMENT 'Người hủy: khách hay admin',
  `transactionCode` varchar(50) COLLATE utf8mb3_unicode_520_ci DEFAULT NULL COMMENT 'Mã giao dịch duy nhất (GODIFA202511040001)',
  `qrUrl` text COLLATE utf8mb3_unicode_520_ci COMMENT 'URL QR Code thanh toán SePay',
  `qrExpiredAt` datetime DEFAULT NULL COMMENT 'Thời gian hết hạn QR (15 phút)',
  PRIMARY KEY (`orderID`),
  KEY `fk_order_user` (`customerID`),
  KEY `fk_order_voucher` (`voucherID`),
  KEY `idx_transaction_code` (`transactionCode`),
  KEY `idx_qr_expired` (`qrExpiredAt`,`paymentStatus`),
  KEY `idx_payment_date` (`paymentDate`),
  KEY `idx_cancelled` (`cancelledAt`,`cancelledBy`)
) ENGINE=MyISAM AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`orderID`, `orderDate`, `paymentDate`, `paymentStatus`, `totalAmount`, `paymentMethod`, `customerID`, `note`, `voucherID`, `deliveryStatus`, `shippingProvider`, `shippingFee`, `cancelReason`, `cancelledAt`, `cancelledBy`, `transactionCode`, `qrUrl`, `qrExpiredAt`) VALUES
(185, '2025-12-04 19:02:03', '2025-12-04 19:02:35', 'Đã thanh toán', 40500, 'QR', 2, '', 24, 'Hoàn thành', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202512040185', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=40500&des=SEVQR+TKP155+GODIFA202512040185', '2025-12-04 12:17:03'),
(184, '2025-12-04 17:52:27', '2025-12-04 17:52:57', 'Đã thanh toán', 70500, 'QR', 2, '', 24, 'Đang tiến hành vận chuyển', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202512040184', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=70500&des=SEVQR+TKP155+GODIFA202512040184', '2025-12-04 11:07:27'),
(183, '2025-12-04 17:51:26', NULL, 'Chờ thanh toán', 84900, 'QR', 3, '123123', 24, 'Chờ xác nhận', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202512040183', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=84900&des=SEVQR+TKP155+GODIFA202512040183', '2025-12-04 11:06:26'),
(182, '2025-12-04 01:13:21', NULL, 'Đã hủy', 100500, 'QR', 2, '111', NULL, 'Đã hủy', 'GHN', 20500.00, 'Khách nhờ hủy', NULL, NULL, 'GODIFA202512030182', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=100500&des=SEVQR+TKP155+GODIFA202512030182', '2025-12-03 18:28:21'),
(181, '2025-12-04 01:12:30', NULL, 'Chờ thanh toán (COD)', 104900, 'COD', 2, 'ádasd', NULL, 'Chờ xác nhận', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202512030181', NULL, NULL),
(179, '2025-12-04 01:00:20', NULL, 'Chờ thanh toán (COD)', 75500, 'COD', 2, 'ádasd', NULL, 'Chờ xác nhận', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202512030179', NULL, NULL),
(180, '2025-12-04 01:04:25', NULL, 'Chờ thanh toán (COD)', 100500, 'COD', 2, '11', NULL, 'Chờ xác nhận', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202512030180', NULL, NULL),
(178, '2025-12-02 15:59:46', '2025-12-02 16:00:18', 'Đã thanh toán', 214900, 'COD', 3, 'ádasdasdas', NULL, 'Hoàn thành', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202512020178', NULL, NULL),
(177, '2025-12-02 15:58:07', '2025-12-02 15:59:17', 'Đã thanh toán', 115800, 'COD', 3, 'ádasdsd', NULL, 'Hoàn thành', 'GHN', 45800.00, NULL, NULL, NULL, 'GODIFA202512020177', NULL, NULL),
(175, '2025-12-02 15:32:51', NULL, 'Đã hủy', 24900, 'COD', 3, 'ádasdasd', 24, 'Đã hủy', 'GHN', 24900.00, 'aaa', NULL, NULL, 'GODIFA202512020175', NULL, NULL),
(176, '2025-12-02 15:46:44', '2025-12-02 15:51:44', 'Đã thanh toán', 44900, 'COD', 3, 'a', NULL, 'Hoàn thành', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202512020176', NULL, NULL),
(174, '2025-12-02 15:31:17', '2025-12-02 15:36:17', 'Đã thanh toán', 13900, 'COD', 3, 'ádqwdsadas', 24, 'Hoàn thành', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202512020174', NULL, NULL),
(173, '2025-12-02 15:27:37', '2025-12-02 15:32:37', 'Đã thanh toán', 29500, 'COD', 3, '123', 24, 'Hoàn thành', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202512020173', NULL, NULL),
(172, '2025-12-02 15:26:47', '2025-12-02 15:31:47', 'Đã thanh toán', 24900, 'COD', 3, '123', 24, 'Hoàn thành', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202512020172', NULL, NULL),
(171, '2025-12-02 15:25:33', '2025-12-02 15:30:33', 'Đã thanh toán', 104900, 'COD', 3, '1111', 24, 'Hoàn thành', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202512020171', NULL, NULL),
(170, '2025-12-02 15:19:54', '2025-12-02 15:24:54', 'Đã thanh toán', 24900, 'QR', 3, '123', 24, 'Hoàn thành', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202512020170', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=24900&des=SEVQR+TKP155+GODIFA202512020170', '2025-12-02 08:34:54'),
(169, '2025-12-02 01:24:42', NULL, 'Chờ thanh toán', 9500, 'QR', 3, '', 24, 'Chờ xác nhận', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202512010169', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=9500&des=SEVQR+TKP155+GODIFA202512010169', '2025-12-02 10:19:43'),
(168, '2025-12-02 01:20:49', '2025-12-02 01:25:49', 'Đã thanh toán', 9500, 'QR', 3, '', 24, 'Hoàn thành', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202512010168', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=9500&des=SEVQR+TKP155+GODIFA202512010168', '2025-12-01 18:35:49'),
(167, '2025-12-02 00:53:38', '2025-12-02 00:58:38', 'Đã thanh toán', 13900, 'QR', 2, '123', 24, 'Hoàn thành', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202512010167', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=13900&des=SEVQR+TKP155+GODIFA202512010167', '2025-12-01 18:08:38'),
(166, '2025-11-30 18:53:38', '2025-12-01 17:39:29', 'Đã thanh toán', 40500, 'QR', 2, '11111', NULL, 'Đang tiến hành vận chuyển', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202511300166', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=40500&des=SEVQR+TKP155+GODIFA202511300166', '2025-12-01 17:52:59'),
(164, '2025-11-30 18:35:46', '2025-11-30 18:40:46', 'Đã thanh toán', 40500, 'QR', 2, 'test1', 24, 'Hoàn thành', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202511300164', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=40500&des=SEVQR+TKP155+GODIFA202511300164', '2025-11-30 11:50:46'),
(165, '2025-11-30 18:51:23', '2025-12-01 17:39:25', 'Đã thanh toán', 60900, 'COD', 2, '12', NULL, 'Hoàn thành', 'GHN', 24900.00, NULL, NULL, NULL, 'GODIFA202511300165', NULL, NULL),
(186, '2025-12-05 00:07:04', NULL, 'Đã hủy', 40500, 'COD', 2, '', NULL, 'Đã hủy', 'GHN', 20500.00, 'k còn nhu cầu', '2025-12-05 00:24:44', 'customer', 'GODIFA202512040186', NULL, NULL),
(187, '2025-12-05 00:40:52', '2025-12-05 00:42:14', 'Đã thanh toán', 40500, 'QR', 2, '123', NULL, 'Đang tiến hành vận chuyển', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202512040187', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=40500&des=SEVQR+TKP155+GODIFA202512040187', '2025-12-04 17:55:52'),
(188, '2025-12-05 00:47:36', '2025-12-05 00:47:59', 'Đã thanh toán', 40500, 'QR', 2, '', NULL, 'Đang tiến hành vận chuyển', 'GHN', 20500.00, NULL, NULL, NULL, 'GODIFA202512040188', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=40500&des=SEVQR+TKP155+GODIFA202512040188', '2025-12-04 18:02:36'),
(189, '2025-12-05 00:51:18', '2025-12-05 00:51:43', 'Đã hủy', 40500, 'QR', 2, 'aaaaa', NULL, 'Đã hủy', 'GHN', 20500.00, 'lỗi', '2025-12-05 01:25:35', 'customer', 'GODIFA202512040189', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=40500&des=SEVQR+TKP155+GODIFA202512040189', '2025-12-04 18:06:18'),
(190, '2025-12-05 01:35:45', '2025-12-05 01:36:15', 'Đã hoàn tiền', 24900, 'QR', 2, '123', 24, '', 'GHN', 24900.00, 'K muốn mua nữa', '2025-12-05 01:36:51', 'customer', 'GODIFA202512040190', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=24900&des=SEVQR+TKP155+GODIFA202512040190', '2025-12-04 18:50:45'),
(191, '2025-12-05 01:41:31', '2025-12-05 01:43:48', 'Đã hoàn tiền', 80500, 'QR', 2, '12', 24, 'Đã hoàn tiền', 'GHN', 20500.00, 'aa', '2025-12-05 01:44:07', 'customer', 'GODIFA202512040191', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=80500&des=SEVQR+TKP155+GODIFA202512040191', '2025-12-04 18:56:31'),
(192, '2025-12-05 01:50:57', '2025-12-05 01:51:21', 'Đã hoàn tiền', 45800, 'QR', 2, '12', 24, '', 'GHN', 45800.00, 'a', '2025-12-05 01:51:39', 'customer', 'GODIFA202512040192', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=45800&des=SEVQR+TKP155+GODIFA202512040192', '2025-12-04 19:05:57'),
(193, '2025-12-05 01:58:06', '2025-12-05 01:58:24', 'Đã hoàn tiền', 20500, 'QR', 2, '', 24, '', 'GHN', 20500.00, 'aaaa', '2025-12-05 01:58:45', 'customer', 'GODIFA202512040193', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=20500&des=SEVQR+TKP155+GODIFA202512040193', '2025-12-04 19:13:06'),
(194, '2025-12-05 02:12:13', '2025-12-05 02:12:36', 'Đã hoàn tiền', 20500, 'QR', 2, '12', 24, 'Đã hoàn tiền', 'GHN', 20500.00, 'a', '2025-12-05 02:12:50', 'customer', 'GODIFA202512040194', 'https://qr.sepay.vn/img?acc=105875539922&bank=VietinBank&amount=20500&des=SEVQR+TKP155+GODIFA202512040194', '2025-12-04 19:27:13');

--
-- Triggers `order`
--
DROP TRIGGER IF EXISTS `after_order_cancelled`;
DELIMITER $$
CREATE TRIGGER `after_order_cancelled` AFTER UPDATE ON `order` FOR EACH ROW BEGIN
    -- Nếu đơn hàng bị hủy và có sử dụng voucher
    IF NEW.paymentStatus = 'Đã hủy' AND OLD.paymentStatus != 'Đã hủy' THEN
        IF NEW.voucherID IS NOT NULL AND NEW.voucherID > 0 THEN
            -- Tăng lại số lượng voucher
            UPDATE voucher 
            SET quantity = quantity + 1 
            WHERE voucherID = NEW.voucherID;
        END IF;
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `after_order_update_assign_group`;
DELIMITER $$
CREATE TRIGGER `after_order_update_assign_group` AFTER UPDATE ON `order` FOR EACH ROW BEGIN
    DECLARE customer_total_spent DECIMAL(15,2);
    DECLARE best_group_id INT;
    
    -- Ch??? ch???y khi payment status thay ?????i
    IF NEW.paymentStatus != OLD.paymentStatus THEN
        -- T??nh t???ng chi ti??u c???a customer (kh??ng t??nh ????n h???y)
        SELECT COALESCE(SUM(totalAmount), 0) INTO customer_total_spent
        FROM `order`
        WHERE customerID = NEW.customerID
          AND paymentStatus = '???? thanh to??n';
        
        -- T??m nh??m ph?? h???p nh???t (REMOVED: AND status = 1)
        SELECT groupID INTO best_group_id
        FROM customer_group
        WHERE customer_total_spent >= minSpent
          AND (maxSpent IS NULL OR customer_total_spent <= maxSpent)
        ORDER BY minSpent DESC
        LIMIT 1;
        
        -- C???p nh???t nh??m cho customer
        IF best_group_id IS NOT NULL THEN
            UPDATE customer
            SET groupID = best_group_id
            WHERE customerID = NEW.customerID;
        END IF;
    END IF;
END
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
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(27, 141, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '370310', '2140', '262', 262, 2140, '370310', '', '2025-11-11 02:22:06'),
(28, 142, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '12', '640403', '1824', '250', 250, 1824, '640403', '', '2025-11-12 13:54:39'),
(29, 143, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '202 Đường Lý Tự Trọng, Quận 10, TP.HCM', '220712', '2194', '268', 268, 2194, '220712', '11', '2025-11-13 07:03:07'),
(30, 144, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '123 Đường Nguyễn Thị Minh Khai', '600303', '1946', '253', 253, 1946, '600303', 'đóng hàng cẩn thận', '2025-11-15 07:34:12'),
(31, 145, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '123 Đường Nguyễn Thị Minh Khai', '141210', '2255', '266', 266, 2255, '141210', '', '2025-11-15 07:37:05'),
(32, 146, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'asdas', '190105', '1644', '249', 249, 1644, '190105', '', '2025-11-15 16:34:18'),
(33, 147, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '12', '620807', '1979', '265', 265, 1979, '620807', '', '2025-11-19 17:27:09'),
(34, 148, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '123 123', '470804', '2012', '258', 258, 2012, '470804', 'tét', '2025-11-19 17:42:51'),
(35, 149, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '12', '360405', '2121', '259', 259, 2121, '360405', 'nháp', '2025-11-22 07:24:14'),
(36, 150, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '70806', '1984', '264', 264, 1984, '70806', 'nháp 2', '2025-11-22 07:25:24'),
(37, 151, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '620202', '2060', '265', 265, 2060, '620202', '11', '2025-11-22 07:28:40'),
(38, 152, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '320606', '2040', '238', 238, 2040, '320606', '11', '2025-11-22 07:29:31'),
(39, 153, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '130913', '1967', '263', 263, 1967, '130913', 'nháp3', '2025-11-22 07:41:04'),
(40, 154, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '610803', '1783', '252', 252, 1783, '610803', '11', '2025-11-22 07:41:47'),
(41, 155, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'asdas', '640808', '3218', '250', 250, 3218, '640808', 'haiz', '2025-11-25 04:42:00'),
(42, 156, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'asdas', '470204', '1781', '258', 258, 1781, '470204', 'haiz', '2025-11-25 04:42:30'),
(43, 157, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'haiz', '610203', '1782', '252', 252, 1782, '610203', '1', '2025-11-25 04:42:55'),
(44, 158, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'asdas', '600303', '1946', '253', 253, 1946, '600303', 'aaaa', '2025-11-25 04:43:19'),
(45, 159, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '640404', '1824', '250', 250, 1824, '640404', '1111', '2025-11-25 06:44:42'),
(46, 160, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '610202', '1782', '252', 252, 1782, '610202', '123', '2025-11-25 07:03:28'),
(47, 161, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '190308', '1728', '249', 249, 1728, '190308', '12334', '2025-11-25 07:08:56'),
(48, 162, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '1111', '610206', '1782', '252', 252, 1782, '610206', 'aaaa', '2025-11-29 16:32:00'),
(49, 163, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '220612', '2018', '268', 268, 2018, '220612', '12', '2025-11-30 09:57:38'),
(50, 164, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '600507', '1998', '253', 253, 1998, '600507', 'test1', '2025-11-30 11:35:46'),
(51, 165, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'aaa', '141012', '1976', '266', 266, 1976, '141012', '12', '2025-11-30 11:51:23'),
(52, 166, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '640705', '1823', '250', 250, 1823, '640705', '11111', '2025-11-30 11:53:38'),
(53, 167, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '190308', '1728', '249', 249, 1728, '190308', '123', '2025-12-01 17:53:38'),
(54, 168, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '600403', '1935', '253', 253, 1935, '600403', '', '2025-12-01 18:20:49'),
(55, 169, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '610204', '1782', '252', 252, 1782, '610204', '', '2025-12-01 18:24:42'),
(56, 170, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '620415', '2022', '265', 265, 2022, '620415', '123', '2025-12-02 08:19:54'),
(57, 171, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'aaa', '190305', '1728', '249', 249, 1728, '190305', '1111', '2025-12-02 08:25:33'),
(58, 172, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '220909', '2046', '268', 268, 2046, '220909', '123', '2025-12-02 08:26:47'),
(59, 173, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '600506', '1998', '253', 253, 1998, '600506', '123', '2025-12-02 08:27:37'),
(60, 174, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '140511', '2079', '266', 266, 2079, '140511', 'ádqwdsadas', '2025-12-02 08:31:17'),
(61, 175, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '180715', '1759', '248', 248, 1759, '180715', 'ádasdasd', '2025-12-02 08:32:51'),
(62, 176, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'ádsadasd', '100707', '1904', '247', 247, 1904, '100707', 'a', '2025-12-02 08:46:44'),
(63, 177, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '910053', '1643', '248', 248, 1643, '910053', 'ádasdsd', '2025-12-02 08:58:07'),
(64, 178, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'ádasdasd', '190306', '1728', '249', 249, 1728, '190306', 'ádasdasdas', '2025-12-02 08:59:46'),
(65, 179, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'aaa', '640603', '1912', '250', 250, 1912, '640603', 'ádasd', '2025-12-03 18:00:20'),
(66, 180, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'ád', '470503', '1776', '258', 258, 1776, '470503', '11', '2025-12-03 18:04:25'),
(67, 181, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', '123 Đường Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '190502', '1730', '249', 249, 1730, '190502', 'ádasd', '2025-12-03 18:12:30'),
(68, 182, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'aa', '450306', '1985', '261', 261, 1985, '450306', '111', '2025-12-03 18:13:21'),
(69, 183, 'Nguyễn Trung Trực', 'trungtruc@gmail.com', '0812412573', 'aaa', '141210', '2255', '266', 266, 2255, '141210', '123123', '2025-12-04 10:51:26'),
(70, 184, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'aaa', '600501', '1998', '253', 253, 1998, '600501', 'a12', '2025-12-04 10:52:27'),
(71, 185, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'Tân Phú', '360902', '2225', '259', 259, 2225, '360902', '', '2025-12-04 12:02:03'),
(72, 186, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'Tân Phú', '640705', '1823', '250', 250, 1823, '640705', '', '2025-12-04 17:07:04'),
(73, 187, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'Tân Phú', '600306', '1946', '253', 253, 1946, '600306', '123', '2025-12-04 17:40:52'),
(74, 188, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'aaa', '610804', '1783', '252', 252, 1783, '610804', '', '2025-12-04 17:47:36'),
(75, 189, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'Tân Phú', '360503', '2205', '259', 259, 2205, '360503', 'aaaaa', '2025-12-04 17:51:18'),
(76, 190, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'aaa', '70705', '2017', '264', 264, 2017, '70705', '123', '2025-12-04 18:35:45'),
(77, 191, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'aaaaaa', '610204', '1782', '252', 252, 1782, '610204', '12', '2025-12-04 18:41:31'),
(78, 192, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'aaaaaa', '910056', '1643', '248', 248, 1643, '910056', '12', '2025-12-04 18:50:57'),
(79, 193, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'a', '640704', '1823', '250', 250, 1823, '640704', '', '2025-12-04 18:58:06'),
(80, 194, 'Lê Trung Hiếu', 'trunghieu@gmail.com', '0978848500', 'a', '640101', '1653', '250', 250, 1653, '640101', '12', '2025-12-04 19:12:13');

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
(141, 36, 1, 10000),
(142, 36, 1, 10000),
(143, 36, 1, 10000),
(144, 31, 1, 60000),
(145, 36, 1, 10000),
(146, 36, 1, 10000),
(147, 31, 5, 60000),
(148, 31, 1, 20000),
(149, 31, 1, 20000),
(150, 31, 1, 20000),
(151, 31, 1, 20000),
(152, 31, 1, 20000),
(153, 31, 1, 20000),
(154, 31, 1, 20000),
(155, 31, 1, 20000),
(156, 31, 1, 20000),
(157, 31, 1, 20000),
(158, 31, 5, 20000),
(159, 31, 4, 20000),
(160, 31, 8, 20000),
(161, 31, 5, 20000),
(162, 31, 2, 20000),
(163, 31, 2, 20000),
(164, 31, 2, 20000),
(165, 37, 4, 9000),
(166, 31, 1, 20000),
(167, 37, 1, 9000),
(168, 37, 1, 9000),
(169, 37, 1, 9000),
(170, 31, 1, 20000),
(171, 31, 1, 20000),
(171, 30, 1, 80000),
(172, 31, 1, 20000),
(173, 37, 1, 9000),
(173, 31, 1, 20000),
(174, 37, 1, 9000),
(175, 31, 1, 20000),
(176, 31, 1, 20000),
(177, 29, 1, 70000),
(178, 28, 2, 95000),
(179, 23, 1, 55000),
(180, 30, 1, 80000),
(181, 30, 1, 80000),
(182, 30, 1, 80000),
(183, 30, 1, 80000),
(184, 29, 1, 70000),
(185, 31, 2, 20000),
(186, 31, 1, 20000),
(187, 31, 1, 20000),
(188, 31, 1, 20000),
(189, 31, 1, 20000),
(190, 31, 1, 20000),
(191, 30, 1, 80000),
(192, 31, 1, 20000),
(193, 31, 1, 20000),
(194, 31, 1, 20000);

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
  `promotional_price` float DEFAULT NULL,
  `description` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `image` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `categoryID` int NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Hoạt động, 0=Đã khóa',
  PRIMARY KEY (`productID`),
  KEY `fk_product_category` (`categoryID`)
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`productID`, `productName`, `SKU_MRK`, `stockQuantity`, `price`, `promotional_price`, `description`, `image`, `categoryID`, `status`) VALUES
(1, 'Viên Uống Bổ Gan Shijimi Orihiro 70 Viên - Nhật Bản\r\n', '4571157257624', 5, 700000, 500000, 'Viên uống bổ gan Orihiro đã được nghiên cứu kỹ lưỡng, thành phần chính có trong viên này là chiết xuất gan lợn, bột hàu, bột ngao và tinh chất nghệ. ...', '4571157257624.jpg', 1, 1),
(2, 'Viên uống bổ não Orihiro Ginkgo Biloba 240 viên\r\n', '4971493101597', 8, 440000, NULL, 'Viên Uống Bổ Não Orihiro Ginkgo Biloba chiết xuất lá cây bạch quả chứa hơn 20 loại Flavonoid cùng một số vi chất giúp hoạt huyết, dưỡng não, tăng cường trí nhớ, giảm nguy cơ sa sút trí tuệ, lú lẫn, stress, suy nhược thần kinh,....', '4971493101597.jpg', 1, 1),
(3, 'Viên uống tinh bột nghệ mùa thu Orihiro 520 viên\r\n', '4971493102426', 15, 570000, NULL, 'Tinh bột nghệ – Curcumin được chứng minh có các công dụng tốt cho chức năng của lá gan:\r\nNghệ giúp hỗ trợ điều trị bệnh gan nhiễm mỡ. Trong một phân tích của tác giả Goodrarzi và các cộng sự (2019), thử nghiệm cho bệnh nhân gan nhiễm mỡ không do rượu dùng Curcumin trong vòng 8 tuần đã giảm được các chỉ số men gan (ALT, AST) tốt hơn nhóm bệnh nhân không sử dụng.\r\nNghệ giúp hỗ trợ điều trị virus viêm gan. Trong một mô hình thí nghiệm cho thấy Curcumin ức chế sự nhân lên của virus viêm gan B (HBV) ', '4971493102426.jpg', 1, 1),
(4, 'Viên uống giảm cân Minami Diet Deruderu giảm 15kg +25% mỡ thừa 540 viên\r\n', '4945904018965', 3, 750000, NULL, 'Thông tin sản phẩm: Viên uống giảm 15kg và 25% mỡ bụng Minami \r\nSản phẩm \"Giảm cân 15kg 540 viên\" là một giải pháp giảm cân được thiết kế để hỗ trợ quá trình giảm cân một cách hiệu quả và an toàn.\r\nThành Phần:\r\nChiết xuất lưới Salacia, bột trái cây lên men (từ quả táo, dâu, berry), thành phần Enzyme, Acid Lactic, muồng trâu, bột cacao, Galactooligosacarit (GOS), Chiết xuất cây Gymnema Sylvestre, Canxi từ vỏ trứng, Hàm lượng vitamin B1, B2, B6, E, D.', '4945904018965.jpg', 1, 1),
(5, 'Viên Uống Hỗ Trợ Điều Trị Bệnh Gout Anserine Minami  lọ 240 viên\r\n', '4945904016138', 0, 650000, NULL, 'Viên hỗ trợ điều trị gout Anserine Minami sử dụng các thành phần có nguồn gốc tự nhiên, vitamin và khoáng chất mà cơ thể cần để loại bỏ các acid uric một cách tự nhiên, lành tính và an toàn cho người sử dụng. Công dụng chính của sản phẩm giúp làm giảm và đào thải lượng axit uric tại các khớp xương, giảm các triệu chứng đau nhức, mệt mỏi do bệnh gout gây ra.', '4945904016138.jpg', 1, 1),
(6, 'Viên uống chống ung thư Fucoidan Kanehide Bio 180 viên\r\n', '4958349250135', 2, 2615000, NULL, 'Chiết xuất từ 100% tảo nâu Mozuku vùng biển nắng, trong sạch Okinawa Nhật Bản\r\nGiúp tăng cường hệ miễn dịch, ngăn chặn sự phát triển của tế bào ung thư\r\nGiảm tác dụng phụ của hóa xạ trị và hóa trị', '4958349250135.jpg', 1, 1),
(7, 'Viên Uống Bổ Sung Kẽm DHC Zin C Cải Thiện Hệ Miễn Dịch  Gói 15v (15 ngày sử dụng)\r\n', '4511413405116', 25, 70000, NULL, 'Thực Phẩm Bảo Vệ Sức Khoẻ DHC Zinc là sản phẩm thực phẩm chức năng hỗ trợ sức khỏe từ thương hiệu DHC Nhật Bản, giúp bổ sung hiệu quả lượng khoáng kẽm cần thiết cho cơ thể. Ngoài ra, sản phẩm còn được bổ sung thêm thành phần selen và crom cũng là những khoáng chất thiết yếu, giúp hỗ trợ duy trì sức khỏe dẻo dai, cho cơ thể tràn đầy năng lượng.', '4511413405116.jpg', 1, 1),
(8, 'Viên Uống Bổ Sung Vitamin E DHC Natural Vitamin E Soybean Giúp Cải Thiện Làn Da, Sức Khoẻ  Gói 30 viên (30 ngày sử dụng)\r\n', '4511413621394', 0, 165000, NULL, 'Vitamin \"trẻ hóa\" giúp ngăn ngừa lão hóa da, cải thiện tình trạng da khô hiệu quả\r\nCải thiện da khô, cho da căng mềm tràn đầy sức sống\r\nHỗ trợ duy trì cơ thể trẻ trung, sức khỏe dẻo dai', '4511413621394.jpg', 1, 1),
(9, 'Set 2 gói Băng Vệ Sinh Ngày LAURIER Nội Địa Nhật Siêu Thấm Không Cánh 20.5cm (gói 28 miếng)\r\n', '4901301392404', 50, 129000, NULL, 'Tên sản phẩm :Băng vệ sinh siêu thấm\r\nThương hiệu :KAO\r\nXuất xứ :Nhật Bản\r\nChất liệu/ Thành phần :Chất liệu: Polyetylen, polypropylene, polyester', '4901301392404.jpg', 2, 1),
(10, 'DUNG DỊCH VỆ SINH PHỤ NỮ PH CARE HƯƠNG BẠC HÀ CỦA NHẬT CHAI 150ML - HÀNG NHẬT NỘI ĐỊA nước rửa phụ khoa làm sạch vùng kín cân bằng độ PH\r\n', '4582372213388', 15, 209000, NULL, 'Dung dịch vệ sinh phụ nữ PH Japan Premium Shower Splash 150ml đến từ thương hiệu mỹ phẩm chăm sóc cơ thể PH JAPAN Premium có khả năng làm sạch và chăm sóc vùng da nhạy cảm của phái nữ.\r\n\r\n', '4582372213388.jpg', 2, 1),
(11, 'Dầu gội Salonlink Extra Treatment siêu dưỡng 1000ml (Màu Xanh)\r\n', '4513574022812', 20, 269000, NULL, 'Dầu Gội Đầu Kumano Salon Link Extra Treatment 1000ml là sản phẩm được thiết kế chuyên biệt cho những mái tóc hư tổn và gãy rụng, không chỉ giúp làm sạch tóc hiệu quả mà còn giúp cung cấp một lượng lớn protein và các axit amin giúp nuôi dưỡng mái tóc, dưỡng ẩm và sửa chữa các tổn thương do hóa chất tạo kiểu gây ra, phục hồi lại mái tóc chắc khỏe vốn có.', '4513574022812.jpg', 2, 1),
(12, 'Kem dưỡng trắng trị nám Transino Whitening Repair Cream EX 35g\r\n', '4987107626530', 10, 1110000, NULL, 'Kem dưỡng trắng da đặc trị nám Medicinal Whitening Repair Cream EX của Transino là dòng sản phẩm dưỡng da ban đêm, giúp tái tạo, phục hồi những hư tổn trên da. Tăng cường nuôi dưỡng và chăm sóc da trắng mịn, tươi trẻ.', '4987107626530.jpg', 2, 1),
(13, 'Sữa Rửa Mặt Tạo Bọt ROHTO HADA LABO Dưỡng Ẩm Cho Mọi Loại Da 160ml\r\n', '4987241145614', 10, 185000, NULL, 'Sữa Rửa Mặt Tạo Bọt Hadalabo Nhật Bản Trắng là một trong những sản phẩm đang được yêu thích nhất của Hada Labo tại thị trường Việt Nam, được nhập khẩu trực tiếp từ Nhật Bản. Với dạng bọt tiện lợi, mềm mịn kết hợp cùng các thành phần giàu dưỡng chất, sản phẩm đem lại hiệu quả làm sạch vô cùng vượt trội, giúp loại bỏ tận gốc bụi bẩn, bã nhờn và vi khuẩn tích tụ trên da, giúp da khô thoáng, căng mịn. ', '4987241145614.jpg', 2, 1),
(14, 'Sữa Rửa Mặt Kumano Deve Men Than Hoạt Tính Cho Nam 130g\r\n', '4513574031449', 10, 95000, NULL, 'ữa Rửa Mặt Kumano Deve Men Than Hoạt Tính Cho Nam 130g là dòng sữa rửa mặt cho nam đến từ thương hiệu mỹ phẩm Kumano của Nhật Bản, với thành phần than hoạt tính đem lại công dụng 2 trong 1 vừa làm sữa rửa mặt vừa tẩy da chết nhẹ nhàng giúp loại sạch bụi bẩn, dầu thừa, bã nhờn và thông thoáng lỗ chân lông đồng thời hỗ trợ ngăn ngừa mụn hiệu quả.', '4513574031449.jpg', 2, 1),
(15, 'Xà Bông Beauty Soap Cow 90g\r\n', '4901525010900', 10, 39000, NULL, 'Với chiết xuất từ sữa bò tươi, kết hợp cùng Squalane dưỡng ẩm da mềm mại\r\nTạo nhiều bọt kem mịn, tắm sạch hoàn hảo, cho da thông thoáng\r\nLàn da sạch mịn, mềm mại, không khô ráp', '4901525010900.jpg', 2, 1),
(16, 'Xà phòng tắm chiết xuất từ sữa và dâu tây 80g\r\n', '4976631477589', 15, 59000, NULL, 'Xà Phòng Tắm Pelican Chiết Xuất Sữa Và Dâu Tây 80g là dòng xà phòng tắm đến từ thương hiệu Pelican của Nhật Bản, chiết xuất thành phần dâu tây và sữa tắm giúp làm sạch bụi bẩn, vi khuẩn trên da đồng thời cung cấp dưỡng chất và vitamin nuôi dưỡng làn da mịn màng, sáng bóng.', '4976631477589.jpg', 2, 1),
(17, 'Bộ cắt móng, kéo du lịch Seiwapro\r\n', '4982790408784', 50, 75000, NULL, 'Chất liệu inox bền chắc, khó gỉ sét\r\nGồm: Kéo tỉa lông, đồ cắt móng, dũa móng, móc lỗ tai, nhíp.\r\nThiết kế nhỏ gọn, tiện mang theo đi du lịch, công tác', '4982790408784.jpg', 3, 1),
(18, 'Mặt nạ Keana chiết xuất từ gạo dưỡng ẩm se khít lỗ chân lông 10 miếng\r\n', '4992440034713', 25, 270000, NULL, 'Chiết xuất 100% từ gạo quốc sản Nhật Bản, cung cấp độ ẩm cho da mịn màng\r\nKết hợp cùng ceramide tạo lớp màng bảo vệ độ ẩm, duy trì làn da mềm mại dài lâu\r\nGiàu vitamin E dưỡng da căng mịn, se khít lỗ chân lông, da sáng mịn, trong suốt tự nhiên', '4992440034713.jpg', 3, 1),
(19, 'Miếng rửa mặt Seiwapro Loven sillicon\r\n', '4982790188631', 30, 45000, NULL, 'Làm sạch là bước đầu tiên và quan trọng nhất trong quá trình chăm sóc da. Chỉ rửa mặt bằng tay cùng sữa rửa mặt thôi là chưa đủ bởi nhiều nghiên cứu chỉ ra rằng, rửa mặt bằng tay không thể làm sạch hoàn toàn da mặt. Đừng quá lo lắng vì Miếng Rửa Mặt Silicon Seiwapro Loven Make Cleansing Pad đến từ Nhật Bản sẽ giúp bạn giải quyết vấn đề này.', '4982790188631.jpg', 3, 1),
(20, 'Set 10 dao cạo cho nữ KAI\r\n', '4901331007439', 10, 85000, NULL, 'Bộ 10 Dao Cạo Lông Mày, Lông Mặt KAI là sản phẩm dao cạo đến từ thương hiệu KAI của Nhật Bản. Sản phẩm có thiết kế nhỏ gọn và tiện lợi, với lưỡi dao làm từ thép không gỉ có độ bền cao, đảm bảo an toàn trong quá trình sử dụng và không gây ra đau rát hay tổn thương da.', '4901331007439.jpg', 3, 1),
(21, 'Miếng thấm mồ hôi nách Kyowa 10 chiếc\r\n', '4969757106143', 10, 65000, NULL, 'Chất liệu thấm hút tốt giữ cho vùng nách áo luôn sạch sẽ, khô thoáng, không ố vàng\r\nBề mặt tiếp xúc êm, dễ chịu với da\r\nSản phẩm không mùi, không làm lấn át mùi nước hoa', '4969757106143.jpg', 3, 1),
(22, 'Set 3 dao cạo lông mày Pretty KAI\r\n', '4901331012860', 100, 115000, NULL, '- Chất liệu:  Lưỡi dao được làm từ thép không gỉ, thân dao được làm từ nhựa dẻo cao cấp và được kháng khuẩn\r\n- Quy cách: gồm 3 cây dao cạo kèm lưỡi.\r\n- HDSD: Dao được thiết kế chuyên dụng dành cho phụ nữ giúp cạo lông mày. Phần tay cầm thiết kế dày dặn giúp cầm nắm dễ dàng. Không thay thế được lưỡi, bỏ đi sau khi lưỡi dao đã cùn.\r\n- Xuất xứ: Nhật Bản. Nhập khẩu trực tiếp từ Nhật', '4901331012860.jpg', 3, 1),
(23, 'Set 5 dao cạo lông mày KAI\r\n', '4901331010781', 18, 55000, NULL, 'MÔ TẢ SẢN PHẨM\r\nCombo 5 dao cạo lông mày KAI Nhật Bản là phụ kiện hỗ trợ tốt cho việc làm đẹp chân mày. Với dao cạo lông mày KAI, bạn có thể thỏa thích tạo đường cong chân mày sắc nét và nổi bật.\r\nDao cạo lông mày KAI​​​ - Mày xinh, mặt càng thêm xinh\r\nThông tin sản phẩm:\r\n- Chất liệu: Thép không gỉ, nhựa cao cấp', '4901331010781.jpg', 3, 1),
(24, 'Xịt Chống Muỗi Và Côn Trùng Cho Bé SKIN VAPE 200ml Nội Địa Nhật (Chai Màu Hồng - Hương Đào) Dùng Cho Bé Từ 6 Tháng Tuổi Trở Lên\r\n', '4902424433081', 20, 225000, NULL, 'Xịt chống muỗi Skin Vape hương mơ đào của Nhật Bản thích hợp cho cả người lớn lẫn trẻ nhỏ.\r\nDùng để xịt lên da vùng tay, chân và cổ, hiệu quả trong việc xua đuổi muỗi và nhiều loại côn trùng khác.', '4902424433081.jpg', 4, 1),
(25, 'Set 3 gói giấy ướt 80 tờ cho bé (100% tinh khiết)\n', '4589506153282', 15, 145000, NULL, 'Chứa đến 99% nước tinh khiết, dịu nhẹ và an toàn cho làn da bé nhỏ\r\nKết hợp thêm thành phần dưỡng ẩm từ collagen, hyaluronic acid giữ cho làn da bé luôn mềm mại, mịn màng\r\nKhăn giấy không chứa cồn, paraben, hương liệu', '4589506153282.jpg', 4, 1),
(26, 'Kem Đánh Răng Cho Trẻ Em KAO KIDS Hương Dâu 70g Hàng Nội Địa Nhật Bản Cho Bé Từ 3 Tuổi\r\n', '4901301281623', 27, 78000, NULL, 'Kem đánh răng trẻ em KAO Clear Clean Kid\'s 70g là thương hiệu nổi tiếng của Nhật Bản, sản phẩm được thiết kế với hình dáng những con vật ngộ nghĩnh trên bao bì sản phẩm nhằm thu hút sự chú ý và tò mò của bé. Giúp chống sâu răng, tăng độ chắc khỏe cho răng, giúp men răng trắng sáng.', '4901301281623.jpg', 4, 1),
(27, 'Lăn Bôi Trị Muỗi Và Côn Trùng Đốt MUHI 50ml  Nội Địa Nhật Chim Cánh Cụt Cho Bé Từ 6 Tháng Tuổi\r\n', '4987426002091', 35, 176000, NULL, 'Lăn trị muỗi đốt Muhi từ Nhật Bản giúp làm xẹp, làm dịu nhanh cơn ngứa, vết sưng tấy do muỗi, các loại côn trùng cắn tức thì và không để lại sẹo. Sản phẩm không chứa cồn hay bất kỳ chất phụ gia độc hại, an toàn cho da nhạy cảm, giúp chống hăm da, rôm sẩy, viêm da, đỏ da, nổi mề đay, chàm, phát ban nhiệt ở cả trẻ em và người lớn.', '4987426002091.jpg', 5, 1),
(28, 'Hộp Đựng Thuốc 2 Ngăn Cao Cấp Inomata Nhật Bản\r\n', '4973228171516', 14, 95000, NULL, 'Mô tả sản phẩm Hộp đựng thuốc Inomata chia 2 ngăn Nhật Bản\r\n- Chất liệu: nhựa PP cao cấp\r\n- Kích thước: đường kính 7cm * độ dày 2cm\r\n- Công dụng: Chia 2 ngăn, dùng để đựng th. Thiết kế nhỏ gọn dễ dàng mang theo người. Kiểu dáng đẹp, sang trọng. \r\n- Hàng nhập khẩu từ Nhật, sản xuất tại Nhật Bản ', '4973228171516.jpg', 5, 1),
(29, 'Kem Đánh Răng Muối SunStar tuýp 170g Hàng Nội Địa Nhật Bản\r\n', '4901616005266', 17, 70000, NULL, 'Kem Đánh Răng Muối SunStar là sản phẩm đến từ Nhật Bản, với khả năng chăm sóc răng miệng 1 cách toàn diện. Kem có chứa thành phần chính là muối kết hợp với canxi carbonate, vitamin E, tinh thể muối, sorbitol giúp đánh bật các mảng bám ố vàng trên răng và trong từng kẽ răng, đồng thời còn giúp ngăn chặn các bệnh về nha chu, sâu răng, chảy máu chân răng hiệu quả.', '4901616005266.jpg', 5, 1),
(30, 'Hộp 180 bông ngoáy tai cao cấp cho người lớn', '4936613072331', 17, 90000, 80000, 'Xuất xứ: Hàng nội địa Nhật Bản, sản xuất tại Nhật Bản.\r\n- Chất liệu: tay cầm bằng nhựa, 2 đầu bằng bông.\r\n- Công dụng: vệ sinh, làm sạch tai. Đầu bông chất liệu cotton cao cấp, không gây đau rát, an toàn khi ngoáy tai.', '4936613072331.jpg', 5, 1),
(31, 'Bàn Chải Chà Gót Chân Sanada Seiko (Đá San Hô)', '4973430023672', 5, 60000, 20000, 'Bàn Chải Chà Gót Chân Sanada Seiko (Đá San Hô)\r\n\r\nBàn chải chà gót chân bằng đá san hô dùng cọ gót chân giúp làm mềm, mịn gót chân, loại bỏ các vết chai sần, xơ cứng phần gót chân bạn.\r\nĐặc điểm\r\nChất liệu an toàn\r\nVới thành phần từ đá thiên nhiên nên bạn sẽ hoàn toàn yên tâm khi sử dụng để chăm sóc cho đôi chân của mình và gia đình\r\nSản phẩm với kích thước vừa tay cầm, dễ dàng cất gọn, giúp tiết kiệm không gian nhà tắm', '4973430023672.jpg', 5, 1),
(37, 'Bàn Chải Chà Gót Chân Sanada Seiko (Đá San Hô)', '', 0, 10000, 9000, '', '692c24dd0e2b9.jpg', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `refund_requests`
--

DROP TABLE IF EXISTS `refund_requests`;
CREATE TABLE IF NOT EXISTS `refund_requests` (
  `refundID` int NOT NULL AUTO_INCREMENT,
  `orderID` int NOT NULL,
  `customerID` int NOT NULL,
  `type` enum('cancel','return') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cancel',
  `amount` decimal(10,2) NOT NULL COMMENT 'Số tiền cần hoàn',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `bankAccount` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Số tài khoản nhận tiền',
  `bankName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên ngân hàng',
  `accountHolder` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên chủ tài khoản',
  `proofImage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ảnh chứng minh đã chuyển khoản',
  `status` enum('Chờ xử lý','Đã hoàn tiền','Từ chối') COLLATE utf8mb4_unicode_ci DEFAULT 'Chờ xử lý',
  `adminNote` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú từ admin',
  `processedBy` int DEFAULT NULL COMMENT 'Admin xử lý',
  `processedAt` datetime DEFAULT NULL COMMENT 'Thời gian xử lý',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refundID`),
  KEY `idx_orderID` (`orderID`),
  KEY `idx_customerID` (`customerID`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`createdAt`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng yêu cầu hoàn tiền khi hủy đơn';

--
-- Dumping data for table `refund_requests`
--

INSERT INTO `refund_requests` (`refundID`, `orderID`, `customerID`, `type`, `amount`, `reason`, `bankAccount`, `bankName`, `accountHolder`, `proofImage`, `status`, `adminNote`, `processedBy`, `processedAt`, `createdAt`) VALUES
(1, 189, 2, 'cancel', 40500.00, NULL, '2123213213123', 'MoMo', 'ÁDASDASDAS', 'image/refund_proofs/refund_proof_189_1764870826.jpg', 'Đã hoàn tiền', 'Đã hoàn cho khách', 1, '2025-12-05 01:19:47', '2025-12-05 00:53:46'),
(2, 190, 2, 'cancel', 24900.00, NULL, '123123123123', 'VIB', 'AAAAAA', 'image/refund_proofs/refund_proof_190_1764873411.jpg', 'Đã hoàn tiền', 'oke', 1, '2025-12-05 01:37:03', '2025-12-05 01:36:51'),
(3, 191, 2, 'cancel', 80500.00, NULL, '2123213213123', 'OCB', 'AAAAAA', 'image/refund_proofs/refund_proof_191_1764873847.jpg', 'Đã hoàn tiền', 'aaaaa', 1, '2025-12-05 01:44:51', '2025-12-05 01:44:07'),
(4, 192, 2, 'cancel', 45800.00, NULL, '13123123', 'LienVietPostBank', '1111', 'image/refund_proofs/refund_proof_192_1764874299.jpg', 'Đã hoàn tiền', '', 1, '2025-12-05 01:51:52', '2025-12-05 01:51:39'),
(5, 193, 2, 'cancel', 20500.00, NULL, '121212', 'OCB', '1111111111', 'image/refund_proofs/refund_proof_193_1764874725.jpg', 'Đã hoàn tiền', 'aaaaaaaaaaa', 1, '2025-12-05 01:58:55', '2025-12-05 01:58:45'),
(6, 194, 2, 'cancel', 20500.00, NULL, '2123213213123', 'PVcomBank', 'ÁDASDASDAS', 'image/refund_proofs/refund_proof_194_1764875570.jpg', 'Đã hoàn tiền', '', 1, '2025-12-05 02:12:56', '2025-12-05 02:12:50');

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
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`reviewID`, `rating`, `comment`, `dateReview`, `orderID`, `productID`, `customerID`, `status`) VALUES
(14, 5, 'ádasdasdasd', '2025-12-02 15:36:47', 171, 30, 3, 1),
(13, 5, 'ádasdasdasdsad', '2025-12-02 15:36:05', 173, 31, 3, 1),
(12, 5, 'ádasdasa', '2025-12-02 15:35:12', 174, 37, 3, 1);

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
(7, 'HHH', 'abc@gmail.com', 'fcea920f7412b5da7be0cf42b8c93759', 'áaa', '1', 2);

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

DROP TABLE IF EXISTS `voucher`;
CREATE TABLE IF NOT EXISTS `voucher` (
  `voucherID` int NOT NULL AUTO_INCREMENT,
  `voucherName` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `value` decimal(10,0) NOT NULL,
  `minOrderValue` int NOT NULL DEFAULT '0',
  `quantity` int NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `requirement` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_520_ci NOT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=active, 0=locked',
  PRIMARY KEY (`voucherID`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_520_ci;

--
-- Dumping data for table `voucher`
--

INSERT INTO `voucher` (`voucherID`, `voucherName`, `value`, `minOrderValue`, `quantity`, `startDate`, `endDate`, `requirement`, `status`) VALUES
(24, 'AASTU', 20000, 10000, 2, '2025-11-04', '2026-02-19', '', 1);

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
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Ánh xạ voucher - nhóm khách hàng';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
