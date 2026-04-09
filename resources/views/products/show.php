<?php
$productName = htmlspecialchars($product['name'] ?? '');
$productPrice = isset($product['price']) ? number_format((float) $product['price'], 2, '.', ' ') : '0.00';
$productDescription = trim((string) ($product['description'] ?? ''));
$shortDescription = function_exists('mb_substr') ? mb_substr($productDescription, 0, 220) : substr($productDescription, 0, 220);
if ((function_exists('mb_strlen') ? mb_strlen($productDescription) : strlen($productDescription)) > 220) {
    $shortDescription .= '…';
}

$placeholderImage = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#64748b" font-family="Arial" font-size="36">No Image</text></svg>');
$mainImage = !empty($product['image']) ? $product['image'] : $placeholderImage;
$galleryItems = $galleryImages ?? [];
$galleryImages = [];
foreach ($galleryItems as $galleryImage) {
    $path = (string) ($galleryImage['image_path'] ?? '');
    if ($path !== '') {
        $galleryImages[] = $path;
    }
}
if (empty($galleryImages)) {
    $galleryImages = [$mainImage];
}
if (!in_array($mainImage, $galleryImages, true)) {
    array_unshift($galleryImages, $mainImage);
}
$galleryImages = array_values(array_unique($galleryImages));
$similarProducts = $similarProducts ?? [];
$categoryTree = $categoryTree ?? [];
$currentCategoryId = (int) (($category['id'] ?? 0));

$groupedSelectableAttributes = [];
foreach (($selectableAttributes ?? []) as $attribute) {
    $label = $attribute['attribute_name'] ?? __('attribute');
    $groupedSelectableAttributes[$label][] = $attribute['value'] ?? '';
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
?>

<section class="pdp">
    <nav class="pdp-breadcrumbs" aria-label="Breadcrumb">
        <a href="/"><?= __('breadcrumb_home') ?></a>
        <span>/</span>
        <a href="/products"><?= __('breadcrumb_products') ?></a>
        <?php foreach (($breadcrumbs ?? []) as $crumb): ?>
            <span>/</span>
            <a href="/category/<?= htmlspecialchars($crumb['slug']) ?>"><?= htmlspecialchars($crumb['name']) ?></a>
        <?php endforeach; ?>
        <span>/</span>
        <span class="is-current"><?= $productName ?></span>
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
                                data-image="<?= htmlspecialchars($image) ?>"
                                aria-label="Фото <?= $idx + 1 ?>"
                            >
                                <img src="<?= htmlspecialchars($image) ?>" alt="<?= $productName ?> thumbnail <?= $idx + 1 ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pdp-info">
                    <h1><?= $productName ?></h1>
                    <div class="pdp-price"><?= $productPrice ?> грн</div>

                    <p class="pdp-short-description">
                        <strong><?= __('short_description') ?>:</strong>
                        <?= htmlspecialchars($shortDescription !== '' ? $shortDescription : __('no_products_found')) ?>
                    </p>

                    <div class="pdp-options" aria-label="Product options">
                        <h3>Оберіть характеристики</h3>
                        <?php if (!empty($groupedSelectableAttributes)): ?>
                            <?php foreach ($groupedSelectableAttributes as $attributeName => $values): ?>
                                <div class="pdp-option-group">
                                    <div class="pdp-option-label"><?= htmlspecialchars($attributeName) ?></div>
                                    <div class="pdp-option-values">
                                        <?php foreach (array_unique(array_filter($values)) as $value): ?>
                                            <button type="button" class="pdp-chip"><?= htmlspecialchars((string) $value) ?></button>
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
                        <button type="button" class="pdp-btn pdp-btn-ghost"><?= __('wishlist') ?></button>
                    </div>
                    <div class="mt-2 small text-muted">
                        <?= __('in_stock') ?>: <?= (int)($product['stock'] ?? 0) ?>
                    </div>
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
                    <p><?= __('reviews_empty') ?></p>
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

.pdp-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
}

.pdp-breadcrumbs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 1.25rem;
    color: var(--pdp-muted);
    font-size: 0.92rem;
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
(() => {
    const mainImage = document.getElementById('pdp-main-image');
    if (!mainImage) {
        return;
    }

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
})();
</script>
