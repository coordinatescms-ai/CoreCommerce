# CoreCommerce

**CoreCommerce** - a fast, secure, and flexible e-commerce platform built on pure PHP and MySQL, designed as a lightweight alternative to bulky CMS systems.

## Project Overview

CoreCommerce is a full-featured online store platform built on a custom minimalist PHP framework. It provides maximum security and flexibility out of the box, with modular architecture for easy customization and extension.

## Key Features

### Core Architecture
- **Custom MVC Framework**: Built-in routing, controllers, models, and views
- **Database Layer**: PDO-based with Active Record pattern
- **Security**: CSRF protection, session management, password hashing, rate limiting
- **Plugin System**: Extensible architecture for adding functionality
- **Theme System**: Multiple built-in themes (default, modern, premium) with easy switching

### E-commerce Functionality
- **Product Management**: Full CRUD with categories, attributes, images, and inventory
- **Advanced Filtering**: 3-step pipeline for category product filtering with range and multi-select support
- **Shopping Cart**: Session-based cart with quantity management
- **Order Processing**: Complete order workflow from checkout to fulfillment
- **Payment Integration**: Support for multiple payment gateways (LiqPay, Prom API)
- **Shipping**: Logistics lookup and shipping method management

### Marketing & SEO
- **SEO Optimization**: Automatic meta tags, sitemaps, and robots.txt generation
- **Product Reviews**: Customer review system with moderation
- **Social Media**: Social login integration and social link management
- **Email Marketing**: Built-in email templates and notification system

### Admin Panel
- **Dashboard**: System status, cache management, and quick statistics
- **Product Management**: Bulk operations, attribute management, image handling
- **Order Management**: Order processing, status updates, customer communication
- **User Management**: Role-based access control, customer accounts
- **Content Management**: Pages, banners, and static content
- **System Settings**: Configuration management for all platform features

### Advanced Services
- **Currency Management**: Multi-currency support with automatic exchange rate updates
- **Search Service**: Full-text product search with relevance ranking
- **Sitemap Generation**: Automatic XML sitemap creation for search engines
- **Prom Integration**: Two-way sync with Prom.ua marketplace
- **Stock Management**: Local and remote stock tracking with alerts
- **Image Management**: Automatic image optimization and thumbnail generation

## Technical Architecture

### Directory Structure
```
├── app/
│   ├── Controllers/          # Request handlers (30+ controllers)
│   ├── Core/                 # Framework core components
│   │   ├── Database/         # Database abstraction layer
│   │   ├── Routing/          # URL routing system
│   │   ├── Payment/          # Payment gateway integrations
│   │   ├── Plugin/           # Plugin management system
│   │   ├── Theme/            # Theme loading and rendering
│   │   └── ...
│   ├── Middleware/           # HTTP middleware (auth, etc.)
│   ├── Models/               # Data models (14 models)
│   └── Services/             # Business logic services (16 services)
├── config/                   # Configuration files
├── lang/                     # Translation files (en, pl, ua)
├── plugins/                  # Plugin system
├── public/                   # Web root directory
├── resources/
│   ├── themes/               # Theme templates (3 themes)
│   └── views/                # View templates
├── routes/                   # Route definitions
├── storage/                  # Cache, logs, uploads
└── migrations/               # Database migrations
```

### Core Components

**Routing System**
- Custom router (`app/Core/Routing/Router.php`)
- Route definitions in `routes/web.php`
- Support for GET/POST requests and URL parameters
- RESTful route patterns

**Database Layer**
- PDO wrapper (`app/Core/Database/DB.php`)
- Active Record base model (`app/Core/Model.php`)
- Migration system for schema versioning
- Support for MySQL 8.0+

**Localization**
- Multi-language support (English, Polish, Ukrainian)
- Easy language addition via configuration
- Language files in `lang/` directory
- URL-based language switching (`/language/{lang}`)

**Theme System**
- Three built-in themes: default, modern, premium
- Theme selection via `config/theme.php`
- Template inheritance and layouts
- Asset versioning for cache busting

**Plugin System**
- Plugin manager (`app/Core/Plugin/PluginManager.php`)
- Plugin loading from `plugins/` directory
- Hook system for extending functionality
- Built-in plugins: CallbackWidget, LiqPayGateway, TestPlugin

**Security**
- CSRF protection on all forms
- Session regeneration and management
- Password hashing with bcrypt
- Login rate limiting
- Security headers service
- Input validation and sanitization

## Services Layer

The platform includes 16 specialized services for business logic:

- **ProductFilterService**: Advanced category filtering with range and multi-select support
- **SearchService**: Full-text product search with relevance ranking
- **SeoService**: SEO optimization and meta tag generation
- **SitemapService**: Automatic XML sitemap generation
- **SecurityHeadersService**: HTTP security headers management
- **ImageManager**: Image optimization and thumbnail generation
- **BankCurrencyService**: Currency exchange rate updates
- **LocalStockService**: Local inventory management
- **PromApiClient**: Prom.ua API integration
- **PromSyncService**: Two-way marketplace synchronization
- **PromStatusService**: Order status synchronization
- **SlugHelper**: URL slug generation and management
- **StockServiceFactory**: Stock management service factory
- **LoginRateLimiter**: Login attempt rate limiting

## Payment Integration

The platform supports multiple payment gateways through the payment system:

- **LiqPay**: Ukrainian payment gateway with webhooks
- **Prom API**: Integration with Prom.ua marketplace payments
- **Extensible Architecture**: Easy addition of new payment gateways

## Requirements

- **PHP**: >= 8.3
- **MySQL**: 8.0 or higher
- **Composer**: For dependency management
- **Web Server**: Apache with mod_rewrite or Nginx
- **PHP Extensions**: PDO, PDO_MySQL, GD, cURL, JSON, MBString

## Installation

### Quick Start

1. **Clone or download the project**
   ```bash
   git clone <repository-url>
   cd mysite.test
   ```

2. **Install dependencies**
   ```bash
   composer install
   composer require phpmailer/phpmailer
   ```

3. **Configure environment**
   - Copy `.env.example` to `.env`
   - Edit `.env` with your database credentials
   - Configure `config/database.php` with database settings

4. **Configure mail settings**
   - Edit `config/mail.php` with your SMTP details
   - Set up email templates for notifications

5. **Set up database**
   - Import the database schema from `ec30.sql`
   - Run migrations if available
   - Configure database connection in `config/database.php`

6. **Set permissions**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 public/uploads/
   ```

7. **Configure web server**
   - Point document root to `public/` directory
   - Ensure mod_rewrite is enabled (Apache)
   - Configure URL rewriting rules

### Web Server Configuration

**Apache (.htaccess included)**
- Document root: `public/`
- mod_rewrite enabled
- Follow symbolic links

**Nginx**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/mysite.test/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## Administration

### Admin Panel Features

The admin panel provides comprehensive management capabilities:

- **Dashboard**: System status, quick statistics, and cache management
- **Products**: Full product management with attributes, images, and inventory
- **Categories**: Category hierarchy and organization
- **Orders**: Order processing, status management, and customer communication
- **Users**: User management with role-based access control
- **Content**: Pages, banners, and static content management
- **Attributes**: Product attribute definitions and options
- **Reviews**: Customer review moderation
- **Plugins**: Plugin management and configuration
- **Themes**: Theme selection and customization
- **Prom Integration**: Prom.ua marketplace synchronization
- **System Settings**: Platform configuration and preferences

### Cache Management

In the admin dashboard (**Панель керування → Стан системи**) there is a **"Почистити кеш"** button.

Features:
- CSRF-protected POST request
- Clears cache files from `storage/cache/*` (including `active_plugins.json`)
- Updates `settings.asset_version` for CSS/JS cache busting
- Safe operation that preserves essential system files

## Advanced Features

### Category Filtering Pipeline (2026 Architecture)

Category product filtering uses a strict 3-step pipeline in `App\Services\ProductFilterService`:

1. **Normalization Layer** (`normalizeFilters`)
   - Converts raw URL/controller filters into deterministic DTO-like arrays
   - Supports `match` (with `option_ids`/`values`) and `range` (with `min`/`max`)
   - Validates and sanitizes filter inputs

2. **SQL Predicate Builder** (`buildBaseProductQuery`)
   - Creates safe prepared-statement conditions
   - Uses isolated `EXISTS` subqueries per attribute
   - Avoids JOIN explosion and duplicate products
   - Ensures SQL injection protection

3. **Filter Options Provider** (`getFilterOptions`)
   - Builds selectable options with canonical `opt:{attribute_option_id}` values
   - Legacy `value` fallback for historic plain-text rows
   - Dynamic option availability based on current results

### Matching Rules
- Selectable attributes are matched by `product_attributes.attribute_option_id` (canonical path)
- Legacy compatibility fallback preserved by matching `product_attributes.value` for old text-only data
- Numeric ranges use `CAST(pa.value AS DECIMAL(12,2))` with numeric guard regex
- Multi-select for one attribute works as OR inside one attribute predicate
- Multi-attribute filtering works as AND via separate `EXISTS` conditions

This architecture maintains existing DB schema and URL format (`attr_{id}`, `attr_{id}_min`, `attr_{id}_max`) while removing legacy runtime branches based on JOIN alias chains.

### Multi-language Support

The platform supports multiple languages with an easy addition process:

**Adding a new language requires exactly two steps:**

1. Add a line to `config/languages.php`:
   ```php
   'pl' => 'Polski'
   ```

2. Create language files:
   - `lang/pl.php` - Core translation file
   - `lang/pl.json` - Plugin translations (if needed)

**Automatic integration:**
- The `/language/{lang}` route automatically handles language switching
- Settings dropdown in admin panel automatically includes new languages
- Theme switchers in 'modern' and 'premium' themes automatically pick up the list
- No code editing required for language addition

**Currently supported languages:**
- English (en)
- Polish (pl) 
- Ukrainian (ua)

### Marketplace Integration

**Prom.ua Integration**
- Two-way product synchronization
- Automatic stock level updates
- Order status synchronization
- Webhook handling for real-time updates
- Bulk operations for efficiency

**Features:**
- Product catalog sync with attribute mapping
- Inventory management across platforms
- Order processing automation
- Status tracking and updates
- Error handling and retry mechanisms

## Security Features

### Implemented Security Measures
- **CSRF Protection**: All forms protected with CSRF tokens
- **Session Management**: Secure session handling with regeneration
- **Password Security**: Bcrypt hashing for user passwords
- **Rate Limiting**: Login attempt rate limiting to prevent brute force
- **Input Validation**: Comprehensive input sanitization and validation
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Output escaping and content security policies
- **Security Headers**: HTTP security headers via SecurityHeadersService

### Configuration
Security settings can be configured in `config/security.php`:
- Session timeout settings
- Password requirements
- Rate limiting thresholds
- Allowed file types for uploads
- Security header configuration

## Performance Optimization

### Caching Strategy
- View caching for improved rendering performance
- Asset versioning for browser cache control
- Query optimization with proper indexing
- Image optimization and thumbnail generation
- Database query caching where appropriate

### Database Optimization
- Proper indexing on frequently queried columns
- Optimized queries with EXISTS instead of JOINs where beneficial
- Connection pooling via PDO
- Migration system for schema updates

## Development

### Adding New Features

**Controllers**
- Create in `app/Controllers/`
- Extend base controller functionality
- Follow naming convention: `FeatureController.php`

**Models**
- Create in `app/Models/`
- Extend `app/Core/Model.php`
- Define table name and relationships

**Services**
- Create in `app/Services/`
- Implement business logic
- Keep controllers thin

**Routes**
- Add to `routes/web.php`
- Follow RESTful conventions
- Group related routes

### Testing
The project structure supports testing with appropriate test directories and fixtures.

## Documentation

Additional documentation is available:
- `INSTALL_EN.md` - Detailed English installation guide
- `INSTALL_UA.md` - Detailed Ukrainian installation guide
- `docs/` directory - Additional documentation and guides

## License

This project is distributed under the MIT License. See the [`LICENSE`](LICENSE) file for details.

## Support & Contributing

For support, issues, or contributions, please refer to the project repository or contact the development team.

## Roadmap

### Planned Features
- Additional payment gateway integrations (Stripe, PayPal)
- RESTful API for mobile app integration
- Advanced analytics dashboard
- Multi-vendor marketplace support
- AI-powered product recommendations
- Advanced marketing tools (coupons, email campaigns)
- Mobile app companion
- Advanced inventory management
- Enhanced reporting and analytics