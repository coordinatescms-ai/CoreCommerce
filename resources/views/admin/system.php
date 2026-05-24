<div class="page-header">
    <h1 class="page-title"><i class="fas fa-server"></i> Система</h1>
</div>

<style>
.system-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(320px,1fr)); gap: 1rem; }
.system-kv { display:grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
.system-kv .key { color:#64748b; font-size:.9rem; }
.system-kv .val { font-weight:600; }
.badge { display:inline-block; padding:4px 10px; border-radius: 999px; font-size:.78rem; font-weight:700; }
.badge.ok { background:#dcfce7; color:#166534; }
.badge.bad { background:#fee2e2; color:#991b1b; }
.log-box { background:#0f172a; color:#e2e8f0; border-radius:8px; padding:12px; max-height:280px; overflow:auto; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12px; }
.actions { display:flex; flex-wrap:wrap; gap:.75rem; }
</style>

<div class="system-grid">
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
            <hr style="margin:1rem 0;border:none;border-top:1px solid #e2e8f0;">
            <div class="system-kv">
                <div class="key">GD</div><div class="val"><span class="badge <?php echo $systemInfo['extensions']['gd'] ? 'ok' : 'bad'; ?>"><?php echo $systemInfo['extensions']['gd'] ? 'Підключено' : 'Відсутнє'; ?></span></div>
                <div class="key">cURL</div><div class="val"><span class="badge <?php echo $systemInfo['extensions']['curl'] ? 'ok' : 'bad'; ?>"><?php echo $systemInfo['extensions']['curl'] ? 'Підключено' : 'Відсутнє'; ?></span></div>
                <div class="key">MBString</div><div class="val"><span class="badge <?php echo $systemInfo['extensions']['mbstring'] ? 'ok' : 'bad'; ?>"><?php echo $systemInfo['extensions']['mbstring'] ? 'Підключено' : 'Відсутнє'; ?></span></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Системні дії</div>
        <div class="card-body actions">
            <form method="POST" action="/admin/clear-cache"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-primary" type="submit"><i class="fas fa-broom"></i> Почистити кеш</button></form>
            <form method="POST" action="/admin/system/logs/clear"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-danger" type="submit" onclick="return confirm('Видалити старі логи?');"><i class="fas fa-trash"></i> Очистити логи</button></form>
            <form method="POST" action="/admin/system/database/backup"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-success" type="submit"><i class="fas fa-database"></i> Створити Backup (.sql)</button></form>
            <form method="POST" action="/admin/system/database/optimize"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>"><button class="btn btn-primary" type="submit"><i class="fas fa-bolt"></i> OPTIMIZE TABLE</button></form>
        </div>
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
