# CoreCommerce

Project Status: In Development
The goal is to create a fast alternative to bulky CMS that provides maximum security and flexibility out of the box.

# Online Store Development Plan

## 1. Introduction

This document outlines a step-by-step plan for developing an online store built on pure PHP and MySQL, taking into account the technical requirements provided. The current state of the project was analyzed, and based on this analysis, a roadmap was formed for improving and expanding the functionality.

## 2. Analysis of the current state of the project

The project is a basic online store built on its own minimalist PHP framework. The main components include:

* **Routing:** A custom router (`app/Core/Routing/Router.php`) is used with routes defined in `routes/web.php`. Basic GET/POST requests and URL parameters (e.g. `/product/{slug}`) are supported.
* **Database:** The schema is defined in `database.sql`. PDO is used via a `app/Core/Database/DB.php` wrapper and a basic Active Record-like `app/Core/Model.php`. The schema is minimal and needs to be expanded.
* **Language support:** A basic localization system is implemented via `app/helpers.php` and language files in the `lang/` directory, allowing you to switch interface languages.
 * **Theme/Design Support:** The theme system allows you to select the active theme via `config/theme.php`, which loads the corresponding `layout.php` from `resources/themes/`. This is the basis for changing the design.
* **Plugin System:** There is a very simple plugin loading system (`app/Core/Plugin/PluginManager.php`), which includes `plugin.php` files from the `plugins/` directory.
*  **Security:** There is protection against CSRF attacks (`public/index.php`, `AuthController`, `CheckoutController`, `CartController`) and regeneration of session ID. User passwords are hashed.
* **Functionality:** Basic functions are implemented: home page, product list, product details, cart, order processing, authentication (login/register/logout), as well as an administrative panel for managing products.

🚀 Future Ideas & Roadmap Extensions
Payment Gateways: Built-in integration for Stripe, PayPal, and local payment providers.
RESTful API: Headless capabilities to power mobile apps (iOS/Android) or external services.
Advanced Analytics: Dashboard with sales reports, customer behavior, and conversion tracking.
Inventory Sync: Auto-sync stock levels with external marketplaces (Amazon, eBay, etc.).
AI-Powered Search: Smart product recommendations and predictive search queries.
Marketing Tools: Built-in SEO analyzer, discount coupons, and email newsletter system.

* ## Requirements
* PHP >= 8.3
* Composer
* MySQL (8.0)

* ## Installation
1. Download the project
2. Install dependencies: composer install
3. Run the command in the terminal: composer require phpmailer/phpmailer
4. Open the `config/mail.php` file and enter your mail server details
5. Configure the configuration (config/database.php)

## License

This project is distributed under the MIT License. See the [`LICENSE`](LICENSE) file for details.


## Admin: Clear Cache Button

In the admin dashboard (**Панель керування → Стан системи**) there is a **"Почистити кеш"** button.
It sends a CSRF-protected POST request and clears project cache files only from `storage/cache/*` (including at minimum `active_plugins.json`).
It also updates `settings.asset_version` so CSS/JS assets can be reloaded with a new `?v=` query parameter in templates (cache busting).

## Category filters pipeline (2026 rewrite)

Category product filtering now uses a strict 3-step pipeline in `App\Services\ProductFilterService`:
1. **Normalization layer** (`normalizeFilters`) converts raw URL/controller filters into deterministic DTO-like arrays (`match` with `option_ids`/`values`, or `range` with `min`/`max`).
2. **SQL predicate builder** (`buildBaseProductQuery`) creates safe prepared-statement conditions, using isolated `EXISTS` subqueries per attribute to avoid JOIN explosion and duplicate products.
3. **Filter options provider** (`getFilterOptions`) builds selectable options with canonical `opt:{attribute_option_id}` values and legacy `value` fallback for historic plain-text rows.

### Matching rules
- Selectable attributes are matched by `product_attributes.attribute_option_id` (canonical path).
- Legacy compatibility fallback is preserved by matching `product_attributes.value` for old text-only data.
- Numeric ranges use `CAST(pa.value AS DECIMAL(12,2))` with numeric guard regex.
- Multi-select for one attribute works as OR inside one attribute predicate.
- Multi-attribute filtering works as AND via separate `EXISTS` conditions.

This keeps existing DB schema and URL format (`attr_{id}`, `attr_{id}_min`, `attr_{id}_max`) intact while removing legacy runtime branches based on JOIN alias chains.
