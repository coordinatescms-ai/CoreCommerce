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
        Міграції бази даних
    </h1>
    <button type="button" id="runMigrationsBtn" class="btn btn-primary"
            <?= $pending === 0 ? 'disabled' : '' ?>>
        <i class="fas fa-play"></i>
        Виконати нові (<?= $pending ?>)
    </button>
</div>

<!-- Статистика -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:1.25rem;">
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem; text-align:center;">
        <div style="font-size:2rem; font-weight:800; color:#10b981;"><?= $applied ?></div>
        <div style="font-size:.85rem; color:#64748b; margin-top:.25rem;">Виконано</div>
    </div>
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem; text-align:center;">
        <div style="font-size:2rem; font-weight:800; color:#f59e0b;"><?= $pending ?></div>
        <div style="font-size:.85rem; color:#64748b; margin-top:.25rem;">Очікують</div>
    </div>
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem; text-align:center;">
        <div style="font-size:2rem; font-weight:800; color:<?= $missing > 0 ? '#ef4444' : '#94a3b8' ?>;"><?= $missing ?></div>
        <div style="font-size:.85rem; color:#64748b; margin-top:.25rem;">Файл відсутній</div>
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
                    <th style="padding:.7rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Файл міграції</th>
                    <th style="padding:.7rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Статус</th>
                    <th style="padding:.7rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Виконано</th>
                    <th style="padding:.7rem 1rem; text-align:right; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($statuses)): ?>
                    <tr>
                        <td colspan="4" style="padding:2rem; text-align:center; color:#94a3b8;">
                            Файлів міграцій не знайдено в папці <code>migrations/</code>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($statuses as $m): ?>
                        <?php
                        $statusColor = match($m['status']) {
                            'applied'      => ['#dcfce7', '#166534', 'fa-check-circle', 'Виконано'],
                            'pending'      => ['#fef3c7', '#92400e', 'fa-clock', 'Очікує'],
                            'missing_file' => ['#fee2e2', '#991b1b', 'fa-exclamation-circle', 'Файл відсутній'],
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
                                            title="Скинути — дозволить виконати міграцію знову">
                                        <i class="fas fa-undo"></i> Скинути
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
    <strong>Як додати нову міграцію:</strong>
    Створіть файл <code>migrations/YYYYMMDD_назва.sql</code> з SQL-командами.
    Система виконає його автоматично при наступному запуску.<br>
    <strong>CLI:</strong> <code>php migrations/migrate.php</code> &nbsp;|&nbsp;
    <strong>Статус:</strong> <code>php migrations/migrate.php status</code>
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
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Виконання…';
        try {
            const data = await postJson('/admin/migrations/run', {});
            showResult(data);
            if (data.success) setTimeout(() => location.reload(), 1200);
        } catch { showResult({ success: false, message: 'Помилка мережі' }); }
        this.disabled  = false;
        this.innerHTML = '<i class="fas fa-play"></i> Виконати нові';
    });

    // Скинути міграцію
    document.querySelectorAll('.reset-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const name = this.dataset.name;
            if (!confirm(`Скинути міграцію «${name}»?\nВона буде виконана знову при наступному запуску.`)) return;
            const data = await postJson('/admin/migrations/reset', { name });
            showResult(data);
            if (data.success) setTimeout(() => location.reload(), 800);
        });
    });
})();
</script>
