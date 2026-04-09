-- Оновлення схеми для розширеного checkout
ALTER TABLE `orders`
    ADD COLUMN `customer_name` VARCHAR(255) NULL AFTER `total`,
    ADD COLUMN `customer_phone` VARCHAR(50) NULL AFTER `customer_name`,
    ADD COLUMN `customer_email` VARCHAR(255) NULL AFTER `customer_phone`,
    ADD COLUMN `delivery_method` VARCHAR(50) NULL AFTER `customer_email`,
    ADD COLUMN `delivery_city` VARCHAR(255) NULL AFTER `delivery_method`,
    ADD COLUMN `delivery_warehouse` VARCHAR(255) NULL AFTER `delivery_city`,
    ADD COLUMN `delivery_address` VARCHAR(255) NULL AFTER `delivery_warehouse`,
    ADD COLUMN `payment_method` VARCHAR(50) NULL AFTER `delivery_address`,
    ADD COLUMN `comment` TEXT NULL AFTER `payment_method`,
    ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `comment`;

-- За потреби індекс для аналітики замовлень
CREATE INDEX `idx_orders_created_at` ON `orders` (`created_at`);
