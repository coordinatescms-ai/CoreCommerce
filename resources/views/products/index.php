<div style="display: flex; justify-content: space-between; gap: 1rem; align-items: baseline; margin-bottom: 1rem; flex-wrap: wrap;">
    <h2 style="margin: 0;"><?= __('products_popular_title') ?></h2>
    <div style="font-size: 0.9rem; color: #64748b;">
        <?= __('products_found_label') ?>: <strong><?= (int)($total ?? count($products ?? [])); ?></strong>
    </div>
</div>

<?php
if (!function_exists('renderCategoryAccordionTree')) {
    function renderCategoryAccordionTree(array $items, int $depth = 0): void
    {
        if (empty($items)) {
            return;
        }

        echo '<ul class="products-category-list">';

        foreach ($items as $item) {
            $children = $item['children'] ?? [];
            $hasChildren = !empty($children);
            $padding = 12 + ($depth * 16);

            echo '<li class="products-category-item">';
            echo '<div class="products-category-row" style="padding-left:' . $padding . 'px">';
            echo '<a href="/category/' . htmlspecialchars((string) ($item['slug'] ?? '')) . '" class="products-category-link">';
            echo htmlspecialchars((string) ($item['name'] ?? ''));
            echo '</a>';

            if ($hasChildren) {
                echo '<button type="button" class="products-category-toggle" data-accordion-trigger aria-expanded="false" aria-label="Toggle subcategories">';
                echo '<svg class="products-category-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">';
                echo '<path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>';
                echo '</svg>';
                echo '</button>';
            }

            echo '</div>';

            if ($hasChildren) {
                echo '<div class="products-category-panel" data-accordion-panel style="max-height:0; opacity:.4;">';
                renderCategoryAccordionTree($children, $depth + 1);
                echo '</div>';
            }

            echo '</li>';
        }

        echo '</ul>';
    }
}
?>

<div style="display:grid; grid-template-columns: 300px 1fr; gap: 1.25rem; align-items: start;">
    <aside>
        <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff;">
            <h3 style="margin: 0 0 0.75rem;"><?= __('categories') ?></h3>
            <?php if (empty($categories ?? [])): ?>
                <p style="margin: 0; color: #64748b;"><?= __('categories_not_found') ?></p>
            <?php else: ?>
                <div class="products-category-nav">
                    <?php renderCategoryAccordionTree(($categories ?? [])); ?>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <section>
        <?php if (empty($products)): ?>
            <p><?= __('no_products_found') ?></p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem;">
                <?php foreach($products as $product): ?>
                    <article style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #fff; position: relative;">
                        <span style="position: absolute; top: 0.75rem; right: 0.75rem; display: inline-block; padding: 0.2rem 0.5rem; border-radius: 999px; background: #f59e0b; color: #111827; font-size: 0.75rem; font-weight: 700;">
                            <?= __('products_top_badge') ?>
                        </span>
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars(product_image_variant_path((string) $product['image'], 'medium')) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 0.75rem;">
                        <?php endif; ?>
                        <h3 style="margin: 0 0 0.5rem;">
                            <a href="/product/<?= htmlspecialchars($product['slug']) ?>" style="text-decoration: none; color: #111827;"><?= htmlspecialchars($product['name']) ?></a>
                        </h3>
                        <p style="margin: 0 0 0.75rem;"><strong><?= htmlspecialchars($product['price']) ?> грн</strong></p>
                        <form action="/cart/add/<?= (int)$product['id'] ?>" method="POST" style="display: inline-block; margin: 0; width: 100%;">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                            <button type="submit" style="display: inline-block; padding: 0.5rem 0.85rem; background: #111827; color: #fff; text-decoration: none; border-radius: 0.45rem; border: 0; cursor: pointer; width: 100%;">
                                <?= __('add_to_cart') ?>
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (($pages ?? 1) > 1): ?>
            <div style="margin-top: 1.5rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                <?php
                    $query = $_GET;
                    for ($i = 1; $i <= (int)$pages; $i++):
                        $query['page'] = $i;
                        $url = '/products?' . http_build_query($query);
                ?>
                    <a href="<?= htmlspecialchars($url); ?>"
                       style="padding: 0.45rem 0.75rem; border-radius: 4px; border: 1px solid #ddd; text-decoration:none; color: <?= $i == (int)$page ? '#fff' : '#334155'; ?>; background: <?= $i == (int)$page ? '#2563eb' : '#fff'; ?>;">
                        <?= $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
@media (max-width: 992px) {
    div[style*="grid-template-columns: 300px 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<style>
.products-category-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.25rem;}
.products-category-row{display:flex;align-items:center;justify-content:space-between;gap:.25rem;}
.products-category-link{text-decoration:none;color:#111827;padding:.35rem 0;display:block;flex:1;}
.products-category-toggle{border:0;background:transparent;cursor:pointer;color:#64748b;padding:.25rem;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;}
.products-category-toggle:hover{background:#f1f5f9;color:#0f172a;}
.products-category-icon{width:16px;height:16px;transition:transform .2s ease;}
.products-category-icon.is-open{transform:rotate(90deg);}
.products-category-panel{overflow:hidden;transition:max-height .22s ease, opacity .22s ease;}
</style>
<script>
document.querySelectorAll('[data-accordion-trigger]').forEach((trigger)=>{
  trigger.addEventListener('click', ()=>{
    const panel = trigger.parentElement?.parentElement?.querySelector(':scope > [data-accordion-panel]');
    const icon = trigger.querySelector('.products-category-icon');
    if(!panel) return;
    const isOpen = trigger.getAttribute('aria-expanded') === 'true';
    trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    icon?.classList.toggle('is-open', !isOpen);
    if (isOpen){panel.style.maxHeight='0';panel.style.opacity='0.4';} else {panel.style.maxHeight=panel.scrollHeight + 'px';panel.style.opacity='1';}
  });
});
</script>
