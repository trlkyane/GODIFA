-- ============================================================
-- FIX COLLATION ERROR CHO BẢNG CHAT
-- Lỗi: Conversion from utf8mb4_unicode_ci into utf8mb3_unicode_520_ci
-- ============================================================

-- Kiểm tra collation hiện tại của bảng chat
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    COLLATION_NAME,
    CHARACTER_SET_NAME,
    COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'godifa1' 
  AND TABLE_NAME = 'chat';

-- Hoặc kiểm tra collation của bảng
SELECT 
    TABLE_NAME,
    TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'godifa1' 
  AND TABLE_NAME = 'chat';

-- Chuyển đổi toàn bộ bảng chat sang utf8mb4
ALTER TABLE `chat` 
CONVERT TO CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Đặc biệt: Fix cột chatContent và senderType
ALTER TABLE `chat` 
MODIFY COLUMN `chatContent` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
MODIFY COLUMN `senderType` ENUM('customer','user','bot') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- Kiểm tra lại sau khi sửa
SELECT 
    COLUMN_NAME,
    COLLATION_NAME,
    CHARACTER_SET_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'godifa1' 
  AND TABLE_NAME = 'chat'
  AND COLUMN_NAME IN ('chatContent', 'senderType');

-- Kết quả mong đợi:
-- chatContent: utf8mb4_unicode_ci
-- senderType: utf8mb4_unicode_ci
