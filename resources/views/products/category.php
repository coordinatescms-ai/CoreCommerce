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

        echo '<ul class="space-y-1" role="tree">';

        foreach ($items as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            $children = $item['children'] ?? [];
            $hasChildren = !empty($children);
            $isActive = $itemId === $currentCategoryId;
            $isExpanded = $isActive || in_array($itemId, $expandedIds, true);

            echo '<li class="relative" role="treeitem" aria-expanded="' . ($isExpanded ? 'true' : 'false') . '">';
            echo '<div class="relative flex items-center gap-1 rounded-lg transition-colors">';
            echo '<a href="/category/' . htmlspecialchars((string) ($item['slug'] ?? '')) . '" class="block min-w-0 flex-1 rounded-lg py-2 pr-2 text-sm transition-colors ';
            echo $isActive ? 'bg-blue-50 font-semibold text-blue-700' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900';
            echo '" style="padding-left:' . $padding . 'px">';
            echo htmlspecialchars((string) ($item['name'] ?? ''));
            echo '</a>';

            if ($hasChildren) {
                echo '<button type="button" class="category-accordion-trigger mr-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"';
                echo ' data-accordion-trigger aria-label="Toggle subcategories" aria-expanded="' . ($isExpanded ? 'true' : 'false') . '">';
                echo '<svg class="h-4 w-4 transition-transform ' . ($isExpanded ? 'rotate-90' : '') . '" viewBox="0 0 24 24" fill="none" aria-hidden="true">';
                echo '<path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>';
                echo '</svg>';
                echo '</button>';
            }

            echo '</div>';

            if ($hasChildren) {
                echo '<div class="category-accordion-panel overflow-hidden transition-all duration-300 ease-out" data-accordion-panel';
                echo $isExpanded ? ' style="max-height: 1000px; opacity:1;"' : ' style="max-height:0; opacity:.4;"';
                echo '>';
                echo '<div class="ml-3 border-l border-slate-200 pl-2">';
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
    <nav class="mb-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm" aria-label="Breadcrumb">
        <ol class="m-0 flex list-none flex-wrap items-center gap-2 p-0 text-slate-500">
            <li>
                <a class="inline-flex items-center gap-2 rounded-md px-1.5 py-1 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900" href="/">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 10.5L12 3L21 10.5V20A1 1 0 0 1 20 21H4A1 1 0 0 1 3 20V10.5Z" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                    <span>Головна</span>
                </a>
            </li>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php $isLast = $index === count($breadcrumbs) - 1; ?>
                <li aria-hidden="true" class="text-slate-300">/</li>
                <li>
                    <?php if ($isLast): ?>
                        <span class="rounded-md bg-slate-100 px-2 py-1 font-medium text-slate-800"><?= htmlspecialchars($crumb['name'] ?? '') ?></span>
                    <?php else: ?>
                        <a class="rounded-md px-1.5 py-1 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900" href="<?= htmlspecialchars($crumb['url'] ?? '#') ?>">
                            <?= htmlspecialchars($crumb['name'] ?? '') ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <section class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h1 class="!mb-0 text-2xl font-semibold text-slate-900"><?= htmlspecialchars($category['name'] ?? __('categories')) ?></h1>
            <div class="text-sm text-slate-600">
                <?= __('products') ?>: <strong id="category-total-products"><?= $totalProducts ?></strong>
            </div>
        </div>
        <?php if (!empty($category['description'])): ?>
            <p class="category-description mb-0 mt-2"><?= nl2br(htmlspecialchars($category['description'])) ?></p>
        <?php endif; ?>
    </section>

    <div class="category-layout">
        <aside class="category-sidebar">
            <section class="mb-4 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <h2 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-slate-700">
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
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

.category-description {
    color: #64748b;
    margin-bottom: 0;
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

    pageRoot.querySelectorAll('[data-accordion-trigger]').forEach((trigger) => {
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
                icon.classList.toggle('rotate-90', !isExpanded);
                icon.classList.toggle('rotate-0', isExpanded);
            }
        });
    });
})();
</script>
