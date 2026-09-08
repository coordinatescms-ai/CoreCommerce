<?php
$productName = htmlspecialchars($product['name'] ?? '');
$productPrice = isset($product['price']) ? number_format((float) $product['price'], 2, '.', ' ') : '0.00';
$productDescription = trim((string) ($product['description'] ?? ''));
$shortDescription = function_exists('mb_substr') ? mb_substr($productDescription, 0, 220) : substr($productDescription, 0, 220);
if ((function_exists('mb_strlen') ? mb_strlen($productDescription) : strlen($productDescription)) > 220) {
    $shortDescription .= '…';
}

$placeholderImage = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#64748b" font-family="Arial" font-size="36">No Image</text></svg>');
$mainImage = !empty($product['image']) ? product_image_variant_path((string) $product['image'], 'original') : $placeholderImage;
$galleryItems = $galleryImages ?? [];
$galleryImages = [];
foreach ($galleryItems as $galleryImage) {
    $path = (string) ($galleryImage['image_path'] ?? '');
    if ($path !== '') {
        $galleryImages[] = [
            'original' => product_image_variant_path($path, 'original'),
            'medium' => product_image_variant_path($path, 'medium'),
        ];
    }
}
if (empty($galleryImages)) {
    $galleryImages = [[
        'original' => $mainImage,
        'medium' => $mainImage,
    ]];
}
if (!in_array($mainImage, array_column($galleryImages, 'original'), true)) {
    array_unshift($galleryImages, [
        'original' => $mainImage,
        'medium' => $mainImage,
    ]);
}
$galleryImages = array_values($galleryImages);
$similarProducts = $similarProducts ?? [];
$categoryTree = $categoryTree ?? [];
$currentCategoryId = (int) (($category['id'] ?? 0));

$groupedSelectableAttributes = [];
foreach (($selectableAttributes ?? []) as $attribute) {
    $attributeId = (int) ($attribute['attribute_id'] ?? 0);
    $label = $attribute['attribute_name'] ?? __('attribute');
    $groupedSelectableAttributes[$attributeId] = [
        'name' => $label,
        'options' => [[
            'option_id' => (int) ($attribute['attribute_option_id'] ?? 0),
            'value' => (string) (($attribute['option_name'] ?? '') ?: ($attribute['value'] ?? '')),
            'price' => max(0, (float) ($attribute['price_modifier'] ?? 0)),
            'op' => (($attribute['price_operation'] ?? '+') === '-') ? '-' : '+',
        ]],
    ];
}

$groupedDetailAttributes = [];
foreach (($detailAttributes ?? []) as $attribute) {
    $label = $attribute['attribute_name'] ?? __('attribute');
    $groupedDetailAttributes[$label][] = $attribute['value'] ?? '';
}

if (!function_exists('renderCategorySidebarAccordion')) {
    function renderCategorySidebarAccordion(array $items, int $currentCategoryId, array $expandedIds, int $depth = 0): void
    {
        if (empty($items)) {
            return;
        }

        $padding = 12 + ($depth * 16);

        echo '<ul class="category-royal-list" role="tree">';

        foreach ($items as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            $children = $item['children'] ?? [];
            $hasChildren = !empty($children);
            $isActive = $itemId === $currentCategoryId;
            $isExpanded = $isActive || in_array($itemId, $expandedIds, true);

            echo '<li class="category-royal-item" role="treeitem" aria-expanded="' . ($isExpanded ? 'true' : 'false') . '">';
            echo '<div class="category-royal-row">';
            echo '<a href="/category/' . htmlspecialchars(ltrim((string) ($item['path'] ?? $item['slug'] ?? ''), '/')) . '" class="category-royal-link';
            echo $isActive ? ' is-active' : '';
            echo '" style="padding-left:' . $padding . 'px">';
            echo htmlspecialchars((string) ($item['name'] ?? ''));
            echo '</a>';

            if ($hasChildren) {
                echo '<button type="button" class="category-accordion-trigger"';
                echo ' data-accordion-trigger aria-label="Toggle subcategories" aria-expanded="' . ($isExpanded ? 'true' : 'false') . '">';
                echo '<svg class="category-accordion-icon ' . ($isExpanded ? 'is-open' : '') . '" viewBox="0 0 24 24" fill="none" aria-hidden="true">';
                echo '<path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>';
                echo '</svg>';
                echo '</button>';
            }

            echo '</div>';

            if ($hasChildren) {
                echo '<div class="category-accordion-panel" data-accordion-panel';
                echo $isExpanded ? ' style="max-height: 1000px; opacity:1;"' : ' style="max-height:0; opacity:.4;"';
                echo '>';
                echo '<div class="category-royal-branch">';
                renderCategorySidebarAccordion($children, $currentCategoryId, $expandedIds, $depth + 1);
                echo '</div>';
                echo '</div>';
            }

            echo '</li>';
        }

        echo '</ul>';
    }
}

$expandedCategoryIds = [];
foreach (($breadcrumbs ?? []) as $crumb) {
    if (!empty($crumb['id'])) {
        $expandedCategoryIds[] = (int) $crumb['id'];
    }
}

$isFavorite = false;
if (isset($_SESSION['user']['id'])) {
    $check = \App\Core\Database\DB::query(
        "SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?", 
        [(int)$_SESSION['user']['id'], (int)$product['id']]
    )->fetch();
    $isFavorite = (bool)$check;
}
?>

<section class="pdp" data-category-page>
    <?php
    $current    = $productName ?? ($product['name'] ?? '');
    $currentUrl = '/products/' . ($product['slug'] ?? '');
    include view_path('components/breadcrumb');
    ?>

    <!-- Image Modal -->
    <div id="image-modal" class="pdp-modal" aria-hidden="true">
        <span class="pdp-modal-close">&times;</span>
        <button type="button" class="pdp-modal-prev" aria-label="Previous image">&#10094;</button>
        <button type="button" class="pdp-modal-next" aria-label="Next image">&#10095;</button>
        <img class="pdp-modal-content" id="modal-img" alt="Product original image">
        <div id="modal-caption" class="pdp-modal-caption"></div>
    </div>

    <div class="pdp-layout">
        <aside class="pdp-sidebar">
            <section class="category-royal-card">
                <h2 class="category-royal-title">
                    <svg class="category-royal-title-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 6H20M4 12H20M4 18H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <?= __('categories') ?>
                </h2>
                <div class="category-royal-nav">
                    <?php renderCategorySidebarAccordion($categoryTree, $currentCategoryId, $expandedCategoryIds); ?>
                </div>
            </section>
        </aside>

        <div class="pdp-content">
            <div class="pdp-main-grid">
                <div class="pdp-gallery">
                    <figure class="pdp-main-image-wrap">
                        <img id="pdp-main-image" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= $productName ?>" class="pdp-main-image">
                    </figure>

                    <div class="pdp-thumbs" role="list" aria-label="Product gallery thumbnails">
                        <?php foreach ($galleryImages as $idx => $image): ?>
                            <button
                                type="button"
                                class="pdp-thumb <?= $idx === 0 ? 'is-active' : '' ?>"
                                data-pdp-thumb
                                data-image="<?= htmlspecialchars((string) ($image['original'] ?? '')) ?>"
                                aria-label="Фото <?= $idx + 1 ?>"
                            >
                                <img src="<?= htmlspecialchars((string) ($image['medium'] ?? '')) ?>" alt="<?= $productName ?> thumbnail <?= $idx + 1 ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pdp-info">
                    <h1><?= $productName ?></h1>
                    <div class="pdp-price" data-base-price="<?= htmlspecialchars((string) number_format((float) ($product['price'] ?? 0), 2, '.', '')) ?>"><?= render_product_price($product) ?></div>

                    <p class="pdp-short-description">
                        <strong><?= __('short_description') ?>:</strong>
                        <?= htmlspecialchars($shortDescription !== '' ? $shortDescription : __('no_products_found')) ?>
                    </p>

                    <div class="pdp-options" aria-label="Product options">
                        <h3><?= __('choose_options') ?></h3>
                        <?php if (!empty($groupedSelectableAttributes)): ?>
                            <?php foreach ($groupedSelectableAttributes as $attributeId => $group): ?>
                                <div class="pdp-option-group">
                                    <div class="pdp-option-label"><?= htmlspecialchars((string) ($group['name'] ?? '')) ?></div>
                                    <div class="pdp-option-values">
                                        <?php foreach (($group['options'] ?? []) as $option): ?>
                                            <label class="pdp-chip" style="display:inline-flex; align-items:center; gap:0.45rem;">
                                                <input
                                                    type="radio"
                                                    form="product-add-to-cart-form"
                                                    name="selected_option_ids[<?= (int) $attributeId ?>]"
                                                    value="<?= (int) ($option['option_id'] ?? 0) ?>"
                                                    data-option-price="<?= htmlspecialchars((string) number_format((float) ($option['price'] ?? 0), 2, '.', '')) ?>"
                                                    data-option-op="<?= htmlspecialchars((string) ($option['op'] ?? '+')) ?>"
                                                >
                                                <span><?= htmlspecialchars((string) ($option['value'] ?? '')) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" id="clear-product-options" class="pdp-options-reset" disabled>
                                <?= __('reset') ?>
                            </button>
                        <?php else: ?>
                            <div class="pdp-option-group">
                                <div class="pdp-option-label"><?= __('no_options_available') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pdp-actions">
                        <?php $outOfStock = render_stock_badge($product) !== ''; ?>
                        <form id="product-add-to-cart-form" action="/cart/add/<?= (int) $product['id'] ?>" method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?? '' ?>">
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/products') ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?= (int)($product['stock'] ?? 0) ?>" class="form-control me-2" style="width: 80px;" <?= $outOfStock ? 'disabled' : '' ?>>
                            <button type="submit" class="pdp-btn pdp-btn-primary" <?= $outOfStock ? 'disabled' : '' ?>><?= $outOfStock ? __('out_of_stock') : __('add_to_cart') ?></button>
                        </form>
                        <button type="button" 
                            class="pdp-btn pdp-btn-ghost <?= $isFavorite ? 'active' : '' ?>" 
                            id="wishlist-btn">
                            <span class="wishlist-text">
                                <?= $isFavorite ? '❤️ ' . __('wishlist') : __('wishlist') ?>
                            </span>
                            </button>
                    </div>
                    <div class="mt-2 small text-muted">
                        <?php if ($outOfStock): ?>
                            <?= render_stock_badge($product) ?>
                        <?php else: ?>
                            <?= __('in_stock') ?>: <?= (int)($product['stock'] ?? 0) ?>
                        <?php endif; ?>
                    </div>

                    <?php do_action('product.summary.after', $product); ?>
                </div>
            </div>

            <section class="pdp-tabs" aria-label="Product details tabs">
                <details open>
                    <summary><?= __('product_details') ?></summary>
                    <?php if (!empty($groupedDetailAttributes)): ?>
                        <?php foreach ($groupedDetailAttributes as $attributeName => $values): ?>
                            <div class="pdp-option-group">
                                <div class="pdp-option-label">
                                    <p>
                                        <?= htmlspecialchars($attributeName) ?> - 
                                        <?php foreach (array_unique(array_filter($values)) as $value): ?>
                                            <span><?= htmlspecialchars((string) $value) ?></span>
                                        <?php endforeach; ?> 
                                    </p> 
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <p><?= nl2br(htmlspecialchars($productDescription)) ?></p>
                </details>

                <details class="pdp-reviews-details">
                    <summary><?= __('reviews') ?></summary>
    
                    <div id="pdp-reviews-panel" data-product-slug="<?= htmlspecialchars($product['slug']) ?>">
        
                        <?php if (!empty($_SESSION['user']['id'])): ?>
                        <!-- Форма відгуку -->
                        <form id="pdp-review-form">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Http\Csrf::token()) ?>">
                            <input type="hidden" name="parent_id" id="pdp-review-parent-id" value="">
                
                            <div class="pdp-form-header">
                                <h3 id="pdp-form-title">Залишити відгук</h3>
                                <div id="pdp-reply-target" style="display:none;"></div>
                            </div>

                            <!-- Блок рейтингу зірочками -->
                            <div class="pdp-rating-wrapper">
                                <label>Ваша оцінка:</label>
                                <div class="pdp-star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                                    <label for="star<?= $i ?>" title="<?= $i ?> зірок">
                                        <svg viewBox="0 0 24 24" width="24" height="24">
                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                        </svg>
                                    </label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="pdp-input-group">
                                <label for="pdp-review-body">Текст повідомлення:</label>
                                <textarea
                                    name="body"
                                    id="pdp-review-body"
                                    rows="4"
                                    required
                                    maxlength="2000"
                                    placeholder="Поділіться враженнями про товар...">
                                </textarea>
                            </div>

                            <div class="pdp-form-actions">
                                <button type="submit" class="pdp-btn pdp-btn-primary">
                                    <span>Надіслати відгук</span>
                                </button>
                                <button type="button" id="pdp-cancel-reply" class="pdp-btn pdp-btn-ghost" style="display:none;">
                                    Скасувати відповідь
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                            <div class="pdp-auth-prompt">
                                <p>Лише зареєстровані користувачі можуть залишати відгуки. <a href="/login">Увійти</a></p>
                            </div>
                        <?php endif; ?>

        <!-- Список відгуків -->
        <div class="pdp-reviews-container">
            <div id="pdp-reviews-list">
                <!-- Сюди JS підвантажує відгуки (pdp-review-item) -->
            </div>

            <button
                id="pdp-reviews-more-btn"
                class="pdp-btn pdp-btn-outline"
                type="button"
                style="display:none;">
                Показати ще відгуки
            </button>
        </div>
    </div>
                </details>

            </section>

            <section class="pdp-similar" aria-label="<?= __('similar_products') ?>" style="margin-top: 2rem;">
                <h2 style="font-size: 1.35rem; margin-bottom: 1rem;"><?= __('similar_products') ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
                    <?php foreach (array_slice($similarProducts, 0, 4) as $item): ?>
                        <?php $outOfStock = render_stock_badge($item) !== ''; ?>
                        <article style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff; display: flex; flex-direction: column;">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= htmlspecialchars(product_image_variant_path((string) $item['image'], 'medium')) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 0.75rem;<?= $outOfStock ? ' opacity: 0.55;' : '' ?>">
                            <?php endif; ?>
                            <h3 style="margin: 0 0 0.5rem; font-size: 1rem;">
                                <a href="/product/<?= htmlspecialchars($item['slug']) ?>" style="text-decoration: none; color: #111827;"><?= htmlspecialchars($item['name']) ?></a>
                            </h3>
                            <?php if ($outOfStock): ?>
                                <p style="margin: 0 0 0.5rem;"><?= render_stock_badge($item) ?></p>
                            <?php endif; ?>
                            <p style="margin: 0 0 0.75rem; margin-top: auto;"><?= render_product_price($item) ?></p>
                            <form action="/cart/add/<?= (int)$item['id'] ?>" method="POST" style="display: inline-block; margin: 0;">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/products') ?>">
                                <button type="submit" <?= $outOfStock ? 'disabled' : '' ?> style="display: inline-block; padding: 0.5rem 0.85rem; background: <?= $outOfStock ? '#9ca3af' : '#111827' ?>; color: #fff; text-decoration: none; border-radius: 0.45rem; border: 0; cursor: <?= $outOfStock ? 'not-allowed' : 'pointer' ?>;">
                                    <?= $outOfStock ? __('out_of_stock') : __('add_to_cart') ?>
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>

                    <?php if (empty($similarProducts)): ?>
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <article style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff; display: flex; flex-direction: column; opacity: 0.5;">
                                <div style="width: 100%; height: 180px; background: #f3f4f6; border-radius: 0.5rem; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: center; color: #9ca3af;">No Image</div>
                                <h3 style="margin: 0 0 0.5rem; font-size: 1rem;">Product <?= $i + 1 ?></h3>
                                <p style="margin: 0 0 0.75rem; margin-top: auto;">0.00 <?= htmlspecialchars($currencySymbol ?? '₴') ?></p>
                                <button disabled style="display: inline-block; padding: 0.5rem 0.85rem; background: #9ca3af; color: #fff; border-radius: 0.45rem; border: 0; cursor: not-allowed;">
                                    <?= __('add_to_cart') ?>
                                </button>
                            </article>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</section>

<style>
.pdp {
    --pdp-primary: var(--primary, #2563eb);
    --pdp-border: #dbe3ef;
    --pdp-muted: #64748b;
}

/* Ізольовані стилі відгуків (щоб не конфліктували з темою) */
/* 1. Головний контейнер (рамка як у "Детальні характеристики") */
.pdp-reviews-details {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-top: 10px;
    background: #fff;
    overflow: hidden;
    font-family: sans-serif;
}

/* 2. Заголовок (Summary) */
.pdp-reviews-details summary {
    display: flex !important;
    align-items: center;
    justify-content: flex-start; /* Все ліворуч */
    gap: 12px;
    padding: 12px 16px;
    background: #f8fafc;
    cursor: pointer;
    list-style: none;
    font-weight: 600;
    color: #1e293b;
    user-select: none;
}

.pdp-reviews-details summary::-webkit-details-marker {
    display: none;
}

/* Кастомна стрілка (трикутник) ліворуч */
.pdp-reviews-details summary::before {
    content: "";
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 5px 0 5px 7px;
    border-color: transparent transparent transparent #1e293b;
    transition: transform 0.2s ease;
    display: inline-block;
}

.pdp-reviews-details[open] summary::before {
    transform: rotate(90deg);
}

.pdp-reviews-details[open] summary {
    border-bottom: 1px solid #e2e8f0;
}

/* 3. Внутрішня панель */
#pdp-reviews-panel {
    padding: 20px;
    color: #1e293b;
}

/* 4. Форма відгуку */
#pdp-review-form {
    background: #f8fafc;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
}

#pdp-review-form h3 {
    margin: 0 0 16px 0;
    font-size: 18px;
}

.pdp-input-group {
    margin-bottom: 16px;
}

.pdp-input-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 6px;
}

#pdp-review-body {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 15px;
    resize: vertical;
    box-sizing: border-box;
}

#pdp-review-body:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* 5. Рейтинг зірочками */
.pdp-rating-wrapper {
    margin-bottom: 16px;
}

.pdp-star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 4px;
}

.pdp-star-rating input { display: none; }

.pdp-star-rating label {
    cursor: pointer;
    line-height: 1;
}

.pdp-star-rating label svg {
    fill: #d1d5db;
    width: 24px;
    height: 24px;
    transition: fill 0.2s;
}

.pdp-star-rating input:checked ~ label svg,
.pdp-star-rating label:hover ~ label svg,
.pdp-star-rating label:hover svg {
    fill: #fbbf24;
}

/* 6. Кнопки */
.pdp-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.pdp-btn-primary {
    background: #2563eb;
    color: white;
}

.pdp-btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.pdp-btn-ghost {
    background: transparent;
    color: #64748b;
}

.pdp-btn-ghost:hover {
    background: #f1f5f9;
}

/* 7. Список відгуків та відповіді */
.pdp-review-item {
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 0;
}

.pdp-review-item:last-child {
    border-bottom: none;
}

.pdp-review-replies {
    margin-left: 30px;
    padding-left: 20px;
    border-left: 2px solid #e2e8f0;
    margin-top: 10px;
}

.pdp-auth-prompt {
    text-align: center;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
    color: #64748b;
}


.pdp-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
}

.category-breadcrumbs {
    margin-bottom: 1rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 10px;
    padding: 0.75rem 1rem;
}

.category-breadcrumbs ol {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
}

.breadcrumb-link {
    color: #475569;
    text-decoration: none;
    border-radius: 6px;
    padding: 0.2rem 0.45rem;
}

.breadcrumb-link:hover {
    background: #f8fafc;
    text-decoration: none;
    color: #0f172a;
}

.breadcrumb-link-home {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.breadcrumb-home-icon {
    width: 16px;
    height: 16px;
}

.breadcrumb-divider {
    color: #cbd5e1;
}

.breadcrumb-current {
    background: #f1f5f9;
    color: #0f172a;
    border-radius: 6px;
    padding: 0.2rem 0.45rem;
    font-weight: 600;
}

.category-royal-card {
    margin-bottom: 1rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 10px;
    padding: 0.75rem;
}

.category-royal-title {
    margin: 0 0 0.5rem 0;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.category-royal-title-icon {
    width: 16px;
    height: 16px;
    color: #64748b;
}

.category-royal-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-royal-item {
    margin-bottom: 0.2rem;
}

.category-royal-row {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.category-royal-link {
    flex: 1;
    min-width: 0;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border-radius: 8px;
    color: #334155;
    text-decoration: none;
    line-height: 1.3;
}

.category-royal-link:hover {
    background: #f8fafc;
    text-decoration: none;
}

.category-royal-link.is-active {
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 600;
}

.category-accordion-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border: 0;
    background: transparent;
    border-radius: 6px;
    color: #64748b;
    cursor: pointer;
}

.category-accordion-trigger:hover {
    background: #f1f5f9;
    color: #334155;
}

.category-accordion-icon {
    width: 16px;
    height: 16px;
    transition: transform .25s ease;
}

.category-accordion-icon.is-open {
    transform: rotate(90deg);
}

.category-accordion-panel {
    overflow: hidden;
    transition: max-height .3s ease, opacity .3s ease;
}

.category-royal-branch {
    margin-left: 0.55rem;
    padding-left: 0.35rem;
    border-left: 1px solid #cbd5e1;
}

.pdp-breadcrumbs .is-current {
    color: #0f172a;
    font-weight: 600;
}

.pdp-main-grid {
    display: grid;
    grid-template-columns: minmax(280px, 1fr) minmax(320px, 1fr);
    gap: 2rem;
}

.pdp-main-image-wrap {
    margin: 0;
    border: 1px solid var(--pdp-border);
    border-radius: 12px;
    overflow: hidden;
    background: #f8fafc;
}

.pdp-main-image {
    width: 100%;
    display: block;
    aspect-ratio: 1/1;
    object-fit: cover;
}

.pdp-thumbs {
    margin-top: 0.75rem;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.5rem;
}

.pdp-thumb {
    border: 1px solid var(--pdp-border);
    border-radius: 10px;
    padding: 0;
    background: white;
    overflow: hidden;
    cursor: pointer;
}

.pdp-thumb img {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    display: block;
}

.pdp-thumb.is-active,
.pdp-thumb:focus-visible {
    outline: 2px solid var(--pdp-primary);
    outline-offset: 1px;
}

.pdp-info h1 {
    margin-bottom: 0.6rem;
    font-size: clamp(1.5rem, 3vw, 2.2rem);
}

.pdp-price {
    color: var(--pdp-primary);
    font-size: clamp(1.35rem, 2.4vw, 2rem);
    font-weight: 700;
    margin-bottom: 1rem;
}

.pdp-short-description {
    color: #334155;
    margin-bottom: 1.3rem;
}

.pdp-options {
    border: 1px solid var(--pdp-border);
    border-radius: 12px;
    padding: 1rem;
    background: #fff;
    margin-bottom: 1.2rem;
}

.pdp-options h3 {
    margin-bottom: 0.8rem;
    font-size: 1rem;
}

.pdp-option-group + .pdp-option-group {
    margin-top: 0.8rem;
}

.pdp-option-label {
    font-weight: 600;
    margin-bottom: 0.35rem;
    font-size: 0.95rem;
}

.pdp-option-values {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.pdp-chip {
    border: 1px solid var(--pdp-border);
    background: #fff;
    border-radius: 999px;
    padding: 0.38rem 0.85rem;
    font-size: 0.88rem;
    cursor: pointer;
}

.pdp-chip:hover,
.pdp-chip:focus-visible {
    border-color: var(--pdp-primary);
    color: var(--pdp-primary);
}

.pdp-actions {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.75rem;
}

.pdp-btn {
    border-radius: 10px;
    padding: 0.85rem 1rem;
    border: 1px solid transparent;
    text-align: center;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.pdp-btn-primary {
    background: var(--pdp-primary);
    color: #fff;
}

.pdp-btn-primary:hover {
    filter: brightness(0.93);
    text-decoration: none;
}

.pdp-btn-ghost {
    background: #fff;
    border-color: var(--pdp-border);
    color: #0f172a;
}

.pdp-tabs {
    margin-top: 2rem;
    border: 1px solid var(--pdp-border);
    border-radius: 12px;
    overflow: hidden;
}

.pdp-tabs details + details {
    border-top: 1px solid var(--pdp-border);
}

.pdp-tabs summary {
    cursor: pointer;
    font-weight: 600;
    padding: 0.9rem 1rem;
    background: #f8fafc;
}

.pdp-tabs p {
    padding: 1rem;
    margin: 0;
}

.pdp-similar {
    margin-top: 2rem;
}

.pdp-similar h2 {
    font-size: 1.35rem;
    margin-bottom: 1rem;
}

.pdp-similar-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
}

.pdp-similar-card {
    border: 1px solid var(--pdp-border);
    border-radius: 10px;
    padding: 0.75rem;
    background: #fff;
    display: flex;
    flex-direction: column;
}

.pdp-similar-image-link {
    display: block;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 0.65rem;
    border: 1px solid #eef2f7;
}

.pdp-similar-image-link img {
    width: 100%;
    aspect-ratio: 1/1;
    object-fit: cover;
    display: block;
}

.pdp-similar-card h3 {
    font-size: 0.98rem;
    margin: 0 0 0.35rem;
    flex: 1;
}

.pdp-similar-price {
    color: #16a34a;
    font-weight: 700;
    margin-top: auto;
}

#auth-popup {
    /* Тепер позиція зверху задається динамічно через JS */
    position: fixed;
    right: 20px;
    z-index: 1000;
    
    /* Оформлення вікна */
    background: #ffffff;
    border: 1px solid #e0e0e0;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    padding: 18px;
    border-radius: 10px;
    min-width: 280px;
    
    /* Анімація появи (плавне випадання зверху) */
    animation: slideDown 0.3s ease-out;
}

.auth-popup-content p {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #333;
    line-height: 1.4;
}

.auth-links {
    display: flex;
    gap: 15px;
}

.btn-link {
    font-weight: 700;
    color: #007bff;
    text-decoration: none;
    font-size: 15px;
}

.btn-link:hover {
    text-decoration: underline;
}

.close-popup {
    position: absolute;
    top: 5px;
    right: 10px;
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: #bbb;
    line-height: 1;
}

.close-popup:hover {
    color: #333;
}

/* Ефект появи */
@keyframes slideDown {
    from { 
        opacity: 0; 
        transform: translateY(-20px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}
.pdp-btn-ghost.active {
    color: #e74c3c; /* Червоний колір тексту */
    border-color: #e74c3c; /* Червона рамка */
}

@media (max-width: 1024px) {
    .pdp-layout {
        grid-template-columns: 1fr;
    }

    .pdp-main-grid {
        grid-template-columns: 1fr;
    }

    .pdp-similar-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .pdp-actions {
        grid-template-columns: 1fr;
    }

    .pdp-similar-grid {
        grid-template-columns: 1fr;
    }
}

/* Image Modal Styles */
.pdp-modal {
    display: none;
    position: fixed;
    z-index: 2000;
    padding-top: 60px;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.9);
    cursor: zoom-out;
}

.pdp-modal-content {
    margin: auto;
    display: block;
    width: auto;
    max-width: 90%;
    max-height: 80vh;
    border-radius: 4px;
    cursor: default;
}

.pdp-modal-caption {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 700px;
    text-align: center;
    color: #ccc;
    padding: 15px 0;
}

.pdp-modal-close {
    position: absolute;
    top: 20px;
    right: 35px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: bold;
    transition: 0.3s;
    cursor: pointer;
}

.pdp-modal-close:hover,
.pdp-modal-close:focus {
    color: #bbb;
    text-decoration: none;
    cursor: pointer;
}

.pdp-modal-prev, .pdp-modal-next {
    cursor: pointer;
    position: absolute;
    top: 50%;
    width: auto;
    padding: 16px;
    margin-top: -50px;
    color: white;
    font-weight: bold;
    font-size: 30px;
    transition: 0.6s ease;
    border-radius: 0 3px 3px 0;
    user-select: none;
    background-color: rgba(0, 0, 0, 0.3);
    border: none;
    z-index: 2001;
}

.pdp-modal-next {
    right: 0;
    border-radius: 3px 0 0 3px;
}

.pdp-modal-prev {
    left: 0;
}

.pdp-modal-prev:hover, .pdp-modal-next:hover {
    background-color: rgba(0, 0, 0, 0.8);
}

@media only screen and (max-width: 700px){
    .pdp-modal-content {
        width: 100%;
    }
}

.pdp-main-image {
    cursor: zoom-in;
}

/* Product card refresh: deliberately scoped to the public product page. */
.pdp {
    --pdp-primary: var(--primary, #2563eb);
    --pdp-ink: #0f172a;
    --pdp-soft-ink: #475569;
    --pdp-border: #e6edf5;
    --pdp-surface: #ffffff;
    --pdp-soft-surface: #f8fafc;
}

.pdp-main-grid {
    gap: 0;
    overflow: hidden;
    border: 1px solid var(--pdp-border);
    border-radius: 24px;
    background: var(--pdp-surface);
    box-shadow: 0 18px 55px rgba(15, 23, 42, 0.09);
}

.pdp-gallery {
    min-width: 0;
    padding: clamp(1rem, 3vw, 2rem);
    background:
        radial-gradient(circle at 15% 10%, rgba(219, 234, 254, 0.85), transparent 38%),
        linear-gradient(145deg, #f8fbff 0%, #eef4fb 100%);
}

.pdp-main-image-wrap {
    position: relative;
    border: 0;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.82);
    box-shadow: 0 12px 30px rgba(30, 64, 175, 0.1);
}

.pdp-main-image {
    aspect-ratio: 1 / 1;
    object-fit: contain;
    padding: clamp(0.75rem, 2vw, 1.5rem);
    transition: transform 0.35s ease;
}

.pdp-main-image-wrap:hover .pdp-main-image {
    transform: scale(1.025);
}

.pdp-thumbs {
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.65rem;
    margin-top: 0.9rem;
}

.pdp-thumb {
    border-color: rgba(148, 163, 184, 0.35);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.7);
    transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.pdp-thumb:hover {
    border-color: rgba(37, 99, 235, 0.55);
    transform: translateY(-2px);
}

.pdp-thumb.is-active,
.pdp-thumb:focus-visible {
    outline: 0;
    border-color: var(--pdp-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
}

.pdp-info {
    display: flex;
    min-width: 0;
    flex-direction: column;
    justify-content: center;
    padding: clamp(1.25rem, 4vw, 3rem);
    background: var(--pdp-surface);
}

.pdp-info h1 {
    color: var(--pdp-ink);
    margin-bottom: 0.85rem;
    font-size: clamp(1.7rem, 3vw, 2.55rem);
    line-height: 1.12;
    letter-spacing: -0.035em;
}

.pdp-price {
    display: inline-flex;
    width: fit-content;
    align-items: baseline;
    margin-bottom: 1.35rem;
    padding: 0.6rem 0.9rem;
    border: 1px solid rgba(37, 99, 235, 0.14);
    border-radius: 12px;
    background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
    color: #1d4ed8;
    font-size: clamp(1.55rem, 2.7vw, 2.15rem);
    line-height: 1;
    letter-spacing: -0.025em;
}

.pdp-short-description {
    margin-bottom: 1.4rem;
    padding: 0.9rem 1rem;
    border-left: 3px solid #93c5fd;
    border-radius: 0 10px 10px 0;
    background: #f8fafc;
    color: var(--pdp-soft-ink);
    line-height: 1.65;
}

.pdp-short-description strong {
    display: block;
    margin-bottom: 0.2rem;
    color: var(--pdp-ink);
    font-size: 0.78rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.pdp-options {
    margin-bottom: 1.35rem;
    border: 1px solid var(--pdp-border);
    border-radius: 16px;
    padding: 1.1rem;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.045);
}

.pdp-options h3 {
    margin-bottom: 1rem;
    color: var(--pdp-ink);
    font-size: 0.9rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.pdp-option-group + .pdp-option-group {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eef2f7;
}

.pdp-option-label {
    margin-bottom: 0.55rem;
    color: var(--pdp-ink);
    font-size: 0.9rem;
}

.pdp-option-values {
    gap: 0.55rem;
}

.pdp-options-reset {
    margin-top: 1rem;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--pdp-primary);
    cursor: pointer;
    font-size: 0.86rem;
    font-weight: 600;
}

.pdp-options-reset:disabled {
    color: #94a3b8;
    cursor: default;
}

.pdp-chip {
    position: relative;
    display: inline-flex !important;
    align-items: center;
    gap: 0.45rem;
    border: 1px solid #dbe5f0;
    border-radius: 10px;
    padding: 0.55rem 0.8rem;
    background: #fff;
    color: #334155;
    font-size: 0.9rem;
    transition: border-color 0.2s ease, background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.pdp-chip input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

.pdp-chip:hover,
.pdp-chip:has(input:checked) {
    border-color: var(--pdp-primary);
    background: #eff6ff;
    color: #1d4ed8;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
}

.pdp-chip:has(input:focus-visible) {
    outline: 3px solid rgba(37, 99, 235, 0.18);
    outline-offset: 2px;
}

.pdp-actions {
    align-items: stretch;
    gap: 0.7rem;
    padding-top: 1.2rem;
    border-top: 1px solid #eef2f7;
}

.pdp-actions form.pdp-cart-form,
.pdp-actions form.d-flex {
    min-width: 0;
    gap: 0.65rem;
}

.pdp-actions .form-control {
    width: 5.25rem !important;
    min-height: 48px;
    border: 1px solid #dbe5f0;
    border-radius: 11px;
    background: #fff;
    color: var(--pdp-ink);
    font-weight: 600;
    text-align: center;
}

.pdp-actions .form-control:focus {
    border-color: var(--pdp-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
    outline: 0;
}

.pdp-actions .pdp-btn {
    min-height: 48px;
    border-radius: 11px;
    padding: 0.8rem 1.05rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
}

.pdp-actions .pdp-btn-primary {
    flex: 1;
    background: linear-gradient(135deg, var(--pdp-primary) 0%, #1d4ed8 100%);
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22);
}

.pdp-actions .pdp-btn-primary:hover:not(:disabled) {
    filter: none;
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.28);
}

.pdp-actions .pdp-btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 48px;
    border-color: #dbe5f0;
    background: #fff;
}

.pdp-actions .pdp-btn-ghost:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: translateY(-2px);
}

.pdp-info > .mt-2.small.text-muted {
    margin-top: 0.85rem !important;
    color: #64748b !important;
    font-size: 0.86rem;
}

@media (max-width: 1024px) {
    .pdp-main-grid {
        gap: 0;
    }

    .pdp-info {
        padding-top: 1.5rem;
    }
}

@media (max-width: 640px) {
    .pdp-main-grid {
        border-radius: 18px;
    }

    .pdp-gallery,
    .pdp-info {
        padding: 1rem;
    }

    .pdp-thumbs {
        gap: 0.45rem;
    }

    .pdp-actions {
        grid-template-columns: 1fr;
    }

    .pdp-actions form.pdp-cart-form,
    .pdp-actions form.d-flex {
        width: 100%;
    }
}
</style>

<script>
(() => {
    // --- 1. ОБРАНЕ (WISHLIST) ---
    const isLoggedIn = <?php echo isset($_SESSION['user']) ? 1 : 0; ?>;
    const productId = <?php echo (int)$product['id']; ?>;
    const wishlistBtn = document.querySelector('.pdp-btn-ghost');

    if (wishlistBtn) {
        const originalText = wishlistBtn.innerText.replace('❤️ ', '').trim();

        wishlistBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (isLoggedIn === 0) {
                showLoginPopup();
                return;
            }

            const btn = this;
            btn.disabled = true;
            const formData = new FormData();
            formData.append('product_id', productId);

            fetch('/favorites/toggle', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'added') {
                    btn.classList.add('active');
                    btn.innerHTML = '❤️ ' + originalText;
                } else if (data.status === 'removed') {
                    btn.classList.remove('active');
                    btn.innerHTML = originalText;
                }
            })
            .finally(() => { btn.disabled = false; });
        });
    }

    function showLoginPopup() {
        if (document.getElementById('auth-popup')) return;
        const popup = document.createElement('div');
        popup.id = 'auth-popup';
        popup.style.cssText = `position:fixed; top:80px; right:20px; background:white; padding:20px; box-shadow:0 4px 15px rgba(0,0,0,0.15); z-index:1000; border-radius:8px; border-left:4px solid #e74c3c;`;
        popup.innerHTML = `
            <div class="auth-popup-content">
                <p style="margin:0 0 15px 0;">Щоб додати в обране, увійдіть у профіль</p>
                <div style="display:flex; gap:10px;">
                    <a href="/login" style="color:#e74c3c; text-decoration:none; font-weight:bold;">Увійти</a>
                    <button onclick="this.closest('#auth-popup').remove()" style="border:none; background:none; cursor:pointer;">Закрити</button>
                </div>
            </div>`;
        document.body.appendChild(popup);
        setTimeout(() => popup.remove(), 5000);
    }

    // --- 2. ГАЛЕРЕЯ ТА АКОРДЕОНИ ---
    const mainImage = document.getElementById('pdp-main-image');
    const modal = document.getElementById('image-modal');
    const modalImg = document.getElementById('modal-img');
    const captionText = document.getElementById('modal-caption');
    const galleryImages = <?php echo json_encode(array_column($galleryImages, 'original')); ?>;
    let currentImgIndex = 0;

    const updateModalImage = (index) => {
        if (index < 0) index = galleryImages.length - 1;
        if (index >= galleryImages.length) index = 0;
        currentImgIndex = index;
        modalImg.src = galleryImages[currentImgIndex];
        captionText.innerHTML = `Фото ${currentImgIndex + 1} із ${galleryImages.length}`;
    };

    if (mainImage) {
        // Thumbnail click logic
        document.querySelectorAll('[data-pdp-thumb]').forEach((thumb, idx) => {
            thumb.addEventListener('click', () => {
                mainImage.src = thumb.getAttribute('data-image');
                document.querySelectorAll('[data-pdp-thumb]').forEach(b => b.classList.remove('is-active'));
                thumb.classList.add('is-active');
                currentImgIndex = idx;
            });
        });

        // Modal open logic
        mainImage.onclick = function() {
            modal.style.display = "block";
            modal.setAttribute('aria-hidden', 'false');
            
            // Find current index based on main image src
            const currentSrc = this.src;
            const foundIndex = galleryImages.findIndex(src => currentSrc.includes(src));
            if (foundIndex !== -1) currentImgIndex = foundIndex;
            
            updateModalImage(currentImgIndex);
            document.body.style.overflow = 'hidden';
        }
    }

    // Modal navigation
    const prevBtn = document.querySelector('.pdp-modal-prev');
    const nextBtn = document.querySelector('.pdp-modal-next');

    if (prevBtn) prevBtn.onclick = (e) => { e.stopPropagation(); updateModalImage(currentImgIndex - 1); };
    if (nextBtn) nextBtn.onclick = (e) => { e.stopPropagation(); updateModalImage(currentImgIndex + 1); };

    // Modal close logic
    if (modal) {
        const closeBtn = document.querySelector(".pdp-modal-close");
        const closeModal = () => {
            modal.style.display = "none";
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = 'auto';
        };

        if (closeBtn) closeBtn.onclick = closeModal;
        modal.onclick = (e) => {
            if (e.target === modal || e.target.classList.contains('pdp-modal-close')) {
                closeModal();
            }
        };

        document.addEventListener('keydown', (e) => {
            if (modal.style.display === "block") {
                if (e.key === "Escape") closeModal();
                if (e.key === "ArrowLeft") updateModalImage(currentImgIndex - 1);
                if (e.key === "ArrowRight") updateModalImage(currentImgIndex + 1);
            }
        });
    }

    document.querySelectorAll('[data-accordion-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const panel = trigger.parentElement?.parentElement?.querySelector(':scope > [data-accordion-panel]');
            if (!panel) return;
            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            trigger.setAttribute('aria-expanded', !isExpanded ? 'true' : 'false');
            panel.style.maxHeight = !isExpanded ? `${panel.scrollHeight + 12}px` : '0';
            panel.style.opacity = !isExpanded ? '1' : '.4';
        });
    });

    // --- 3. ЦІНА ТА ОПЦІЇ ---
    const priceNode = document.querySelector('.pdp-price[data-base-price]');
    const optionRadios = document.querySelectorAll('.pdp-options input[type="radio"]');

    function updatePrice() {
        if (!priceNode) return;
        let delta = 0;
        document.querySelectorAll('.pdp-options input[type="radio"]:checked').forEach(r => {
            const p = Number(r.dataset.optionPrice || 0);
            delta += (r.dataset.optionOp === '-' ? -1 : 1) * p;
        });
        priceNode.textContent = `${(Number(priceNode.dataset.basePrice) + delta).toFixed(2)} ${window.CURRENCY_SYMBOL || '₴'}`;
    }

    const clearOptionsButton = document.getElementById('clear-product-options');
    function updateSelectedOptions() {
        updatePrice();
        if (clearOptionsButton) {
            clearOptionsButton.disabled = !document.querySelector('.pdp-options input[type="radio"]:checked');
        }
    }

    optionRadios.forEach(r => r.addEventListener('change', updateSelectedOptions));
    if (clearOptionsButton) {
        clearOptionsButton.addEventListener('click', () => {
            optionRadios.forEach(r => { r.checked = false; });
            updateSelectedOptions();
        });
    }
    updateSelectedOptions();

    // --- 4. ВІДГУКИ (ОСНОВНА ЛОГІКА) ---
    const reviewsPanel = document.getElementById('pdp-reviews-panel');
    if (reviewsPanel) {
        const slug = reviewsPanel.dataset.productSlug;
        const listEl = document.getElementById('pdp-reviews-list');
        const form = document.getElementById('pdp-review-form');
        const moreBtn = document.getElementById('pdp-reviews-more-btn');
        const ratingWrapper = document.querySelector('.pdp-rating-wrapper');
        const replyTarget = document.getElementById('pdp-reply-target');
        const cancelBtn = document.getElementById('pdp-cancel-reply');
        let page = 1;

        const escapeHtml = (str) => String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

        const resetForm = () => {
            form.reset();
            document.getElementById('pdp-review-parent-id').value = '';
            if (ratingWrapper) ratingWrapper.style.display = 'block'; // Повертаємо зірочки
            if (replyTarget) { replyTarget.style.display = 'none'; replyTarget.innerText = ''; }
            if (cancelBtn) cancelBtn.style.display = 'none';
        };

        const renderStars = (rating) => {
            if (!rating) return '';
            return `<div style="color:#fbbf24; margin-bottom:4px;">${'★'.repeat(rating)}${'☆'.repeat(5-rating)}</div>`;
        };

        const renderReview = (item) => {
            const replies = (item.replies || []).map(r => `
                <div class="pdp-review-item" style="background:#f8fafc; margin-top:8px;">
                    <div style="font-size:13px;"><b>${escapeHtml(r.author_name)}</b> <span style="color:#64748b; margin-left:8px;">${r.created_at}</span></div>
                    <div style="margin-top:4px;">${escapeHtml(r.body)}</div>
                </div>
            `).join('');

            return `
                <div class="pdp-review-item">
                    <div style="font-size:14px; display:flex; justify-content:space-between;">
                        <b>${escapeHtml(item.author_name)}</b>
                        <span style="color:#64748b; font-size:12px;">${item.created_at}</span>
                    </div>
                    ${renderStars(item.rating)}
                    <div style="margin:8px 0;">${escapeHtml(item.body)}</div>
                    ${isLoggedIn ? `<button class="pdp-reply-btn pdp-btn pdp-btn-ghost" style="padding:4px 8px; font-size:12px;" data-id="${item.id}" data-author="${item.author_name}">Відповісти</button>` : ''}
                    <div class="pdp-review-replies">${replies}</div>
                </div>`;
        };

        const loadReviews = async (reset = false) => {
            if (reset) { page = 1; listEl.innerHTML = ''; }
            const res = await fetch(`/product/${encodeURIComponent(slug)}/reviews?page=${page}`, { headers: {'X-Requested-With':'XMLHttpRequest'} });
            const data = await res.json();
            if (data.success) {
                listEl.insertAdjacentHTML('beforeend', data.items.map(renderReview).join(''));
                moreBtn.style.display = data.has_more ? 'inline-block' : 'none';
            }
        };

        loadReviews();

        if (moreBtn) moreBtn.addEventListener('click', () => { page++; loadReviews(); });

        if (listEl) {
            listEl.addEventListener('click', (e) => {
                const btn = e.target.closest('.pdp-reply-btn');
                if (!btn) return;
                document.getElementById('pdp-review-parent-id').value = btn.dataset.id;
                if (ratingWrapper) ratingWrapper.style.display = 'none'; // ХОВАЄМО зірочки при відповіді
                if (replyTarget) { replyTarget.innerHTML = `Відповідь для <b>${btn.dataset.author}</b>`; replyTarget.style.display = 'block'; }
                if (cancelBtn) cancelBtn.style.display = 'inline-block';
                form.scrollIntoView({behavior:'smooth', block:'center'});
            });
        }

        if (cancelBtn) cancelBtn.addEventListener('click', resetForm);

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;

                try {
                    const res = await fetch(`/product/${encodeURIComponent(slug)}/reviews`, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {'X-Requested-With':'XMLHttpRequest'}
                    });
                    const data = await res.json();
                    if (data.success) {
                        resetForm();
                        await loadReviews(true);
                    } else {
                        alert(data.message || 'Помилка');
                    }
                } catch (e) { alert('Помилка з’єднання'); }
                finally { submitBtn.disabled = false; }
            });
        }
    }
})();

</script>
