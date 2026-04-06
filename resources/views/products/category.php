<?php
$category = $category ?? [];
$products = $products ?? [];
$totalProducts = (int) ($totalProducts ?? count($products));
$page = max(1, (int) ($page ?? 1));
$pages = max(1, (int) ($pages ?? 1));
$categorySlug = $category['slug'] ?? '';
$currentCategoryId = (int) ($category['id'] ?? 0);
$categoryTree = $categoryTree ?? [];
$breadcrumbs = $breadcrumbs ?? [];
$expandedCategoryIds = array_values(array_filter(array_map(static function ($crumb) {
    return (int) ($crumb['id'] ?? 0);
}, $breadcrumbs)));
$childCategories = $childCategories ?? [];

if (!function_exists('renderCategorySidebarAccordion')) {
    /**
     * Рекурсивно рендерить дерево категорій для sidebar-accordion.
     *
     * @param array $items
     * @param int $currentCategoryId
     * @param array $expandedIds
     * @param int $depth
     * @return void
     */
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
?>

<div class="category-page" data-category-page>
    <nav class="category-breadcrumbs" aria-label="Breadcrumb">
        <ol>
            <li>
                <a class="breadcrumb-link breadcrumb-link-home" href="/">
                    <svg class="breadcrumb-home-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 10.5L12 3L21 10.5V20A1 1 0 0 1 20 21H4A1 1 0 0 1 3 20V10.5Z" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                    <span>Головна</span>
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

    <section class="category-heading">
        <div class="category-heading-top">
            <h1 class="category-title"><?= htmlspecialchars($category['name'] ?? __('categories')) ?></h1>
            <div class="category-total">
                <?= __('products') ?>: <strong id="category-total-products"><?= $totalProducts ?></strong>
            </div>
        </div>
        <?php if (!empty($category['description'])): ?>
            <p class="category-description"><?= nl2br(htmlspecialchars($category['description'])) ?></p>
        <?php endif; ?>
    </section>

    <div class="category-layout">
        <aside class="category-sidebar">
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

            <?php
            $filterAjaxEnabled = true;
            include __DIR__ . '/../components/product_filters.php';
            ?>
        </aside>

        <section class="category-content">
            <div id="category-products" data-products-container>
                <?php include __DIR__ . '/partials/category_products.php'; ?>
            </div>
        </section>
    </div>
</div>

<style>
.category-layout {
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

.category-heading {
    margin-bottom: 1rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 10px;
    padding: 1rem;
}

.category-heading-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.category-title {
    margin: 0;
    font-size: 2rem;
}

.category-total {
    color: #475569;
}

.category-description {
    color: #64748b;
    margin-bottom: 0;
    margin-top: 0.4rem;
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

.category-products-grid {
    margin-top: 0;
}

.category-product-image-link {
    display: block;
}

.category-product-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
}

.category-product-image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    color: #94a3b8;
}

.category-product-content .btn {
    width: 100%;
    text-align: center;
}

.category-empty-state {
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

.category-pagination {
    margin-top: 1.25rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.category-pagination-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.2rem;
    height: 2.2rem;
    padding: 0 0.6rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #334155;
    text-decoration: none;
}

.category-pagination-link.is-current,
.category-pagination-link:hover {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

@media (max-width: 992px) {
    .category-layout {
        grid-template-columns: 1fr;
    }

    .category-title {
        font-size: 1.6rem;
    }
}
</style>

<script>
(() => {
    const pageRoot = document.querySelector('[data-category-page]');
    if (!pageRoot) {
        return;
    }

    const filterForm = pageRoot.querySelector('#filter-form');
    const productsContainer = pageRoot.querySelector('[data-products-container]');
    const totalElement = pageRoot.querySelector('#category-total-products');
    const categorySlug = <?= json_encode($categorySlug) ?>;
    const accordionTriggers = pageRoot.querySelectorAll('[data-accordion-trigger]');

    accordionTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const panel = trigger.parentElement?.parentElement?.querySelector(':scope > [data-accordion-panel]');
            const icon = trigger.querySelector('svg');
            if (!panel) {
                return;
            }

            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            trigger.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            panel.style.maxHeight = isExpanded ? '0px' : `${panel.scrollHeight}px`;
            panel.style.opacity = isExpanded ? '0.4' : '1';

            if (icon) {
                icon.classList.toggle('is-open', !isExpanded);
            }
        });
    });

    if (!filterForm || !productsContainer || !categorySlug) {
        return;
    }

    let controller = null;
    let debounceTimer = null;

    const fetchProducts = (page = null) => {
        const formData = new FormData(filterForm);
        if (page !== null) {
            formData.set('page', String(page));
        }

        const query = new URLSearchParams(formData);
        query.set('ajax', '1');

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();

        fetch(`/category/${encodeURIComponent(categorySlug)}/filter?${query.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Unable to filter products');
                }

                return response.json();
            })
            .then((data) => {
                if (!data || typeof data.html !== 'string') {
                    return;
                }

                productsContainer.innerHTML = data.html;
                if (totalElement && typeof data.total !== 'undefined') {
                    totalElement.textContent = String(data.total);
                }

                const cleanQuery = new URLSearchParams(formData);
                if (page !== null) {
                    cleanQuery.set('page', String(page));
                }
                const queryString = cleanQuery.toString();
                const nextUrl = queryString
                    ? `/category/${encodeURIComponent(categorySlug)}?${queryString}`
                    : `/category/${encodeURIComponent(categorySlug)}`;
                window.history.replaceState({}, '', nextUrl);
            })
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    console.error(error);
                }
            });
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        fetchProducts(1);
    });

    filterForm.addEventListener('change', () => {
        fetchProducts(1);
    });

    filterForm.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (!target.matches('input[type="number"], input[type="text"], input[type="search"]')) {
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchProducts(1), 300);
    });

    productsContainer.addEventListener('click', (event) => {
        const link = event.target.closest('.category-pagination-link[data-page]');
        if (!link) {
            return;
        }

        event.preventDefault();
        const page = Number(link.getAttribute('data-page'));
        if (Number.isFinite(page) && page > 0) {
            fetchProducts(page);
        }
    });
})();
</script>
