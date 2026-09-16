<?php
/**
 * @var array  $batches
 * @var string $csrf
 */

/**
 * Людський опис режиму й параметрів пакетної зміни — для колонки "Режим".
 */
$describeMode = static function (array $batch): string {
    $params = json_decode((string) ($batch['mode_params'] ?? ''), true) ?: [];

    switch ($batch['mode']) {
        case 'percent':
            $sign = ($params['direction'] ?? 'increase') === 'decrease' ? '−' : '+';
            $desc = sprintf(__('price_batch_mode_desc_percent'), $sign, (float) ($params['value'] ?? 0));
            break;

        case 'fixed':
            $sign = ($params['direction'] ?? 'increase') === 'decrease' ? '−' : '+';
            $desc = sprintf(__('price_batch_mode_desc_fixed'), $sign, format_price((float) ($params['value'] ?? 0)));
            break;

        case 'currency':
            $desc = sprintf(__('price_batch_mode_desc_currency'), htmlspecialchars((string) ($params['from_currency'] ?? '')));
            break;

        default:
            $desc = htmlspecialchars((string) $batch['mode']);
            break;
    }

    if (!empty($params['round_price'])) {
        $desc .= ' <span style="color:#94a3b8;">· ' . __('price_batch_round_price_badge') . '</span>';
    }

    return $desc;
};

/**
 * Людський опис вибірки товарів — для колонки "Вибірка".
 */
$describeScope = static function (array $batch): string {
    $parts = [];

    if (!empty($batch['scope_category_id'])) {
        $categoryLabel = !empty($batch['category_name']) ? $batch['category_name'] : ('#' . $batch['scope_category_id']);
        $parts[] = htmlspecialchars($categoryLabel) . (!empty($batch['scope_include_subcategories']) ? ' ' . __('price_batch_scope_with_subcategories') : '');
    }
    if (!empty($batch['scope_vendor'])) {
        $parts[] = htmlspecialchars($batch['scope_vendor']);
    }

    return $parts ? implode(', ', $parts) : __('price_batch_scope_all');
};
?>

<div class="page-header">
    <h1 class="page-title"><?= __('price_batch_history_title') ?></h1>
    <a href="/admin/products" class="btn btn-outline" style="border:1px solid #ddd;">
        <i class="fas fa-arrow-left"></i> <?= __('back') ?>
    </a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars((string) $_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars((string) $_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card">
    <?php if (empty($batches)): ?>
        <div class="card-body" style="text-align:center; color:#94a3b8; padding:2.5rem;">
            <?= __('price_batch_history_empty') ?>
        </div>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <th style="padding:.8rem 1rem; text-align:left; font-size:.8rem; color:#64748b;"><?= __('price_batch_col_date') ?></th>
                    <th style="padding:.8rem 1rem; text-align:left; font-size:.8rem; color:#64748b;"><?= __('price_batch_col_mode') ?></th>
                    <th style="padding:.8rem 1rem; text-align:left; font-size:.8rem; color:#64748b;"><?= __('price_batch_col_scope') ?></th>
                    <th style="padding:.8rem 1rem; text-align:left; font-size:.8rem; color:#64748b;"><?= __('price_batch_col_affected') ?></th>
                    <th style="padding:.8rem 1rem; text-align:left; font-size:.8rem; color:#64748b;"><?= __('price_batch_col_admin') ?></th>
                    <th style="padding:.8rem 1rem; text-align:right; font-size:.8rem; color:#64748b;"><?= __('price_batch_col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $batch): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:.8rem 1rem; font-size:.85rem; color:#334155; white-space:nowrap;">
                            <?= htmlspecialchars((string) $batch['created_at']) ?>
                        </td>
                        <td style="padding:.8rem 1rem; font-size:.85rem; color:#334155;">
                            <?= $describeMode($batch) ?>
                        </td>
                        <td style="padding:.8rem 1rem; font-size:.85rem; color:#334155;">
                            <?= $describeScope($batch) ?>
                        </td>
                        <td style="padding:.8rem 1rem; font-size:.85rem; color:#334155;">
                            <?= (int) $batch['affected_count'] ?>
                            <?php if ((int) $batch['skipped_count'] > 0): ?>
                                <span style="color:#94a3b8;"> (<?= sprintf(__('price_batch_skipped_note'), (int) $batch['skipped_count']) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:.8rem 1rem; font-size:.85rem; color:#334155;">
                            <?= !empty($batch['first_name']) ? htmlspecialchars(trim($batch['first_name'] . ' ' . ($batch['last_name'] ?? ''))) : '—' ?>
                        </td>
                        <td style="padding:.8rem 1rem; text-align:right; white-space:nowrap;">
                            <?php if (!empty($batch['reverted_at'])): ?>
                                <span style="display:inline-block; padding:.25rem .6rem; background:#f1f5f9; color:#64748b; border-radius:20px; font-size:.78rem; font-weight:600;">
                                    <?= __('price_batch_reverted_badge') ?>
                                </span>
                            <?php else: ?>
                                <form action="/admin/products/price-batch/undo/<?= (int) $batch['id'] ?>" method="POST" style="display:inline-block; margin:0;"
                                      onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('price_batch_undo_confirm')), ENT_QUOTES) ?>);">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                    <button type="submit" class="btn btn-outline" style="border:1px solid #fecaca; color:#dc2626;">
                                        <i class="fas fa-rotate-left"></i> <?= __('price_batch_undo_btn') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
