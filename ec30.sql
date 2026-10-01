-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0:3306
-- Час створення: Вер 25 2026 р., 09:59
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
(1, 'Color', 'color', 'select', 'Product color', 1, 1, 1, '2026-03-29 16:28:20', '2026-08-26 08:13:23'),
(2, 'Size', 'size', 'select', 'Product size', 1, 1, 2, '2026-03-29 16:28:20', '2026-08-26 08:14:36'),
(3, 'Material', 'material', 'select', 'Product material', 1, 1, 3, '2026-03-29 16:28:20', '2026-08-26 08:15:25'),
(4, 'Brand', 'brand', 'select', 'Product manufacturer', 1, 1, 4, '2026-03-29 16:28:20', '2026-08-26 08:16:47'),
(5, 'Guarantee', 'warranty', 'text', 'Warranty period', 0, 1, 5, '2026-03-29 16:28:20', '2026-08-26 08:18:06');

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
(94, 2, 'XS', 'XS', NULL, 1, '2026-05-10 11:49:51'),
(95, 2, 'S', 'S', NULL, 2, '2026-05-10 11:49:51'),
(96, 2, 'M', 'M', NULL, 3, '2026-05-10 11:49:51'),
(97, 2, 'L', 'L', NULL, 4, '2026-05-10 11:49:51'),
(98, 2, 'XL', 'XL', NULL, 5, '2026-05-10 11:49:51'),
(99, 2, 'XXL', 'XXL', NULL, 6, '2026-05-10 11:49:51'),
(102, 1, 'Black', 'Black', NULL, 1, '2026-08-26 08:44:14'),
(103, 1, 'White', 'White', NULL, 2, '2026-08-26 08:44:14'),
(104, 1, 'Red', 'Red', NULL, 3, '2026-08-26 08:44:15'),
(105, 1, 'Blue', 'Blue', NULL, 4, '2026-08-26 08:44:15'),
(106, 4, 'Redmi', 'Redmi', NULL, 1, '2026-08-26 08:47:55'),
(107, 4, 'Xiaomi', 'Xiaomi', NULL, 2, '2026-08-26 08:47:55'),
(108, 3, 'Plastics', 'Plastics', NULL, 1, '2026-08-26 08:52:26');

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
(1, 'Smartphones', NULL, 'smartfoni', '/smartfoni', 'Smartphone category', 0, '/uploads/categories/category_106c7944bb3e5632.jpg', 1, 'Buy Smartphones', 'A wide selection of smartphones!', NULL, '2026-04-04 07:47:51', '2026-09-23 19:27:48'),
(2, 'iPhone', 1, 'iphone', '/smartfoni/iphone', 'iPhone! iPhone! iPhone! iPhone! iPhone! iPhone!', 0, NULL, 1, 'iPhone!', 'iPhone! iPhone!', NULL, '2026-04-04 15:31:02', '2026-08-25 10:39:43'),
(3, 'Televisions', NULL, 'televzori', '/televzori', 'The most modern televisions.', 0, '/uploads/categories/category_088f70fc1d096947.png', 1, 'The most modern televisions.', 'televisions televisions televisions televisions!', NULL, '2026-04-04 21:23:11', '2026-09-23 19:26:56'),
(4, 'Refurbished smartphones', 1, 'vdnovlen-smartfoni', '/smartfoni/vdnovlen-smartfoni', 'Used smartphones', 0, NULL, 1, '', '', NULL, '2026-07-04 11:38:04', '2026-08-25 10:41:10'),
(5, 'Headphone', NULL, 'navushniki', '/navushniki', '', 0, '/uploads/categories/category_11e098608661ab5a.jpg', 1, '', '', NULL, '2026-07-07 08:28:14', '2026-09-23 19:28:32'),
(6, 'Headphone accessories', 5, 'aksesuari-dlya-navushnikv', '/navushniki/aksesuari-dlya-navushnikv', '', 0, NULL, 1, '', '', NULL, '2026-07-07 08:28:53', '2026-08-25 10:38:27');

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
(24, 3, 4, 0, 0, '2026-08-26 08:47:55'),
(25, 3, 3, 0, 0, '2026-08-26 08:52:26');

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
-- Структура таблиці `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `sender_type` enum('visitor','operator','system') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_operator_id` int DEFAULT NULL COMMENT 'Заповнено лише коли sender_type=operator',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read_by_operator` tinyint(1) NOT NULL DEFAULT '0',
  `is_read_by_visitor` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `chat_operators`
--

CREATE TABLE `chat_operators` (
  `id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'Штатний користувач ядра (admin/moderator), якого призначили оператором чату',
  `status` enum('online','away','offline') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'offline',
  `max_concurrent_chats` int NOT NULL DEFAULT '5' COMMENT 'Ліміт одночасних активних чатів для автопризначення',
  `last_heartbeat_at` datetime DEFAULT NULL COMMENT 'Останній "я ще тут" від панелі оператора, протухає -> status=away автоматично',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int NOT NULL,
  `visitor_id` int NOT NULL,
  `operator_id` int DEFAULT NULL COMMENT 'NULL, доки чат ще в черзі й не прийнятий жодним оператором',
  `channel_token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Секрет автентифікації віджета для ЦІЄЇ сесії — єдина межа ізоляції даних',
  `status` enum('queued','active','offline_form','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `source_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Сторінка, з якої відвідувач почав чат',
  `source_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` datetime NOT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `last_message_preview` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Денормалізовано для швидкого рендеру списку черги без JOIN на останнє повідомлення',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `chat_sessions`
--

-- Chat sessions data removed for security

-- --------------------------------------------------------

--
-- Структура таблиці `chat_visitors`
--

CREATE TABLE `chat_visitors` (
  `id` int NOT NULL,
  `visitor_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Генерується в браузері (crypto.randomUUID), зберігається в localStorage+cookie',
  `user_id` int DEFAULT NULL COMMENT 'Якщо відвідувач був залогінений на сайті в момент чату',
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_seen_at` datetime NOT NULL,
  `last_seen_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `chat_visitors`
--

-- Chat visitors data removed for security

-- --------------------------------------------------------

--
-- Структура таблиці `compare_list`
--

CREATE TABLE `compare_list` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
(2, 'USD', '$', 1.0000, 0),
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

-- Inventory log data removed for security

-- --------------------------------------------------------

--
-- Структура таблиці `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int UNSIGNED NOT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IPv4 або IPv6',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `login_attempts`
--

-- Login attempts data removed for security

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
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
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
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `prom_order_id` bigint UNSIGNED DEFAULT NULL COMMENT 'ID замовлення на Prom.ua',
  `prom_source` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = прийшло з Prom webhook'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'About Us', 'pro-nas', '<b>About Us&nbsp;&nbsp;About Us&nbsp;</b>About Us&nbsp;About Us&nbsp;About Us&nbsp;About Us&nbsp;About Us&nbsp;About Us&nbsp;About Us<div><div><ul><li>About Us</li><li>About Us&nbsp;About Us</li></ul>About Us&nbsp;About Us&nbsp;About Us About Us</div><div><br></div><div><img src=\"/uploads/pages/img_69ef222b92b294.15759830.jpg\" style=\"max-width: 100%; height: auto; display: block; margin: 10px 0px;\"></div>                                                                                                </div>                                    ', 'About Us', 'About Us About Us About Us About Us About Us About Us About Us About Us About Us About Us About Us!', 1, 0, '2026-04-26 14:12:53', '2026-08-26 08:20:54'),
(3, 'Delivery', 'dostavka', 'Delivery&nbsp;Delivery&nbsp;Delivery&nbsp;Delivery&nbsp;Delivery&nbsp;Delivery&nbsp;Delivery&nbsp;Delivery&nbsp;Delivery!&nbsp;Delivery&nbsp;Delivery&nbsp;Delivery&nbsp;Delivery!<div><br></div><div>Delivery&nbsp;Delivery&nbsp;Delivery!</div>', '', '', 1, 1, '2026-04-27 08:05:34', '2026-08-26 08:23:22');

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
(1, 'Test Plugin', 'TestPlugin', '/plugins/TestPlugin/plugin.php', 0, '1.0.0', '2026-04-30 19:25:51', '2026-09-25 09:38:38'),
(2, 'LiqPay — Online payment', 'LiqPayGateway', '/plugins/LiqPayGateway/plugin.php', 1, '1.0.0', '2026-06-10 13:42:15', '2026-09-25 09:38:38'),
(4, 'Request a call', 'CallbackWidget', '/plugins/CallbackWidget/plugin.php', 1, '1.0.0', '2026-07-29 20:32:45', '2026-09-25 09:38:38');

-- --------------------------------------------------------

--
-- Структура таблиці `plugin_settings`
--

CREATE TABLE `plugin_settings` (
  `id` int UNSIGNED NOT NULL,
  `plugin_slug` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `price_change_batches`
--

CREATE TABLE `price_change_batches` (
  `id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'percent | fixed | currency',
  `mode_params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'JSON з параметрами розрахунку',
  `scope_category_id` int DEFAULT NULL,
  `scope_include_subcategories` tinyint(1) NOT NULL DEFAULT '0',
  `scope_vendor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affected_count` int NOT NULL DEFAULT '0',
  `skipped_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reverted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `price_change_batches`
--

INSERT INTO `price_change_batches` (`id`, `admin_id`, `mode`, `mode_params`, `scope_category_id`, `scope_include_subcategories`, `scope_vendor`, `affected_count`, `skipped_count`, `created_at`, `reverted_at`) VALUES
(1, 5, 'percent', '{\"value\":5,\"direction\":\"increase\"}', NULL, 0, NULL, 7, 0, '2026-09-15 07:01:30', NULL),
(2, 5, 'percent', '{\"value\":5,\"direction\":\"decrease\",\"round_price\":true}', NULL, 0, NULL, 7, 0, '2026-09-15 16:34:12', NULL);

-- --------------------------------------------------------

--
-- Структура таблиці `price_change_batch_items`
--

CREATE TABLE `price_change_batch_items` (
  `id` int NOT NULL,
  `batch_id` int NOT NULL,
  `product_id` int NOT NULL,
  `old_price` decimal(10,2) NOT NULL,
  `new_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `price_change_batch_items`
--

INSERT INTO `price_change_batch_items` (`id`, `batch_id`, `product_id`, `old_price`, `new_price`) VALUES
(1, 1, 7, 949.00, 996.45),
(2, 1, 1, 999.00, 1048.95),
(3, 1, 2, 2050.08, 2152.58),
(4, 1, 3, 5199.00, 5458.95),
(5, 1, 5, 5299.00, 5563.95),
(6, 1, 4, 5599.00, 5878.95),
(7, 1, 6, 29199.00, 30658.95),
(8, 2, 7, 996.45, 947.00),
(9, 2, 1, 1048.95, 997.00),
(10, 2, 2, 2152.58, 2045.00),
(11, 2, 3, 5458.95, 5186.00),
(12, 2, 5, 5563.95, 5286.00),
(13, 2, 4, 5878.95, 5585.00),
(14, 2, 6, 30658.95, 29126.00);

-- --------------------------------------------------------

--
-- Структура таблиці `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vendor` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Бренд/виробник товару (напр. для Hotline.ua <vendor>, Google Merchant тощо)',
  `hotline_excluded` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Виключити товар з фіда Hotline.ua (плагін HotlineExport)',
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

INSERT INTO `products` (`id`, `sku`, `vendor`, `hotline_excluded`, `is_visible`, `category_id`, `name`, `description`, `image`, `slug`, `meta_title`, `meta_description`, `meta_keywords`, `price`, `created_at`, `updated_at`, `views_count`, `prom_product_id`) VALUES
(1, 'ID-1', NULL, 0, 1, 2, 'Smart TV Apple 55\" I Android 15 120 Гц/Smart TV/DVB/T2/FullHD/USB/ (1980x1080)', 'The 55\" Apple Smart TV (Android 15) combines cutting-edge technology with a stylish design. It is ideal for home cinema, gaming, and everyday use. High image quality, fast response times, and user-friendly controls make this TV an excellent choice for the whole family.\r\n\r\nWith Full HD resolution (1980x1080) and a 120 Hz refresh rate, the TV delivers a crisp image and smooth motion. A response time of just 1 ms ensures there is no blur, even during fast-paced action scenes.\r\n\r\nThe TV is equipped with 2 GB of RAM and 16 GB of internal storage, ensuring fast app performance and stable operation of Android 15. The device supports Smart TV, DVB-T2, and USB connectivity, allowing for easy content viewing from various sources.', '/uploads/products/gallery/original/product_6a8e9f6d243fc044107860.webp', 'iphone-13', '', '', NULL, 997.00, '2026-03-30 07:45:12', '2026-09-15 16:34:12', 0, NULL),
(2, 'ID-2', NULL, 0, 1, 3, 'Xiaomi', 'Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi Xiaomi!', '/uploads/products/gallery/original/product_69e608b6ddae3707094810.webp', 'siom', 'Xiaomi is a cool product.', '', NULL, 2045.00, '2026-04-05 08:11:57', '2026-09-21 17:37:22', 51, NULL),
(3, 'BRAVIS-24K5000H', NULL, 0, 1, 3, 'Television BRAVIS 24K5000H', 'BRAVIS 24K5000H — an ideal choice for small rooms, kitchens, or as a second TV in the bedroom. It features a stylish, slim 24-inch design with a black bezel that complements any interior. (Design details confirmed by multiple sources, for example.)', '/uploads/products/gallery/original/product_6a48eefff07ba546360331.jpg', 'bravis-24k5000h', '', '', NULL, 5186.00, '2026-07-03 21:27:11', '2026-09-21 17:37:05', 0, NULL),
(4, 'SetUP-32HSF30', NULL, 0, 1, 3, 'Television SetUP 32HSF30', 'The HD format supported by the TV delivers excellent image detail and clarity. Experience your favorite TV shows and movies in a whole new way.', '/uploads/products/gallery/original/product_6a48eef337307640388684.jpg', 'setup-32hsf30', '', '', NULL, 5585.00, '2026-07-03 21:27:12', '2026-09-21 17:37:12', 0, NULL),
(5, 'Grunhelm-24H300-T2', 'Grunhel', 0, 1, 3, 'Television Grunhelm 24H300-T2', 'GRUNHELM 24H300‑T2 - the TV features a 24-inch screen with a resolution of 1366×768 (HD Ready), providing acceptable image quality for small rooms or kitchens. It is equipped with LED backlighting (Direct LED type) and a VA panel, offering wide viewing angles of up to 170° both horizontally and vertically.', '/uploads/products/gallery/original/product_6a482b3f04178722104981.jpg', 'grunhelm-24H300-T2', '', '', NULL, 5286.00, '2026-07-03 21:27:12', '2026-09-21 17:36:53', 0, NULL),
(6, 'ID-3', NULL, 0, 1, 2, 'iPhone 15 128GB Black', 'iPhone 15 — this is a smartphone that embodies innovation in every aspect, from design to performance. Unrivaled from its exterior to its internal components, this device will become your reliable partner in daily life, ensuring maximum convenience and productivity. With its refreshed look, powerful A16 Bionic chip, and 48MP camera, the iPhone 15 opens up a boundless world of new possibilities.', '/uploads/products/gallery/original/product_6a48edeb1719a274349597.jpg', 'iphone-15-128gv-black', '', '', NULL, 29126.00, '2026-07-04 11:24:17', '2026-09-21 17:36:25', 0, NULL),
(7, 'ugreen-lp152', NULL, 0, 1, 6, 'Headphone case UGREEN LP152 Travel Storage Case Gray', 'Accessory organizer case UGREEN LP152 Travel Storage Case.\r\n\r\nStoring and transporting electronic accessories has never been so simple and secure! Introducing the Ugreen Compact Travel Bag, measuring 20.3 x 12.9 x 7.2 cm. This optimal size allows you to carry a variety of handy items, including:\r\n\r\ncables,\r\nelectronic gadgets,\r\ncosmetics.\r\nEverything is organized and kept in one place, making it easy to find exactly what you need.', '/uploads/products/gallery/original/product_6a4cbe67e5901786626367.webp', 'chohol-dlya-navushnikv-ugreen-lp152-travel-storage-case-gray', '', '', NULL, 947.00, '2026-07-07 08:51:11', '2026-09-21 07:40:31', 0, NULL);

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
(45, NULL, 2, 3, 'Plastics', 108, 0.00, '+', NULL, 0, '2026-09-21 17:37:23', '2026-09-21 17:37:23'),
(46, 'ID-2-106', 2, 4, 'Redmi', 106, 150.00, '+', 5, 1, '2026-09-21 17:37:23', '2026-09-21 17:37:23'),
(47, 'BRAVIS-24K5000H-108', 3, 3, 'Plastics', 108, 9.95, '+', 10, 1, '2026-09-22 17:16:13', '2026-09-22 17:16:13');

-- --------------------------------------------------------

--
-- Структура таблиці `product_discounts`
--

CREATE TABLE `product_discounts` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `discount_price` decimal(10,2) NOT NULL COMMENT 'Нова (акційна) ціна, має бути СТРОГО менша за products.price',
  `start_date` datetime DEFAULT NULL COMMENT 'Початок дії знижки, NULL = діє одразу',
  `end_date` datetime DEFAULT NULL COMMENT 'Кінець дії знижки, NULL = без обмеження за часом',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(9, 5, '/uploads/products/gallery/original/product_6a482b3f04178722104981.jpg', 1, '2026-07-03 21:35:59'),
(10, 6, '/uploads/products/gallery/original/product_6a48edeb1719a274349597.jpg', 1, '2026-07-04 11:26:35'),
(11, 4, '/uploads/products/gallery/original/product_6a48eef337307640388684.jpg', 1, '2026-07-04 11:30:59'),
(12, 3, '/uploads/products/gallery/original/product_6a48eefff07ba546360331.jpg', 1, '2026-07-04 11:31:12'),
(13, 7, '/uploads/products/gallery/original/product_6a4cbe67e5901786626367.webp', 1, '2026-07-07 08:52:56'),
(14, 1, '/uploads/products/gallery/original/product_6a8e9f6d243fc044107860.webp', 3, '2026-08-26 08:10:21'),
(15, 1, '/uploads/products/gallery/original/product_6a8e9f7fee947587998458.webp', 4, '2026-08-26 08:10:40');

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
(1, 1, NULL, 'ID-1', 4, 1, '2026-06-26 18:09:03'),
(8, 0, NULL, 'ID-2', 10, 2, '2026-06-26 22:18:44'),
(12, 3, NULL, 'BRAVIS-24K5000H', 25, 1, '2026-07-10 09:04:27'),
(13, 4, NULL, 'SetUP-32HSF30', 40, 0, '2026-07-03 21:27:12'),
(14, 5, NULL, 'Grunhelm-24H300-T2', 18, 4, '2026-09-24 11:27:28'),
(17, 6, NULL, NULL, 25, 0, '2026-07-04 11:27:15'),
(18, 7, NULL, 'ugreen-lp152', 25, 3, '2026-09-24 11:27:28'),
(31, 2, 106, 'ID-2-106', 5, 0, '2026-09-21 17:37:23'),
(32, 3, 108, 'BRAVIS-24K5000H-108', 10, 0, '2026-09-22 17:16:13');

-- --------------------------------------------------------

--
-- Структура таблиці `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Код промокоду, зберігається у верхньому регістрі',
  `assigned_user_id` int DEFAULT NULL COMMENT 'Якщо задано — промокод особистий, застосувати може лише цей користувач',
  `type` enum('percent','fixed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent' COMMENT 'percent = % від суми кошика, fixed = фіксована сума знижки',
  `value` decimal(10,2) NOT NULL COMMENT 'Розмір знижки: для percent — число 0-100, для fixed — сума у валюті магазину',
  `min_order_amount` decimal(10,2) DEFAULT NULL COMMENT 'Мінімальна сума кошика для застосування, NULL = без обмеження',
  `max_discount_amount` decimal(10,2) DEFAULT NULL COMMENT 'Максимальна сума знижки (стеля), актуально для type=percent',
  `usage_limit` int UNSIGNED DEFAULT NULL COMMENT 'Загальний ліміт використань, NULL = необмежено',
  `usage_limit_per_user` int UNSIGNED DEFAULT NULL COMMENT 'Ліміт використань на одного користувача/гостя, NULL = необмежено',
  `used_count` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Скільки разів промокод вже застосовано в оформлених замовленнях',
  `starts_at` datetime DEFAULT NULL COMMENT 'Дата початку дії, NULL = діє одразу',
  `expires_at` datetime DEFAULT NULL COMMENT 'Дата закінчення дії, NULL = без обмеження',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `promo_code_usages`
--

CREATE TABLE `promo_code_usages` (
  `id` int NOT NULL,
  `promo_code_id` int NOT NULL,
  `user_id` int DEFAULT NULL COMMENT 'NULL для гостьових замовлень',
  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID сесії гостя, для обмеження повторного використання без реєстрації',
  `order_id` int DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `prom_sync_queue`
--

CREATE TABLE `prom_sync_queue` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int NOT NULL COMMENT 'ID товару в нашій БД',
  `action` enum('price','quantity','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `status` enum('pending','processing','done','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` tinyint NOT NULL DEFAULT '0',
  `last_error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблиці `search_cache`
--

CREATE TABLE `search_cache` (
  `id` int UNSIGNED NOT NULL,
  `query_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA-256 від нормалізованого запиту',
  `query_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Оригінальний запит',
  `results` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON з результатами',
  `hits` int UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Кількість звернень',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `search_cache`
--

-- Search cache data removed for security

-- --------------------------------------------------------

--
-- Структура таблиці `search_queries`
--

CREATE TABLE `search_queries` (
  `id` int UNSIGNED NOT NULL,
  `query` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `results_count` int UNSIGNED NOT NULL DEFAULT '0',
  `search_count` int UNSIGNED NOT NULL DEFAULT '1',
  `last_searched` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `search_queries`
--

-- Search queries data removed for security

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
(1, 'category', 1, 'Buy Smartphones', 'A wide selection of smartphones!', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 07:47:51', '2026-08-25 10:25:40'),
(2, 'category', 2, 'iPhone!', 'iPhone! iPhone!', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 15:31:02', '2026-08-25 10:39:43'),
(3, 'category', 3, 'The most modern televisions.', 'televisions televisions televisions televisions!', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 21:23:11', '2026-08-25 10:19:52'),
(4, 'product', 2, 'Xiaomi is a cool product.', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-27 15:59:34', '2026-08-25 10:29:13');

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
('active_logotype', '/uploads/logotypes/logotype_6a8099021b98c144124377.png', 'general', 'text', '2026-04-27 16:44:28', '2026-08-15 16:51:16'),
('active_theme', 'modern', 'appearance', 'select', '2026-04-03 08:17:13', '2026-09-07 17:16:53'),
('contact_address', 'Kyiv, Ukraine', 'contact', 'text', '2026-08-22 17:39:29', '2026-09-07 17:16:54'),
('contact_email', 'admin@example.com', 'contact', 'text', '2026-04-03 08:17:13', '2026-09-07 17:16:53'),
('contact_phone', '+380 00 000 00 00', 'contact', 'text', '2026-04-03 08:17:13', '2026-09-07 17:16:53'),
('csp_mode', 'off', 'security', 'select', '2026-07-02 18:09:14', '2026-09-25 07:33:22'),
('currency_source', 'manual', 'currency', 'select', '2026-06-05 10:00:00', '2026-06-27 10:03:31'),
('date_format', 'd.m.Y H:i', 'general', 'text', '2026-06-13 18:37:31', '2026-09-07 17:16:52'),
('default_currency', 'UAH', 'localization', 'select', '2026-04-03 08:17:13', '2026-09-17 16:37:40'),
('default_language', 'en', 'localization', 'select', '2026-04-03 08:17:13', '2026-09-07 17:16:52'),
('display_errors', '0', 'system', 'checkbox', '2026-09-06 17:36:21', '2026-09-06 17:36:21'),
('email', 'admin@example.com', 'general', 'text', '2026-04-12 16:35:40', '2026-06-13 18:37:31'),
('engine_version', '1.0.0', 'system', 'text', '2026-07-01 09:41:28', '2026-09-16 10:38:31'),
('facebook_auth_enabled', '0', 'social_auth', 'checkbox', '2026-05-09 12:03:51', '2026-09-07 17:16:54'),
('facebook_client_id', '', 'social_auth', 'text', '2026-05-09 12:01:55', '2026-09-07 17:16:54'),
('facebook_client_secret', '', 'social_auth', 'text', '2026-05-09 12:01:55', '2026-09-07 17:16:54'),
('facebook_redirect_url', '/auth/facebook/callback', 'social_auth', 'text', '2026-05-09 12:02:18', '2026-09-07 17:16:54'),
('footer_about_text', 'Your text about the store goes here', 'general', 'text', '2026-08-15 09:31:15', '2026-09-07 17:24:46'),
('force_https', '0', 'general', 'checkbox', '2026-05-01 17:21:57', '2026-05-01 17:21:57'),
('google_auth_enabled', '0', 'social_auth', 'checkbox', '2026-05-09 12:03:51', '2026-09-07 17:16:54'),
('google_client_id', '', 'social_auth', 'text', '2026-05-09 12:01:55', '2026-09-07 17:16:54'),
('google_client_secret', '', 'social_auth', 'text', '2026-05-09 12:01:55', '2026-09-07 17:16:54'),
('google_redirect_url', '/auth/google/callback', 'social_auth', 'text', '2026-05-09 12:02:18', '2026-09-07 17:16:54'),
('hsts_enabled', '0', 'security', 'checkbox', '2026-07-02 18:09:14', '2026-09-25 07:33:22'),
('hsts_max_age', '300', 'security', 'number', '2026-07-02 18:09:14', '2026-07-02 18:09:14'),
('hsts_preload', '0', 'security', 'checkbox', '2026-07-02 18:09:14', '2026-07-02 18:09:14'),
('hsts_subdomains', '0', 'security', 'checkbox', '2026-07-02 18:09:14', '2026-07-02 18:09:14'),
('https_redirect', '0', 'security', 'checkbox', '2026-07-02 18:09:14', '2026-09-25 07:33:22'),
('maintenance_message', 'We apologize, but the store is temporarily closed for maintenance.', 'general', 'textarea', '2026-04-03 08:17:13', '2026-09-07 17:16:52'),
('media_apply_watermark', '0', 'media', 'checkbox', '2026-04-13 09:12:17', '2026-09-08 17:30:37'),
('media_auto_webp', '0', 'media', 'checkbox', '2026-04-13 09:12:17', '2026-09-08 17:30:37'),
('media_watermark_position', 'bottom-right', 'media', 'select', '2026-04-13 09:12:17', '2026-09-08 17:30:37'),
('phone_mask', '+38 (###) ###-##-##', 'contact', 'text', '2026-05-16 09:39:11', '2026-09-07 17:16:52'),
('prom_api_key', '', 'prom', 'text', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('prom_enabled', '0', 'prom', 'checkbox', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('prom_last_sync', '', 'prom', 'text', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('prom_sync_method', 'xml', 'prom', 'select', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('prom_webhook_secret', '', 'prom', 'text', '2026-06-10 17:52:36', '2026-06-10 17:52:36'),
('seo_category_desc_template', '', 'general', 'text', '2026-06-25 15:25:47', '2026-09-07 17:16:53'),
('seo_category_title_template', '', 'general', 'text', '2026-06-25 15:25:47', '2026-09-07 17:16:53'),
('seo_desc_template', 'We offer {name} at the best price of {price} UAH. Category: {category}. Delivery across Ukraine!', 'seo', 'textarea', '2026-04-13 08:34:14', '2026-09-07 17:16:53'),
('seo_home_description', '', 'general', 'text', '2026-06-25 15:25:48', '2026-09-07 17:16:53'),
('seo_home_keywords', '', 'general', 'text', '2026-06-25 15:25:48', '2026-09-07 17:16:53'),
('seo_home_title', '', 'general', 'text', '2026-06-25 15:25:48', '2026-09-07 17:16:53'),
('seo_page_desc_template', '', 'general', 'text', '2026-06-25 15:25:48', '2026-09-07 17:16:53'),
('seo_page_title_template', '', 'general', 'text', '2026-06-25 15:25:48', '2026-09-07 17:16:53'),
('seo_title_template', 'Buy {name} for {price} UAH at the MyStore store', 'seo', 'text', '2026-04-13 08:34:14', '2026-09-07 17:16:53'),
('site_description', 'The best online store built with PHP', 'general', 'textarea', '2026-04-03 08:17:13', '2026-09-07 17:16:52'),
('site_name', 'MySite', 'general', 'text', '2026-04-03 08:17:13', '2026-09-07 17:16:52'),
('site_timezone', 'Europe/Kiev', 'general', 'text', '2026-04-12 16:52:37', '2026-09-07 17:16:52'),
('site_url', 'https://example.com', 'general', 'text', '2026-06-26 12:05:23', '2026-09-07 17:16:52'),
('sitemap_last_generated', '2026-07-01 07:12:44', 'system', 'text', '2026-07-01 07:12:44', '2026-07-01 07:12:44'),
('smtp_encryption', '', 'general', 'text', '2026-06-14 14:55:14', '2026-09-07 17:16:52'),
('smtp_from_email', '', 'general', 'text', '2026-06-14 14:55:14', '2026-09-07 17:16:53'),
('smtp_from_name', '', 'general', 'text', '2026-06-14 14:55:14', '2026-09-07 17:16:53'),
('smtp_host', '', 'general', 'text', '2026-06-14 14:55:14', '2026-09-07 17:16:52'),
('smtp_pass', '', 'general', 'text', '2026-04-12 16:47:28', '2026-09-07 17:16:52'),
('smtp_port', '', 'general', 'text', '2026-04-12 16:42:48', '2026-09-07 17:16:52'),
('smtp_username', '', 'general', 'text', '2026-06-14 14:55:14', '2026-09-07 17:16:52'),
('smtr', '', 'general', 'text', '2026-04-12 16:28:35', '2026-06-13 18:37:31'),
('store_status', 'open', 'general', 'select', '2026-04-03 08:17:13', '2026-09-07 17:16:52'),
('update_last_checked', '2026-09-17 16:52:23', 'system', 'text', '2026-07-01 09:39:47', '2026-09-17 16:52:23'),
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
(1, 'shipping', 'nova_poshta', 'Нова Пошта', NULL, '', 0, 0, '{\"cost\": \"70\", \"api_key\": \"\"}', 0, '2026-04-20 16:09:35', '2026-09-07 17:19:01'),
(2, 'shipping', 'self_pickup', 'Self-pickup', NULL, '', 1, 0, '{\"address\": \"1 Tsentralna St., Kyiv\"}', 0, '2026-04-20 16:09:35', '2026-09-07 17:19:01'),
(3, 'payment', 'cash', 'Payment upon receipt', NULL, '', 1, 0, '{\"gateway_name\": \"cash\"}', 0, '2026-04-20 16:09:35', '2026-09-08 17:30:37'),
(4, 'payment', 'liqpay', 'Онлайн-оплата (LiqPay)', NULL, '', 0, 1, '{\"public_key\": \"\", \"private_key\": \"\", \"gateway_name\": \"liqpay\"}', 0, '2026-04-20 16:09:35', '2026-09-08 17:30:37');

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
-- Структура таблиці `social_links`
--

CREATE TABLE `social_links` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп даних таблиці `social_links`
--

INSERT INTO `social_links` (`id`, `name`, `slug`, `url`, `is_active`, `sort_order`) VALUES
(1, 'Facebook', 'facebook', 'https://facebook.com', 1, 1),
(2, 'Instagram', 'instagram', 'https://instagram.com', 1, 2),
(3, 'Telegram', 'telegram', 'https://t.me', 1, 3),
(4, 'YouTube', 'youtube', 'https://youtube.com', 1, 4);

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

--
-- Дамп даних таблиці `url_redirects`
--

INSERT INTO `url_redirects` (`id`, `old_slug`, `new_slug`, `entity_type`, `entity_id`, `status_code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'siomi', 'siom', 'product', 2, 301, 1, '2026-07-03 21:41:03', '2026-07-08 11:17:35'),
(2, 'siomii', 'siomi', 'product', 2, 301, 1, '2026-07-03 21:42:00', '2026-07-03 21:42:00'),
(3, 'televzor-bravis-24k5000h', 'iphone-15-128gv-black', 'product', 6, 301, 1, '2026-07-04 11:29:40', '2026-07-04 11:29:40');

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
-- Індекси таблиці `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_message_session_id` (`session_id`,`id`) COMMENT 'Швидке впорядковане читання історії + long-poll "after_id"',
  ADD KEY `idx_chat_message_session_unread_op` (`session_id`,`is_read_by_operator`),
  ADD KEY `fk_chat_message_operator` (`sender_operator_id`);

--
-- Індекси таблиці `chat_operators`
--
ALTER TABLE `chat_operators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chat_operator_user` (`user_id`);

--
-- Індекси таблиці `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chat_session_token` (`channel_token`),
  ADD KEY `idx_chat_session_visitor` (`visitor_id`),
  ADD KEY `idx_chat_session_operator_status` (`operator_id`,`status`),
  ADD KEY `idx_chat_session_status` (`status`);

--
-- Індекси таблиці `chat_visitors`
--
ALTER TABLE `chat_visitors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chat_visitor_uuid` (`visitor_uuid`),
  ADD KEY `idx_chat_visitor_user` (`user_id`);

--
-- Індекси таблиці `compare_list`
--
ALTER TABLE `compare_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_compare_user` (`user_id`),
  ADD KEY `idx_compare_session` (`session_id`),
  ADD KEY `idx_compare_product` (`product_id`);

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
-- Індекси таблиці `price_change_batches`
--
ALTER TABLE `price_change_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_batch_created` (`created_at`);

--
-- Індекси таблиці `price_change_batch_items`
--
ALTER TABLE `price_change_batch_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_batch` (`batch_id`),
  ADD KEY `idx_item_product` (`product_id`);

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
  ADD KEY `idx_products_views` (`views_count`),
  ADD KEY `idx_products_vendor` (`vendor`);
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
-- Індекси таблиці `product_discounts`
--
ALTER TABLE `product_discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_product_discounts_product_id` (`product_id`);

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
-- Індекси таблиці `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_promo_codes_code` (`code`),
  ADD KEY `idx_promo_codes_active` (`is_active`),
  ADD KEY `idx_promo_codes_assigned_user` (`assigned_user_id`);

--
-- Індекси таблиці `promo_code_usages`
--
ALTER TABLE `promo_code_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pcu_promo_code_id` (`promo_code_id`),
  ADD KEY `idx_pcu_user_id` (`user_id`),
  ADD KEY `idx_pcu_order_id` (`order_id`);

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
-- Індекси таблиці `social_links`
--
ALTER TABLE `social_links`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT для таблиці `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT для таблиці `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблиці `category_attributes`
--
ALTER TABLE `category_attributes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблиці `category_filters`
--
ALTER TABLE `category_filters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблиці `chat_operators`
--
ALTER TABLE `chat_operators`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблиці `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT для таблиці `chat_visitors`
--
ALTER TABLE `chat_visitors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблиці `compare_list`
--
ALTER TABLE `compare_list`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблиці `crm_user_action_audit`
--
ALTER TABLE `crm_user_action_audit`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `crm_user_activity_logs`
--
ALTER TABLE `crm_user_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=536;

--
-- AUTO_INCREMENT для таблиці `cron_tasks`
--
ALTER TABLE `cron_tasks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблиці `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT для таблиці `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблиці `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблиці `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблиці `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблиці `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблиці `plugins`
--
ALTER TABLE `plugins`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблиці `plugin_settings`
--
ALTER TABLE `plugin_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `price_change_batches`
--
ALTER TABLE `price_change_batches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `price_change_batch_items`
--
ALTER TABLE `price_change_batch_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблиці `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблиці `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT для таблиці `product_discounts`
--
ALTER TABLE `product_discounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблиці `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблиці `product_stocks`
--
ALTER TABLE `product_stocks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT для таблиці `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблиці `promo_code_usages`
--
ALTER TABLE `promo_code_usages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблиці `prom_sync_queue`
--
ALTER TABLE `prom_sync_queue`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `search_cache`
--
ALTER TABLE `search_cache`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT для таблиці `search_queries`
--
ALTER TABLE `search_queries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT для таблиці `seo_settings`
--
ALTER TABLE `seo_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблиці `shop_methods`
--
ALTER TABLE `shop_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблиці `slug_history`
--
ALTER TABLE `slug_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблиці `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблиці `stock_documents`
--
ALTER TABLE `stock_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблиці `url_redirects`
--
ALTER TABLE `url_redirects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблиці `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Обмеження зовнішнього ключа таблиці `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_message_operator` FOREIGN KEY (`sender_operator_id`) REFERENCES `chat_operators` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_chat_message_session` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `chat_operators`
--
ALTER TABLE `chat_operators`
  ADD CONSTRAINT `fk_chat_operator_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD CONSTRAINT `fk_chat_session_operator` FOREIGN KEY (`operator_id`) REFERENCES `chat_operators` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_chat_session_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `chat_visitors` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `chat_visitors`
--
ALTER TABLE `chat_visitors`
  ADD CONSTRAINT `fk_chat_visitor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Обмеження зовнішнього ключа таблиці `product_discounts`
--
ALTER TABLE `product_discounts`
  ADD CONSTRAINT `fk_product_discounts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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
-- Обмеження зовнішнього ключа таблиці `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD CONSTRAINT `fk_promo_codes_assigned_user` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `promo_code_usages`
--
ALTER TABLE `promo_code_usages`
  ADD CONSTRAINT `fk_pcu_promo_code` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE;

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
