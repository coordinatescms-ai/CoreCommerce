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
            echo '<a href="/category/' . htmlspecialchars((string) ($item['slug'] ?? '')) . '" class="category-royal-link';
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
    <nav class="category-breadcrumbs" aria-label="Breadcrumb">
        <ol>
            <li>
                <a class="breadcrumb-link breadcrumb-link-home" href="/">
                    <svg class="breadcrumb-home-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 10.5L12 3L21 10.5V20A1 1 0 0 1 20 21H4A1 1 0 0 1 3 20V10.5Z" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                    <span><?= __('breadcrumb_home') ?></span>
                </a>
            </li>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php $isLast = $index === count($breadcrumbs) - 1; ?>
                <li class="breadcrumb-divider" aria-hidden="true">/</li>
                <li>
                    <?php if ($isLast): ?>
                        <span class="breadcrumb-current"><?= htmlspecialchars($crumb['name'] ?? '') ?></span>
                    <?php else: ?>
                        <a class="breadcrumb-link" href="<?= htmlspecialchars($crumb['url'] ?? '#') ?>">
                            <?= htmlspecialchars($crumb['name'] ?? '') ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <div class="pdp-layout">
        <aside class="pdp-sidebar">
            <section class="category-royal-card">
                <h2 class="category-royal-title">
                    <svg class="category-royal-title-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 6H20M4 12H20M4 18H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Категорії
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
                    <div class="pdp-price" data-base-price="<?= htmlspecialchars((string) number_format((float) ($product['price'] ?? 0), 2, '.', '')) ?>"><?= $productPrice ?> грн</div>

                    <p class="pdp-short-description">
                        <strong><?= __('short_description') ?>:</strong>
                        <?= htmlspecialchars($shortDescription !== '' ? $shortDescription : __('no_products_found')) ?>
                    </p>

                    <div class="pdp-options" aria-label="Product options">
                        <h3>Оберіть характеристики</h3>
                        <?php if (!empty($groupedSelectableAttributes)): ?>
                            <?php foreach ($groupedSelectableAttributes as $attributeId => $group): ?>
                                <div class="pdp-option-group">
                                    <div class="pdp-option-label"><?= htmlspecialchars((string) ($group['name'] ?? '')) ?></div>
                                    <div class="pdp-option-values">
                                        <?php foreach (($group['options'] ?? []) as $option): ?>
                                            <label class="pdp-chip" style="display:inline-flex; align-items:center; gap:0.45rem;">
                                                <input
                                                    type="radio"
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
                        <?php else: ?>
                            <div class="pdp-option-group">
                                <div class="pdp-option-label">Для цього товару немає варіантів вибору.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pdp-actions">
                        <form action="/cart/add/<?= (int) $product['id'] ?>" method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?? '' ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?= (int)($product['stock'] ?? 0) ?>" class="form-control me-2" style="width: 80px;">
                            <button type="submit" class="pdp-btn pdp-btn-primary"><?= __('add_to_cart') ?></button>
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
                        <?= __('in_stock') ?>: <?= (int)($product['stock'] ?? 0) ?>
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
                                <div class="pdp-option-label"><?= htmlspecialchars($attributeName) ?></div>
                                <div class="pdp-option-values">
                                    <?php foreach (array_unique(array_filter($values)) as $value): ?>
                                        <span class="pdp-chip"><?= htmlspecialchars((string) $value) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($productDescription)): ?>
                        <p><?= nl2br(htmlspecialchars($productDescription)) ?></p>
                    <?php elseif (empty($groupedDetailAttributes)): ?>
                        <p>Детальні характеристики відсутні.</p>
                    <?php endif; ?>
                </details>

                <details>
    <summary><?= __('reviews') ?></summary>
    <div id="pdp-reviews-panel" data-product-slug="<?= htmlspecialchars($product['slug']) ?>">
        <?php if (!empty($_SESSION['user']['id'])): ?>
            <form id="pdp-review-form" style="margin: 12px 0;">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(\App\Core\Http\Csrf::token()) ?>">
                <input type="hidden" name="parent_id" id="pdp-review-parent-id" value="">
                
                <label for="pdp-review-rating">Рейтинг (1-5, тільки для основного):</label>
                <input type="number" name="rating" id="pdp-review-rating" min="1" max="5" value="5">

                <label for="pdp-review-body">Текст відгуку:</label>
                <textarea
                    name="body"
                    id="pdp-review-body"
                    rows="3"
                    required
                    maxlength="2000"
                    placeholder="Напишіть відгук"
                ></textarea>

                <button type="submit" class="pdp-btn pdp-btn-primary">Додати</button>
            </form>
        <?php else: ?>
            <p>Лише зареєстровані користувачі можуть залишати відгуки.</p>
        <?php endif; ?>

        <div id="pdp-reviews-list"></div>
        <button
            id="pdp-reviews-more-btn"
            class="pdp-btn pdp-btn-ghost"
            type="button"
            style="display:none; margin-top:10px;"
        >
            Показати ще
        </button>
    </div>
</details>

                <details>
                    <summary><?= __('shipping_terms') ?></summary>
                    <p><?= __('delivery_info_default') ?></p>
                </details>
            </section>

            <section class="pdp-similar" aria-label="<?= __('similar_products') ?>">
                <h2><?= __('similar_products') ?></h2>
                <div class="pdp-similar-grid">
                    <?php foreach (array_slice($similarProducts, 0, 4) as $item): ?>
                        <article class="pdp-similar-card">
                            <a href="/product/<?= htmlspecialchars($item['slug']) ?>" class="pdp-similar-image-link">
                                <img src="<?= htmlspecialchars(!empty($item['image']) ? $item['image'] : $placeholderImage) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            </a>
                            <h3><a href="/product/<?= htmlspecialchars($item['slug']) ?>"><?= htmlspecialchars($item['name']) ?></a></h3>
                            <div class="pdp-similar-price"><?= number_format((float) ($item['price'] ?? 0), 2, '.', ' ') ?> грн</div>
                        </article>
                    <?php endforeach; ?>

                    <?php if (empty($similarProducts)): ?>
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <article class="pdp-similar-card is-placeholder">
                                <div class="pdp-similar-image-link"><img src="<?= htmlspecialchars($placeholderImage) ?>" alt="placeholder"></div>
                                <h3>Product <?= $i + 1 ?></h3>
                                <div class="pdp-similar-price">0.00 грн</div>
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
#pdp-reviews-panel {
    max-width: 100%;
}

.pdp-review-item {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px;
    margin-top: 10px;
    background: #fff;
}

.pdp-review-replies {
    margin-left: 24px;
}

#pdp-review-form textarea,
#pdp-review-form input[type="number"] {
    width: 100%;
    box-sizing: border-box;
    margin: 6px 0 10px;
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
}

.pdp-similar-price {
    color: var(--pdp-primary);
    font-weight: 700;
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
</style>

<script>
    // 1. Отримуємо дані з PHP
    const isLoggedIn = <?php echo isset($_SESSION['user']) ? 1 : 0; ?>;
    const productId = <?php echo (int)$product['id']; ?>;
    
    // Знаходимо кнопку та її текстовий контейнер
    const wishlistBtn = document.querySelector('.pdp-btn-ghost');
    // Зберігаємо початковий переклад (наприклад, "Wishlist" або "В обране") 
    // щоб знати, що написати, коли товар буде видалено
    const originalText = wishlistBtn.innerText.replace('❤️ ', '').trim();

    wishlistBtn.addEventListener('click', function(e) {
        e.preventDefault();

        // 2. Перевірка авторизації
        if (isLoggedIn === 0) {
            showLoginPopup();
            return;
        }

        const btn = this;
        btn.disabled = true; // Блокуємо кнопку на час запиту

        // 3. Підготовка даних для відправки
        const formData = new FormData();
        formData.append('product_id', productId);

        // 4. Відправка запиту на контролер
        fetch('/favorites/toggle', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'added') {
                // Товар додано: додаємо клас та сердечко
                btn.classList.add('active');
                btn.innerHTML = '❤️ ' + originalText;
            } else if (data.status === 'removed') {
                // Товар видалено: прибираємо клас та сердечко
                btn.classList.remove('active');
                btn.innerHTML = originalText;
            } else {
                alert(data.message || 'Сталася помилка');
            }
        })
        .catch(error => {
            console.error('Помилка AJAX:', error);
            alert('Не вдалося з’єднатися з сервером');
        })
        .finally(() => {
            btn.disabled = false; // Розблоковуємо кнопку
        });
    });

    // Функція показу попапа для гостей
    function showLoginPopup() {
        if (document.getElementById('auth-popup')) return;

        const popup = document.createElement('div');
        popup.id = 'auth-popup';
        
        const header = document.querySelector('header') || document.querySelector('.main-header'); 
        const headerHeight = header ? header.offsetHeight : 0;

        popup.style.cssText = `
            position: fixed;
            top: ${headerHeight + 10}px;
            right: 20px;
            background: white;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            z-index: 1000;
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
        `;

        popup.innerHTML = `
            <div class="auth-popup-content">
                <p style="margin: 0 0 15px 0;">Щоб додати в обране, увійдіть у профіль</p>
                <div style="display: flex; gap: 10px;">
                    <a href="/login" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Увійти</a>
                    <a href="/register" style="color: #666; text-decoration: none;">Реєстрація</a>
                </div>
                <button onclick="this.closest('#auth-popup').remove()" 
                        style="position: absolute; top: 5px; right: 10px; border: none; background: none; cursor: pointer; font-size: 20px;">
                        &times;
                </button>
            </div>
        `;
        document.body.appendChild(popup);
        setTimeout(() => { if(popup) popup.remove(); }, 5000);
    }

(() => {
    const mainImage = document.getElementById('pdp-main-image');
    if (mainImage) {
        document.querySelectorAll('[data-pdp-thumb]').forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const image = thumb.getAttribute('data-image');
                if (!image) {
                    return;
                }

                mainImage.src = image;

                document.querySelectorAll('[data-pdp-thumb]').forEach((button) => {
                    button.classList.remove('is-active');
                });
                thumb.classList.add('is-active');
            });
        });
    }

    document.querySelectorAll('[data-accordion-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const panel = trigger.parentElement?.parentElement?.querySelector(':scope > [data-accordion-panel]');
            const icon = trigger.querySelector('.category-accordion-icon');
            if (!panel) {
                return;
            }

            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            const nextExpanded = !isExpanded;

            trigger.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
            const treeItem = trigger.closest('.category-royal-item');
            if (treeItem) {
                treeItem.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
            }

            if (icon) {
                icon.classList.toggle('is-open', nextExpanded);
            }

            if (nextExpanded) {
                panel.style.maxHeight = `${panel.scrollHeight + 12}px`;
                panel.style.opacity = '1';
            } else {
                panel.style.maxHeight = '0';
                panel.style.opacity = '.4';
            }
        });
    });

    const priceNode = document.querySelector('.pdp-price[data-base-price]');
    const buyForm = document.querySelector('.pdp-actions form[action^="/cart/add/"]');
    const optionRadios = document.querySelectorAll('.pdp-options input[type="radio"][name^="selected_option_ids"]');

    function updateDisplayedPrice() {
        if (!priceNode) {
            return;
        }

        const basePrice = Number(priceNode.dataset.basePrice || 0);
        let delta = 0;
        document.querySelectorAll('.pdp-options input[type="radio"]:checked').forEach((radio) => {
            const price = Number(radio.dataset.optionPrice || 0);
            const op = radio.dataset.optionOp === '-' ? -1 : 1;
            if (price > 0) {
                delta += price * op;
            }
        });

        const finalPrice = Math.max(0, basePrice + delta);
        priceNode.textContent = `${finalPrice.toFixed(2)} грн`;
    }

    optionRadios.forEach((radio) => {
        radio.addEventListener('change', updateDisplayedPrice);
    });
    updateDisplayedPrice();

    if (buyForm) {
        buyForm.addEventListener('submit', () => {
            buyForm.querySelectorAll('input[data-dynamic-option="1"]').forEach((input) => input.remove());
            document.querySelectorAll('.pdp-options input[type="radio"]:checked').forEach((radio) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = radio.name;
                hidden.value = radio.value;
                hidden.setAttribute('data-dynamic-option', '1');
                buyForm.appendChild(hidden);
            });
        });
    }

    const reviewsPanel = document.getElementById('pdp-reviews-panel');
    if (reviewsPanel) {
        const slug = reviewsPanel.dataset.productSlug;
        const listEl = document.getElementById('pdp-reviews-list');
        const moreBtn = document.getElementById('pdp-reviews-more-btn');
        const form = document.getElementById('pdp-review-form');
        let page = 1;

        const escapeHtml = (str) => String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const renderReview = (item) => {
            const replies = (item.replies || []).map((r) => `
                <div class="pdp-review-item">
                    <div><b>${escapeHtml(r.author_name)}</b> · ${escapeHtml(r.created_at)}</div>
                    <div>${escapeHtml(r.body)}</div>
                </div>
            `).join('');

            return `
                <div class="pdp-review-item">
                    <div><b>${escapeHtml(item.author_name)}</b> · ${escapeHtml(item.created_at)}</div>
                    <div>Рейтинг: ${item.rating !== null ? escapeHtml(item.rating) : '-'}</div>
                    <div>${escapeHtml(item.body)}</div>
                    ${form ? `<button class="pdp-reply-btn pdp-btn pdp-btn-ghost" data-id="${escapeHtml(item.id)}">Відповісти</button>` : ''}
                    <div class="pdp-review-replies">${replies}</div>
                </div>
            `;
        };

        const loadReviews = async () => {
            try {
                const res = await fetch(`/product/${encodeURIComponent(slug)}/reviews?page=${page}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (!data.success) return;

                listEl.insertAdjacentHTML('beforeend', data.items.map(renderReview).join(''));
                moreBtn.style.display = data.has_more ? 'inline-block' : 'none';
            } catch (e) {
                console.error('Reviews load error', e);
            }
        };

        loadReviews();

        if (moreBtn) {
            moreBtn.addEventListener('click', () => {
                page += 1;
                loadReviews();
            });
        }

        if (listEl) {
            listEl.addEventListener('click', (e) => {
                const btn = e.target.closest('.pdp-reply-btn');
                if (!btn) return;

                const parentIdEl = document.getElementById('pdp-review-parent-id');
                const ratingEl = document.getElementById('pdp-review-rating');

                if (parentIdEl) parentIdEl.value = btn.dataset.id || '';
                if (ratingEl) ratingEl.value = '';
            });
        }

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                try {
                    const fd = new FormData(form);
                    const res = await fetch(`/product/${encodeURIComponent(slug)}/reviews`, {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();

                    if (!data.success) {
                        alert(data.message || 'Помилка');
                        return;
                    }

                    listEl.innerHTML = '';
                    page = 1;

                    const parentIdEl = document.getElementById('pdp-review-parent-id');
                    const ratingEl = document.getElementById('pdp-review-rating');
                    const bodyEl = document.getElementById('pdp-review-body');

                    if (parentIdEl) parentIdEl.value = '';
                    if (ratingEl) ratingEl.value = '5';
                    if (bodyEl) bodyEl.value = '';

                    loadReviews();
                } catch (err) {
                    console.error('Reviews submit error', err);
                    alert('Помилка відправки відгуку');
                }
            });
        }
    }
})();
</script>
