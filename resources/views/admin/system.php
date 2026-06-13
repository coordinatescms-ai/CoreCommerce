<div class="page-header">
    <h1 class="page-title"><i class="fas fa-server"></i> Система</h1>
</div>

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
    <div class="card">
        <div class="card-header">Environment</div>
        <div class="card-body">
            <form method="POST" action="/admin/system/environment">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <p><label><input type="checkbox" name="display_errors" value="1" <?php echo ($environment['display_errors'] ?? '0') === '1' ? 'checked' : ''; ?>> Режим розробки (Debug Mode / Display Errors)</label></p>
                <p><label><input type="checkbox" name="maintenance_mode" value="1" <?php echo ($environment['store_status'] ?? 'open') === 'closed' ? 'checked' : ''; ?>> Технічні роботи (Maintenance Mode)</label></p>
                <button class="btn btn-primary" type="submit">Зберегти режими</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Mail Settings</div>
        <div class="card-body">
            <form method="POST" action="/admin/system/mail/test">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <p><label>Email для тесту</label><input type="email" name="test_email" class="form-control" required placeholder="test@example.com"></p>
                <p><label><input type="checkbox" name="test_email_use_db" value="1" checked> Тест з налаштувань бази даних</label></p>
                <button class="btn btn-success" type="submit">Надіслати тестовий лист</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">System Info</div>
        <div class="card-body">
            <div class="system-kv">
                <div class="key">PHP</div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['php_version']); ?></div>
                <div class="key">MySQL</div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['mysql_version']); ?></div>
                <div class="key">Версія движка</div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['engine_version']); ?></div>
                <div class="key">upload_max_filesize</div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['upload_max_filesize']); ?></div>
                <div class="key">memory_limit</div><div class="val"><?php echo htmlspecialchars((string) $systemInfo['memory_limit']); ?></div>
                <div class="key">Диск (зайнято/всього)</div><div class="val"><?php echo $systemInfo['disk_used'] !== false ? round($systemInfo['disk_used'] / 1073741824, 2) . ' GB / ' . round($systemInfo['disk_total'] / 1073741824, 2) . ' GB' : 'unknown'; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Cron-завдання</div>
    <div class="card-body">
        <table class="table" id="cronTasksTable">
            <thead><tr><th>Назва</th><th>Періодичність</th><th>Останній запуск</th><th>Наступний запуск</th><th>Статус</th><th>Результат</th><th>Дії</th></tr></thead>
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
                            <button class="btn btn-primary js-edit-task" type="button" title="Редагувати"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-secondary js-toggle-task" type="button"><?php echo $task['status'] === 'active' ? 'Disable' : 'Enable'; ?></button>
                            <button class="btn btn-success js-run-task" type="button" title="Запустити зараз"><i class="fas fa-plug"></i></button>
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
        <div class="cron-modal-header"><strong>Редагування Cron-завдання</strong></div>
        <div class="cron-modal-body">
            <input type="hidden" id="cronTaskId">
            <label for="cronTaskName">Назва</label>
            <input type="text" id="cronTaskName">
            <label for="cronTaskSchedule">Cron string</label>
            <input type="text" id="cronTaskSchedule" placeholder="*/30 * * * *">
            <label for="cronTaskCommand">Шлях до файлу</label>
            <input type="text" id="cronTaskCommand" placeholder="tasks/import_products.php">
            <label for="cronTaskParams">Params (JSON або текст)</label>
            <textarea id="cronTaskParams"></textarea>
        </div>
        <div class="cron-modal-footer">
            <button type="button" class="btn btn-secondary" id="cronModalCancel">Скасувати</button>
            <button type="button" class="btn btn-primary" id="cronModalSave">Зберегти</button>
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
    <div class="card-header">Системні дії</div>
    <div class="card-body actions">
        <form method="POST" action="/admin/clear-cache"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-primary" type="submit"><i class="fas fa-broom"></i> Почистити кеш</button></form>
        <form method="POST" action="/admin/system/logs/clear"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-danger" type="submit" onclick="return confirm('Видалити старі логи?');"><i class="fas fa-trash"></i> Очистити логи</button></form>
        <form method="POST" action="/admin/system/database/backup"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-success" type="submit"><i class="fas fa-database"></i> Створити Backup (.sql)</button></form>
        <form method="POST" action="/admin/system/database/optimize"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-primary" type="submit"><i class="fas fa-bolt"></i> OPTIMIZE TABLE</button></form>
    </div>
</div>

<div class="card">
    <div class="card-header">PHP error.log</div>
    <div class="card-body">
        <div class="log-box"><?php if (empty($logs['php_errors'])): ?>Лог порожній або відсутній.<?php else: ?><?php echo htmlspecialchars(implode("\n", $logs['php_errors'])); ?><?php endif; ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header">Логи дій адміністраторів</div>
    <div class="card-body">
        <div class="log-box"><?php if (empty($logs['admin_actions'])): ?>Лог порожній або відсутній.<?php else: ?><?php echo htmlspecialchars(implode("\n", $logs['admin_actions'])); ?><?php endif; ?></div>
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

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success" style="margin-bottom:1rem;"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="card" style="margin-top:1.25rem;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <span><i class="fas fa-coins"></i> Управління валютами</span>
        <button class="btn btn-primary" style="padding:.4rem .9rem; font-size:.85rem;"
                onclick="currOpenModal()">
            <i class="fas fa-plus"></i> Додати валюту
        </button>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="curr-table">
            <thead>
                <tr>
                    <th>Код</th>
                    <th>Символ</th>
                    <th>Курс (до UAH)</th>
                    <th>Статус</th>
                    <th style="text-align:right;">Дії</th>
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
                            <span class="curr-active-badge"><i class="fas fa-check-circle"></i> Активна</span>
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
                              onsubmit="return confirm('Видалити валюту <?= htmlspecialchars(addslashes($cur['code'])) ?>?')">
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
                <tr><td colspan="5" style="text-align:center; padding:1.5rem; color:#94a3b8;">Валют не знайдено.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Модал додавання/редагування валюти -->
<div class="curr-modal-backdrop" id="currModal">
    <div class="curr-modal">
        <div class="curr-modal-hdr">
            <span id="currModalTitle">Додати валюту</span>
            <button onclick="currCloseModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b;">✕</button>
        </div>
        <form method="POST" action="/admin/currencies/store">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" name="id" id="currId" value="0">
            <div class="curr-modal-body">
                <div>
                    <label>Код валюти (3 літери, напр. USD) *</label>
                    <input type="text" name="code" id="currCode" maxlength="3"
                           placeholder="USD" required style="text-transform:uppercase;">
                </div>
                <div>
                    <label>Символ (напр. $, €, ₴) *</label>
                    <input type="text" name="symbol" id="currSymbol" maxlength="10"
                           placeholder="$" required>
                </div>
                <div>
                    <label>Курс відносно UAH *</label>
                    <input type="number" name="rate" id="currRate" min="0.0001"
                           step="0.0001" placeholder="41.5000" required>
                </div>
            </div>
            <div class="curr-modal-ftr">
                <button type="button" onclick="currCloseModal()"
                        class="btn btn-outline" style="border:1px solid #ddd;">Скасувати</button>
                <button type="submit" class="btn btn-primary">Зберегти</button>
            </div>
        </form>
    </div>
</div>

<script>
function currOpenModal(data) {
    const modal = document.getElementById('currModal');
    if (data) {
        document.getElementById('currModalTitle').textContent = 'Редагувати валюту';
        document.getElementById('currId').value     = data.id;
        document.getElementById('currCode').value   = data.code;
        document.getElementById('currSymbol').value = data.symbol;
        document.getElementById('currRate').value   = data.rate;
    } else {
        document.getElementById('currModalTitle').textContent = 'Додати валюту';
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
            <i class="fas fa-shield-alt"></i> Моніторинг входів
            <span style="margin-left:.5rem; background:#f1f5f9; color:#64748b;
                         font-size:.75rem; font-weight:600; padding:2px 10px; border-radius:20px;">
                останні 15 хв
            </span>
        </span>
        <span style="display:flex; gap:.75rem; align-items:center; font-size:.85rem;">
            <span style="color:#ef4444; font-weight:700;">
                <i class="fas fa-times-circle"></i> Невдалих: <?= $totalFails ?>
            </span>
            <?php if (!empty($blockedIps)): ?>
                <span style="background:#fee2e2; color:#991b1b; font-weight:700;
                             padding:2px 10px; border-radius:20px; font-size:.78rem;">
                    <i class="fas fa-ban"></i> Заблоковано IP: <?= count($blockedIps) ?>
                </span>
            <?php endif; ?>
        </span>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($recentFails)): ?>
            <div style="padding:1.5rem; text-align:center; color:#94a3b8; font-size:.9rem;">
                <i class="fas fa-check-circle" style="color:#10b981; margin-right:.4rem;"></i>
                Підозрілої активності не виявлено
            </div>
        <?php else: ?>
            <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:.6rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">IP-адреса</th>
                        <th style="padding:.6rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Email</th>
                        <th style="padding:.6rem 1rem; text-align:center; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Спроб</th>
                        <th style="padding:.6rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Остання спроба</th>
                        <th style="padding:.6rem 1rem; text-align:left; color:#64748b; font-size:.78rem; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Статус</th>
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
                                    <i class="fas fa-ban"></i> Заблоковано
                                </span>
                            <?php else: ?>
                                <span style="background:#fef3c7; color:#92400e; font-size:.75rem;
                                             font-weight:700; padding:2px 8px; border-radius:20px;">
                                    <i class="fas fa-exclamation-triangle"></i> Підозріло
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
