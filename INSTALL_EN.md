CoreCommerce Web Hosting Installation Guide
Engine Version: 1.0.0
Requirements: PHP 8.1+ (PHP 8.3 Recommended), MySQL 5.7+ / MariaDB 10.5+, 
mod_rewrite (Apache) or URL rewriting (Nginx)

Step 1 — Uploading Files to Hosting
Upload all project files to your hosting inside the site's root directory (e.g., public_html/, www/, htdocs/ — depending on your hosting provider).

Important: Your domain's Document Root must point to the public/ 
subfolder, not the project root. If your hosting allows changing the Document Root via the control panel (cPanel, ISPManager, DirectAdmin), 
please do so. If not, refer to Appendix A at the end of this file.

Structure after upload:

/home/user/://site.com          ← Project root
    app/
    config/
    lang/
    plugins/
    public/                   ← Document Root must point here
        index.php
        .htaccess
        ...
    resources/
    storage/
    vendor/
    .env
    ec30.sql
    ...
```

---

## Step 2 — Database Creation

1. In your hosting control panel (cPanel → MySQL Databases or equivalent):
   * Create a **new database** (e.g., `site_shop`).
   * Create a **new database user** with a strong password.
   * Grant the user **all privileges** for this database.

2. Import the initial structure and data:
   * Via **phpMyAdmin**: Select your DB → "Import" tab → upload the `ec30.sql` file → click "Go" / "Execute".
   * Via SSH: `mysql -u user -p site_shop < ec30.sql`.

---

## Step 3 — Configuring .env

If the `.env` file is missing, the system will automatically copy `.env.example` to `.env` during the first launch.

Edit the `.env` file:

```ini
# ─── Database ─────────────────────────────────
DB_HOST=localhost
DB_PORT=3306
DB_NAME=site_shop          # ← Your DB name from Step 2
DB_USER=site_user          # ← Your DB user
DB_PASS=strong_password    # ← Your DB password

# ─── Application ──────────────────────────────
APP_ENV=production
APP_DEBUG=false            # ← MUST BE false in production!
APP_URL=https://site.com   # ← Your actual domain (no trailing slash)

# ─── SMTP Email ───────────────────────────────
MAIL_HOST=://gmail.com   # ← Or your email service provider
MAIL_PORT=587
MAIL_USER=your@gmail.com
MAIL_PASS=app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@site.com
MAIL_FROM_NAME="Store Name"
```

> **Note:** Email settings can also be configured later directly in the admin panel under **Settings → Email**. Values set in the admin panel always take priority over `.env`.

---

## Step 4 — Folder Permissions

The `storage/` folder and all its subdirectories must be writable by the web server:

```bash
chmod -R 755 storage/
chmod -R 755 public/uploads/
```

If your hosting environment requires 777 permissions:
```bash
chmod -R 777 storage/
chmod -R 777 public/uploads/
```

Minimum required writable folders:
```
storage/cache/
storage/logs/
storage/backups/
storage/local_updates/
storage/temp/
public/uploads/
```

---

## Step 5 — Checking .htaccess

The `public/.htaccess` file is already included and configured properly. Ensure that `mod_rewrite` is enabled on your hosting server:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

If your website returns a **404 error on all pages except the homepage**, `mod_rewrite` is disabled or `AllowOverride None` is set in the Apache configuration. Please contact your hosting support.

---

## Step 6 — First Authorization

Open your website in a browser and proceed with **Registration**. These credentials will become your Administrator account details:

* **Email**
* **Password**

---

## Step 7 — Basic Admin Panel Configuration

Once logged in, navigate to **Settings → General** and fill in the following:

| Field | What to enter |
|---|---|
| Store Name | Name of your online store |
| Store URL | `https://site.com` (must match `.env APP_URL`) |
| Contact Email | Support email address (displayed in the footer) |
| Contact Phone | Store phone number |
| Store Address | Physical address (or leave empty) |
| Default Language | Ukrainian or English |
| Active Theme | Choose your storefront appearance |

---

## Step 8 — Setting Up Shipping and Payment Methods

Navigate to **Settings → Shipping and Payments**:
* Activate the required shipping methods (e.g., Nova Poshta, Self-pickup).
* Activate and configure payment gateways (e.g., for LiqPay, enter the Public Key and Private Key from your LiqPay merchant dashboard).

---

## All set! ✅

Open `https://site.com` — your store should now be fully operational.

---

## Appendix A — If Document Root Cannot Be Changed

If your hosting provider does not allow changing the Document Root and your domain points directly to the project root (where `app/`, `config/`, etc. reside), add a `.htaccess` file directly to the **project root** (not inside `public/`) with the following rules:

```apache
RewriteEngine On
RewriteRule ^$ public/ [L]
RewriteRule ^(.*)$ public/$1 [L]
```

> **Warning:** This setup is less secure because the project root directory becomes accessible via the web server. Whenever possible, always configure the Document Root to point to `public/`.

---

## Appendix B — Nginx Configuration (Alternative to Apache)

If your server runs on Nginx, add the following configuration block inside your `server {}` block:

```nginx
root /home/user/://site.compublic;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}

location ~ /\. {
    deny all;
}
```

---

## Troubleshooting

| Symptom | Cause | Solution |
|---|---|---|
| White screen / 500 Error | PHP Error | Temporarily set `APP_DEBUG=true` in `.env`, check `storage/logs/` |
| 404 on all inner pages | `mod_rewrite` is disabled | Contact your hosting technical support |
| "Table not found" | Database was not imported | Repeat the Step 2 database import process |
| Emails are not sending | Incorrect SMTP credentials | Verify settings in Admin Panel → Settings → Email |
| DB Connection Error | Incorrect configuration in `.env` | Double-check `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` |

---

If you need any adjustments, let me know:
* Do you want to add **specific configurations** for other payment gateways?
* Should we expand the **troubleshooting** section with more server errors?
* Do you need an updated **Deployment script** (like a Bash script) to automate this setup?

