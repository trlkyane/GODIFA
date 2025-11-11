-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 08, 2025 at 07:29 PM
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
-- Database: `godifa`
--

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
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Hoạt động, 0=Tạm dừng',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isSystem` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`groupID`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Nhóm khách hàng theo chi tiêu';

--
-- Dumping data for table `customer_group`
--

INSERT INTO `customer_group` (`groupID`, `groupName`, `description`, `minSpent`, `maxSpent`, `color`, `status`, `createdAt`, `isSystem`) VALUES
(1, 'Bronze', 'Hạng Đồng', 1, 4999999, '#cd7f32', 1, '2025-10-30 05:13:38', 0),
(2, 'Silver', 'Hạng Bạc', 5000000, 14999999, '#99a6b8', 1, '2025-10-29 10:21:19', 0),
(3, 'Gold', 'Hạng Vàng', 15000000, 29999999, '#fbbf24', 1, '2025-10-29 10:21:19', 0),
(4, 'Platinum', 'Hạng Bạch Kim', 30000000, 49999999, '#42e9ff', 1, '2025-10-29 10:21:19', 0),
(5, 'Diamond', 'Hạng Kim Cương', 50000000, NULL, '#2042ee', 1, '2025-10-29 10:21:19', 0),
(8, 'Khách hàng mới', 'New', 0, 0, '#000000', 1, '2025-10-30 05:17:15', 1);

--
-- Triggers `customer_group`
--
DROP TRIGGER IF EXISTS `after_customer_group_update_reassign`;
DELIMITER $$
CREATE TRIGGER `after_customer_group_update_reassign` AFTER UPDATE ON `customer_group` FOR EACH ROW BEGIN
    IF NEW.isSystem = 0 OR NEW.isSystem IS NULL THEN
        CALL auto_assign_customer_groups_by_spending();
    END IF;
END
$$
DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
