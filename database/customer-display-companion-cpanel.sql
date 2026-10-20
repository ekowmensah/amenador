-- Select the existing POS database in phpMyAdmin before importing this file.
-- Equivalent to 2026_10_20_120000_create_customer_display_devices_table.

CREATE TABLE IF NOT EXISTS `customer_display_devices` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `business_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `register_hash` VARCHAR(64) NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `name` VARCHAR(80) NOT NULL,
    `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `customer_display_devices_token_hash_unique` (`token_hash`),
    KEY `customer_display_register` (`business_id`, `user_id`, `register_hash`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Record it so a later artisan migrate does not try to recreate the table.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_20_120000_create_customer_display_devices_table',
       COALESCE((SELECT MAX(`batch`) FROM `migrations`), 0) + 1
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_20_120000_create_customer_display_devices_table'
);
