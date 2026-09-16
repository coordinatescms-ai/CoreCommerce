<?php
/**
 * @var array $items             Товари у списку порівняння (App\Models\CompareList::getItems())
 * @var array $attributesMatrix  Матриця характеристик (App\Models\CompareList::getAttributesMatrix())
 * @var int   $maxItems
 * @var string $csrf
 */
$items = $items ?? [];
$attributesMatrix = $attributesMatrix ?? [];
$maxItems = $maxItems ?? 4;
$csrf = $csrf ?? ($_SESSION['csrf'] ?? '');

/**
 * Чи є в рядку хоч одна відмінність між товарами (для режиму "Тільки відмінності").
 * null/порожнє значення теж вважається окремим станом — відсутність характеристики
 * в одного з товарів це так само значуща відмінність.
 */
$rowHasDiff = static function (array $valuesByProductId): bool {
    $normalized = array_map(
        static fn ($v) => $v === null || trim((string) $v) === '' ? "\0__empty__" : trim((string) $v),
        array_values($valuesByProductId)
    );
    return count(array_unique($normalized)) > 1;
};
?>
<div class="container my-5" style="max-width: 1200px; margin: 0 auto; font-family: sans-serif;">
    <h2 style="font-weight: 700; color: #333; margin-bottom: 0.4rem;"><?= __('compare') ?></h2>

    <?php if (empty($items)): ?>
        <p style="color: #6b7280; margin-bottom: 1.5rem;"><?= sprintf(__('compare_empty_hint'), $maxItems) ?></p>
        <div style="text-align: center; padding: 40px; background: #fff; border-radius: 10px; border: 1px solid #ddd;">
            <p><?= __('compare_empty') ?></p>
            <a href="/products" class="btn btn-primary" style="display: inline-block; padding: 0.6rem 1.2rem; background: #111827; color: #fff; border-radius: 0.45rem; text-decoration: none; margin-top: 0.5rem;"><?= __('continue_shopping') ?></a>
        </div>
    <?php else: ?>
        <p style="color: #6b7280; margin-bottom: 1.25rem;"><?= sprintf(__('compare_empty_hint'), $maxItems) ?></p>

        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
            <?php if (count($items) > 1): ?>
                <div class="compare-mode-toggle" role="tablist" style="display: inline-flex; background: #f3f4f6; border-radius: 0.6rem; padding: 0.25rem;">
                    <button type="button" class="compare-mode-btn is-active" data-compare-mode="all" style="border: 0; background: #fff; color: #111827; font-weight: 600; padding: 0.5rem 1rem; border-radius: 0.45rem; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                        <?= __('compare_all_specs') ?>
                    </button>
                    <button type="button" class="compare-mode-btn" data-compare-mode="diff" style="border: 0; background: transparent; color: #6b7280; font-weight: 600; padding: 0.5rem 1rem; border-radius: 0.45rem; cursor: pointer;">
                        <?= __('compare_only_differences') ?>
                    </button>
                </div>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <form action="/compare/clear" method="POST" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('compare_clear_all')), ENT_QUOTES) ?>);">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <button type="submit" style="background: none; border: none; color: #dc3545; cursor: pointer; font-size: 14px; font-weight: 600;"><?= __('compare_clear_all') ?></button>
            </form>
        </div>

        <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #fff;">
            <table id="compareTable" class="compare-table" style="width: 100%; border-collapse: collapse; min-width: <?= 220 + count($items) * 220 ?>px;">
                <thead>
                    <tr>
                        <th style="width: 200px; padding: 1rem; text-align: left; vertical-align: bottom; border-bottom: 2px solid #e5e7eb; background: #fafafa;"></th>
                        <?php foreach ($items as $item): ?>
                            <th style="padding: 1rem; min-width: 220px; text-align: center; vertical-align: top; border-bottom: 2px solid #e5e7eb; border-left: 1px solid #f0f0f0;">
                                <form action="/compare/remove/<?= (int) $item['id'] ?>" method="POST" style="margin: 0 0 0.5rem; text-align: right;">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                    <button type="submit" title="<?= htmlspecialchars(__('compare_remove')) ?>" style="background: #fff; color: #ff5c5c; border: 1px solid #ffeded; width: 26px; height: 26px; border-radius: 5px; cursor: pointer; font-weight: bold; line-height: 1;">✕</button>
                                </form>

                                <a href="/product/<?= htmlspecialchars($item['slug']) ?>" style="display: block; text-decoration: none; color: #111827;">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= htmlspecialchars(product_image_variant_path((string) $item['image'], 'medium')) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 100%; max-width: 160px; height: 140px; object-fit: cover; border-radius: 0.5rem; margin: 0 auto 0.6rem; display: block;">
                                    <?php endif; ?>
                                    <span style="display: block; font-weight: 600; font-size: 0.95rem; line-height: 1.3;"><?= htmlspecialchars($item['name']) ?></span>
                                </a>

                                <div style="margin-top: 0.6rem; font-weight: 700; font-size: 1.05rem;"><?= render_product_price($item) ?></div>

                                <?php $outOfStock = render_stock_badge($item) !== ''; ?>
                                <?php if ($outOfStock): ?>
                                    <div style="margin-top: 0.4rem;"><?= render_stock_badge($item) ?></div>
                                <?php endif; ?>

                                <form action="/cart/add/<?= (int) $item['id'] ?>" method="POST" style="margin-top: 0.6rem;">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="return_url" value="/compare">
                                    <button type="submit" <?= $outOfStock ? 'disabled' : '' ?> style="width: 100%; padding: 0.5rem 0.75rem; background: <?= $outOfStock ? '#9ca3af' : '#111827' ?>; color: #fff; border: 0; border-radius: 0.45rem; cursor: <?= $outOfStock ? 'not-allowed' : 'pointer' ?>; font-size: 0.85rem; font-weight: 600;">
                                        <?= $outOfStock ? __('out_of_stock') : __('add_to_cart') ?>
                                    </button>
                                </form>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Ряд "Ціна" — теж характеристика для порівняння, тому теж бере участь у режимі "Тільки відмінності".
                    $priceValues = [];
                    foreach ($items as $item) {
                        $priceValues[$item['id']] = format_price((float) $item['price']);
                    }
                    ?>
                    <tr data-diff="<?= $rowHasDiff($priceValues) ? '1' : '0' ?>">
                        <td style="padding: 0.9rem 1rem; font-weight: 600; color: #374151; background: #fafafa; border-bottom: 1px solid #f0f0f0;"><?= __('price') ?></td>
                        <?php foreach ($items as $item): ?>
                            <td style="padding: 0.9rem 1rem; text-align: center; border-bottom: 1px solid #f0f0f0; border-left: 1px solid #f0f0f0;"><?= htmlspecialchars($priceValues[$item['id']]) ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <?php
                    $stockValues = [];
                    foreach ($items as $item) {
                        $stockValues[$item['id']] = ((int) ($item['stock_quantity'] ?? 0)) > 0 ? __('in_stock') : __('out_of_stock');
                    }
                    ?>
                    <tr data-diff="<?= $rowHasDiff($stockValues) ? '1' : '0' ?>">
                        <td style="padding: 0.9rem 1rem; font-weight: 600; color: #374151; background: #fafafa; border-bottom: 1px solid #f0f0f0;"><?= __('compare_availability') ?></td>
                        <?php foreach ($items as $item): ?>
                            <td style="padding: 0.9rem 1rem; text-align: center; border-bottom: 1px solid #f0f0f0; border-left: 1px solid #f0f0f0;"><?= htmlspecialchars($stockValues[$item['id']]) ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <?php foreach ($attributesMatrix as $attributeRow): ?>
                        <?php $hasDiff = $rowHasDiff($attributeRow['values']); ?>
                        <tr data-diff="<?= $hasDiff ? '1' : '0' ?>">
                            <td style="padding: 0.9rem 1rem; font-weight: 600; color: #374151; background: #fafafa; border-bottom: 1px solid #f0f0f0;"><?= htmlspecialchars($attributeRow['attribute_name']) ?></td>
                            <?php foreach ($items as $item): ?>
                                <?php $value = $attributeRow['values'][$item['id']] ?? null; ?>
                                <td style="padding: 0.9rem 1rem; text-align: center; border-bottom: 1px solid #f0f0f0; border-left: 1px solid #f0f0f0; <?= $value === null ? 'color: #d1d5db;' : '' ?>">
                                    <?= $value !== null && $value !== '' ? htmlspecialchars($value) : '—' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p id="compareNoDifferences" style="display: none; text-align: center; color: #6b7280; padding: 2rem;"><?= __('compare_no_differences') ?></p>
    <?php endif; ?>
</div>

<?php if (count($items) > 1): ?>
<script>
(() => {
    const table = document.getElementById('compareTable');
    const emptyNote = document.getElementById('compareNoDifferences');
    if (!table) return;

    const buttons = document.querySelectorAll('[data-compare-mode]');
    const rows = table.querySelectorAll('tbody tr');

    const applyMode = (mode) => {
        let visibleCount = 0;
        rows.forEach((row) => {
            const isDiffRow = row.getAttribute('data-diff') === '1';
            const show = mode === 'all' || isDiffRow;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (emptyNote) {
            emptyNote.style.display = (mode === 'diff' && visibleCount === 0) ? 'block' : 'none';
        }
        table.style.display = (mode === 'diff' && visibleCount === 0) ? 'none' : '';
    };

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            buttons.forEach((b) => {
                b.classList.remove('is-active');
                b.style.background = 'transparent';
                b.style.color = '#6b7280';
                b.style.boxShadow = 'none';
            });
            btn.classList.add('is-active');
            btn.style.background = '#fff';
            btn.style.color = '#111827';
            btn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.08)';

            applyMode(btn.getAttribute('data-compare-mode'));
        });
    });
})();
</script>
<?php endif; ?>
