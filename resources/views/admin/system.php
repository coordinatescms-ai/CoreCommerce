<div class="page-header">
    <h1 class="page-title"><i class="fas fa-server"></i> <?= __('system_title') ?></h1>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success" style="margin-bottom:1rem;"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<style>
.system-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(320px,1fr)); gap: 1rem; }
.system-kv { display:grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.system-kv .key { color:#64748b; font-size:.9rem; }
.system-kv .val { font-weight:600; }
.badge { display:inline-block; padding:4px 10px; border-radius: 999px; font-size:.78rem; font-weight:700; }
.badge.ok, .badge.active, .badge.success { background:#dcfce7; color:#166534; }
.badge.bad, .badge.disabled, .badge.failed { background:#fee2e2; color:#991b1b; }
.badge.running { background:#fef3c7; color:#92400e; }
.log-box { background:#0f172a; color:#e2e8f0; border-radius:8px; padding:12px; max-height:280px; overflow:auto; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12px; }
.actions { display:flex; flex-wrap:wrap; gap:.75rem; }
.cron-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
.cron-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.55); display:none; align-items:center; justify-content:center; z-index:9999; }
.cron-modal-backdrop.active { display:flex; }
.cron-modal { background:#fff; border-radius:10px; width:min(680px,95vw); box-shadow:0 15px 45px rgba(0,0,0,.3); }
.cron-modal-header, .cron-modal-footer { padding:14px 18px; border-bottom:1px solid #e2e8f0; }
.cron-modal-footer { border-bottom:0; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:.5rem; }
.cron-modal-body { padding:16px 18px; }
.cron-modal-body label { display:block; margin:.35rem 0; font-weight:600; }
.cron-modal-body input, .cron-modal-body textarea { width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:7px; }
.cron-modal-body textarea { min-height:130px; resize:vertical; }
</style>

<div class="system-grid">

    <!-- ═══ БЕЗПЕКА ТА SSL ══════════════════════════════════════════════ -->
    <div class="card" style="grid-column:1/-1;">
        <div class="card-header">
            <i class="fas fa-shield-alt"></i> <?= __('security_ssl_title') ?>
        </div>
        <div class="card-body">
            <form method="POST" action="/admin/system/security">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem 2rem;">

                    <!-- ── Крок 1: HTTPS редирект ────────────────────────── -->
                    <div>
                        <h4 style="margin:0 0 .5rem; font-size:.95rem; color:#1e293b;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#3b82f6;color:#fff;border-radius:50%;font-size:.75rem;font-weight:700;margin-right:.4rem;">1</span>
                            <?= __('https_redirect_title') ?>
                        </h4>
                        <p style="font-size:.83rem;color:#64748b;margin:0 0 .75rem;line-height:1.5;">
                            <?= __('https_redirect_desc') ?>
                        </p>
                        <label style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:500;">
                            <input type="checkbox" name="https_redirect" value="1" id="https_redirect"
                                <?= get_setting('https_redirect','0') === '1' ? 'checked' : '' ?>>
                            <?= __('https_redirect_label') ?>
                        </label>
                        <p style="font-size:.78rem;color:#f59e0b;margin:.6rem 0 0;display:flex;align-items:flex-start;gap:.3rem;">
                            <i class="fas fa-exclamation-triangle" style="margin-top:.15rem;flex-shrink:0;"></i>
                            <?= __('https_redirect_warning') ?>
                        </p>
                    </div>

                    <!-- ── Крок 2: HSTS ──────────────────────────────────── -->
                    <div id="hsts-block" style="<?= get_setting('https_redirect','0') !== '1' ? 'opacity:.45;pointer-events:none;' : '' ?>">
                        <h4 style="margin:0 0 .5rem; font-size:.95rem; color:#1e293b;">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#10b981;color:#fff;border-radius:50%;font-size:.75rem;font-weight:700;margin-right:.4rem;">2</span>
                            <?= __('hsts_title') ?>
                        </h4>
                        <p style="font-size:.83rem;color:#64748b;margin:0 0 .75rem;line-height:1.5;">
                            <?= __('hsts_desc') ?>
                        </p>
                        <label style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:500;margin-bottom:.75rem;">
                            <input type="checkbox" name="hsts_enabled" value="1" id="hsts_enabled"
                                <?= get_setting('hsts_enabled','0') === '1' ? 'checked' : '' ?>>
                            <?= __('hsts_enable_label') ?>
                        </label>

                        <!-- Термін дії HSTS -->
                        <div style="margin:.5rem 0 .75rem;">
                            <label style="font-size:.83rem;font-weight:600;display:block;margin-bottom:.3rem;">
                                <?= __('hsts_max_age_label') ?>
                            </label>
                            <?php
                            $currentMaxAge = (int) get_setting('hsts_max_age', 300);
                            $ageOptions = \App\Services\SecurityHeadersService::hstsMaxAgeOptions();
                            ?>
                            <select name="hsts_max_age" class="form-control" style="max-width:340px;">
                                <?php foreach ($ageOptions as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $currentMaxAge === $val ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Додаткові опції -->
                        <div style="display:flex;flex-direction:column;gap:.4rem;">
                            <label style="display:inline-flex;align-items:center;gap:.5rem;font-size:.85rem;cursor:pointer;">
                                <input type="checkbox" name="hsts_subdomains" value="1"
                                    <?= get_setting('hsts_subdomains','0') === '1' ? 'checked' : '' ?>>
                                <span><?= __('hsts_subdomains_label') ?></span>
                                <span style="font-size:.75rem;color:#94a3b8;">(includeSubDomains)</span>
                            </label>
                            <label style="display:inline-flex;align-items:center;gap:.5rem;font-size:.85rem;cursor:pointer;">
                                <input type="checkbox" name="hsts_preload" value="1"
                                    <?= get_setting('hsts_preload','0') === '1' ? 'checked' : '' ?>>
                                <span><?= __('hsts_preload_label') ?></span>
                                <span style="font-size:.75rem;color:#94a3b8;">(preload)</span>
                            </label>
                        </div>
                    </div>

                    <!-- ── CSP ───────────────────────────────────────────── -->
                    <div style="grid-column:1/-1;border-top:1px solid #e2e8f0;padding-top:1.25rem;">
                        <h4 style="margin:0 0 .5rem; font-size:.95rem; color:#1e293b;">
                            <i class="fas fa-lock" style="color:#8b5cf6;margin-right:.4rem;"></i>
                            <?= __('csp_title') ?>
                        </h4>
                        <p style="font-size:.83rem;color:#64748b;margin:0 0 .75rem;line-height:1.5;">
                            <?= __('csp_desc') ?>
                        </p>
                        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                            <?php
                            $currentCsp = get_setting('csp_mode','off');
                            foreach (\App\Services\SecurityHeadersService::cspModeOptions() as $val => $label):
                            ?>
                            <label style="display:inline-flex;align-items:center;gap:.4rem;cursor:pointer;font-size:.88rem;">
                                <input type="radio" name="csp_mode" value="<?= $val ?>"
                                    <?= $currentCsp === $val ? 'checked' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p style="font-size:.78rem;color:#64748b;margin:.6rem 0 0;">
                            <i class="fas fa-info-circle"></i> <?= __('csp_hint') ?>
                        </p>
                    </div>

                </div>

                <div style="margin-top:1.25rem;border-top:1px solid #e2e8f0;padding-top:1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= __('save') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══ SITEMAP ═══════════════════════════════════════════════════ -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-sitemap"></i> Sitemap XML
        </div>
        <div class="card-body">
            <?php
            $sitemapLastGen = get_setting('sitemap_last_generated', '');
            $sitemapExists  = file_exists(dirname(__DIR__, 3) . '/public/sitemap.xml');
            ?>
            <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; margin-bottom:1rem;">
                <div>
                    <p style="margin:0; font-size:.88rem; color:#64748b;">
                        <?= __('sitemap_last_gen') ?>:
                        <strong style="color:#1e293b;">
                            <?= $sitemapLastGen
                                ? htmlspecialchars(date('d.m.Y H:i', strtotime($sitemapLastGen)))
                                : __('sitemap_never') ?>
                        </strong>
                    </p>
                    <p style="margin:.3rem 0 0; font-size:.83rem; color:#94a3b8;">
                        <?= __('sitemap_hint') ?>
                    </p>
                </div>
                <?php if ($sitemapExists): ?>
                <a href="/sitemap.xml" target="_blank" class="btn btn-outline" style="border:1px solid #ddd;">
                    <i class="fas fa-external-link-alt"></i> sitemap.xml
                </a>
                <?php endif; ?>
            </div>
            <form method="POST" action="/admin/system/sitemap/generate" id="sitemap-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                <button type="submit" class="btn btn-primary" id="sitemap-btn">
                    <i class="fas fa-cogs"></i> <?= __('sitemap_generate') ?>
                </button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Environment</div>
        <div class="card-body">
            <form method="POST" action="/admin/system/environment">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <p><label><input type="checkbox" name="display_errors" value="1" <?php echo ($environment['display_errors'] ?? '0') === '1' ? 'checked' : ''; ?>> <?= __('system_debug_mode') ?></label></p>
                <p><label><input type="checkbox" name="maintenance_mode" value="1" <?php echo ($environment['store_status'] ?? 'open') === 'closed' ? 'checked' : ''; ?>> <?= __('system_maintenance_mode') ?></label></p>
                <button class="btn btn-primary" type="submit"><?= __('settings_save_modes') ?></button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Mail Settings</div>
        <div class="card-body">
            <form method="POST" action="/admin/system/mail/test">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <p><label><?= __('smtp_test_email') ?></label><input type="email" name="test_email" class="form-control" required placeholder="test@example.com"></p>
                <p><label><input type="checkbox" name="test_email_use_db" value="1" checked><?= __('smtp_from_db') ?></label></p>
                <button class="btn btn-success" type="submit"><?= __('smtp_test_send') ?></button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">System Info</div>
        <div class="card-body">
            <div class="system-kv">
                <div class="key">PHP</div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['php_version']); ?></div>
                <div class="key">MySQL</div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['mysql_version']); ?></div>
                <div class="key"><?= __('update_engine_version') ?></div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['engine_version']); ?></div>
                <div class="key">upload_max_filesize</div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['upload_max_filesize']); ?></div>
                <div class="key">memory_limit</div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['memory_limit']); ?></div>
                <div class="key"><?= __('system_disk') ?></div><div class="val"><?php echo $systemInfo['disk_used'] !== false ? round($systemInfo['disk_used'] / 1073741824, 2) . ' GB / ' . round($systemInfo['disk_total'] / 1073741824, 2) . ' GB' : 'unknown'; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><?= __('cron_tasks') ?></div>
    <div class="card-body">
        <table class="table" id="cronTasksTable">
            <thead><tr><th><?= __('name') ?></th><th><?= __('cron_period') ?></th><th><?= __('cron_last_run') ?></th><th><?= __('cron_next_run') ?></th><th><?= __('status') ?></th><th><?= __('result') ?></th><th><?= __('actions') ?></th></tr></thead>
            <tbody>
            <?php foreach (($cronTasks ?? []) as $task): ?>
                <tr data-task='<?php echo htmlspecialchars(json_encode($task, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>'>
                    <td><?php echo htmlspecialchars((string) $task['name']); ?></td>
                    <td class="cron-schedule"><?php echo htmlspecialchars((string) $task['schedule']); ?></td>
                    <td><?php echo htmlspecialchars((string) ($task['last_run'] ?: '—')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($task['next_run'] ?: '—')); ?></td>
                    <td><span class="badge <?php echo htmlspecialchars((string) $task['status']); ?> cron-status"><?php echo htmlspecialchars((string) $task['status']); ?></span></td>
                    <td>
                        <span class="badge <?php echo htmlspecialchars((string) $task['last_result']); ?> cron-result"><?php echo htmlspecialchars((string) $task['last_result']); ?></span>
                        <div class="cron-error" style="font-size:12px;color:#991b1b;"><?php echo htmlspecialchars((string) ($task['error_message'] ?? '')); ?></div>
                    </td>
                    <td>
                        <div class="cron-actions">
                            <button class="btn btn-primary js-edit-task" type="button" title="<?= __('edit') ?>"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-secondary js-toggle-task" type="button"><?php echo $task['status'] === 'active' ? 'Disable' : 'Enable'; ?></button>
                            <button class="btn btn-success js-run-task" type="button" title="<?= __('run_now') ?>"><i class="fas fa-plug"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="cron-modal-backdrop" id="cronModalBackdrop">
    <div class="cron-modal">
        <div class="cron-modal-header"><strong><?= __('cron_edit') ?></strong></div>
        <div class="cron-modal-body">
            <input type="hidden" id="cronTaskId">
            <label for="cronTaskName"><?= __('name') ?></label>
            <input type="text" id="cronTaskName">
            <label for="cronTaskSchedule">Cron string</label>
            <input type="text" id="cronTaskSchedule" placeholder="*/30 * * * *">
            <label for="cronTaskCommand"><?= __('migration_file_path') ?></label>
            <input type="text" id="cronTaskCommand" placeholder="tasks/import_products.php">
            <label for="cronTaskParams">Params (JSON)</label>
            <textarea id="cronTaskParams"></textarea>
        </div>
        <div class="cron-modal-footer">
            <button type="button" class="btn btn-secondary" id="cronModalCancel"><?= __('cancel') ?></button>
            <button type="button" class="btn btn-primary" id="cronModalSave"><?= __('save') ?></button>
        </div>
    </div>
</div>

<script src="/js/cron_tasks.js"></script>
<script>
window.CRON_TASKS_CONFIG = {
    endpoint: '/cron_tasks_ajax.php',
    csrf: <?php echo json_encode($_SESSION['csrf'] ?? ''); ?>
};
</script>

<div class="card">
    <div class="card-header"><?= __('system_actions') ?></div>
    <div class="card-body actions">
        <form method="POST" action="/admin/clear-cache"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-primary" type="submit"><i class="fas fa-broom"></i> <?= __('system_clear_cache') ?></button></form>
        <form method="POST" action="/admin/system/logs/clear"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-danger" type="submit" onclick="return confirm('<?= __('system_confirm_clear_logs') ?>');"><i class="fas fa-trash"></i> <?= __('system_clear_logs') ?></button></form>
        <form method="POST" action="/admin/system/database/backup"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-success" type="submit"><i class="fas fa-database"></i> <?= __('system_backup') ?></button></form>
        <form method="POST" action="/admin/system/database/optimize"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-primary" type="submit"><i class="fas fa-bolt"></i> OPTIMIZE TABLE</button></form>
    </div>
</div>

<div class="card">
    <div class="card-header">PHP error.log</div>
    <div class="card-body">
        <div class="log-box"><?php if (empty($logs['php_errors'])): ?><?= __('system_log_empty') ?><?php else: ?><?php echo htmlspecialchars(implode("\n", $logs['php_errors'])); ?><?php endif; ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><?= __('system_admin_log') ?></div>
    <div class="card-body">
        <div class="log-box"><?php if (empty($logs['admin_actions'])): ?><?= __('system_log_empty') ?><?php else: ?><?php echo htmlspecialchars(implode("\n", $logs['admin_actions'])); ?><?php endif; ?></div>
    </div>
</div>

<!-- ═══════════════ УПРАВЛІННЯ ВАЛЮТАМИ ═══════════════ -->
<style>
.curr-table      { width:100%; border-collapse:collapse; font-size:.9rem; }
.curr-table th   { background:#f8fafc; color:#64748b; font-weight:700; font-size:.78rem;
                   text-transform:uppercase; letter-spacing:.04em;
                   padding:.6rem 1rem; text-align:left; border-bottom:2px solid #e2e8f0; }
.curr-table td   { padding:.65rem 1rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.curr-table tr:last-child td { border-bottom:none; }
.curr-active-badge { display:inline-flex; align-items:center; gap:.3rem;
                     background:#dcfce7; color:#166534; font-size:.75rem;
                     font-weight:700; padding:2px 10px; border-radius:20px; }
.curr-modal-backdrop { position:fixed; inset:0; background:rgba(2,6,23,.55);
                        display:none; align-items:center; justify-content:center; z-index:9999; }
.curr-modal-backdrop.active { display:flex; }
.curr-modal      { background:#fff; border-radius:10px; width:min(440px,95vw);
                   box-shadow:0 15px 45px rgba(0,0,0,.3); }
.curr-modal-hdr  { padding:14px 18px; border-bottom:1px solid #e2e8f0;
                   font-weight:700; font-size:1rem; display:flex; justify-content:space-between; align-items:center; }
.curr-modal-body { padding:16px 18px; display:grid; gap:.75rem; }
.curr-modal-ftr  { padding:12px 18px; border-top:1px solid #e2e8f0;
                   display:flex; justify-content:flex-end; gap:.5rem; }
.curr-modal-body label       { display:block; font-size:.82rem; font-weight:600; color:#475569; margin-bottom:.3rem; }
.curr-modal-body input       { width:100%; box-sizing:border-box; padding:.48rem .75rem;
                               border:1px solid #e2e8f0; border-radius:7px; font-size:.9rem; }
</style>



<div class="card" style="margin-top:1.25rem;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <span><i class="fas fa-coins"></i> <?= __('currency_manage') ?></span>
        <button class="btn btn-primary" style="padding:.4rem .9rem; font-size:.85rem;"
                onclick="currOpenModal()">
            <i class="fas fa-plus"></i><?= __('currency_add') ?>
        </button>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="curr-table">
            <thead>
                <tr>
                    <th><?= __('currency_code_label') ?></th>
                    <th><?= __('currency_symbol_label') ?></th>
                    <th><?= __('currency_rate') ?></th>
                    <th><?= __('status') ?></th>
                    <th style="text-align:right;"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (($currencies ?? []) as $cur): ?>
                <tr>
                    <td style="font-weight:700; color:#0f172a;"><?= htmlspecialchars($cur['code']) ?></td>
                    <td style="font-size:1.1rem;"><?= htmlspecialchars($cur['symbol']) ?></td>
                    <td><?= number_format((float)$cur['rate'], 4, '.', ' ') ?></td>
                    <td>
                        <?php if ((int)$cur['is_active']): ?>
                            <span class="curr-active-badge"><i class="fas fa-check-circle"></i> <?= __('active') ?></span>
                        <?php else: ?>
                            <span style="color:#94a3b8; font-size:.82rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right; white-space:nowrap;">
                        <button class="btn btn-outline" style="border:1px solid #ddd; color:#2563eb; padding:.3rem .7rem; font-size:.82rem;"
                                onclick='currOpenModal(<?= htmlspecialchars(json_encode($cur), ENT_QUOTES) ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if (!(int)$cur['is_active']): ?>
                        <form method="POST" action="/admin/currencies/delete/<?= (int)$cur['id'] ?>"
                              style="display:inline;"
                              onsubmit="return confirm('<?= __('delete') ?>  <?= htmlspecialchars(addslashes($cur['code'])) ?>?')">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                            <button class="btn btn-outline" style="border:1px solid #ddd; color:#ef4444; padding:.3rem .7rem; font-size:.82rem;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($currencies)): ?>
                <tr><td colspan="5" style="text-align:center; padding:1.5rem; color:#94a3b8;"><?= __('currency_not_found') ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Модал додавання/редагування валюти -->
<div class="curr-modal-backdrop" id="currModal">
    <div class="curr-modal">
        <div class="curr-modal-hdr">
            <span id="currModalTitle"><?= __('currency_add') ?></span>
            <button onclick="currCloseModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <form method="POST" action="/admin/currencies/store">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" name="id" id="currId" value="0">
            <div class="curr-modal-body">
                <div>
                    <label><?= __('currency_code') ?> *</label>
                    <input type="text" name="code" id="currCode" maxlength="3"
                           placeholder="USD" required style="text-transform:uppercase;">
                </div>
                <div>
                    <label><?= __('currency_symbol') ?> *</label>
                    <input type="text" name="symbol" id="currSymbol" maxlength="10"
                           placeholder="$" required>
                </div>
                <div>
                    <label><?= __('currency_rate_to_uah') ?> *</label>
                    <input type="number" name="rate" id="currRate" min="0.0001"
                           step="0.0001" placeholder="41.5000" required>
                </div>
            </div>
            <div class="curr-modal-ftr">
                <button type="button" onclick="currCloseModal()"
                        class="btn btn-outline" style="border:1px solid #ddd;"><?= __('cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= __('save') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function currOpenModal(data) {
    const modal = document.getElementById('currModal');
    if (data) {
        document.getElementById('currModalTitle').textContent = <?= json_encode(__('currency_edit')) ?>;
        document.getElementById('currId').value     = data.id;
        document.getElementById('currCode').value   = data.code;
        document.getElementById('currSymbol').value = data.symbol;
        document.getElementById('currRate').value   = data.rate;
    } else {
        document.getElementById('currModalTitle').textContent = <?= json_encode(__('currency_add')) ?>;
        document.getElementById('currId').value     = '0';
        document.getElementById('currCode').value   = '';
        document.getElementById('currSymbol').value = '';
        document.getElementById('currRate').value   = '';
    }
    modal.classList.add('active');
}
function currCloseModal() {
    document.getElementById('currModal').classList.remove('active');
}
document.getElementById('currModal').addEventListener('click', function(e) {
    if (e.target === this) currCloseModal();
});
</script>

<script>
// Sitemap — показуємо спіннер під час генерації
(function () {
    const form = document.getElementById('sitemap-form');
    const btn  = document.getElementById('sitemap-btn');
    if (!form || !btn) return;
    form.addEventListener('submit', function () {
        btn.disabled    = true;
        btn.innerHTML   = '<span class="spin"></span> <?= __('generating') ?>…';
    });
})();

// HTTPS → HSTS залежність
(function () {
    const httpsToggle = document.getElementById('https_redirect');
    const hstsBlock   = document.getElementById('hsts-block');
    const hstsToggle  = document.getElementById('hsts_enabled');
    if (!httpsToggle || !hstsBlock) return;

    function syncHstsBlock() {
        if (httpsToggle.checked) {
            hstsBlock.style.opacity       = '1';
            hstsBlock.style.pointerEvents = '';
        } else {
            hstsBlock.style.opacity       = '.45';
            hstsBlock.style.pointerEvents = 'none';
            if (hstsToggle) hstsToggle.checked = false;
        }
    }

    httpsToggle.addEventListener('change', syncHstsBlock);
    syncHstsBlock();
})();
</script>

<!-- ═══════════════ МОНІТОРИНГ ВХОДІВ ═══════════════ -->
<?php
$windowStart = date('Y-m-d H:i:s', time() - 15 * 60);
$recentFails = \App\Core\Database\DB::query(
    "SELECT ip, email, COUNT(*) as attempts,
            MAX(created_at) as last_attempt
     FROM login_attempts
     WHERE success = 0 AND created_at >= ?
     GROUP BY ip, email
     HAVING attempts >= 3
     ORDER BY attempts DESC
     LIMIT 20",
    [$windowStart]
)->fetchAll(\PDO::FETCH_ASSOC);

$totalFails = (int) \App\Core\Database\DB::query(
    "SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND created_at >= ?",
    [$windowStart]
)->fetchColumn();

$blockedIps = \App\Core\Database\DB::query(
    "SELECT ip, COUNT(*) as attempts, MAX(created_at) as last_attempt
     FROM login_attempts
     WHERE success = 0 AND created_at >= ?
     GROUP BY ip
     HAVING attempts >= 10
     ORDER BY attempts DESC",
    [$windowStart]
)->fetchAll(\PDO::FETCH_ASSOC);
?>

<div class="card" style="margin-top:1.25rem;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <span>
            <i class="fas fa-shield-alt"></i> <?= __('security_login_monitor') ?>
            <span style="margin-left:.5rem; background:#f1f5f9; color:#64748b;
                         font-size:.75rem; font-weight:600; padding:2px 10px; border-radius:20px;">
                <?= __('security_last_15min') ?>
            </span>
        </span>
        <span style="display:flex; gap:.75rem; align-items:center; font-size:.85rem;">
            <span style="color:#ef4444; font-weight:700;">
                <i class="fas fa-times-circle"></i> <?= __('security_failed') ?>: <?= $totalFails ?>
            </span>
            <?php if (!empty($blockedIps)): ?>
                <span style="background:#fee2e2; color:#991b1b; font-weight:700;
                             padding:2px 10px; border-radius:20px; font-size:.78rem;">
                    <i class="fas fa-ban"></i> <?= __('security_blocked_ip') ?>: <?= count($blockedIps) ?>
                </span>
            <?php endif; ?>
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($recentFails)): ?>
            <div style="padding:1.5rem; text-align:center; color:#94a3b8; font-size:.9rem;">
                <i class="fas fa-check-circle" style="color:#10b981; margin-right:.4rem;"></i>
                <?= __('security_no_suspicious') ?>
            </div>
        <?php else: ?>
            <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:.6rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;"><?= __('security_ip') ?></th>
                        <th style="padding:.6rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;"><?= __('email') ?></th>
                        <th style="padding:.6rem 1rem; text-align:center; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;"><?= __('cron_attempts') ?></th>
                        <th style="padding:.6rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;"><?= __('cron_last_attempt') ?></th>
                        <th style="padding:.6rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;"><?= __('status') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentFails as $row): ?>
                    <?php $isBlocked = (int)$row['attempts'] >= 10; ?>
                    <tr style="border-bottom:1px solid #f1f5f9; <?= $isBlocked ? 'background:#fff5f5;' : '' ?>">
                        <td style="padding:.6rem 1rem; font-family:monospace; font-weight:600;">
                            <?= htmlspecialchars($row['ip']) ?>
                        </td>
                        <td style="padding:.6rem 1rem; color:#475569;">
                            <?= htmlspecialchars($row['email'] ?: '—') ?>
                        </td>
                        <td style="padding:.6rem 1rem; text-align:center;">
                            <span style="background:<?= $isBlocked ? '#fee2e2; color:#991b1b' : '#fef3c7; color:#92400e' ?>;
                                         font-weight:700; padding:2px 10px; border-radius:20px; font-size:.8rem;">
                                <?= (int)$row['attempts'] ?>
                            </span>
                        </td>
                        <td style="padding:.6rem 1rem; color:#64748b; font-size:.82rem;">
                            <?= date('d.m.Y H:i:s', strtotime($row['last_attempt'])) ?>
                        </td>
                        <td style="padding:.6rem 1rem;">
                            <?php if ($isBlocked): ?>
                                <span style="background:#fee2e2; color:#991b1b; font-size:.75rem;
                                             font-weight:700; padding:2px 8px; border-radius:20px;">
                                    <i class="fas fa-ban"></i> <?= __('security_blocked') ?>
                                </span>
                            <?php else: ?>
                                <span style="background:#fef3c7; color:#92400e; font-size:.75rem;
                                             font-weight:700; padding:2px 8px; border-radius:20px;">
                                    <i class="fas fa-exclamation-triangle"></i> <?= __('security_suspicious') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
