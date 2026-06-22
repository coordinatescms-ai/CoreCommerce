<?php

namespace App\Services;

use App\Models\Setting;
use App\Core\Database\DB;

/**
 * SeoService — централізована логіка SEO для всіх типів сторінок.
 *
 * Пріоритет для кожного поля:
 *   1. Запис у таблиці seo_settings (entity_type + entity_id)
 *   2. Поле meta_* безпосередньо в таблиці сутності (products, categories, pages)
 *   3. Шаблон з налаштувань магазину (seo_{type}_title_template і т.д.)
 *   4. Глобальний fallback (site_name / site_description)
 *
 * Доступні маски шаблонів:
 *   {name}      — назва товару / категорії / сторінки
 *   {price}     — ціна товару
 *   {category}  — назва категорії товару
 *   {shop_name} — назва магазину
 *   {slug}      — slug сутності
 */
class SeoService
{
    // ── Публічні фабричні методи ─────────────────────────────────────────────

    /**
     * SEO для сторінки товару.
     *
     * @param array      $product  Рядок з таблиці products
     * @param array|null $category Рядок з таблиці categories (необов'язково)
     */
    public static function forProduct(array $product, ?array $category = null): array
    {
        $fromDb = self::fromSeoTable('product', (int) $product['id']);

        $title = ($fromDb['title'] ?? null)
            ?? self::notEmpty($product['meta_title'] ?? '')
            ?? self::applyTemplate(
                (string) Setting::get('seo_title_template', ''),
                $product,
                $category
            )
            ?? self::shopName();

        $description = ($fromDb['description'] ?? null)
            ?? self::notEmpty($product['meta_description'] ?? '')
            ?? self::applyTemplate(
                (string) Setting::get('seo_desc_template', ''),
                $product,
                $category
            )
            ?? self::shopDescription();

        $keywords = ($fromDb['keywords'] ?? null)
            ?? self::notEmpty($product['meta_keywords'] ?? '')
            ?? '';

        return self::buildResult($title, $description, $keywords, $fromDb, [
            'url'   => '/products/' . ($product['slug'] ?? ''),
            'image' => !empty($product['image']) ? '/uploads/' . $product['image'] : null,
            'name'  => $product['name'] ?? '',
        ]);
    }

    /**
     * SEO для сторінки категорії.
     *
     * @param array $category Рядок з таблиці categories
     */
    public static function forCategory(array $category): array
    {
        $fromDb = self::fromSeoTable('category', (int) $category['id']);

        $title = ($fromDb['title'] ?? null)
            ?? self::notEmpty($category['meta_title'] ?? '')
            ?? self::applyTemplate(
                (string) Setting::get('seo_category_title_template', ''),
                $category,
                null
            )
            ?? ($category['name'] ?? self::shopName());

        $description = ($fromDb['description'] ?? null)
            ?? self::notEmpty($category['meta_description'] ?? '')
            ?? self::applyTemplate(
                (string) Setting::get('seo_category_desc_template', ''),
                $category,
                null
            )
            ?? self::shopDescription();

        $keywords = ($fromDb['keywords'] ?? null)
            ?? self::notEmpty($category['meta_keywords'] ?? '')
            ?? '';

        return self::buildResult($title, $description, $keywords, $fromDb, [
            'url'   => '/category/' . ltrim($category['path'] ?? $category['slug'], '/'),
            'image' => !empty($category['image']) ? '/uploads/' . $category['image'] : null,
            'name'  => $category['name'] ?? '',
        ]);
    }

    /**
     * SEO для статичної сторінки (Page).
     *
     * @param array $page Рядок з таблиці pages
     */
    public static function forPage(array $page): array
    {
        $fromDb = self::fromSeoTable('page', (int) $page['id']);

        $title = ($fromDb['title'] ?? null)
            ?? self::notEmpty($page['meta_title'] ?? '')
            ?? self::applyTemplate(
                (string) Setting::get('seo_page_title_template', ''),
                $page,
                null
            )
            ?? ($page['title'] ?? self::shopName());

        $description = ($fromDb['description'] ?? null)
            ?? self::notEmpty($page['meta_description'] ?? '')
            ?? self::applyTemplate(
                (string) Setting::get('seo_page_desc_template', ''),
                $page,
                null
            )
            ?? self::shopDescription();

        $keywords = ($fromDb['keywords'] ?? null)
            ?? self::notEmpty($page['meta_keywords'] ?? '')
            ?? '';

        return self::buildResult($title, $description, $keywords, $fromDb, [
            'url'  => '/pages/' . ($page['slug'] ?? ''),
            'name' => $page['title'] ?? '',
        ]);
    }

    /**
     * SEO для головної сторінки.
     */
    public static function forHome(): array
    {
        $title       = (string) Setting::get('seo_home_title', '')
            ?: self::shopName();
        $description = (string) Setting::get('seo_home_description', '')
            ?: self::shopDescription();
        $keywords    = (string) Setting::get('seo_home_keywords', '');

        return self::buildResult($title, $description, $keywords, [], [
            'url'  => '/',
            'name' => self::shopName(),
        ]);
    }

    /**
     * SEO для довільної системної сторінки (кошик, пошук, чекаут тощо).
     *
     * @param string $titleKey  Ключ перекладу або готовий рядок
     * @param string $url       URL сторінки
     */
    public static function forSystem(string $titleKey, string $url = ''): array
    {
        $title = __($titleKey) !== $titleKey ? __($titleKey) : $titleKey;
        $title = $title . ' — ' . self::shopName();

        return self::buildResult($title, self::shopDescription(), '', [], [
            'url'  => $url,
            'name' => $title,
        ]);
    }

    // ── Приватні хелпери ─────────────────────────────────────────────────────

    /**
     * Отримати запис з таблиці seo_settings.
     * Повертає масив або null якщо запис не знайдено.
     */
    private static function fromSeoTable(string $type, int $id): ?array
    {
        try {
            $row = DB::query(
                'SELECT * FROM seo_settings WHERE entity_type = ? AND entity_id = ? LIMIT 1',
                [$type, $id]
            )->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            // Повертаємо тільки непорожні поля
            return array_filter($row, fn($v) => $v !== null && $v !== '');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Застосувати маски шаблону.
     *
     * Маски: {name}, {price}, {category}, {shop_name}, {slug}
     */
    private static function applyTemplate(string $template, array $entity, ?array $category): ?string
    {
        $template = trim($template);
        if ($template === '') {
            return null;
        }

        $result = strtr($template, [
            '{name}'      => $entity['name'] ?? $entity['title'] ?? '',
            '{price}'     => isset($entity['price']) ? number_format((float) $entity['price'], 2, '.', '') : '',
            '{category}'  => $category['name'] ?? '',
            '{shop_name}' => self::shopName(),
            '{slug}'      => $entity['slug'] ?? '',
        ]);

        return trim($result) !== '' ? trim($result) : null;
    }

    /**
     * Зібрати фінальний SEO-масив що передається у View.
     *
     * @param array $fromDb   Рядок з seo_settings (може бути порожнім)
     * @param array $defaults url, image, name для OG-тегів
     */
    private static function buildResult(
        string $title,
        string $description,
        string $keywords,
        ?array $fromDb,
        array  $defaults
    ): array {
        $fromDb    = $fromDb ?? [];
        $shopName  = self::shopName();
        $siteUrl   = rtrim((string) Setting::get('site_url', ''), '/');

        $ogTitle       = $fromDb['og_title']       ?? $title;
        $ogDescription = $fromDb['og_description'] ?? $description;
        $ogImage       = $fromDb['og_image']
            ?? (isset($defaults['image']) ? $siteUrl . $defaults['image'] : '');
        $canonical     = $fromDb['canonical_url']
            ?? ($siteUrl . ($defaults['url'] ?? ''));
        $robots        = $fromDb['robots_meta'] ?? 'index,follow';

        return [
            // Базові
            'meta_title'       => $title,
            'meta_description' => $description,
            'meta_keywords'    => $keywords,
            // Open Graph
            'og_title'         => $ogTitle,
            'og_description'   => $ogDescription,
            'og_image'         => $ogImage,
            'og_type'          => 'website',
            // Технічні
            'canonical'        => $canonical,
            'robots'           => $robots,
            'shop_name'        => $shopName,
        ];
    }

    private static function notEmpty(string $value): ?string
    {
        $v = trim($value);
        return $v !== '' ? $v : null;
    }

    private static function shopName(): string
    {
        return (string) Setting::get('site_name', 'My Shop');
    }

    private static function shopDescription(): string
    {
        return (string) Setting::get('site_description', '');
    }
}
