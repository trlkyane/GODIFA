-- =========================================
-- Migration: Remove userID from order table
-- Date: 2024
-- Author: Database Cleanup Initiative
-- =========================================

-- REASON FOR REMOVAL:
-- Analysis showed that 66.7% (16/24) of orders had NULL userID
-- The userID column was intended to track which staff member processed the order
-- However, this feature was never fully implemented and the data is mostly NULL
-- Removing this simplifies the schema and removes confusion

-- FORWARD MIGRATION
-- ==================
ALTER TABLE `order` DROP COLUMN userID;

-- ROLLBACK (if needed)
-- =====================
-- To restore the column (data will be lost):
-- ALTER TABLE `order` ADD COLUMN userID INT DEFAULT NULL AFTER voucherID;
-- ALTER TABLE `order` ADD CONSTRAINT fk_order_user FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE SET NULL;

-- CODE CHANGES REQUIRED:
-- ======================
-- ✅ model/mOrder.php:
--    - createOrder(): Removed $userId parameter, removed userID from INSERT
--    - getOrderById(): Removed LEFT JOIN user
--    - getAllOrders(): Removed LEFT JOIN user, removed staffName from SELECT
--    - searchOrders(): Removed LEFT JOIN user from all 3 query variants
-- 
-- ✅ admin/pages/orders.php:
--    - Removed "Nhân viên xử lý" field from order detail modal
--    - Removed detail_staffName element references
-- 
-- ℹ️ controller/cOrder.php:
--    - Already calling createOrder() without $userId parameter (no changes needed)

-- MIGRATION STATUS: ✅ COMPLETED
-- Database: Column dropped
-- Code: All references removed
-- UI: Staff handler field removed from admin interface
