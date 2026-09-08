<?php
/**
 * Вкладка «Оновлення» в налаштуваннях адмінки.
 *
 * Змінні з контролера:
 *   $updaterConfig  — масив з config/updater.php
 *   $phpVersion     — поточна версія PHP
 *   $mysqlVersion   — поточна версія MySQL
 */

$cfg            = $updaterConfig ?? require dirname(__DIR__, 4) . '/config/updater.php';
$currentVersion = htmlspecialchars((string) ($cfg['current_version'] ?? '—'));
$source         = (string) ($cfg['source'] ?? 'local');
$allowUpdates   = !empty($cfg['allow_updates']);
$csrf           = htmlspecialchars($_SESSION['csrf'] ?? '');
$lastChecked    = get_setting('update_last_checked', '');
$lastCheckedFmt = $lastChecked ? date('d.m.Y H:i', strtotime($lastChecked)) : '—';
$cachedVersion  = get_setting('update_check_result', '');
$hasRollback    = is_file(dirname(__DIR__, 4) . '/' . ltrim(
    str_replace(dirname(__DIR__, 4), '', $cfg['rollback_marker'] ?? ''), '/\\'
));
?>

<div class="update-page">

    <!-- ── Статус ──────────────────────────────────────────────────────── -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
        <div class="card" style="margin:0;">
            <div class="card-body" style="padding:1rem;">
                <p style="margin:0 0 .4rem;font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;"><?= __('update_current_version') ?></p>
                <p style="margin:0;font-size:1.6rem;font-weight:700;color:#1e293b;"><?= $currentVersion ?></p>
            </div>
        </div>
        <div class="card" style="margin:0;">
            <div class="card-body" style="padding:1rem;">
                <p style="margin:0 0 .4rem;font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;"><?= __('update_source') ?></p>
                <p style="margin:0;font-size:1rem;font-weight:600;color:#1e293b;">
                    <?php if ($source === 'remote'): ?>
                        <span style="color:#10b981;">🌐 <?= __('update_source_remote') ?></span>
                    <?php else: ?>
                        <span style="color:#f59e0b;">📁 <?= __('update_source_local') ?></span>
                    <?php endif; ?>
                </p>
                <p style="margin:.25rem 0 0;font-size:.78rem;color:#94a3b8;">
                    <?= __('update_last_checked') ?>: <strong><?= htmlspecialchars($lastCheckedFmt) ?></strong>
                    <?php if ($cachedVersion !== ''): ?>
                        — <span style="color:#ef4444;font-weight:600;"><?= __('update_new_available') ?>: <?= htmlspecialchars($cachedVersion) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- ── Джерело в config/updater.php ───────────────────────────────── -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header" style="font-size:.9rem;">
            <i class="fas fa-sliders-h"></i> <?= __('update_source_config') ?>
        </div>
        <div class="card-body">
            <p style="font-size:.85rem;color:#64748b;margin:0 0 .75rem;">
                <?= __('update_source_hint') ?>
                <code style="background:#f1f5f9;padding:.1rem .4rem;border-radius:4px;">config/updater.php</code>
            </p>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;font-size:.9rem;">
                <label style="display:flex;align-items:center;gap:.4rem;">
                    <code style="background:#f1f5f9;padding:.2rem .5rem;border-radius:4px;">'source' => 'local'</code>
                    — <?= __('update_source_local_desc') ?>
                </label>
                <label style="display:flex;align-items:center;gap:.4rem;">
                    <code style="background:#f1f5f9;padding:.2rem .5rem;border-radius:4px;">'source' => 'remote'</code>
                    — <?= __('update_source_remote_desc') ?>
                </label>
            </div>
        </div>
    </div>

    <?php if (!$allowUpdates): ?>
    <!-- ── Оновлення вимкнені ──────────────────────────────────────────── -->
    <div class="alert alert-error">
        <i class="fas fa-ban"></i>
        <?= __('update_disabled_hint') ?>
        <code>config/updater.php</code> → <code>'allow_updates' => true</code>
    </div>
    <?php else: ?>

    <!-- ── Перевірка наявності оновлень ───────────────────────────────── -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header"><i class="fas fa-search"></i> <?= __('update_check_title') ?></div>
        <div class="card-body">
            <div id="update-check-result" style="margin-bottom:.75rem;font-size:.9rem;color:#64748b;">
                <?= __('update_check_hint') ?>
            </div>
            <button type="button" class="btn btn-outline" id="btn-check-update" style="border:1px solid #ddd;">
                <i class="fas fa-sync-alt"></i> <?= __('update_check_btn') ?>
            </button>
        </div>
    </div>

    <!-- ── Запуск оновлення ────────────────────────────────────────────── -->
    <div class="card" id="update-run-card" style="display:none;margin-bottom:1rem;">
        <div class="card-header"><i class="fas fa-download"></i> <?= __('update_run_title') ?></div>
        <div class="card-body">

            <div id="update-changelog" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:1rem;font-size:.88rem;white-space:pre-wrap;max-height:180px;overflow-y:auto;"></div>

            <!-- Чек-лист -->
            <div style="margin-bottom:1.25rem;">
                <p style="font-weight:600;margin:0 0 .5rem;"><?= __('update_checklist') ?>:</p>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.3rem;font-size:.88rem;">
                    <?php
                    $checks = [
                        'PHP ' . PHP_VERSION => version_compare(PHP_VERSION, '8.1', '>='),
                        'MySQLi/PDO'         => extension_loaded('pdo_mysql'),
                        'ZipArchive'         => class_exists('ZipArchive'),
                        __('update_check_disk') => (disk_free_space(dirname(__DIR__, 4)) ?: 0) > 50 * 1024 * 1024,
                        __('update_check_writable') => is_writable(dirname(__DIR__, 4)),
                        __('update_check_backup_dir') => is_dir(dirname(__DIR__, 4) . '/backups') || is_writable(dirname(__DIR__, 4)),
                    ];
                    foreach ($checks as $label => $ok): ?>
                    <li>
                        <i class="fas <?= $ok ? 'fa-check-circle' : 'fa-times-circle' ?>"
                           style="color:<?= $ok ? '#10b981' : '#ef4444' ?>;width:18px;"></i>
                        <?= htmlspecialchars($label) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Пароль -->
            <div class="form-group" style="max-width:320px;margin-bottom:1rem;">
                <label style="font-weight:600;"><?= __('update_password_prompt') ?></label>
                <input type="password" id="update-password" class="form-control" placeholder="••••••••" autocomplete="current-password">
            </div>

            <!-- Прогрес -->
            <div id="update-progress" style="display:none;margin-bottom:1rem;">
                <div style="display:flex;flex-direction:column;gap:.4rem;font-size:.88rem;" id="update-steps-list"></div>
                <div style="margin-top:.75rem;background:#e2e8f0;border-radius:999px;height:8px;overflow:hidden;">
                    <div id="update-progress-bar" style="background:#3b82f6;height:100%;width:0;transition:width .4s;"></div>
                </div>
            </div>

            <div id="update-final-message" style="display:none;"></div>

            <div id="update-action-btns">
                <button type="button" class="btn btn-primary" id="btn-start-update">
                    <i class="fas fa-play"></i> <?= __('update_start_btn') ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Rollback ────────────────────────────────────────────────────── -->
    <?php if ($hasRollback): ?>
    <div class="card" style="border:1px solid #fecaca;">
        <div class="card-header" style="background:#fef2f2;color:#991b1b;">
            <i class="fas fa-undo"></i> <?= __('update_rollback_title') ?>
        </div>
        <div class="card-body">
            <p style="font-size:.88rem;color:#64748b;margin:0 0 .75rem;"><?= __('update_rollback_hint') ?></p>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <form method="POST" action="/admin/update/rollback" id="rollback-form">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <button type="submit" class="btn" style="background:#ef4444;color:#fff;"
                        onclick="return confirm('<?= __('update_rollback_confirm') ?>')">
                        <i class="fas fa-undo"></i> <?= __('update_rollback_btn') ?>
                    </button>
                </form>
                <form method="POST" action="/admin/update/confirm" id="confirm-update-form">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <button type="submit" class="btn btn-outline" style="border:1px solid #10b981;color:#10b981;"
                        onclick="return confirm('<?= __('update_confirm_hint') ?>')">
                        <i class="fas fa-check"></i> <?= __('update_confirm_btn') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script>
(function () {
    const csrf    = <?= json_encode($csrf) ?>;
    const STEPS   = ['init','backup','download','extract','database','finish'];
    const LABELS  = {
        init:     <?= json_encode(__('update_step_init')) ?>,
        backup:   <?= json_encode(__('update_step_backup')) ?>,
        download: <?= json_encode(__('update_step_download')) ?>,
        extract:  <?= json_encode(__('update_step_extract')) ?>,
        database: <?= json_encode(__('update_step_database')) ?>,
        finish:   <?= json_encode(__('update_step_finish')) ?>,
    };

    // ── Перевірка оновлення ───────────────────────────────────────────
    const btnCheck   = document.getElementById('btn-check-update');
    const checkResult= document.getElementById('update-check-result');
    const runCard    = document.getElementById('update-run-card');

    if (btnCheck) {
        btnCheck.addEventListener('click', async () => {
            btnCheck.disabled = true;
            btnCheck.innerHTML = '<span class="spin"></span> <?= __("admin_settings_update_checking") ?>';
            checkResult.style.color = '#64748b';
            checkResult.textContent = <?= json_encode(__("admin_settings_update_network_error")) ?>;

            try {
                const res  = await fetch('/admin/update/check');
                const data = await res.json();

                if (!data.update_available) {
                    checkResult.textContent = '✅ ' + data.message;
                    checkResult.style.color = '#10b981';
                } else {
                    checkResult.innerHTML = `🆕 <strong>${data.message}</strong>`;
                    checkResult.style.color = '#3b82f6';
                    document.getElementById('update-changelog').textContent = data.changelog || '';
                    runCard.style.display = 'block';
                    runCard.scrollIntoView({ behavior: 'smooth' });
                }
            } catch (e) {
                checkResult.textContent = <?= json_encode(__("admin_settings_update_network_error")) ?>;
                checkResult.style.color = '#ef4444';
            }

            btnCheck.disabled = false;
            btnCheck.innerHTML = '<i class="fas fa-sync-alt"></i> <?= __('update_check_btn') ?>';
        });
    }

    // ── Запуск оновлення ──────────────────────────────────────────────
    const btnStart  = document.getElementById('btn-start-update');
    const stepsEl   = document.getElementById('update-steps-list');
    const progressBar = document.getElementById('update-progress-bar');
    const progressEl  = document.getElementById('update-progress');
    const finalMsg    = document.getElementById('update-final-message');
    const actionBtns  = document.getElementById('update-action-btns');

    if (btnStart) {
        btnStart.addEventListener('click', () => runUpdate());
    }

    async function runUpdate() {
        const password = document.getElementById('update-password')?.value ?? '';
        if (!password) {
            alert(<?= json_encode(__('update_password_prompt')) ?>);
            return;
        }

        btnStart && (btnStart.disabled = true);
        progressEl.style.display = 'block';
        stepsEl.innerHTML = '';
        finalMsg.style.display = 'none';

        let stepIndex  = 0;
        let nextStep   = 'init';
        const total    = STEPS.length;

        while (nextStep) {
            const stepEl = document.createElement('div');
            stepEl.style.cssText = 'display:flex;align-items:center;gap:.5rem;';
            stepEl.innerHTML = `<span class="spin" style="flex-shrink:0;"></span> <span>${LABELS[nextStep] ?? nextStep}…</span>`;
            stepsEl.appendChild(stepEl);

            try {
                const body = new URLSearchParams({ csrf });
                if (nextStep === 'init') body.append('password', password);

                const res  = await fetch('/admin/update/' + nextStep, { method: 'POST', body });
                const data = await res.json();

                stepEl.innerHTML = `<i class="fas ${data.success ? 'fa-check-circle' : 'fa-times-circle'}"
                    style="color:${data.success ? '#10b981' : '#ef4444'};flex-shrink:0;"></i>
                    <span>${data.message}</span>`;

                stepIndex++;
                progressBar.style.width = Math.round(stepIndex / total * 100) + '%';

                if (!data.success) {
                    showFinalMessage(data.message, false);
                    return;
                }

                nextStep = data.next_step || null;

            } catch (err) {
                stepEl.innerHTML = `<i class="fas fa-times-circle" style="color:#ef4444;flex-shrink:0;"></i> <span><?= __("admin_settings_update_error_prefix") ?>${err.message}</span>`;
                showFinalMessage(<?= json_encode(__("admin_settings_update_network_error")) ?> + ": " + err.message, false);
                return;
            }
        }

        // Успіх
        progressBar.style.width = '100%';
        progressBar.style.background = '#10b981';
        showFinalMessage(<?= json_encode(__("update_complete")) ?>, true);
    }

    function showFinalMessage(text, success) {
        finalMsg.style.display = 'block';
        finalMsg.style.cssText = `display:block;padding:.75rem 1rem;border-radius:8px;margin-top:.75rem;
            background:${success ? '#f0fdf4' : '#fef2f2'};
            color:${success ? '#166534' : '#991b1b'};
            border:1px solid ${success ? '#bbf7d0' : '#fecaca'};
            font-weight:500;`;
        finalMsg.textContent = text;
        if (success) {
            actionBtns.innerHTML = '<a href="/admin" class="btn btn-primary"><i class="fas fa-home"></i> <?= __("admin_settings_update_admin_link") ?></a>';
        }
    }
})();
</script>
