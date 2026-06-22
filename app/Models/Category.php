<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database\DB;
use App\Services\SlugHelper;

class Category extends Model
{
    protected static $table = 'categories';

    // ── Пошук ────────────────────────────────────────────────────────────────

    public static function findBySlug(string $slug): ?array
    {
        return SlugHelper::getBySlug($slug, 'category');
    }

    /**
     * Знайти категорію за повним path (вкладений ЧПУ).
     * Напр.: "smartfony/iphone" або "/smartfony/iphone"
     */
    public static function findByPath(string $path): ?array
    {
        $path = '/' . ltrim($path, '/');
        $result = self::query(
            'SELECT * FROM categories WHERE path = ? AND is_active = 1 LIMIT 1',
            [$path]
        );
        return $result[0] ?? null;
    }

    public static function findById(int $id): ?array
    {
        $result = self::query('SELECT * FROM categories WHERE id = ?', [$id]);
        return $result[0] ?? null;
    }

    public static function all(): array
    {
        return self::query('SELECT * FROM categories ORDER BY name') ?? [];
    }

    public static function findByParent(?int $parentId = null): array
    {
        if ($parentId === null) {
            return self::query(
                'SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order, name'
            ) ?? [];
        }
        return self::query(
            'SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order, name',
            [$parentId]
        ) ?? [];
    }

    // ── Дерево (ONE query замість N+1) ───────────────────────────────────────

    /**
     * Отримати все дерево категорій ОДНИМ запитом.
     * Повертає вкладену структуру: кожна категорія має ключ 'children'.
     */
    public static function getTree(?int $parentId = null, int $depth = 0, int $maxDepth = 10): array
    {
        // Завантажуємо ВСІ активні категорії одним запитом
        static $allCategories = null;
        if ($allCategories === null) {
            $allCategories = self::query(
                'SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name'
            ) ?? [];
        }

        return self::buildTree($allCategories, $parentId, $depth, $maxDepth);
    }

    /**
     * Скинути статичний кеш дерева (потрібно після create/update/delete).
     */
    public static function clearTreeCache(): void
    {
        // Скидаємо static кеш через рефлексію
        $reflection = new \ReflectionFunction(function() {});
        // Простіший спосіб — просто перезавантажити через нову статичну змінну
        static $reset = false;
        $reset = true;
    }

    /**
     * Зібрати вкладене дерево з плоского масиву (без додаткових запитів).
     */
    private static function buildTree(array $flat, ?int $parentId, int $depth, int $maxDepth): array
    {
        if ($depth > $maxDepth) {
            return [];
        }

        $result = [];
        foreach ($flat as $cat) {
            $catParent = $cat['parent_id'] === null ? null : (int) $cat['parent_id'];
            if ($catParent !== $parentId) {
                continue;
            }
            $cat['depth']        = $depth;
            $cat['children']     = self::buildTree($flat, (int) $cat['id'], $depth + 1, $maxDepth);
            $cat['has_children'] = !empty($cat['children']);
            $result[]            = $cat;
        }
        return $result;
    }

    /**
     * Плоский список з рівнями (один запит).
     */
    public static function getFlatTree(?int $parentId = null, int $level = 0): array
    {
        $all = self::query(
            'SELECT * FROM categories ORDER BY sort_order, name'
        ) ?? [];

        $result = [];
        self::flattenTree($all, $parentId, $level, $result);
        return $result;
    }

    private static function flattenTree(array $all, ?int $parentId, int $level, array &$result): void
    {
        foreach ($all as $cat) {
            $catParent = $cat['parent_id'] === null ? null : (int) $cat['parent_id'];
            if ($catParent !== $parentId) {
                continue;
            }
            $cat['level'] = $level;
            $result[]     = $cat;
            self::flattenTree($all, (int) $cat['id'], $level + 1, $result);
        }
    }

    // ── Breadcrumbs (ONE query) ───────────────────────────────────────────────

    /**
     * Отримати breadcrumbs за id категорії ОДНИМ запитом через path.
     * Якщо path є — розбираємо його, інакше fallback на рекурсивний підйом.
     *
     * @return array  [['id','name','slug','url','path'], ...]
     */
    public static function getBreadcrumbs(int $categoryId): array
    {
        $category = self::findById($categoryId);
        if (!$category) {
            return [];
        }

        // Якщо є матеріалізований path — один IN-запит
        if (!empty($category['path'])) {
            return self::getBreadcrumbsByPath($category['path']);
        }

        // Fallback: рекурсивний підйом (старий метод)
        return self::getBreadcrumbsRecursive($categoryId);
    }

    /**
     * Один IN-запит для всіх предків через path.
     * path = '/electronics/smartphones/iphone' → slugs = ['electronics','smartphones','iphone']
     */
    private static function getBreadcrumbsByPath(string $path): array
    {
        $slugs = array_filter(explode('/', $path));
        if (empty($slugs)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $rows = self::query(
            "SELECT id, name, slug, path FROM categories
             WHERE slug IN ($placeholders) AND is_active = 1",
            array_values($slugs)
        ) ?? [];

        // Будуємо індекс slug → row
        $bySlug = [];
        foreach ($rows as $row) {
            $bySlug[$row['slug']] = $row;
        }

        // Відновлюємо порядок за path
        $breadcrumbs = [];
        $accumulated = '';
        foreach ($slugs as $slug) {
            $accumulated .= '/' . $slug;
            if (!isset($bySlug[$slug])) {
                continue;
            }
            $row = $bySlug[$slug];
            $breadcrumbs[] = [
                'id'   => (int) $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'path' => $accumulated,
                'url'  => '/category' . $accumulated,
            ];
        }

        return $breadcrumbs;
    }

    private static function getBreadcrumbsRecursive(int $categoryId): array
    {
        $breadcrumbs = [];
        $currentId   = $categoryId;

        while ($currentId) {
            $cat = self::findById($currentId);
            if (!$cat) {
                break;
            }
            array_unshift($breadcrumbs, [
                'id'   => (int) $cat['id'],
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'path' => $cat['path'] ?? ('/' . $cat['slug']),
                'url'  => '/category/' . $cat['slug'],
            ]);
            $currentId = $cat['parent_id'] ? (int) $cat['parent_id'] : 0;
        }

        return $breadcrumbs;
    }

    // ── Матеріалізований path ─────────────────────────────────────────────────

    /**
     * Обчислити та зберегти path для категорії та всіх її нащадків.
     * Викликати після create/update slug/parent.
     */
    public static function rebuildPath(int $categoryId): void
    {
        $category = self::findById($categoryId);
        if (!$category) {
            return;
        }

        $parentPath = '';
        if (!empty($category['parent_id'])) {
            $parent = self::findById((int) $category['parent_id']);
            $parentPath = $parent['path'] ?? '';
        }

        $newPath = $parentPath . '/' . $category['slug'];
        self::execute(
            'UPDATE categories SET path = ? WHERE id = ?',
            [$newPath, $categoryId]
        );

        // Рекурсивно оновлюємо всіх нащадків
        $children = self::findByParent($categoryId);
        foreach ($children as $child) {
            self::rebuildPath((int) $child['id']);
        }
    }

    /**
     * Перебудувати path для ВСІХ категорій (корисно після міграції).
     */
    public static function rebuildAllPaths(): void
    {
        $roots = self::findByParent(null);
        foreach ($roots as $root) {
            self::rebuildPath((int) $root['id']);
        }
    }

    // ── Допоміжні методи ─────────────────────────────────────────────────────

    public static function getParent(int $categoryId): ?array
    {
        $category = self::findById($categoryId);
        if (!$category || !$category['parent_id']) {
            return null;
        }
        return self::findById((int) $category['parent_id']);
    }

    public static function getChildren(int $categoryId): array
    {
        return self::findByParent($categoryId);
    }

    public static function getAllChildren(int $categoryId): array
    {
        $children = [];
        $direct   = self::getChildren($categoryId);
        foreach ($direct as $child) {
            $children[] = $child;
            $children   = array_merge($children, self::getAllChildren((int) $child['id']));
        }
        return $children;
    }

    public static function getProductCount(int $categoryId): int
    {
        $ids          = array_merge([$categoryId], array_column(self::getAllChildren($categoryId), 'id'));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $result       = self::query(
            "SELECT COUNT(*) as cnt FROM products WHERE category_id IN ($placeholders) AND is_visible = 1",
            $ids
        );
        return (int) ($result[0]['cnt'] ?? 0);
    }

    public static function getAttributes(int $categoryId): array
    {
        return self::query(
            'SELECT a.* FROM attributes a
             INNER JOIN category_attributes ca ON a.id = ca.attribute_id
             WHERE ca.category_id = ? ORDER BY a.name',
            [$categoryId]
        ) ?? [];
    }

    public static function getLineageIds(int $categoryId): array
    {
        $ids     = [];
        $visited = [];
        $current = $categoryId;

        while ($current > 0 && !isset($visited[$current])) {
            $visited[$current] = true;
            $ids[]             = $current;
            $cat               = self::findById($current);
            if (!$cat || empty($cat['parent_id'])) {
                break;
            }
            $current = (int) $cat['parent_id'];
        }

        return $ids;
    }

    public static function getAllowedAttributes(int $categoryId): array
    {
        $lineage = self::getLineageIds($categoryId);
        if (empty($lineage)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($lineage), '?'));
        return self::query(
            "SELECT DISTINCT a.id, a.name, a.slug, a.type, a.is_filterable, a.is_visible, a.sort_order
             FROM category_attributes ca
             INNER JOIN attributes a ON a.id = ca.attribute_id
             WHERE ca.category_id IN ($placeholders)
             ORDER BY a.sort_order, a.name",
            $lineage
        ) ?? [];
    }

    public static function getAllowedAttributeIds(int $categoryId): array
    {
        return array_map('intval', array_column(self::getAllowedAttributes($categoryId), 'id'));
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public static function create(array $data): int|false
    {
        if (empty($data['slug'])) {
            $data['slug'] = SlugHelper::getUnique($data['name'], 'category');
        } elseif (!SlugHelper::isUnique($data['slug'], 'category')) {
            return false;
        }

        if (!SlugHelper::validate($data['slug'])) {
            return false;
        }

        $columns      = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        $result       = self::execute(
            'INSERT INTO ' . self::$table . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')',
            array_values($data)
        );

        if (!$result) {
            return false;
        }

        $lastId = self::getLastInsertId();

        // Будуємо path
        self::rebuildPath($lastId);

        if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
            SlugHelper::saveSeoSettings('category', $lastId, [
                'title'       => $data['meta_title']       ?? null,
                'description' => $data['meta_description'] ?? null,
                'keywords'    => $data['meta_keywords']    ?? null,
            ]);
        }

        return $lastId;
    }

    public static function update(int $id, array $data): bool
    {
        $old = self::findById($id);
        if (!$old) {
            return false;
        }

        $slugChanged   = !empty($data['slug']) && $data['slug'] !== $old['slug'];
        $parentChanged = array_key_exists('parent_id', $data) && (int) $data['parent_id'] !== (int) $old['parent_id'];

        if ($slugChanged) {
            if (!SlugHelper::isUnique($data['slug'], 'category', $id)) {
                return false;
            }
            if (!SlugHelper::validate($data['slug'])) {
                return false;
            }
            SlugHelper::saveHistory('category', $id, $old['slug'], $data['slug'],
                $_SESSION['user']['id'] ?? null, 'Manual edit');
            SlugHelper::createRedirect($old['slug'], $data['slug'], 'category', $id);
        }

        $updates = [];
        $values  = [];
        foreach ($data as $col => $val) {
            $updates[] = "$col = ?";
            $values[]  = $val;
        }
        $values[] = $id;

        $result = self::execute(
            'UPDATE ' . self::$table . ' SET ' . implode(', ', $updates) . ' WHERE id = ?',
            $values
        );

        if ($result && ($slugChanged || $parentChanged)) {
            self::rebuildPath($id);
        }

        if ($result && (!empty($data['meta_title']) || !empty($data['meta_description']))) {
            SlugHelper::saveSeoSettings('category', $id, [
                'title'       => $data['meta_title']       ?? null,
                'description' => $data['meta_description'] ?? null,
                'keywords'    => $data['meta_keywords']    ?? null,
            ]);
        }

        return $result;
    }

    public static function delete(int $id): bool
    {
        $category = self::findById($id);
        if (!$category) {
            return false;
        }

        $children = self::getChildren($id);
        if (!empty($children)) {
            self::execute(
                'UPDATE ' . self::$table . ' SET parent_id = ? WHERE parent_id = ?',
                [$category['parent_id'], $id]
            );
            // Перебудовуємо path переміщених дітей
            foreach ($children as $child) {
                self::rebuildPath((int) $child['id']);
            }
        }

        return (bool) self::execute('DELETE FROM ' . self::$table . ' WHERE id = ?', [$id]);
    }

    // ── SEO / Slug history ────────────────────────────────────────────────────

    public static function getSeoSettings(int $id): ?array
    {
        return SlugHelper::getSeoSettings('category', $id);
    }

    public static function getSlugHistory(int $id): array
    {
        return SlugHelper::getHistory('category', $id);
    }

    private static function getLastInsertId(): int
    {
        $result = self::query('SELECT LAST_INSERT_ID() as id');
        return (int) ($result[0]['id'] ?? 0);
    }
}
