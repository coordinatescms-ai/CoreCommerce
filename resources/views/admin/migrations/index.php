<?php
/**
 * @var array  $statuses
 * @var int    $pending
 * @var int    $applied
 * @var int    $missing
 * @var string $csrf
 */
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-database" style="color:#6366f1;"></i>
        <?= __('migration_title') ?>
    </h1>
    <button type="button" id="runMigrationsBtn" class="btn btn-primary"
            <?= $pending === 0 ? 'disabled' : '' ?>>
        <i class="fas fa-play"></i>
        <?= sprintf(__('migration_run_new_count'), $pending) ?>
    </button>
</div>

<!-- Статистика -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:1.25rem;">
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem; text-align:center;">
        <div style="font-size:2rem; font-weight:800; color:#10b981;"><?= $applied ?></div>
        <div style="font-size:.85rem; color:#64748b; margin-top:.25rem;"><?= __('migration_applied') ?></div>
    </div>
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem; text-align:center;">
        <div style="font-size:2rem; font-weight:800; color:#f59e0b;"><?= $pending ?></div>
        <div style="font-size:.85rem; color:#64748b; margin-top:.25rem;"><?= __('migration_pending') ?></div>
    </div>
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem; text-align:center;">
        <div style="font-size:2rem; font-weight:800; color:<?= $missing > 0 ? '#ef4444' : '#94a3b8' ?>;"><?= $missing ?></div>
        <div style="font-size:.85rem; color:#64748b; margin-top:.25rem;"><?= __('migration_file_missing') ?></div>
    </div>
</div>

<!-- Результат -->
<div id="migrationResult" style="display:none; margin-bottom:1rem;" class="alert"></div>

<!-- Таблиця міграцій -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:.7rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;"><?= __('migration_file') ?></th>
                    <th style="padding:.7rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;"><?= __('status') ?></th>
                    <th style="padding:.7rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;"><?= __('migration_applied') ?></th>
                    <th style="padding:.7rem 1rem; text-align:right; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($statuses)): ?>
                    <tr>
                        <td colspan="4" style="padding:2rem; text-align:center; color:#94a3b8;">
                            <?= __('migration_no_files') ?> <code>migrations/</code>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($statuses as $m): ?>
                        <?php
                        $statusColor = match($m['status']) {
                            'applied'      => ['#dcfce7', '#166534', 'fa-check-circle', __('stock_done')],
                            'pending'      => ['#fef3c7', '#92400e', 'fa-clock', __('status_pending')],
                            'missing_file' => ['#fee2e2', '#991b1b', 'fa-exclamation-circle', __('missing')],
                            default        => ['#f1f5f9', '#64748b', 'fa-question-circle', $m['status']],
                        };
                        [$bg, $color, $icon, $label] = $statusColor;
                        ?>
                        <tr style="border-bottom:1px solid #f1f5f9;" id="row-<?= htmlspecialchars($m['name']) ?>">
                            <td style="padding:.75rem 1rem; font-family:monospace; font-size:.82rem;">
                                <?= htmlspecialchars($m['name']) ?>
                            </td>
                            <td style="padding:.75rem 1rem;">
                                <span style="display:inline-flex; align-items:center; gap:.3rem;
                                             background:<?= $bg ?>; color:<?= $color ?>;
                                             padding:2px 10px; border-radius:20px;
                                             font-size:.75rem; font-weight:700;">
                                    <i class="fas <?= $icon ?>"></i> <?= $label ?>
                                </span>
                            </td>
                            <td style="padding:.75rem 1rem; color:#64748b; font-size:.82rem;">
                                <?= $m['applied_at']
                                    ? date('d.m.Y H:i:s', strtotime($m['applied_at']))
                                    : '—' ?>
                            </td>
                            <td style="padding:.75rem 1rem; text-align:right;">
                                <?php if ($m['status'] === 'applied'): ?>
                                    <button class="btn btn-outline reset-btn"
                                            style="border:1px solid #ddd; color:#94a3b8; font-size:.78rem; padding:.3rem .7rem;"
                                            data-name="<?= htmlspecialchars($m['name']) ?>"
                                            title="<?= __('migration_reset_hint') ?>">
                                        <i class="fas fa-undo"></i> <?= __('migration_reset') ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:1rem; padding:1rem; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; font-size:.82rem; color:#64748b;">
    <strong><?= __('migration_how_to') ?>:</strong>
    <?= __('migration_create_file') ?> <code>migrations/YYYYMMDD_name.sql</code> <?= __('migration_with_sql') ?>
    <?= __('migration_auto_run') ?><br>
    <strong><?= __('migration_cli') ?>:</strong> <code>php migrations/migrate.php</code> &nbsp;|&nbsp;
    <strong><?= __('status') ?>:</strong> <code>php migrations/migrate.php status</code>
</div>

<script>
(function () {
    const CSRF = <?= json_encode($csrf) ?>;

    async function postJson(url, body) {
        const res  = await fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams({ csrf: CSRF, ...body }),
        });
        return res.json();
    }

    function showResult(data) {
        const el = document.getElementById('migrationResult');
        el.style.display = 'block';
        el.className     = 'alert ' + (data.success ? 'alert-success' : 'alert-error');

        let html = '<strong>' + (data.message || '') + '</strong>';
        if (data.run?.length) {
            html += '<ul style="margin:.5rem 0 0; padding-left:1.25rem;">';
            data.run.forEach(m => { html += `<li>✓ ${m}</li>`; });
            html += '</ul>';
        }
        if (data.failed && Object.keys(data.failed).length) {
            html += '<ul style="margin:.5rem 0 0; padding-left:1.25rem;">';
            Object.entries(data.failed).forEach(([n, e]) => {
                html += `<li>✗ ${n}: <code>${e}</code></li>`;
            });
            html += '</ul>';
        }
        el.innerHTML = html;
    }

    // Виконати міграції
    document.getElementById('runMigrationsBtn')?.addEventListener('click', async function () {
        this.disabled  = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + window.LANG.migration_run_loading;
        try {
            const data = await postJson('/admin/migrations/run', {});
            showResult(data);
            if (data.success) setTimeout(() => location.reload(), 1200);
        } catch { showResult({ success: false, message: window.LANG.migration_network_error }); }
        this.disabled  = false;
        this.innerHTML = '<i class="fas fa-play"></i> ' + window.LANG.migration_run_new;
    });

    // Скинути міграцію
    document.querySelectorAll('.reset-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const name = this.dataset.name;
            if (!confirm(window.LANG.migration_reset_confirm.replace('%s', name))) return;
            const data = await postJson('/admin/migrations/reset', { name });
            showResult(data);
            if (data.success) setTimeout(() => location.reload(), 800);
        });
    });
})();
</script>
