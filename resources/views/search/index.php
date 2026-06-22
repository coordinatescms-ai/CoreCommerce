<?php
/** @var array $results */
/** @var int   $total */
/** @var int   $page */
/** @var int   $pages */
/** @var string $query */
/** @var string $safeQuery */
/** @var string[] $tokens */
/** @var string|null $suggestion */
/** @var array $popularQueries */
?>

<div class="search-page">

    <!-- ── Заголовок ─────────────────────────────────────────────── -->
    <div class="search-header">
        <h1 class="search-title">
            <?php if ($query !== ''): ?>
                <?= __('search_results_for') ?>:
                <span class="search-query-display">"<?= $safeQuery ?>"</span>
            <?php else: ?>
                <?= __('search') ?>
            <?php endif; ?>
        </h1>
        <?php if ($query !== '' && $total > 0): ?>
            <span class="search-count">
                <?= __('search_found') ?> <?= $total ?> <?= __('search_items') ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- ── Форма пошуку ───────────────────────────────────────────── -->
    <form class="search-form" action="/search" method="GET" role="search">
        <div class="search-input-wrap">
            <i class="fas fa-search search-input-icon"></i>
            <input
                type="search"
                name="q"
                class="search-input"
                value="<?= $safeQuery ?>"
                placeholder="<?= htmlspecialchars(__('search_placeholder') ?: 'Пошук товарів...') ?>"
                autocomplete="off"
                autofocus
                aria-label="<?= htmlspecialchars(__('search')) ?>">
            <button type="submit" class="search-submit-btn">
                <?= __('search') ?>
            </button>
        </div>
    </form>

    <?php if ($query === ''): ?>
    <!-- ── Порожній стан: показуємо популярні запити ─────────────── -->
    <?php if (!empty($popularQueries)): ?>
    <div class="search-popular">
        <h3 class="search-popular-title">
            <i class="fas fa-fire"></i> <?= __('search_popular') ?>
        </h3>
        <div class="search-popular-tags">
            <?php foreach ($popularQueries as $pq): ?>
            <a href="/search?q=<?= urlencode($pq['query']) ?>" class="search-tag">
                <?= htmlspecialchars($pq['query']) ?>
                <span class="search-tag-count"><?= $pq['search_count'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif ($total === 0): ?>
    <!-- ── Нічого не знайдено ────────────────────────────────────── -->
    <div class="search-empty">
        <i class="fas fa-search search-empty-icon"></i>

        <?php if ($suggestion !== null): ?>
        <p class="search-empty-title">
            <?= __('search_not_found_for') ?> "<?= $safeQuery ?>"
        </p>
        <p class="search-suggestion">
            <?= __('search_did_you_mean') ?>
            <a href="/search?q=<?= urlencode($suggestion) ?>" class="search-suggestion-link">
                <?= htmlspecialchars($suggestion) ?>
            </a>?
        </p>
        <?php else: ?>
        <p class="search-empty-title">
            <?= __('search_nothing_found') ?>
        </p>
        <p class="search-empty-hint"><?= __('search_try_different') ?></p>
        <?php endif; ?>

        <?php if (!empty($popularQueries)): ?>
        <div class="search-popular" style="margin-top:1.5rem;">
            <h3 class="search-popular-title">
                <?= __('search_popular') ?>
            </h3>
            <div class="search-popular-tags">
                <?php foreach ($popularQueries as $pq): ?>
                <a href="/search?q=<?= urlencode($pq['query']) ?>" class="search-tag">
                    <?= htmlspecialchars($pq['query']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ── Результати ────────────────────────────────────────────── -->
    <div class="search-results-grid">
        <?php foreach ($results as $product): ?>
        <?php
        $imageUrl = !empty($product['image'])
            ? htmlspecialchars(product_image_variant_path($product['image'], 'medium'))
            : '/resources/themes/default/img/no-image.png';
        ?>
        <article class="search-card">
            <a href="/product/<?= htmlspecialchars($product['slug']) ?>" class="search-card-img-link">
                <img
                    src="<?= $imageUrl ?>"
                    alt="<?= htmlspecialchars($product['name']) ?>"
                    class="search-card-img"
                    loading="lazy">
            </a>
            <div class="search-card-body">
                <a href="/product/<?= htmlspecialchars($product['slug']) ?>" class="search-card-title">
                    <?= \App\Services\SearchService::highlight($product['name'], $tokens, 80) ?>
                </a>
                <?php if (!empty($product['category_name'])): ?>
                <span class="search-card-category">
                    <i class="fas fa-folder"></i>
                    <?= htmlspecialchars($product['category_name']) ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($product['description'])): ?>
                <p class="search-card-desc">
                    <?= \App\Services\SearchService::highlight($product['description'], $tokens, 160) ?>
                </p>
                <?php endif; ?>
                <div class="search-card-footer">
                    <span class="search-card-price">
                        <?= format_price($product['price']) ?>
                    </span>
                    <?php
                    $inStock = (int)($product['stock_qty'] ?? 0) > 0;
                    ?>
                    <span class="search-card-stock <?= $inStock ? 'in-stock' : 'out-of-stock' ?>">
                        <i class="fas <?= $inStock ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                        <?= $inStock ? __('in_stock') : __('out_of_stock') ?>
                    </span>
                    <a href="/product/<?= htmlspecialchars($product['slug']) ?>"
                       class="btn btn-primary btn-sm">
                        <?= __('view') ?>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- ── Пагінація ─────────────────────────────────────────────── -->
    <?php if ($pages > 1): ?>
    <nav class="search-pagination" aria-label="<?= __('pagination') ?>">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="/search?q=<?= urlencode($query) ?>&page=<?= $p ?>"
           class="search-page-btn <?= $p === $page ? 'active' : '' ?>"
           <?= $p === $page ? 'aria-current="page"' : '' ?>>
            <?= $p ?>
        </a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>

</div>

<style>
.search-page { max-width:900px; margin:0 auto; }
.search-header { display:flex; align-items:baseline; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem; }
.search-title { font-size:1.4rem; font-weight:700; color:#1e293b; margin:0; }
.search-query-display { color:#3b82f6; }
.search-count { font-size:.9rem; color:#64748b; }

.search-form { margin-bottom:1.75rem; }
.search-input-wrap { display:flex; align-items:center; gap:0; border:2px solid #e2e8f0; border-radius:10px; overflow:hidden; background:#fff; transition:border-color .2s; }
.search-input-wrap:focus-within { border-color:#3b82f6; }
.search-input-icon { padding:0 .75rem; color:#94a3b8; font-size:1rem; flex-shrink:0; }
.search-input { flex:1; border:none; outline:none; padding:.75rem .5rem; font-size:1rem; background:transparent; }
.search-submit-btn { padding:.75rem 1.5rem; background:#3b82f6; color:#fff; border:none; font-size:.95rem; font-weight:600; cursor:pointer; transition:background .2s; white-space:nowrap; }
.search-submit-btn:hover { background:#2563eb; }

.search-popular { margin-top:1rem; }
.search-popular-title { font-size:.9rem; color:#64748b; margin:0 0 .6rem; display:flex; align-items:center; gap:.4rem; }
.search-popular-tags { display:flex; flex-wrap:wrap; gap:.5rem; }
.search-tag { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .75rem; background:#f1f5f9; border-radius:999px; font-size:.83rem; color:#475569; text-decoration:none; transition:background .15s; }
.search-tag:hover { background:#e2e8f0; color:#1e293b; }
.search-tag-count { font-size:.75rem; background:#cbd5e1; border-radius:999px; padding:.1rem .4rem; }

.search-empty { text-align:center; padding:3rem 1rem; }
.search-empty-icon { font-size:3rem; color:#e2e8f0; display:block; margin-bottom:1rem; }
.search-empty-title { font-size:1.1rem; color:#475569; margin:0 0 .5rem; }
.search-empty-hint { color:#94a3b8; font-size:.9rem; }
.search-suggestion { color:#64748b; margin:.5rem 0 0; }
.search-suggestion-link { color:#3b82f6; font-weight:600; text-decoration:underline; }

.search-results-grid { display:grid; gap:1rem; }
.search-card { display:grid; grid-template-columns:120px 1fr; gap:1rem; padding:1rem; background:#fff; border:1px solid #e2e8f0; border-radius:10px; transition:box-shadow .2s; }
.search-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.08); }
.search-card-img-link { display:block; }
.search-card-img { width:120px; height:120px; object-fit:cover; border-radius:7px; }
.search-card-body { display:flex; flex-direction:column; gap:.4rem; }
.search-card-title { font-size:1rem; font-weight:600; color:#1e293b; text-decoration:none; line-height:1.4; }
.search-card-title:hover { color:#3b82f6; }
.search-card-title mark { background:#fef08a; border-radius:2px; padding:0 .15rem; }
.search-card-category { font-size:.78rem; color:#94a3b8; display:flex; align-items:center; gap:.3rem; }
.search-card-desc { font-size:.83rem; color:#64748b; line-height:1.5; margin:0; }
.search-card-desc mark { background:#fef08a; border-radius:2px; }
.search-card-footer { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; margin-top:auto; padding-top:.5rem; }
.search-card-price { font-size:1.1rem; font-weight:700; color:#1e293b; }
.search-card-stock { font-size:.78rem; display:flex; align-items:center; gap:.3rem; }
.search-card-stock.in-stock { color:#10b981; }
.search-card-stock.out-of-stock { color:#ef4444; }

.search-pagination { display:flex; gap:.4rem; justify-content:center; margin-top:2rem; flex-wrap:wrap; }
.search-page-btn { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:8px; border:1px solid #e2e8f0; color:#475569; text-decoration:none; font-size:.88rem; transition:all .15s; }
.search-page-btn:hover, .search-page-btn.active { background:#3b82f6; color:#fff; border-color:#3b82f6; }

@media (max-width:640px) {
    .search-card { grid-template-columns:80px 1fr; }
    .search-card-img { width:80px; height:80px; }
}
</style>
