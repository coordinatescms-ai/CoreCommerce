-- Product reviews schema compatible with ec30.sql (INT signed keys)
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `parent_id` INT NULL,
  `rating` TINYINT NULL,
  `author_name` VARCHAR(191) NOT NULL,
  `body` TEXT NOT NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_product_parent_created` (`product_id`, `parent_id`, `created_at`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_parent` FOREIGN KEY (`parent_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TRIGGER IF EXISTS `trg_reviews_validate_insert`;
DELIMITER $$
CREATE TRIGGER `trg_reviews_validate_insert`
BEFORE INSERT ON `product_reviews`
FOR EACH ROW
BEGIN
  IF NEW.parent_id IS NULL THEN
    IF NEW.rating IS NULL OR NEW.rating < 1 OR NEW.rating > 5 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Root review rating must be between 1 and 5';
    END IF;
  ELSE
    IF NEW.rating IS NOT NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reply review must not have rating';
    END IF;
  END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_reviews_validate_update`;
DELIMITER $$
CREATE TRIGGER `trg_reviews_validate_update`
BEFORE UPDATE ON `product_reviews`
FOR EACH ROW
BEGIN
  IF NEW.parent_id IS NULL THEN
    IF NEW.rating IS NULL OR NEW.rating < 1 OR NEW.rating > 5 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Root review rating must be between 1 and 5';
    END IF;
  ELSE
    IF NEW.rating IS NOT NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reply review must not have rating';
    END IF;
  END IF;
END$$
DELIMITER ;
