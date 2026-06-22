-- ============================================================
-- Міграція: ініціалізувати path для всіх існуючих категорій
-- Запустити ПІСЛЯ 2026_06_14_category_path.sql
--
-- Алгоритм: рекурсивне оновлення через PHP після застосування.
-- SQL лише очищає і встановлює кореневі path.
-- ============================================================

-- 1. Встановлюємо path для кореневих категорій (parent_id IS NULL)
UPDATE `categories`
SET `path` = CONCAT('/', `slug`)
WHERE `parent_id` IS NULL;

-- 2. Рівень 1 (прямі нащадки кореня)
UPDATE `categories` c
INNER JOIN `categories` p ON c.`parent_id` = p.`id`
SET c.`path` = CONCAT(p.`path`, '/', c.`slug`)
WHERE p.`parent_id` IS NULL;

-- 3. Рівень 2
UPDATE `categories` c
INNER JOIN `categories` p ON c.`parent_id` = p.`id`
INNER JOIN `categories` gp ON p.`parent_id` = gp.`id`
SET c.`path` = CONCAT(p.`path`, '/', c.`slug`)
WHERE gp.`parent_id` IS NULL;

-- 4. Рівень 3
UPDATE `categories` c
INNER JOIN `categories` p ON c.`parent_id` = p.`id`
INNER JOIN `categories` gp ON p.`parent_id` = gp.`id`
INNER JOIN `categories` ggp ON gp.`parent_id` = ggp.`id`
SET c.`path` = CONCAT(p.`path`, '/', c.`slug`)
WHERE ggp.`parent_id` IS NULL;

-- 5. Рівень 4 (більшості магазинів достатньо 4 рівнів)
UPDATE `categories` c
INNER JOIN `categories` p  ON c.`parent_id`  = p.`id`
INNER JOIN `categories` p2 ON p.`parent_id`  = p2.`id`
INNER JOIN `categories` p3 ON p2.`parent_id` = p3.`id`
INNER JOIN `categories` p4 ON p3.`parent_id` = p4.`id`
SET c.`path` = CONCAT(p.`path`, '/', c.`slug`)
WHERE p4.`parent_id` IS NULL;
