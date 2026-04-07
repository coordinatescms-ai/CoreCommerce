-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0:3306
-- Час створення: Квт 07 2026 р., 21:42
-- Версія сервера: 8.0.44
-- Версія PHP: 8.3.29

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
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('text','select','multiselect','color','range') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `description` text COLLATE utf8mb4_unicode_ci,
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
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_code` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `attribute_options`
--

INSERT INTO `attribute_options` (`id`, `attribute_id`, `name`, `value`, `color_code`, `sort_order`, `created_at`) VALUES
(1, 1, 'Чорний', 'black', '#000000', 1, '2026-03-29 16:28:20'),
(2, 1, 'Білий', 'white', '#FFFFFF', 2, '2026-03-29 16:28:20'),
(3, 1, 'Червоний', 'red', '#FF0000', 3, '2026-03-29 16:28:20'),
(4, 1, 'Синій', 'blue', '#0000FF', 4, '2026-03-29 16:28:20'),
(5, 2, 'XS', 'xs', NULL, 1, '2026-03-29 16:28:20'),
(6, 2, 'S', 's', NULL, 2, '2026-03-29 16:28:20'),
(7, 2, 'M', 'm', NULL, 3, '2026-03-29 16:28:20'),
(8, 2, 'L', 'l', NULL, 4, '2026-03-29 16:28:20'),
(9, 2, 'XL', 'xl', NULL, 5, '2026-03-29 16:28:20'),
(10, 2, 'XXL', 'xxl', NULL, 6, '2026-03-29 16:28:20'),
(11, 3, 'Бавовна', 'cotton', NULL, 1, '2026-03-29 16:28:20'),
(12, 3, 'Поліестер', 'polyester', NULL, 2, '2026-03-29 16:28:20'),
(13, 3, 'Шовк', 'silk', NULL, 3, '2026-03-29 16:28:20'),
(14, 3, 'Вовна', 'wool', NULL, 4, '2026-03-29 16:28:20'),
(31, 4, 'Китай', 'Китай', NULL, 1, '2026-04-05 15:56:41');

-- --------------------------------------------------------

--
-- Структура таблиці `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int DEFAULT '0',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `slug`, `description`, `sort_order`, `image`, `is_active`, `meta_title`, `meta_description`, `meta_keywords`, `created_at`, `updated_at`) VALUES
(1, 'Смартфони', NULL, 'smartfoni', 'Категорія смартфони', 0, NULL, 1, 'Купити Смартфони', 'Великий вибір смартфонів!', NULL, '2026-04-04 07:47:51', '2026-04-04 07:47:51'),
(2, 'iPhone', 1, 'iphone', 'Смартфони iPhone! Смартфони iPhone! Смартфони iPhone! Смартфони iPhone! Смартфони iPhone! Смартфони iPhone!', 0, NULL, 1, 'Смартфони iPhone!', 'Смартфони iPhone! Смартфони iPhone!', NULL, '2026-04-04 15:31:02', '2026-04-04 15:31:02'),
(3, 'Телевізори', NULL, 'televzori', 'Телевізори Телевізори Телевізори Телевізори! Телевізори Телевізори Телевізори Телевізори! Телевізори Телевізори Телевізори Телевізори!', 0, NULL, 1, 'Телевізори Телевізори Телевізори Телевізори!', 'Телевізори Телевізори Телевізори Телевізори!', NULL, '2026-04-04 21:23:11', '2026-04-04 21:23:11');

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
(1, 3, 4, 0, 0, '2026-04-05 15:56:41');

-- --------------------------------------------------------

--
-- Структура таблиці `category_filters`
--

CREATE TABLE `category_filters` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `filter_type` enum('checkbox','range','color') COLLATE utf8mb4_unicode_ci DEFAULT 'checkbox',
  `min_value` decimal(10,2) DEFAULT NULL,
  `max_value` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `filter_history`
--

CREATE TABLE `filter_history` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_general_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `image`, `slug`, `meta_title`, `meta_description`, `meta_keywords`, `price`, `created_at`, `updated_at`) VALUES
(1, 2, 'iPhone 13', 'Крутий смартфон, по дуже низьким цінам! Доступна ціна за круту якість!', NULL, 'iphone-13', '', '', NULL, 999.00, '2026-03-30 07:45:12', '2026-04-04 17:05:24'),
(2, 3, 'Сіомі', 'Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі! Сіомі Сіомі Сіомі Сіомі Сіомі!', NULL, 'siomi', '', '', NULL, 2050.00, '2026-04-05 08:11:57', '2026-04-05 11:31:15');

-- --------------------------------------------------------

--
-- Структура таблиці `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attribute_option_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `product_attributes`
--

INSERT INTO `product_attributes` (`id`, `product_id`, `attribute_id`, `value`, `attribute_option_id`, `created_at`, `updated_at`) VALUES
(2, 2, 4, 'Китай', NULL, '2026-04-05 11:31:15', '2026-04-05 11:31:15');

-- --------------------------------------------------------

--
-- Структура таблиці `product_attribute_values`
--

CREATE TABLE `product_attribute_values` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `value_text` text COLLATE utf8mb4_general_ci,
  `is_selectable` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп даних таблиці `product_attribute_values`
--

INSERT INTO `product_attribute_values` (`id`, `product_id`, `attribute_id`, `value_text`, `is_selectable`) VALUES
(1, 2, 4, 'Китай', 1);

-- --------------------------------------------------------

--
-- Структура таблиці `seo_settings`
--

CREATE TABLE `seo_settings` (
  `id` int NOT NULL,
  `entity_type` enum('product','category','page') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_description` text COLLATE utf8mb4_unicode_ci,
  `og_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canonical_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `robots_meta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `seo_settings`
--

INSERT INTO `seo_settings` (`id`, `entity_type`, `entity_id`, `title`, `description`, `keywords`, `og_title`, `og_description`, `og_image`, `canonical_url`, `robots_meta`, `created_at`, `updated_at`) VALUES
(1, 'category', 1, 'Купити Смартфони', 'Великий вибір смартфонів!', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 07:47:51', '2026-04-04 07:47:51'),
(2, 'category', 2, 'Смартфони iPhone!', 'Смартфони iPhone! Смартфони iPhone!', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 15:31:02', '2026-04-04 15:31:02'),
(3, 'category', 3, 'Телевізори Телевізори Телевізори Телевізори!', 'Телевізори Телевізори Телевізори Телевізори!', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 21:23:11', '2026-04-04 21:23:11');

-- --------------------------------------------------------

--
-- Структура таблиці `settings`
--

CREATE TABLE `settings` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `settings`
--

INSERT INTO `settings` (`key`, `value`, `group`, `type`, `created_at`, `updated_at`) VALUES
('active_theme', 'modern', 'appearance', 'select', '2026-04-03 08:17:13', '2026-04-03 08:17:13'),
('contact_email', 'admin@mysite.test', 'contact', 'text', '2026-04-03 08:17:13', '2026-04-03 08:17:13'),
('contact_phone', '+380 00 000 00 00', 'contact', 'text', '2026-04-03 08:17:13', '2026-04-03 08:17:13'),
('default_currency', 'UAH', 'localization', 'select', '2026-04-03 08:17:13', '2026-04-03 08:17:13'),
('default_language', 'ua', 'localization', 'select', '2026-04-03 08:17:13', '2026-04-03 08:17:13'),
('maintenance_message', 'Вибачте, магазин тимчасово закритий на технічне обслуговування.', 'general', 'textarea', '2026-04-03 08:17:13', '2026-04-03 08:17:13'),
('site_description', 'Найкращий інтернет-магазин на PHP', 'general', 'textarea', '2026-04-03 08:17:13', '2026-04-03 08:17:13'),
('site_name', 'MySite Store', 'general', 'text', '2026-04-03 08:17:13', '2026-04-03 08:17:13'),
('store_status', 'open', 'general', 'select', '2026-04-03 08:17:13', '2026-04-03 08:17:13');

-- --------------------------------------------------------

--
-- Структура таблиці `slug_history`
--

CREATE TABLE `slug_history` (
  `id` int NOT NULL,
  `entity_type` enum('product','category','page') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `old_slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` int DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `url_redirects`
--

CREATE TABLE `url_redirects` (
  `id` int NOT NULL,
  `old_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` enum('product','category','page') COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` int DEFAULT '3',
  `is_active` tinyint(1) DEFAULT '1',
  `email_verified` tinyint(1) DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `avatar`, `role_id`, `is_active`, `email_verified`, `email_verified_at`, `last_login`, `password_reset_token`, `password_reset_expires`, `remember_token`, `created_at`, `updated_at`) VALUES
(2, 'systemmaster@meta.ua', '$2y$12$knhVn0wIOYbnqx3TRccf1OrGmEGu3JWSZsbLQ/c9tvLrmZElAaU86', 'Василь', 'Присяжнюк', NULL, NULL, 1, 1, 0, NULL, '2026-04-07 19:27:21', NULL, NULL, '88c765047366ed96657e639c1ea40360ea8115e428c0ed372422f91263aa6e2b', '2026-03-31 09:57:24', '2026-04-07 19:27:21');

-- --------------------------------------------------------

--
-- Структура таблиці `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
-- Індекси таблиці `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_category_parent` (`parent_id`);

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
-- Індекси таблиці `filter_history`
--
ALTER TABLE `filter_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `user_id` (`user_id`);

--
-- Індекси таблиці `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_time` (`login_time`);

--
-- Індекси таблиці `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Індекси таблиці `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Індекси таблиці `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_price` (`price`);

--
-- Індекси таблиці `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attribute_option_id` (`attribute_option_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_attribute_id` (`attribute_id`),
  ADD KEY `idx_value` (`value`),
  ADD KEY `idx_product_attr_product` (`product_id`),
  ADD KEY `idx_product_attr_value` (`value`);

--
-- Індекси таблиці `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD PRIMARY KEY (`id`);

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
-- Індекси таблиці `slug_history`
--
ALTER TABLE `slug_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity_type_id` (`entity_type`,`entity_id`),
  ADD KEY `idx_old_slug` (`old_slug`),
  ADD KEY `idx_new_slug` (`new_slug`),
  ADD KEY `changed_by` (`changed_by`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT для таблиці `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `category_attributes`
--
ALTER TABLE `category_attributes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблиці `category_filters`
--
ALTER TABLE `category_filters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `filter_history`
--
ALTER TABLE `filter_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблиці `seo_settings`
--
ALTER TABLE `seo_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `slug_history`
--
ALTER TABLE `slug_history`
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
