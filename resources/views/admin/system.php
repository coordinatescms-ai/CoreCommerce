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
