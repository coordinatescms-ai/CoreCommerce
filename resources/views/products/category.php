<?php
$category = $category ?? [];
$products = $products ?? [];
$totalProducts = (int) ($totalProducts ?? count($products));
$page = max(1, (int) ($page ?? 1));
$pages = max(1, (int) ($pages ?? 1));
$categorySlug = $category['slug'] ?? '';
$childCategories = $childCategories ?? [];
?>

<div class="category-page" data-category-page>
    <header class="category-header">
        <h1><?= htmlspecialchars($category['name'] ?? __('categories')) ?></h1>
        <?php if (!empty($category['description'])): ?>
            <p class="category-description"><?= nl2br(htmlspecialchars($category['description'])) ?></p>
        <?php endif; ?>

        <div class="category-results">
            <?= __('products') ?>: <strong id="category-total-products"><?= $totalProducts ?></strong>
        </div>

        <?php if (!empty($childCategories)): ?>
            <div class="category-children">
                <?php foreach ($childCategories as $child): ?>
                    <a class="category-child-link" href="/category/<?= htmlspecialchars($child['slug']) ?>">
                        <?= htmlspecialchars($child['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </header>

    <div class="category-layout">
        <aside class="category-sidebar">
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

.category-header {
    margin-bottom: 1.25rem;
}

.category-description {
    color: #64748b;
    margin-bottom: 0.75rem;
}

.category-results {
    color: #334155;
    margin-bottom: 0.75rem;
}

.category-children {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.category-child-link {
    display: inline-block;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    padding: 0.35rem 0.7rem;
    color: #334155;
    text-decoration: none;
    font-size: 0.9rem;
}

.category-child-link:hover {
    background: #eff6ff;
    border-color: #93c5fd;
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
})();
</script>
