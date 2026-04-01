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

📅 Phase 1: Core Architecture & Security
MVC-like Structure: Implementing a clean and scalable directory layout.
SEO-friendly URLs (SEF): Advanced routing system for human-readable links.
Database Layer: Secure MySQL integration using PDO with SQL injection protection.
Security Suite: Built-in protection against XSS, CSRF, and brute-force attacks.
Auth System: Secure user registration, login, and Role-Based Access Control (RBAC).
🎨 Phase 2: Interface & Customization
I18n Engine: Full multi-language support with easy-to-edit translation files.
Theme Engine: Decoupled logic and presentation for seamless UI/UX skinning.
Mobile-First Design: Fully responsive frontend out of the box.
Dynamic Content: Management system for static pages and navigation menus.
🔌 Phase 3: Plugin & Extension System
Hook & Event System: Robust architecture to extend functionality without modifying the core.
Plugin API: Comprehensive documentation and tools for third-party developers.
Core Extensions: Developing essential modules (e.g., Wishlist, Product Reviews).
📦 Phase 4: E-commerce Features
Product Catalog: Categories, multi-level filtering, and smart search.
Shopping Cart: Optimized AJAX-powered cart and checkout workflow.
Order Management: Automated status tracking and customer notifications.
Admin Dashboard: Powerful control panel for inventory and sales management.
🔄 Phase 5: Automation & Deployment
Auto-Update System: Integrated mechanism for seamless core version updates.
Web Installer: User-friendly GUI for quick server setup and configuration.
Performance Audit: Final code optimization and security penetration testing.
v1.0.0 Release: Official stable launch.
🚀 Future Ideas & Roadmap Extensions
Payment Gateways: Built-in integration for Stripe, PayPal, and local payment providers.
RESTful API: Headless capabilities to power mobile apps (iOS/Android) or external services.
Advanced Analytics: Dashboard with sales reports, customer behavior, and conversion tracking.
Inventory Sync: Auto-sync stock levels with external marketplaces (Amazon, eBay, etc.).
AI-Powered Search: Smart product recommendations and predictive search queries.
Marketing Tools: Built-in SEO analyzer, discount coupons, and email newsletter system.

Legend:
Completed
In Progress / Planned

* ## Requirements
* PHP >= 8.3
* Composer
* MySQL (8.0)

* ## Installation
1. Download the project
2. Install dependencies: composer install
3. Configure the configuration (config/database.php)

## License

This project is distributed under the MIT License. See the [`LICENSE`](LICENSE) file for details.
