<?php
use App\Core\Localization\LocalizationManager;
use App\Models\Setting;

/**
 * Отримати переклад.
 *
 * @param  string $key      'my_key' або 'namespace::my_key'
 * @param  array  $replace  Параметри підстановки: ['name' => 'Іван'] → :name = Іван
 */
function __(string $key, array $replace = []): string
{
    return LocalizationManager::translate($key, $replace);
}

/**
 * Псевдонім __() для зручності.
 */
function trans(string $key, array $replace = []): string
{
    return LocalizationManager::translate($key, $replace);
}

/**
 * Отримати поточну мову
 */
function get_current_language()
{
    return LocalizationManager::getCurrentLanguage();
}

/**
 * Отримати список підтримуваних мов
 */
function get_supported_languages()
{
    return LocalizationManager::getSupportedLanguages();
}

function get_setting($key, $default = null)
{
    return Setting::get($key, $default);
}

/**
 * Відформатувати ціну з символом активної валюти.
 * Використовується на всіх сторінках замість захардкодених "грн" / "₴".
 *
 * @param  float|int|string $amount
 * @param  int              $decimals
 * @return string   наприклад "1 250,00 $"
 */
function format_price($amount, int $decimals = 2): string
{
    static $symbol = null;
    if ($symbol === null) {
        // Кешуємо на час запиту — один запит до БД
        $row = \App\Core\Database\DB::query(
            'SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1'
        )->fetch(\PDO::FETCH_ASSOC);
        $symbol = $row ? $row['symbol'] : '₴';
    }
    return number_format((float)$amount, $decimals, ',', ' ') . ' ' . $symbol;
}

function product_image_variant_path(?string $path, string $variant = 'original'): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    $allowedVariants = ['original', 'medium', 'thumb'];
    if (!in_array($variant, $allowedVariants, true)) {
        $variant = 'original';
    }

    if (strpos($path, '/uploads/products/gallery/') !== 0) {
        return $path;
    }

    $normalized = preg_replace('#^/uploads/products/gallery/(original|medium|thumb)/#', '/uploads/products/gallery/' . $variant . '/', $path);
    return is_string($normalized) ? $normalized : $path;
}


function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 99): void
{
    \App\Core\Plugin\PluginManager::getInstance()->addAction($hook, $callback, $priority, $acceptedArgs);
}

function do_action(string $hook, mixed ...$args): void
{
    \App\Core\Plugin\PluginManager::getInstance()->doAction($hook, ...$args);
}

function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 99): void
{
    \App\Core\Plugin\PluginManager::getInstance()->addFilter($hook, $callback, $priority, $acceptedArgs);
}

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    return \App\Core\Plugin\PluginManager::getInstance()->applyFilters($hook, $value, ...$args);
}

/**
 * Застосовує фільтр ядра 'product.price' до кожного товару в списку.
 *
 * Раніше цей фільтр викликався лише на сторінці конкретного товару
 * (ProductController::show()) — списки (категорія, головна, пошук,
 * улюблені) показували "сиру" ціну з БД напряму. Через це плагін
 * акційних цін (чи будь-який інший, що змінює ціну через цей фільтр)
 * показував би знижку лише на сторінці товару, а в категоріях/на
 * головній — ні. Ця функція — єдина точка, яку викликають усі
 * контролери списків товарів, щоб таких прогалин більше не було.
 *
 * @param array<int, array<string, mixed>> $products
 * @return array<int, array<string, mixed>>
 */
function apply_product_price_filter(array $products): array
{
    foreach ($products as &$product) {
        if (is_array($product) && array_key_exists('price', $product)) {
            $product['price'] = apply_filters('product.price', (float) $product['price'], $product);
        }
    }
    unset($product);

    return $products;
}

/**
 * Рендерить HTML ціни товару (використовується і на сторінці товару,
 * і в усіх списках товарів — категорія, головна, пошук, улюблені).
 *
 * Викликати ПІСЛЯ apply_product_price_filter() / фільтра 'product.price',
 * щоб $product['price'] вже містив фінальну ціну.
 *
 * Плагіни (напр. акційна ціна) можуть повністю замінити розмітку через
 * фільтр 'product.price.html' — напр. додати перекреслену стару ціну
 * й бейдж "-20%", маючи доступ до $product (щоб самим підвантажити
 * оригінальну ціну з products.price чи своєї таблиці знижок).
 */
function render_product_price(array $product): string
{
    $html = '<strong>' . format_price((float) ($product['price'] ?? 0)) . '</strong>';

    return (string) apply_filters('product.price.html', $html, $product);
}

function normalize_phone_mask(string $mask): string
{
    return preg_replace('/\s+/', ' ', trim($mask)) ?? '';
}

/**
 * Підвантажує залишок на складі для списку товарів ОДНИМ додатковим
 * запитом (без N+1) і не чіпаючи наявні SQL-запити в контролерах/сервісах
 * (ProductFilterService, ProductController::index, HomeController тощо).
 * Викликати перед render_stock_badge() для товарів зі списків
 * (каталог, категорія, головна, пошук). Сторінка одного товару (show())
 * вже сама рахує $product['stock'] окремим запитом — там цей хелпер
 * не потрібен.
 *
 * @param array<int, array<string, mixed>> $products
 * @return array<int, array<string, mixed>>
 */
function attach_stock_status(array $products): array
{
    $skus = [];
    foreach ($products as $product) {
        $sku = trim((string) ($product['sku'] ?? ''));
        if ($sku !== '') {
            $skus[$sku] = true;
        }
    }

    if (empty($skus)) {
        return $products;
    }

    $skuList = array_keys($skus);
    $placeholders = implode(',', array_fill(0, count($skuList), '?'));

    try {
        // COLLATE обов'язково: products.sku і product_stocks.sku можуть мати
        // різні collation за замовчуванням (як і в усіх інших місцях проєкту,
        // де ці дві таблиці порівнюються — див. Product.php, Cart.php).
        // Без цього порівняння мовчки не знаходить жодного збігу на бойовій
        // MySQL 8.0 (хоча в тестовому середовищі з вирівняними collation
        // цього не було видно) — бейдж просто ніколи не з'являвся у списках.
        $rows = \App\Core\Database\DB::query(
            "SELECT sku, COALESCE(quantity, 0) AS quantity FROM product_stocks
             WHERE option_id IS NULL AND sku COLLATE utf8mb4_general_ci IN ({$placeholders})",
            $skuList
        )->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) {
        error_log('attach_stock_status() error: ' . $e->getMessage());
        return $products;
    }

    $stockBySku = [];
    foreach ($rows as $row) {
        $stockBySku[(string) $row['sku']] = (int) $row['quantity'];
    }

    foreach ($products as &$product) {
        $sku = trim((string) ($product['sku'] ?? ''));
        if ($sku !== '') {
            // COALESCE(quantity, 0): якщо для товару взагалі немає рядка в
            // product_stocks (а не просто quantity=0) — це так само рахується
            // як 0 на складі, так само як і всюди в проєкті-Cart::add(),
            // ProductController::show(), Product::allWithCategory().
            // Раніше тут пропускався товар без рядка, тому бейдж мовчки не
            // з'являвся саме для товарів, у яких стоку взагалі не заведено.
            $product['stock_quantity'] = $stockBySku[$sku] ?? 0;
        }
    }
    unset($product);

    return $products;
}

/**
 * Рендерить бейдж "Немає в наявності" для картки товару в списках
 * (каталог, категорія, головна, пошук) і на сторінці товару.
 *
 * Дивиться спочатку на 'stock_quantity' (заповнюється attach_stock_status()
 * для списків), потім на 'stock' (заповнюється ProductController::show()
 * для сторінки одного товару). Якщо жодного з ключів немає — вважаємо,
 * що дані про залишок недоступні, і НЕ показуємо бейдж (щоб помилково
 * не приховати товар, який насправді є в наявності).
 */
function render_stock_badge(array $product): string
{
    if (array_key_exists('stock_quantity', $product)) {
        $quantity = (int) $product['stock_quantity'];
    } elseif (array_key_exists('stock', $product)) {
        $quantity = (int) $product['stock'];
    } else {
        return '';
    }

    if ($quantity > 0) {
        return '';
    }

    return '<span class="badge-out-of-stock" style="display:inline-block;padding:0.25rem 0.6rem;background:#fee2e2;color:#b91c1c;border-radius:0.4rem;font-size:0.85rem;font-weight:600;">'
        . htmlspecialchars(__('out_of_stock'))
        . '</span>';
}

function is_valid_phone_mask(string $mask): bool
{
    if ($mask === '' || mb_strlen($mask) > 40) {
        return false;
    }

    if (substr_count($mask, '#') < 7) {
        return false;
    }

    return (bool) preg_match('/^[\d\#\+\(\)\-\s]+$/u', $mask);
}

function phone_mask_to_regex(string $mask): string
{
    $escaped = preg_quote($mask, '/');
    return '/^' . str_replace('\\#', '\\d', $escaped) . '$/u';
}

function is_phone_matching_mask(string $phone, string $mask): bool
{
    if (!is_valid_phone_mask($mask)) {
        return false;
    }

    return (bool) preg_match(phone_mask_to_regex($mask), trim($phone));
}

/**
 * Повернути абсолютний шлях до файлу views.
 * Напр.: view_path('components/breadcrumb') → /path/to/resources/views/components/breadcrumb.php
 */
function view_path(string $view): string
{
    return dirname(__DIR__) . '/resources/views/' . ltrim($view, '/') . '.php';
}
