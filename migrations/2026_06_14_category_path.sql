-- ============================================================
-- Міграція: додати колонку path до таблиці categories
-- path = матеріалізований шлях slug від кореня, напр.: /smartfony/iphone
-- Використовується для ЧПУ вкладених категорій та швидкого пошуку предків.
-- ============================================================

ALTER TABLE `categories`
    ADD COLUMN `path` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
        COMMENT 'Матеріалізований шлях slug від кореня: /slug1/slug2/slug3'
        AFTER `slug`,
    ADD KEY `idx_categories_path` (`path`(255));
