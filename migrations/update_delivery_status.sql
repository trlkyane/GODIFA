-- Migration: Update old delivery statuses to new naming
-- Date: 2025-11-09
-- Purpose: Update delivery status from old names to new simplified flow

-- Update "Chờ xử lý" -> "Chờ xác nhận"
UPDATE `order` 
SET deliveryStatus = 'Chờ xác nhận' 
WHERE deliveryStatus = 'Chờ xử lý';

-- Update "Đang xử lý" -> "Đang tiến hành vận chuyển"  
UPDATE `order` 
SET deliveryStatus = 'Đang tiến hành vận chuyển' 
WHERE deliveryStatus = 'Đang xử lý';

-- Update "Đang giao" -> "Đang tiến hành vận chuyển"
UPDATE `order` 
SET deliveryStatus = 'Đang tiến hành vận chuyển' 
WHERE deliveryStatus = 'Đang giao';

-- Update "Đã giao" -> "Hoàn thành"
UPDATE `order` 
SET deliveryStatus = 'Hoàn thành' 
WHERE deliveryStatus = 'Đã giao';

-- Show results
SELECT 
    deliveryStatus,
    COUNT(*) as total_orders
FROM `order`
GROUP BY deliveryStatus
ORDER BY total_orders DESC;
