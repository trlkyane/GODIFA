-- ============================================
-- XÓA CÁC CỘT KHÔNG DÙNG ĐẾN TRONG BẢNG CUSTOMER
-- Ngày: 10/11/2025
-- Mục đích: Đơn giản hóa database, xóa birthdate và gender
-- ============================================

-- BƯỚC 1: Xóa VIEW sử dụng birthdate và gender
DROP VIEW IF EXISTS v_customer_demographics;

-- BƯỚC 2: Xóa 2 cột không dùng đến
ALTER TABLE customer 
DROP COLUMN IF EXISTS birthdate,
DROP COLUMN IF EXISTS gender;

-- ============================================
-- VERIFICATION
-- ============================================
DESCRIBE customer;

-- Expected Result:
-- +--------------+--------------+------+-----+---------+----------------+
-- | Field        | Type         | Null | Key | Default | Extra          |
-- +--------------+--------------+------+-----+---------+----------------+
-- | customerID   | int          | NO   | PRI | NULL    | auto_increment |
-- | customerName | varchar(100) | NO   |     | NULL    |                |
-- | phone        | text         | NO   |     | NULL    |                |
-- | email        | varchar(100) | NO   | UNI | NULL    |                |
-- | password     | varchar(100) | NO   |     | NULL    |                |
-- | status       | tinyint(1)   | NO   | MUL | 1       |                |
-- | groupID      | int          | YES  | MUL | NULL    |                |
-- +--------------+--------------+------+-----+---------+----------------+

-- Kiểm tra dữ liệu
SELECT * FROM customer LIMIT 3;

-- ============================================
-- ROLLBACK (NẾU CẦN)
-- ============================================

-- Để rollback, thêm lại cột:
-- ALTER TABLE customer 
-- ADD COLUMN birthdate DATE NULL COMMENT 'Ngày sinh',
-- ADD COLUMN gender ENUM('Nam','Nữ','Khác') NULL;
--
-- Tạo lại VIEW (nếu cần):
-- CREATE VIEW v_customer_demographics AS ...

-- ============================================
-- LÝ DO XÓA
-- ============================================

/*
1. KHÔNG DÙNG ĐẾN:
   - Không có form nhập birthdate/gender
   - Không có chức năng lọc theo tuổi/giới tính
   - Không có báo cáo demographics
   - Tất cả customer có birthdate = NULL, gender = NULL

2. ĐƠN GIẢN HÓA:
   - Giảm số cột trong bảng customer
   - Giảm complexity của database
   - Dễ maintain hơn

3. GIẢM CONFUSION:
   - Không còn thắc mắc tại sao có cột nhưng không dùng
   - Code sạch hơn, rõ ràng hơn
*/

-- ============================================
-- DONE!
-- ============================================

/*
✅ Đã xóa cột birthdate
✅ Đã xóa cột gender
✅ Đã xóa VIEW v_customer_demographics
✅ Database đơn giản hơn, dễ maintain
*/
