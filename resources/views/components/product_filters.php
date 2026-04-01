<?php
/**
 * Компонент для відображення фільтрів товарів
 * 
 * Змінні:
 * - $filterOptions: масив доступних опцій фільтрів
 * - $priceRange: діапазон цін ['min' => ..., 'max' => ...]
 * - $currentFilters: поточні активні фільтри
 * - $categorySlug: slug категорії
 */

$filterOptions = $filterOptions ?? [];
$priceRange = $priceRange ?? ['min' => 0, 'max' => 1000];
$currentFilters = $currentFilters ?? [];
$categorySlug = $categorySlug ?? '';

?>

<div class="product-filters">
    <h3 class="filters-title"><?php echo __('filters'); ?></h3>
    
    <form id="filter-form" method="GET" class="filter-form">
        
        <!-- Фільтр за ціною -->
        <div class="filter-group filter-price">
            <h4 class="filter-group-title"><?php echo __('price'); ?></h4>
            
            <div class="price-range-inputs">
                <input type="number" 
                       name="min_price" 
                       class="price-input" 
                       placeholder="<?php echo __('min'); ?>" 
                       value="<?php echo htmlspecialchars($currentFilters['min_price'] ?? ''); ?>"
                       min="<?php echo $priceRange['min']; ?>"
                       max="<?php echo $priceRange['max']; ?>">
                
                <span class="price-separator">-</span>
                
                <input type="number" 
                       name="max_price" 
                       class="price-input" 
                       placeholder="<?php echo __('max'); ?>" 
                       value="<?php echo htmlspecialchars($currentFilters['max_price'] ?? ''); ?>"
                       min="<?php echo $priceRange['min']; ?>"
                       max="<?php echo $priceRange['max']; ?>">
            </div>
        </div>

        <!-- Фільтри за атрибутами -->
        <?php foreach ($filterOptions as $attributeId => $attribute): ?>
            <div class="filter-group filter-attribute">
                <h4 class="filter-group-title"><?php echo htmlspecialchars($attribute['name']); ?></h4>
                
                <div class="filter-options">
                    <?php foreach ($attribute['options'] as $option): ?>
                        <?php 
                            $inputName = "attr_{$attributeId}[]";
                            $isChecked = false;
                            
                            if (!empty($currentFilters['attributes'][$attributeId])) {
                                if (is_array($currentFilters['attributes'][$attributeId])) {
                                    $isChecked = in_array($option['value'], $currentFilters['attributes'][$attributeId]);
                                } else {
                                    $isChecked = $currentFilters['attributes'][$attributeId] === $option['value'];
                                }
                            }
                        ?>
                        
                        <label class="filter-option">
                            <input type="checkbox" 
                                   name="<?php echo $inputName; ?>" 
                                   value="<?php echo htmlspecialchars($option['value']); ?>"
                                   <?php echo $isChecked ? 'checked' : ''; ?>>
                            
                            <?php if ($attribute['type'] === 'color' && $option['color']): ?>
                                <span class="color-swatch" style="background-color: <?php echo htmlspecialchars($option['color']); ?>;"></span>
                            <?php endif; ?>
                            
                            <span class="option-label">
                                <?php echo htmlspecialchars($option['label']); ?>
                                <span class="option-count">(<?php echo $option['count']; ?>)</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Кнопки -->
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><?php echo __('apply_filters'); ?></button>
            <a href="/category/<?php echo htmlspecialchars($categorySlug); ?>" class="btn btn-secondary"><?php echo __('clear_filters'); ?></a>
        </div>
    </form>
</div>

<style>
.product-filters {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.filters-title {
    margin: 0 0 15px 0;
    font-size: 16px;
    font-weight: bold;
    color: #333;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filter-group {
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 15px;
}

.filter-group:last-of-type {
    border-bottom: none;
}

.filter-group-title {
    margin: 0 0 10px 0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.price-range-inputs {
    display: flex;
    gap: 10px;
    align-items: center;
}

.price-input {
    flex: 1;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.price-separator {
    color: #999;
}

.filter-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.filter-option input[type="checkbox"] {
    cursor: pointer;
}

.color-swatch {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 3px;
    border: 1px solid #ddd;
}

.option-label {
    font-size: 14px;
    color: #333;
    flex: 1;
}

.option-count {
    font-size: 12px;
    color: #999;
    margin-left: 5px;
}

.filter-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn {
    flex: 1;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: background-color 0.2s;
}

.btn-primary {
    background-color: #0066cc;
    color: white;
}

.btn-primary:hover {
    background-color: #0052a3;
}

.btn-secondary {
    background-color: #e0e0e0;
    color: #333;
}

.btn-secondary:hover {
    background-color: #d0d0d0;
}
</style>
