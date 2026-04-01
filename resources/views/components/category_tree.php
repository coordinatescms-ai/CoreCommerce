<?php
/**
 * Компонент для відображення дерева категорій
 * 
 * Змінні:
 * - $categories: масив категорій
 * - $currentCategoryId: ID поточної категорії (опціонально)
 * - $maxDepth: максимальна глибина відображення (за замовчуванням 3)
 */

$maxDepth = $maxDepth ?? 3;
$currentCategoryId = $currentCategoryId ?? null;

function renderCategoryTree($categories, $currentCategoryId = null, $depth = 0, $maxDepth = 3) {
    if ($depth > $maxDepth || empty($categories)) {
        return;
    }
    
    echo '<ul class="category-list' . ($depth === 0 ? ' category-list-root' : ' category-list-nested') . '">';
    
    foreach ($categories as $category) {
        $isActive = $currentCategoryId === $category['id'] ? 'active' : '';
        $hasChildren = !empty($category['children']);
        
        echo '<li class="category-item ' . $isActive . '">';
        echo '<a href="/category/' . htmlspecialchars($category['slug']) . '" class="category-link">';
        echo htmlspecialchars($category['name']);
        
        if ($hasChildren) {
            echo ' <span class="category-count">(' . count($category['children']) . ')</span>';
        }
        
        echo '</a>';
        
        if ($hasChildren && $depth < $maxDepth) {
            renderCategoryTree($category['children'], $currentCategoryId, $depth + 1, $maxDepth);
        }
        
        echo '</li>';
    }
    
    echo '</ul>';
}

?>

<div class="category-tree">
    <h3 class="category-tree-title"><?php echo __('categories'); ?></h3>
    <?php renderCategoryTree($categories, $currentCategoryId, 0, $maxDepth); ?>
</div>

<style>
.category-tree {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.category-tree-title {
    margin: 0 0 15px 0;
    font-size: 16px;
    font-weight: bold;
    color: #333;
}

.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list-nested {
    padding-left: 20px;
    margin-top: 5px;
}

.category-item {
    margin: 8px 0;
}

.category-link {
    display: inline-block;
    color: #0066cc;
    text-decoration: none;
    padding: 5px 0;
    transition: color 0.2s;
}

.category-link:hover {
    color: #0052a3;
    text-decoration: underline;
}

.category-item.active > .category-link {
    color: #333;
    font-weight: bold;
    text-decoration: underline;
}

.category-count {
    font-size: 12px;
    color: #999;
    margin-left: 5px;
}
</style>
