-- Select the POS database in phpMyAdmin before running.
-- Removes brands across ALL businesses, without deleting products or users.
-- Existing products lose their brand assignment. Export a backup first.
START TRANSACTION;
UPDATE `products` SET `brand_id` = NULL WHERE `brand_id` IS NOT NULL;
DELETE FROM `brands`;
COMMIT;
ALTER TABLE `brands` AUTO_INCREMENT = 1;
SELECT DATABASE() AS selected_database, COUNT(*) AS brands_remaining FROM `brands`;
