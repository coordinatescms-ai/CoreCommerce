-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0:3306
-- Час створення: Чрв 22 2026 р., 18:04
-- Версія сервера: 8.0.45
-- Версія PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База даних: `ec30`
--

DELIMITER $$
--
-- Процедури
--
CREATE DEFINER=`root`@`%` PROCEDURE `AddSlugColumns` ()   BEGIN
    -- Перевірка та додавання колонок до таблиці products
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'products' AND column_name = 'slug' AND table_schema = DATABASE()) THEN
        ALTER TABLE products ADD COLUMN slug VARCHAR(255) UNIQUE NOT NULL AFTER name;
    END IF;
    
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'products' AND column_name = 'meta_title' AND table_schema = DATABASE()) THEN
        ALTER TABLE products ADD COLUMN meta_title VARCHAR(255) AFTER slug;
    END IF;
    
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'products' AND column_name = 'meta_description' AND table_schema = DATABASE()) THEN
        ALTER TABLE products ADD COLUMN meta_description TEXT AFTER meta_title;
    END IF;
    
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'products' AND column_name = 'meta_keywords' AND table_schema = DATABASE()) THEN
        ALTER TABLE products ADD COLUMN meta_keywords VARCHAR(255) AFTER meta_description;
    END IF;

    -- Перевірка та додавання колонок до таблиці categories
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'categories' AND column_name = 'slug' AND table_schema = DATABASE()) THEN
        ALTER TABLE categories ADD COLUMN slug VARCHAR(255) UNIQUE NOT NULL AFTER name;
    END IF;
    
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'categories' AND column_name = 'meta_title' AND table_schema = DATABASE()) THEN
        ALTER TABLE categories ADD COLUMN meta_title VARCHAR(255) AFTER slug;
    END IF;
    
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'categories' AND column_name = 'meta_description' AND table_schema = DATABASE()) THEN
        ALTER TABLE categories ADD COLUMN meta_description TEXT AFTER meta_title;
    END IF;
    
    IF NOT EXISTS (SELECT * FROM information_schema.columns WHERE table_name = 'categories' AND column_name = 'meta_keywords' AND table_schema = DATABASE()) THEN
        ALTER TABLE categories ADD COLUMN meta_keywords VARCHAR(255) AFTER meta_description;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблиці `attributes`
--

CREATE TABLE `attributes` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('text','select','multiselect','color','range') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_filterable` tinyint(1) DEFAULT '1',
  `is_visible` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `attributes`
--

INSERT INTO `attributes` (`id`, `name`, `slug`, `type`, `description`, `is_filterable`, `is_visible`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Колір', 'color', 'select', 'Колір товару', 1, 1, 1, '2026-03-29 16:28:20', '2026-03-29 16:28:20'),
(2, 'Розмір', 'size', 'select', 'Розмір товару', 1, 1, 2, '2026-03-29 16:28:20', '2026-03-29 16:28:20'),
(3, 'Матеріал', 'material', 'select', 'Матеріал товару', 1, 1, 3, '2026-03-29 16:28:20', '2026-03-29 16:28:20'),
(4, 'Бренд', 'brand', 'select', 'Виробник товару', 1, 1, 4, '2026-03-29 16:28:20', '2026-03-29 16:28:20'),
(5, 'Гарантія', 'warranty', 'text', 'Період гарантії', 0, 1, 5, '2026-03-29 16:28:20', '2026-03-29 16:28:20');

-- --------------------------------------------------------

--
-- Структура таблиці `attribute_options`
--

CREATE TABLE `attribute_options` (
  `id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_code` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `attribute_options`
--

INSERT INTO `attribute_options` (`id`, `attribute_id`, `name`, `value`, `color_code`, `sort_order`, `created_at`) VALUES
(68, 3, 'Бавовна', 'Бавовна', NULL, 1, '2026-05-10 11:28:30'),
(69, 3, 'Поліестер', 'Поліестер', NULL, 2, '2026-05-10 11:28:30'),
(70, 3, 'Шовк', 'Шовк', NULL, 3, '2026-05-10 11:28:30'),
(71, 3, 'Вовна', 'Вовна', NULL, 4, '2026-05-10 11:28:30'),
(90, 1, 'Чорний', 'Чорний', NULL, 1, '2026-05-10 11:49:44'),
(91, 1, 'Білий', 'Білий', NULL, 2, '2026-05-10 11:49:44'),
(92, 1, 'Червоний', 'Червоний', NULL, 3, '2026-05-10 11:49:44'),
(93, 1, 'Синій', 'Синій', NULL, 4, '2026-05-10 11:49:44'),
(94, 2, 'XS', 'XS', NULL, 1, '2026-05-10 11:49:51'),
(95, 2, 'S', 'S', NULL, 2, '2026-05-10 11:49:51'),
(96, 2, 'M', 'M', NULL, 3, '2026-05-10 11:49:51'),
(97, 2, 'L', 'L', NULL, 4, '2026-05-10 11:49:51'),
(98, 2, 'XL', 'XL', NULL, 5, '2026-05-10 11:49:51'),
(99, 2, 'XXL', 'XXL', NULL, 6, '2026-05-10 11:49:51'),
(100, 4, 'Китай', 'Китай', NULL, 1, '2026-05-10 11:55:08'),
(101, 4, 'Філіпс', 'Філіпс', NULL, 2, '2026-05-10 11:55:08');

-- --------------------------------------------------------

--
-- Структура таблиці `cart`
--

CREATE TABLE `cart` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` int NOT NULL,
  `selected_options` json DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Матеріалізований шлях slug від кореня: /slug1/slug2/slug3',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort_order` int DEFAULT '0',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `slug`, `path`, `description`, `sort_order`, `image`, `is_active`, `meta_title`, `meta_description`, `meta_keywords`, `created_at`, `updated_at`) VALUES
(1, 'Смартфони', NULL, 'smartfoni', '/smartfoni', 'Категорія смартфони', 0, NULL, 1, 'Купити Смартфони', 'Великий вибір смартфонів!', NULL, '2026-04-04 07:47:51', '2026-06-19 06:30:07'),
(2, 'iPhone', 1, 'iphone', '/smartfoni/iphone', 'Смартфони iPhone! Смартфони iPhone! Смартфони iPhone! Смартфони iPhone! Смартфони iPhone! Смартфони iPhone!', 0, NULL, 1, 'Смартфони iPhone!', 'Смартфони iPhone! Смартфони iPhone!', NULL, '2026-04-04 15:31:02', '2026-06-19 06:30:07'),
(3, 'Телевізори', NULL, 'televzori', '/televzori', 'Телевізори Телевізори Телевізори Телевізори! Телевізори Телевізори Телевізори Телевізори! Телевізори Телевізори Телевізори Телевізори!', 0, NULL, 1, 'Телевізори Телевізори Телевізори Телевізори!', 'Телевізори Телевізори Телевізори Телевізори!', NULL, '2026-04-04 21:23:11', '2026-06-19 06:30:07');

-- --------------------------------------------------------

--
-- Структура таблиці `category_attributes`
--

CREATE TABLE `category_attributes` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `is_required` tinyint(1) DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `category_attributes`
--

INSERT INTO `category_attributes` (`id`, `category_id`, `attribute_id`, `is_required`, `sort_order`, `created_at`) VALUES
(20, 3, 4, 0, 0, '2026-05-10 13:01:02'),
(21, 3, 3, 0, 0, '2026-05-10 13:06:03');

-- --------------------------------------------------------

--
-- Структура таблиці `category_filters`
--

CREATE TABLE `category_filters` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `filter_type` enum('checkbox','range','color') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'checkbox',
  `min_value` decimal(10,2) DEFAULT NULL,
  `max_value` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `crm_user_action_audit`
--

CREATE TABLE `crm_user_action_audit` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `old_value` text,
  `new_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `crm_user_activity_logs`
--

CREATE TABLE `crm_user_activity_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп даних таблиці `crm_user_activity_logs`
--

INSERT INTO `crm_user_activity_logs` (`id`, `user_id`, `event_type`, `description`, `meta`, `created_at`) VALUES
(1, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-26 15:24:32'),
(2, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-27 15:33:34'),
(3, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-27 16:18:24'),
(4, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-27 16:38:29'),
(5, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-04-27 16:38:55'),
(6, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-04-27 17:12:35'),
(7, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-27 17:16:30'),
(8, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-27 17:24:19'),
(9, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-27 17:25:33'),
(10, 2, 'subscription_update', 'Увімкнено email-розсилку', NULL, '2026-04-28 16:08:08'),
(11, 2, 'subscription_update', 'Вимкнено email-розсилку', NULL, '2026-04-28 16:08:09'),
(12, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 15:30:17'),
(13, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 15:30:50'),
(14, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 15:32:26'),
(15, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 15:40:34'),
(16, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 15:47:40'),
(17, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 15:51:39'),
(18, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:02:36'),
(19, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:03:09'),
(20, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:05:32'),
(21, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:12:53'),
(22, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:33'),
(23, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:34'),
(24, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:35'),
(25, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:35'),
(26, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:36'),
(27, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:36'),
(28, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:36'),
(29, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:37'),
(30, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:37'),
(31, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:37'),
(32, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:37'),
(33, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:37'),
(34, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:37'),
(35, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:38'),
(36, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:38'),
(37, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:38'),
(38, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:38'),
(39, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:39'),
(40, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:39'),
(41, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:39'),
(42, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:39'),
(43, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-29 16:28:39'),
(44, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-04-30 16:36:52'),
(45, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-01 07:15:05'),
(46, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-02 15:28:13'),
(47, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 12:37:46'),
(48, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 14:18:37'),
(49, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-04 14:19:04'),
(50, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-04 14:22:03'),
(51, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 14:22:33'),
(52, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 14:22:39'),
(53, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 14:28:04'),
(54, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 14:29:08'),
(55, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 14:34:12'),
(56, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 14:52:52'),
(57, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 15:00:17'),
(58, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 15:32:57'),
(59, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-04 15:35:51'),
(60, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 15:36:07'),
(61, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-04 15:39:34'),
(62, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 15:39:51'),
(63, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-04 15:42:43'),
(64, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-05 17:55:35'),
(65, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-05 17:56:25'),
(66, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-06 06:58:08'),
(67, 2, 'cart_add', 'Додав у кошик: Сіомі', NULL, '2026-05-07 15:17:39'),
(68, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-07 15:18:23'),
(69, 2, 'cart_add', 'Додав у кошик: iPhone 13', NULL, '2026-05-07 15:18:27'),
(70, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-07 15:18:33'),
(71, 2, 'cart_add', 'Додав у кошик: Сіомі', NULL, '2026-05-07 15:18:41'),
(72, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-07 15:33:34'),
(73, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-07 15:33:35'),
(74, 2, 'cart_add', 'Додав у кошик: Сіомі', NULL, '2026-05-08 06:44:30'),
(75, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-08 06:44:43'),
(76, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-08 06:44:47'),
(77, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-08 06:44:49'),
(78, 2, 'cart_add', 'Додав у кошик: iPhone 13', NULL, '2026-05-08 06:45:03'),
(79, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-08 06:45:07'),
(80, 2, 'cart_add', 'Додав у кошик: iPhone 13', NULL, '2026-05-08 06:45:26'),
(81, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-08 06:47:41'),
(82, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-08 06:47:43'),
(83, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-09 17:41:33'),
(84, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-09 17:47:54'),
(85, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-09 17:47:56'),
(86, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-09 17:47:57'),
(87, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-09 17:47:57'),
(88, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-09 17:47:58'),
(89, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 10:48:34'),
(90, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 10:55:56'),
(91, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 10:56:19'),
(92, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 10:56:28'),
(93, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 10:56:44'),
(94, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 10:56:49'),
(95, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 10:57:01'),
(96, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 10:57:36'),
(97, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 10:57:42'),
(98, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 10:57:45'),
(99, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 10:58:06'),
(100, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 10:58:11'),
(101, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 11:01:30'),
(102, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 11:41:21'),
(103, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 11:42:08'),
(104, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 11:42:13'),
(105, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 11:44:34'),
(106, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 11:44:38'),
(107, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 11:45:18'),
(108, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 11:45:35'),
(109, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 11:45:45'),
(110, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 11:45:52'),
(111, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 11:48:23'),
(112, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 11:53:53'),
(113, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 11:55:14'),
(114, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 12:47:56'),
(115, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 12:51:00'),
(116, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 12:52:36'),
(117, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 12:55:13'),
(118, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 12:56:28'),
(119, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 13:03:41'),
(120, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 13:05:32'),
(121, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 13:05:38'),
(122, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 13:07:15'),
(123, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 13:41:04'),
(124, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-10 13:42:44'),
(125, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-10 13:42:55'),
(126, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-11 11:43:49'),
(127, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-11 11:44:44'),
(128, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-11 12:04:08'),
(129, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-11 12:28:24'),
(130, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-11 12:28:32'),
(131, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-11 12:29:02'),
(132, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-11 12:29:28'),
(133, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-11 12:29:58'),
(134, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-11 12:56:30'),
(135, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 06:30:58'),
(136, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 07:17:52'),
(137, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 07:32:04'),
(138, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 07:35:12'),
(139, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-12 07:35:18'),
(140, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 07:40:31'),
(141, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 07:40:56'),
(142, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-12 07:41:09'),
(143, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 07:41:15'),
(144, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 07:44:02'),
(145, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-12 07:44:26'),
(146, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 07:44:43'),
(147, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 09:06:10'),
(148, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 09:15:47'),
(149, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 09:21:16'),
(150, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 09:22:08'),
(151, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 09:26:45'),
(152, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 09:27:11'),
(153, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 09:37:19'),
(154, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 09:37:33'),
(155, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 15:17:52'),
(156, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-12 15:19:56'),
(157, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 15:20:30'),
(158, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 15:25:06'),
(159, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 15:55:03'),
(160, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-12 16:26:51'),
(161, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-13 14:48:52'),
(162, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-13 15:05:35'),
(163, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-13 15:06:07'),
(164, 2, 'cart_add', 'Додав у кошик: iPhone 13', NULL, '2026-05-16 13:06:51'),
(165, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-16 13:07:14'),
(166, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-16 13:07:22'),
(167, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-16 13:07:32'),
(168, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-16 13:07:37'),
(169, 2, 'cart_add', 'Додав у кошик: iPhone 13', NULL, '2026-05-16 13:07:44'),
(170, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-16 13:07:47'),
(171, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-16 13:07:50'),
(172, 2, 'cart_add', 'Додав у кошик: iPhone 13', NULL, '2026-05-16 13:07:56'),
(173, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-16 13:07:58'),
(174, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-16 13:08:00'),
(175, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-16 13:08:03'),
(176, 2, 'cart_add', 'Додав у кошик: iPhone 13', NULL, '2026-05-16 13:09:54'),
(177, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-16 13:09:59'),
(178, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-16 13:10:06'),
(179, 2, 'cart_add', 'Додав у кошик: iPhone 13', NULL, '2026-05-16 13:11:03'),
(180, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-16 13:11:21'),
(181, 2, 'cart_add', 'Додав у кошик: Сіомі', NULL, '2026-05-16 13:14:40'),
(182, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-16 13:15:29'),
(183, 2, 'cart_add', 'Додав у кошик: iPhone 13', NULL, '2026-05-19 16:42:11'),
(184, 2, 'cart_remove', 'Видалив товар із кошика', NULL, '2026-05-19 17:15:18'),
(185, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-19 17:50:38'),
(186, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-19 17:51:10'),
(187, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-19 17:51:14'),
(188, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-19 17:51:38'),
(189, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-19 17:52:03'),
(190, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:37:28'),
(191, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:40:35'),
(192, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:41:01'),
(193, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:41:29'),
(194, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:41:35'),
(195, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:42:18'),
(196, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:42:19'),
(197, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:42:19'),
(198, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:42:53'),
(199, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:43:22'),
(200, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:44:55'),
(201, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:45:59'),
(202, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-22 16:46:07'),
(203, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:46:15'),
(204, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:53:52'),
(205, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 16:55:01'),
(206, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:01:23'),
(207, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:02:26'),
(208, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:03:26'),
(209, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:04:35'),
(210, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:05:47'),
(211, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:06:32'),
(212, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:07:45'),
(213, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-22 17:09:24'),
(214, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:09:41'),
(215, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:11:14'),
(216, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:12:19'),
(217, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:13:16'),
(218, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:15:34'),
(219, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-22 17:15:54'),
(220, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-22 17:16:21'),
(221, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-27 17:19:06'),
(222, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-27 17:19:46'),
(223, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-27 17:19:58'),
(224, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:07:57'),
(225, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:09:14'),
(226, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:10:39'),
(227, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:11:59'),
(228, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:12:34'),
(229, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:24:02'),
(230, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:25:16'),
(231, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:25:25'),
(232, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:26:54'),
(233, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:26:57'),
(234, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:27:08'),
(235, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:30:44'),
(236, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:30:57'),
(237, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:31:56'),
(238, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:32:00'),
(239, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-05-29 16:32:04'),
(240, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:40:16'),
(241, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-05-29 16:53:35'),
(242, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-01 11:36:10'),
(243, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-01 11:36:18'),
(244, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-02 14:58:39'),
(245, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-10 18:00:39'),
(246, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-10 18:01:01'),
(247, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-17 06:46:56'),
(248, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-18 16:53:33'),
(249, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:54:02'),
(250, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-18 16:54:14'),
(251, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:54:18'),
(252, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:54:39'),
(253, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-18 16:54:42'),
(254, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:55:00'),
(255, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:55:05'),
(256, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-18 16:55:07'),
(257, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-18 16:55:15'),
(258, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:55:21'),
(259, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-18 16:56:51'),
(260, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:57:11'),
(261, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-18 16:57:16'),
(262, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:57:48'),
(263, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-18 16:57:55'),
(264, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:58:41'),
(265, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-18 16:59:03'),
(266, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-18 17:04:09'),
(267, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 06:26:45'),
(268, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 06:26:58'),
(269, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-19 06:27:06'),
(270, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-19 06:30:53'),
(271, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 06:30:59'),
(272, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-19 06:35:38'),
(273, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 06:35:44'),
(274, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 06:35:52'),
(275, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 06:40:21'),
(276, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 06:40:44'),
(277, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 06:44:18'),
(278, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 06:44:24'),
(279, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 11:48:24'),
(280, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-19 11:48:44'),
(281, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 11:49:30'),
(282, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-19 11:49:37'),
(283, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 11:55:09'),
(284, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 12:03:15'),
(285, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 12:18:45'),
(286, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-19 12:19:12'),
(287, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-19 12:21:07'),
(288, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:04:16'),
(289, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:04:33'),
(290, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:06:42'),
(291, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:06:47'),
(292, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:09:18'),
(293, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:20:20'),
(294, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:20:24'),
(295, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:20:27'),
(296, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:20:38'),
(297, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:33:43'),
(298, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:33:57'),
(299, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:33:59'),
(300, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:37:01'),
(301, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:37:10'),
(302, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:37:11'),
(303, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-20 17:37:12'),
(304, 2, 'product_view', 'Перегляд товару: iPhone 13', NULL, '2026-06-21 16:41:54'),
(305, 2, 'product_view', 'Перегляд товару: Сіомі', NULL, '2026-06-21 16:48:26');

-- --------------------------------------------------------

--
-- Структура таблиці `crm_user_bonus`
--

CREATE TABLE `crm_user_bonus` (
  `user_id` int NOT NULL,
  `balance` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `crm_user_subscriptions`
--

CREATE TABLE `crm_user_subscriptions` (
  `user_id` int NOT NULL,
  `marketing_email` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп даних таблиці `crm_user_subscriptions`
--

INSERT INTO `crm_user_subscriptions` (`user_id`, `marketing_email`, `updated_at`) VALUES
(2, 0, '2026-04-28 16:08:09');

-- --------------------------------------------------------

--
-- Структура таблиці `cron_tasks`
--

CREATE TABLE `cron_tasks` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Зрозуміла назва задачі для адмінки',
  `command` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Шлях до PHP файлу або назва методу',
  `schedule` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '* * * * *' COMMENT 'Періодичність у форматі Cron',
  `last_run` datetime DEFAULT NULL COMMENT 'Дата і час останнього запуску',
  `next_run` datetime DEFAULT NULL COMMENT 'Коли запускати наступного разу',
  `status` enum('active','disabled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'Статус задачі',
  `last_result` enum('success','running','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success' COMMENT 'Результат останнього виконання',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Текст помилки, якщо статус failed',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `cron_tasks`
--

INSERT INTO `cron_tasks` (`id`, `name`, `command`, `schedule`, `last_run`, `next_run`, `status`, `last_result`, `error_message`, `updated_at`, `params`) VALUES
(1, 'Очищення старих сесій та логів', 'tasks/clear_logs.php', '0 3 * * *', NULL, '2026-05-26 03:00:00', 'active', 'success', NULL, '2026-05-25 12:29:16', NULL),
(2, 'Автоматичний імпорт товарів з XML', 'tasks/import_products.php', '*/30 * * * *', NULL, '2026-05-25 16:00:00', 'active', 'success', NULL, '2026-05-25 12:29:16', NULL);

-- --------------------------------------------------------

--
-- Структура таблиці `currencies`
--

CREATE TABLE `currencies` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Код (USD, UAH, EUR)',
  `symbol` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Символ ($, ₴, €)',
  `rate` decimal(10,4) NOT NULL DEFAULT '1.0000' COMMENT 'Курс відносно UAH (базової)',
  `is_active` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 - активна на сайті, 0 - ні'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `currencies`
--

INSERT INTO `currencies` (`id`, `code`, `symbol`, `rate`, `is_active`) VALUES
(1, 'UAH', '₴', 1.0000, 1),
(2, 'USD', '$', 41.5000, 0),
(3, 'EUR', '€', 45.2000, 0);

-- --------------------------------------------------------

--
-- Структура таблиці `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп даних таблиці `favorites`
--

INSERT INTO `favorites` (`user_id`, `product_id`, `created_at`) VALUES
(2, 2, '2026-04-21 11:50:09');

-- --------------------------------------------------------

--
-- Структура таблиці `filter_history`
--

CREATE TABLE `filter_history` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `inventory_log`
--

CREATE TABLE `inventory_log` (
  `id` int NOT NULL,
  `sku` varchar(64) NOT NULL,
  `event_type` varchar(32) NOT NULL,
  `qty` int NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп даних таблиці `inventory_log`
--

INSERT INTO `inventory_log` (`id`, `sku`, `event_type`, `qty`, `comment`, `created_at`) VALUES
(1, 'ID-1', 'add', 4, '', '2026-05-09 17:46:52'),
(2, 'ID-2', 'add', 10, '', '2026-05-16 13:14:11');

-- --------------------------------------------------------

--
-- Структура таблиці `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int UNSIGNED NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IPv4 або IPv6',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip`, `email`, `success`, `created_at`) VALUES
(1, '127.0.0.1', 'admin@mysite.test', 0, '2026-06-21 18:39:02'),
(2, '127.0.0.1', 'systemmaster@meta.ua', 1, '2026-06-21 18:39:57');

-- --------------------------------------------------------

--
-- Структура таблиці `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `applied_at`) VALUES
(1, '2026_06_14_category_path.sql', '2026-06-19 08:28:43'),
(2, '2026_06_15_rebuild_category_paths.sql', '2026-06-19 08:30:07');

-- --------------------------------------------------------

--
-- Структура таблиці `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `customer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customer_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customer_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_warehouse` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new',
  `ttn_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_id` int DEFAULT NULL,
  `delivery_id` int DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `prom_order_id` bigint UNSIGNED DEFAULT NULL COMMENT 'ID замовлення на Prom.ua',
  `prom_source` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = прийшло з Prom webhook'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `customer_name`, `customer_phone`, `customer_email`, `delivery_method`, `delivery_city`, `delivery_warehouse`, `delivery_address`, `payment_method`, `status`, `ttn_code`, `payment_id`, `delivery_id`, `comment`, `created_at`, `prom_order_id`, `prom_source`) VALUES
(1, 2, 3199.00, 'Василь Присяжнюк', '+380967445693', 'systemmaster@meta.ua', 'courier', '', '', 'с.Ольгопіль', 'cod', 'new', NULL, NULL, NULL, '', '2026-04-18 14:43:34', NULL, 0),
(2, 2, 2050.00, 'Василь Присяжнюк', '+380967445693', 'systemmaster@meta.ua', 'self_pickup', '', '', '', 'cash', 'completed', '135790', 3, 2, '', '2026-04-23 09:40:30', NULL, 0),
(3, 2, 2200.00, 'Василь Присяжнюк', '+380967445693', 'systemmaster@meta.ua', 'self_pickup', 'Ольгопіль', '', '', 'cash', 'shipped', '123456', 3, 2, '', '2026-04-23 16:36:08', NULL, 0);

-- --------------------------------------------------------

--
-- Структура таблиці `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `selected_options` json DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `selected_options`, `qty`, `price`) VALUES
(1, 1, 2, '[{\"op\": \"+\", \"name\": \"Бренд\", \"price\": 150, \"value\": \"Китай\", \"option_id\": 31}]', 1, 2200.00),
(2, 1, 1, NULL, 1, 999.00),
(3, 2, 2, NULL, 1, 2050.00),
(4, 3, 2, '[{\"op\": \"+\", \"name\": \"Бренд\", \"price\": 150, \"value\": \"Китай\", \"option_id\": 31}]', 1, 2200.00);

-- --------------------------------------------------------

--
-- Структура таблиці `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `old_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `new_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ttn_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `changed_by` int DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `old_status`, `new_status`, `ttn_code`, `changed_by`, `changed_at`) VALUES
(1, 1, 'new', 'confirmed', NULL, 2, '2026-04-18 15:13:14'),
(2, 1, 'confirmed', 'processing', NULL, 2, '2026-04-18 15:14:03'),
(3, 1, 'processing', 'new', NULL, 2, '2026-04-18 15:16:17'),
(4, 1, 'new', 'confirmed', NULL, 2, '2026-04-18 15:37:55'),
(5, 1, 'confirmed', 'processing', NULL, 2, '2026-04-18 15:38:05'),
(6, 1, 'processing', 'confirmed', NULL, 2, '2026-04-18 15:38:19'),
(7, 1, 'confirmed', 'new', NULL, 2, '2026-04-18 15:39:06'),
(8, 1, 'new', 'processing', NULL, 2, '2026-04-18 15:42:17'),
(9, 1, 'processing', 'confirmed', NULL, 2, '2026-04-18 15:42:26'),
(10, 1, 'confirmed', 'new', NULL, 2, '2026-04-18 15:42:30'),
(11, 1, 'new', 'confirmed', NULL, 2, '2026-04-18 15:50:38'),
(12, 1, 'confirmed', 'new', NULL, 2, '2026-04-18 15:51:14'),
(13, 2, 'new', 'confirmed', NULL, 2, '2026-04-23 10:26:04'),
(14, 2, 'confirmed', 'new', NULL, 2, '2026-04-23 10:26:06'),
(15, 3, 'new', 'shipped', '123456', 2, '2026-04-28 16:15:24'),
(16, 2, 'new', 'shipped', '135790', 2, '2026-04-28 16:30:46'),
(17, 2, 'shipped', 'completed', '135790', 2, '2026-04-28 16:30:55');

-- --------------------------------------------------------

--
-- Структура таблиці `pages`
--

CREATE TABLE `pages` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп даних таблиці `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `meta_title`, `meta_description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Про нас', 'pro-nas', '\r\n                \r\n                \r\n                \r\n                \r\n                \r\n                \r\n                \r\n                \r\n                \r\n                <b>Про насіфч фчфічфічфіч&nbsp;&nbsp;</b>Про нас&nbsp;Про нас&nbsp;Про нас&nbsp;Про насм<div><div><ul><li>фЧФчФЧсфі</li><li>фісфісіфсіфсіф</li></ul>ссфісфісфісфіс сіфсіфс <a href=\"null\">фсфісіфс</a></div><div><br></div><div><img src=\"/uploads/pages/img_69ef222b92b294.15759830.jpg\" style=\"max-width: 100%; height: auto; display: block; margin: 10px 0px;\"></div>                                                                                                </div>                        ', 'Про сайт', 'Про сайт Про сайт Про сайт Про сайт! Про сайт Про сайт Про сайт Про сайт! Про сайт Про сайт Про сайт Про сайт! Про сайт Про сайт Про сайт Про сайт!', 1, 0, '2026-04-26 14:12:53', '2026-04-27 08:48:33'),
(3, 'Доставка', 'dostavka', '\r\n                \r\n                <p>Доставка&nbsp;Доставка&nbsp;Доставка&nbsp;Доставка! Доставка&nbsp;Доставка&nbsp;Доставка&nbsp;Доставка! Доставка&nbsp;Доставка&nbsp;Доставка&nbsp;Доставка!</p><p>Доставка&nbsp;Доставка&nbsp;Доставка&nbsp;Доставка! Доставка&nbsp;Доставка&nbsp;Доставка&nbsp;Доставка!</p><p>Доставка&nbsp;Доставка&nbsp;Доставка&nbsp;Доставка!</p>\r\n                        ', NULL, NULL, 1, 1, '2026-04-27 08:05:34', '2026-04-27 08:06:37');

-- --------------------------------------------------------

--
-- Структура таблиці `plugins`
--

CREATE TABLE `plugins` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `main_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `version` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `plugins`
--

INSERT INTO `plugins` (`id`, `name`, `slug`, `main_file`, `is_active`, `version`, `created_at`, `updated_at`) VALUES
(1, 'Test Plugin', 'TestPlugin', 'D:\\OSPanel\\home\\mysite.test/plugins/TestPlugin/plugin.php', 0, '1.0.0', '2026-04-30 19:25:51', '2026-06-19 14:39:35'),
(2, 'LiqPay — онлайн оплата', 'LiqPayGateway', 'D:\\OSPanel\\home\\mysite.test/plugins/LiqPayGateway/plugin.php', 0, '1.0.0', '2026-06-10 13:42:15', '2026-06-19 14:39:35');

-- --------------------------------------------------------

--
-- Структура таблиці `plugin_settings`
--

CREATE TABLE `plugin_settings` (
  `id` int UNSIGNED NOT NULL,
  `plugin_slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_visible` tinyint(1) DEFAULT '1',
  `category_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `meta_keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `views_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Кількість переглядів для ранжування в пошуку',
  `prom_product_id` bigint UNSIGNED DEFAULT NULL COMMENT 'ID товару на Prom.ua'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `products`
--

INSERT INTO `products` (`id`, `sku`, `is_visible`, `category_id`, `name`, `description`, `image`, `slug`, `meta_title`, `meta_description`, `meta_keywords`, `price`, `created_at`, `updated_at`, `views_count`, `prom_product_id`) VALUES
(1, 'ID-1', 1, 2, 'iPhone 13', 'Крутий смартфон, по дуже низьким цінам! Доступна ціна за круту якість!', '/uploads/products/gallery/original/product_69e6092591658980274602.jpg', 'iphone-13', '', '', NULL, 999.00, '2026-03-30 07:45:12', '2026-05-10 10:57:17', 0, NULL),
(2, 'ID-2', 1, 3, 'Сіомі', 'Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі!', '/uploads/products/gallery/original/product_69e608b6ddae3707094810.webp', 'siomi', 'Сіомі крутий продукт', '', NULL, 2050.00, '2026-04-05 08:11:57', '2026-06-20 17:41:14', 50, NULL);

-- --------------------------------------------------------

--
-- Структура таблиці `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` int NOT NULL,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attribute_option_id` int DEFAULT NULL,
  `price_modifier` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_operation` enum('+','-') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '+',
  `stock_quantity` int DEFAULT NULL,
  `is_selectable` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `product_attributes`
--

INSERT INTO `product_attributes` (`id`, `sku`, `product_id`, `attribute_id`, `value`, `attribute_option_id`, `price_modifier`, `price_operation`, `stock_quantity`, `is_selectable`, `created_at`, `updated_at`) VALUES
(27, NULL, 2, 3, 'Поліестер', 69, 0.00, '+', NULL, 0, '2026-05-10 13:09:14', '2026-05-10 13:09:14'),
(28, 'ID-2-100', 2, 4, 'Китай', 100, 150.00, '+', 5, 1, '2026-05-10 13:09:14', '2026-05-10 13:34:32');

-- --------------------------------------------------------

--
-- Структура таблиці `product_images`
--

CREATE TABLE `product_images` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `sort_order`, `created_at`) VALUES
(6, 2, '/uploads/products/gallery/original/product_69e608b6ddae3707094810.webp', 1, '2026-04-20 11:06:31'),
(7, 1, '/uploads/products/gallery/original/product_69e6092591658980274602.jpg', 1, '2026-04-20 11:08:21'),
(8, 1, '/uploads/products/gallery/original/product_69e6093776cb2485391237.jpg', 2, '2026-04-20 11:08:39');

-- --------------------------------------------------------

--
-- Структура таблиці `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `rating` tinyint DEFAULT NULL,
  `author_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `parent_id`, `rating`, `author_name`, `body`, `is_visible`, `created_at`, `updated_at`) VALUES
(1, 2, 2, NULL, 5, 'Василь Присяжнюк', 'Текст відгуку, Текст відгуку, Текст відгуку, Текст відгуку!\r\nТекст відгуку1', 1, '2026-05-04 17:33:26', '2026-05-04 17:33:26'),
(2, 1, 2, NULL, 5, 'Василь Присяжнюк', 'Схожі товари Схожі товари Схожі товари!', 1, '2026-05-04 17:39:47', '2026-05-04 17:39:47'),
(3, 2, 2, 1, NULL, 'Василь Присяжнюк', 'йййййййййййййййй ййййййййййййййййййййййййй йййййййййй1!!!!!!', 1, '2026-05-04 17:42:09', '2026-05-04 17:42:09');

--
-- Тригери `product_reviews`
--
DELIMITER $$
CREATE TRIGGER `trg_reviews_validate_insert` BEFORE INSERT ON `product_reviews` FOR EACH ROW BEGIN
  IF NEW.parent_id IS NULL THEN
    IF NEW.rating IS NULL OR NEW.rating < 1 OR NEW.rating > 5 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Root review rating must be between 1 and 5';
    END IF;
  ELSE
    IF NEW.rating IS NOT NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reply review must not have rating';
    END IF;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_reviews_validate_update` BEFORE UPDATE ON `product_reviews` FOR EACH ROW BEGIN
  IF NEW.parent_id IS NULL THEN
    IF NEW.rating IS NULL OR NEW.rating < 1 OR NEW.rating > 5 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Root review rating must be between 1 and 5';
    END IF;
  ELSE
    IF NEW.rating IS NOT NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reply review must not have rating';
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблиці `product_stocks`
--

CREATE TABLE `product_stocks` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `option_id` int DEFAULT NULL,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `reserved` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп даних таблиці `product_stocks`
--

INSERT INTO `product_stocks` (`id`, `product_id`, `option_id`, `sku`, `quantity`, `reserved`, `updated_at`) VALUES
(1, 1, NULL, 'ID-1', 4, 0, '2026-05-10 12:19:24'),
(7, 2, 100, 'ID-2-100', 2, 0, '2026-05-11 12:10:15'),
(8, 0, NULL, 'ID-2', 10, 0, '2026-05-16 13:14:11');

-- --------------------------------------------------------

--
-- Структура таблиці `prom_sync_queue`
--

CREATE TABLE `prom_sync_queue` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int NOT NULL COMMENT 'ID товару в нашій БД',
  `action` enum('price','quantity','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `status` enum('pending','processing','done','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` tinyint NOT NULL DEFAULT '0',
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `search_cache`
--

CREATE TABLE `search_cache` (
  `id` int UNSIGNED NOT NULL,
  `query_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA-256 від нормалізованого запиту',
  `query_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Оригінальний запит',
  `results` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON з результатами',
  `hits` int UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Кількість звернень',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `search_cache`
--

INSERT INTO `search_cache` (`id`, `query_hash`, `query_text`, `results`, `hits`, `created_at`, `expires_at`) VALUES
(1, '6425bdfc130e5368454444b1efecdc46a9d9beb1e698a3cb1b95920e685f81bc', 'сіомі', '{\"results\":[{\"id\":2,\"sku\":\"ID-2\",\"is_visible\":1,\"category_id\":3,\"name\":\"Сіомі\",\"description\":\"Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі!\",\"image\":\"\\/uploads\\/products\\/gallery\\/original\\/product_69e608b6ddae3707094810.webp\",\"slug\":\"siomi\",\"meta_title\":\"Сіомі крутий продукт\",\"meta_description\":\"\",\"meta_keywords\":null,\"price\":\"2050.00\",\"created_at\":\"2026-04-05 10:11:57\",\"updated_at\":\"2026-06-20 19:37:18\",\"views_count\":48,\"prom_product_id\":null,\"category_name\":\"Телевізори\",\"stock_qty\":10,\"relevance\":3.5384288351535798}],\"total\":1,\"page\":1,\"pages\":1,\"query\":\"сіомі\",\"tokens\":[\"сіомі\"],\"suggestion\":null,\"strategy\":\"fulltext\",\"from_cache\":false}', 16, '2026-06-20 19:03:01', '2026-06-20 17:51:12'),
(3, 'f43e648ddbdf7b3384f6181760945ab2a111fd84a88c3ff606c24c22d8f9537f', 'сіомі', '{\"results\":[{\"id\":2,\"sku\":\"ID-2\",\"is_visible\":1,\"category_id\":3,\"name\":\"Сіомі\",\"description\":\"Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі!\",\"image\":\"\\/uploads\\/products\\/gallery\\/original\\/product_69e608b6ddae3707094810.webp\",\"slug\":\"siomi\",\"meta_title\":\"Сіомі крутий продукт\",\"meta_description\":\"\",\"meta_keywords\":null,\"price\":\"2050.00\",\"created_at\":\"2026-04-05 10:11:57\",\"updated_at\":\"2026-06-20 19:41:12\",\"views_count\":49,\"prom_product_id\":null,\"category_name\":\"Телевізори\",\"stock_qty\":10,\"relevance\":3.5394288351535796}],\"total\":1,\"page\":1,\"pages\":1,\"query\":\"сіомі\",\"tokens\":[\"сіомі\"],\"suggestion\":null,\"strategy\":\"fulltext\",\"from_cache\":false}', 34, '2026-06-20 19:03:33', '2026-06-20 17:51:14'),
(51, 'cee603f301ba871344dd4e01073284462f13af5d9656b8e75b8ea38580a08c23', 'ас', '{\"results\":[],\"total\":0,\"page\":1,\"pages\":1,\"query\":\"ас\",\"tokens\":[\"ас\"],\"suggestion\":null,\"strategy\":\"fuzzy\",\"from_cache\":false}', 1, '2026-06-20 19:41:37', '2026-06-20 17:51:37'),
(52, '2de405c6892a11f3a5d31e1daa67acf4bef1b6338624b282cbd8d8191cbdc34f', 'асу', '{\"results\":[],\"total\":0,\"page\":1,\"pages\":1,\"query\":\"асу\",\"tokens\":[\"асу\"],\"suggestion\":null,\"strategy\":\"fuzzy\",\"from_cache\":false}', 1, '2026-06-20 19:41:37', '2026-06-20 17:51:37'),
(53, 'dd3f5734e5e28f20f5d75a9274a1af7b393c7404dc4e0e4b2f9995115506f46e', 'асус', '{\"results\":[],\"total\":0,\"page\":1,\"pages\":1,\"query\":\"асус\",\"tokens\":[\"асус\"],\"suggestion\":null,\"strategy\":\"fuzzy\",\"from_cache\":false}', 1, '2026-06-20 19:41:38', '2026-06-20 17:51:38'),
(54, '0b5ec06d8d2a36d4465e462182cd196b59448c03d42effba3802f541176b988b', 'асус', '{\"results\":[],\"total\":0,\"page\":1,\"pages\":1,\"query\":\"асус\",\"tokens\":[\"асус\"],\"suggestion\":null,\"strategy\":\"fuzzy\",\"from_cache\":false}', 1, '2026-06-20 19:41:40', '2026-06-20 17:51:40');

-- --------------------------------------------------------

--
-- Структура таблиці `search_queries`
--

CREATE TABLE `search_queries` (
  `id` int UNSIGNED NOT NULL,
  `query` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `results_count` int UNSIGNED NOT NULL DEFAULT '0',
  `search_count` int UNSIGNED NOT NULL DEFAULT '1',
  `last_searched` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `search_queries`
--

INSERT INTO `search_queries` (`id`, `query`, `results_count`, `search_count`, `last_searched`) VALUES
(1, 'сіомі', 1, 50, '2026-06-20 19:41:14'),
(51, 'ас', 0, 1, '2026-06-20 19:41:37'),
(52, 'асу', 0, 1, '2026-06-20 19:41:37'),
(53, 'асус', 0, 2, '2026-06-20 19:41:40');

-- --------------------------------------------------------

--
-- Структура таблиці `seo_settings`
--

CREATE TABLE `seo_settings` (
  `id` int NOT NULL,
  `entity_type` enum('product','category','page') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `og_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canonical_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `robots_meta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `seo_settings`
--

INSERT INTO `seo_settings` (`id`, `entity_type`, `entity_id`, `title`, `description`, `keywords`, `og_title`, `og_description`, `og_image`, `canonical_url`, `robots_meta`, `created_at`, `updated_at`) VALUES
(1, 'category', 1, 'Купити Смартфони', 'Великий вибір смартфонів!', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 07:47:51', '2026-04-27 15:48:21'),
(2, 'category', 2, 'Смартфони iPhone!', 'Смартфони iPhone! Смартфони iPhone!', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 15:31:02', '2026-04-04 15:31:02'),
(3, 'category', 3, 'Телевізори Телевізори Телевізори Телевізори!', 'Телевізори Телевізори Телевізори Телевізори!', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 21:23:11', '2026-04-04 21:23:11'),
(4, 'product', 2, 'Сіомі крутий продукт', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-27 15:59:34', '2026-04-27 15:59:34');

-- --------------------------------------------------------

--
-- Структура таблиці `settings`
--

CREATE TABLE `settings` (
  `key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `group` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `settings`
--

INSERT INTO `settings` (`key`, `value`, `group`, `type`, `created_at`, `updated_at`) VALUES
('active_logotype', '/uploads/logotypes/logotype_69ef99d44d2d0241246888.jpg', 'general', 'text', '2026-04-27 16:44:28', '2026-04-27 17:16:05'),
('active_theme', 'modern', 'appearance', 'select', '2026-04-03 08:17:13', '2026-05-09 12:54:14'),
('contact_email', 'admin@mysite.test', 'contact', 'text', '2026-04-03 08:17:13', '2026-06-14 14:55:15'),
('contact_phone', '+380 00 000 00 00', 'contact', 'text', '2026-04-03 08:17:13', '2026-06-14 14:55:15'),
('currency_source', 'manual', 'currency', 'select', '2026-06-05 10:00:00', '2026-06-05 10:00:00'),
('date_format', 'd.m.Y H:i', 'general', 'text', '2026-06-13 18:37:31', '2026-06-14 14:55:14'),
('default_currency', 'UAH', 'localization', 'select', '2026-04-03 08:17:13', '2026-05-09 12:54:14'),
('default_language', 'ua', 'localization', 'select', '2026-04-03 08:17:13', '2026-06-14 14:55:13'),
('email', 'admin@localhost.local', 'general', 'text', '2026-04-12 16:35:40', '2026-06-13 18:37:31'),
('facebook_auth_enabled', '0', 'social_auth', 'checkbox', '2026-05-09 12:03:51', '2026-06-14 14:55:15'),
('facebook_client_id', '', 'social_auth', 'text', '2026-05-09 12:01:55', '2026-06-14 14:55:15'),
('facebook_client_secret', '', 'social_auth', 'text', '2026-05-09 12:01:55', '2026-06-14 14:55:16'),
('facebook_redirect_url', 'https://mysite.test', 'social_auth', 'text', '2026-05-09 12:02:18', '2026-05-09 12:54:15'),
('force_https', '0', 'general', 'checkbox', '2026-05-01 17:21:57', '2026-05-01 17:21:57'),
('google_auth_enabled', '1', 'social_auth', 'checkbox', '2026-05-09 12:03:51', '2026-06-14 14:55:15'),
('google_client_id', '', 'social_auth', 'text', '2026-05-09 12:01:55', '2026-06-14 14:55:15'),
('google_client_secret', '', 'social_auth', 'text', '2026-05-09 12:01:55', '2026-06-14 14:55:15'),
('google_redirect_url', 'https://mysite.test', 'social_auth', 'text', '2026-05-09 12:02:18', '2026-05-09 12:54:15'),
('maintenance_message', 'Вибачте, магазин тимчасово закритий на технічне обслуговування.', 'general', 'textarea', '2026-04-03 08:17:13', '2026-06-14 14:55:13'),
('media_apply_watermark', '0', 'media', 'checkbox', '2026-04-13 09:12:17', '2026-06-14 14:55:16'),
('media_auto_webp', '0', 'media', 'checkbox', '2026-04-13 09:12:17', '2026-06-14 14:55:16'),
('media_watermark_position', 'bottom-right', 'media', 'select', '2026-04-13 09:12:17', '2026-06-14 14:55:16'),
('phone_mask', '+38 (###) ###-##-##', 'contact', 'text', '2026-05-16 09:39:11', '2026-06-14 14:55:13'),
('prom_api_key', '', 'prom', 'text', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('prom_enabled', '0', 'prom', 'checkbox', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('prom_last_sync', '', 'prom', 'text', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('prom_sync_method', 'xml', 'prom', 'select', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('prom_webhook_secret', '', 'prom', 'text', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('seo_desc_template', 'Пропонуємо {name} за найкращою ціною {price} грн. Категорія: {category}. Доставка по Україні!', 'seo', 'textarea', '2026-04-13 08:34:14', '2026-06-14 14:55:15'),
('seo_title_template', '{name} купити за {price} грн у магазині MyStore', 'seo', 'text', '2026-04-13 08:34:14', '2026-06-14 14:55:15'),
('site_description', 'Найкращий інтернет-магазин на PHP', 'general', 'textarea', '2026-04-03 08:17:13', '2026-06-14 14:55:13'),
('site_name', 'MySite', 'general', 'text', '2026-04-03 08:17:13', '2026-06-14 14:55:13'),
('site_timezone', 'Europe/Kiev', 'general', 'text', '2026-04-12 16:52:37', '2026-06-14 14:55:13'),
('smtp_encryption', '', 'general', 'text', '2026-06-14 14:55:14', '2026-06-14 14:55:14'),
('smtp_from_email', 'no-reply@mysite.test', 'general', 'text', '2026-06-14 14:55:14', '2026-06-14 14:55:14'),
('smtp_from_name', 'MySite Store', 'general', 'text', '2026-06-14 14:55:14', '2026-06-14 14:55:14'),
('smtp_host', '127.0.0.1', 'general', 'text', '2026-06-14 14:55:14', '2026-06-14 14:55:14'),
('smtp_pass', '', 'general', 'text', '2026-04-12 16:47:28', '2026-06-14 14:55:14'),
('smtp_port', '25', 'general', 'text', '2026-04-12 16:42:48', '2026-06-14 14:55:14'),
('smtp_username', '', 'general', 'text', '2026-06-14 14:55:14', '2026-06-14 14:55:14'),
('smtr', '127.0.0.1', 'general', 'text', '2026-04-12 16:28:35', '2026-06-13 18:37:31'),
('store_status', 'open', 'general', 'select', '2026-04-03 08:17:13', '2026-06-14 14:55:13'),
('upload_max_filesize', '10M', 'general', 'text', '2026-05-16 09:39:11', '2026-05-16 09:39:11');

-- --------------------------------------------------------

--
-- Структура таблиці `shop_methods`
--

CREATE TABLE `shop_methods` (
  `id` int NOT NULL,
  `type` enum('shipping','payment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '0',
  `is_test_mode` tinyint(1) DEFAULT '1',
  `settings` json DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `shop_methods`
--

INSERT INTO `shop_methods` (`id`, `type`, `code`, `name`, `icon`, `description`, `is_active`, `is_test_mode`, `settings`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'shipping', 'nova_poshta', 'Нова Пошта', NULL, '', 1, 0, '{\"cost\": \"70\", \"api_key\": \"\"}', 0, '2026-04-20 16:09:35', '2026-04-22 14:13:57'),
(2, 'shipping', 'self_pickup', 'Самовивіз', NULL, '', 1, 0, '{\"address\": \"м. Київ, вул. Центральна, 1\"}', 0, '2026-04-20 16:09:35', '2026-04-22 14:13:57'),
(3, 'payment', 'cash', 'Оплата при отриманні', NULL, '', 1, 0, NULL, 0, '2026-04-20 16:09:35', '2026-04-22 13:51:29'),
(4, 'payment', 'liqpay', 'Онлайн-оплата (LiqPay)', NULL, '', 0, 1, '{\"public_key\": \"admin@mysite.test\", \"private_key\": \"password123\"}', 0, '2026-04-20 16:09:35', '2026-04-22 13:51:29');

-- --------------------------------------------------------

--
-- Структура таблиці `slug_history`
--

CREATE TABLE `slug_history` (
  `id` int NOT NULL,
  `entity_type` enum('product','category','page') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `old_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` int DEFAULT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `stock_documents`
--

CREATE TABLE `stock_documents` (
  `id` int NOT NULL,
  `doc_type` varchar(32) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `url_redirects`
--

CREATE TABLE `url_redirects` (
  `id` int NOT NULL,
  `old_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` enum('product','category','page') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int DEFAULT NULL,
  `status_code` int DEFAULT '301',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` int DEFAULT '3',
  `is_active` tinyint(1) DEFAULT '1',
  `email_verified` tinyint(1) DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `avatar`, `role_id`, `is_active`, `email_verified`, `email_verified_at`, `last_login`, `password_reset_token`, `password_reset_expires`, `remember_token`, `created_at`, `updated_at`) VALUES
(2, 'systemmaster@meta.ua', '$2y$12$knhVn0wIOYbnqx3TRccf1OrGmEGu3JWSZsbLQ/c9tvLrmZElAaU86', 'Василь', 'Присяжнюк', NULL, NULL, 1, 1, 0, NULL, '2026-06-21 16:39:57', NULL, NULL, 'e9ff8eb8230832c8158a28b53ee08c88431dd7b170740ff4be3ae449b5ba882e', '2026-03-31 09:57:24', '2026-06-21 16:39:57');

-- --------------------------------------------------------

--
-- Структура таблиці `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `user_roles`
--

INSERT INTO `user_roles` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Адміністратор', 'admin', 'Повний доступ до всіх функцій системи', '2026-03-26 09:29:43'),
(2, 'Модератор', 'moderator', 'Доступ до модерування контенту та управління замовленнями', '2026-03-26 09:29:43'),
(3, 'Покупець', 'customer', 'Звичайний користувач з правами покупця', '2026-03-26 09:29:43');

--
-- Індекси збережених таблиць
--

--
-- Індекси таблиці `attributes`
--
ALTER TABLE `attributes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_filterable` (`is_filterable`);

--
-- Індекси таблиці `attribute_options`
--
ALTER TABLE `attribute_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_option` (`attribute_id`,`value`),
  ADD KEY `idx_attribute_id` (`attribute_id`);

--
-- Індекси таблиці `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Індекси таблиці `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_category_parent` (`parent_id`),
  ADD KEY `idx_categories_path` (`path`(255));

--
-- Індекси таблиці `category_attributes`
--
ALTER TABLE `category_attributes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_category_attribute` (`category_id`,`attribute_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_attribute_id` (`attribute_id`);

--
-- Індекси таблиці `category_filters`
--
ALTER TABLE `category_filters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_category_filter` (`category_id`,`attribute_id`),
  ADD KEY `attribute_id` (`attribute_id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Індекси таблиці `crm_user_action_audit`
--
ALTER TABLE `crm_user_action_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crm_audit_user_created` (`user_id`,`created_at`),
  ADD KEY `fk_crm_audit_admin` (`admin_id`);

--
-- Індекси таблиці `crm_user_activity_logs`
--
ALTER TABLE `crm_user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crm_activity_user_created` (`user_id`,`created_at`);

--
-- Індекси таблиці `crm_user_bonus`
--
ALTER TABLE `crm_user_bonus`
  ADD PRIMARY KEY (`user_id`);

--
-- Індекси таблиці `crm_user_subscriptions`
--
ALTER TABLE `crm_user_subscriptions`
  ADD PRIMARY KEY (`user_id`);

--
-- Індекси таблиці `cron_tasks`
--
ALTER TABLE `cron_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_next_run` (`status`,`next_run`);

--
-- Індекси таблиці `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_code` (`code`);

--
-- Індекси таблиці `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`product_id`),
  ADD KEY `fk_favorites_product` (`product_id`);

--
-- Індекси таблиці `filter_history`
--
ALTER TABLE `filter_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `user_id` (`user_id`);

--
-- Індекси таблиці `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_log_sku` (`sku`);

--
-- Індекси таблиці `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_created` (`ip`,`created_at`),
  ADD KEY `idx_email_created` (`email`,`created_at`);

--
-- Індекси таблиці `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_time` (`login_time`);

--
-- Індекси таблиці `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_migration` (`migration`);

--
-- Індекси таблиці `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_created_at` (`created_at`),
  ADD KEY `idx_orders_status_ttn` (`status`,`ttn_code`),
  ADD KEY `idx_prom_order_id` (`prom_order_id`);

--
-- Індекси таблиці `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Індекси таблиці `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_status_history_order_id` (`order_id`);

--
-- Індекси таблиці `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Індекси таблиці `plugins`
--
ALTER TABLE `plugins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_plugins_active` (`is_active`);

--
-- Індекси таблиці `plugin_settings`
--
ALTER TABLE `plugin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_plugin_key` (`plugin_slug`,`key`);

--
-- Індекси таблиці `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_products_sku` (`sku`),
  ADD UNIQUE KEY `idx_slug` (`slug`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_price` (`price`),
  ADD KEY `idx_products_visible` (`is_visible`),
  ADD KEY `idx_prom_product_id` (`prom_product_id`),
  ADD KEY `idx_products_views` (`views_count`);
ALTER TABLE `products` ADD FULLTEXT KEY `ft_products_name` (`name`);
ALTER TABLE `products` ADD FULLTEXT KEY `ft_products_description` (`description`);
ALTER TABLE `products` ADD FULLTEXT KEY `ft_products_combined` (`name`,`description`,`sku`);

--
-- Індекси таблиці `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_attributes_sku` (`sku`),
  ADD KEY `attribute_option_id` (`attribute_option_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_attribute_id` (`attribute_id`),
  ADD KEY `idx_value` (`value`),
  ADD KEY `idx_product_attr_product` (`product_id`),
  ADD KEY `idx_product_attr_value` (`value`);

--
-- Індекси таблиці `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_images_product_id` (`product_id`),
  ADD KEY `idx_product_images_sort_order` (`sort_order`);

--
-- Індекси таблиці `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reviews_product_parent_created` (`product_id`,`parent_id`,`created_at`),
  ADD KEY `idx_reviews_user` (`user_id`),
  ADD KEY `fk_reviews_parent` (`parent_id`);

--
-- Індекси таблиці `product_stocks`
--
ALTER TABLE `product_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_product_stocks_sku` (`sku`),
  ADD KEY `idx_stock_product` (`product_id`),
  ADD KEY `idx_stock_option` (`option_id`);

--
-- Індекси таблиці `prom_sync_queue`
--
ALTER TABLE `prom_sync_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Індекси таблиці `search_cache`
--
ALTER TABLE `search_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_query_hash` (`query_hash`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Індекси таблиці `search_queries`
--
ALTER TABLE `search_queries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_query` (`query`(191)),
  ADD KEY `idx_search_count` (`search_count` DESC);

--
-- Індекси таблиці `seo_settings`
--
ALTER TABLE `seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seo` (`entity_type`,`entity_id`),
  ADD KEY `idx_entity_type` (`entity_type`);

--
-- Індекси таблиці `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Індекси таблиці `shop_methods`
--
ALTER TABLE `shop_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Індекси таблиці `slug_history`
--
ALTER TABLE `slug_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity_type_id` (`entity_type`,`entity_id`),
  ADD KEY `idx_old_slug` (`old_slug`),
  ADD KEY `idx_new_slug` (`new_slug`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Індекси таблиці `stock_documents`
--
ALTER TABLE `stock_documents`
  ADD PRIMARY KEY (`id`);

--
-- Індекси таблиці `url_redirects`
--
ALTER TABLE `url_redirects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_redirect` (`old_slug`,`entity_type`),
  ADD KEY `idx_old_slug` (`old_slug`),
  ADD KEY `idx_new_slug` (`new_slug`),
  ADD KEY `idx_entity_type` (`entity_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Індекси таблиці `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role_id` (`role_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Індекси таблиці `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- AUTO_INCREMENT для збережених таблиць
--

--
-- AUTO_INCREMENT для таблиці `attributes`
--
ALTER TABLE `attributes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблиці `attribute_options`
--
ALTER TABLE `attribute_options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT для таблиці `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT для таблиці `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `category_attributes`
--
ALTER TABLE `category_attributes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблиці `category_filters`
--
ALTER TABLE `category_filters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `crm_user_action_audit`
--
ALTER TABLE `crm_user_action_audit`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `crm_user_activity_logs`
--
ALTER TABLE `crm_user_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=306;

--
-- AUTO_INCREMENT для таблиці `cron_tasks`
--
ALTER TABLE `cron_tasks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `filter_history`
--
ALTER TABLE `filter_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблиці `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблиці `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `plugins`
--
ALTER TABLE `plugins`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `plugin_settings`
--
ALTER TABLE `plugin_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT для таблиці `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблиці `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `product_stocks`
--
ALTER TABLE `product_stocks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблиці `prom_sync_queue`
--
ALTER TABLE `prom_sync_queue`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `search_cache`
--
ALTER TABLE `search_cache`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT для таблиці `search_queries`
--
ALTER TABLE `search_queries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT для таблиці `seo_settings`
--
ALTER TABLE `seo_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблиці `shop_methods`
--
ALTER TABLE `shop_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблиці `slug_history`
--
ALTER TABLE `slug_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `stock_documents`
--
ALTER TABLE `stock_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `url_redirects`
--
ALTER TABLE `url_redirects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Обмеження зовнішнього ключа збережених таблиць
--

--
-- Обмеження зовнішнього ключа таблиці `attribute_options`
--
ALTER TABLE `attribute_options`
  ADD CONSTRAINT `attribute_options_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Обмеження зовнішнього ключа таблиці `category_attributes`
--
ALTER TABLE `category_attributes`
  ADD CONSTRAINT `category_attributes_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `category_attributes_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `category_filters`
--
ALTER TABLE `category_filters`
  ADD CONSTRAINT `category_filters_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `category_filters_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `crm_user_action_audit`
--
ALTER TABLE `crm_user_action_audit`
  ADD CONSTRAINT `fk_crm_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_crm_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `crm_user_activity_logs`
--
ALTER TABLE `crm_user_activity_logs`
  ADD CONSTRAINT `fk_crm_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `crm_user_bonus`
--
ALTER TABLE `crm_user_bonus`
  ADD CONSTRAINT `fk_crm_bonus_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `crm_user_subscriptions`
--
ALTER TABLE `crm_user_subscriptions`
  ADD CONSTRAINT `fk_crm_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_favorites_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `filter_history`
--
ALTER TABLE `filter_history`
  ADD CONSTRAINT `filter_history_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `filter_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Обмеження зовнішнього ключа таблиці `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Обмеження зовнішнього ключа таблиці `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attributes_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attributes_ibfk_3` FOREIGN KEY (`attribute_option_id`) REFERENCES `attribute_options` (`id`) ON DELETE SET NULL;

--
-- Обмеження зовнішнього ключа таблиці `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_reviews_parent` FOREIGN KEY (`parent_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `slug_history`
--
ALTER TABLE `slug_history`
  ADD CONSTRAINT `slug_history_ibfk_1` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Обмеження зовнішнього ключа таблиці `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
