<div class="page-header">
    <h1 class="page-title">Керування плагінами</h1>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert <?php echo !empty($flash['success']) ? 'alert-success' : 'alert-error'; ?>">
        <?php echo htmlspecialchars((string) ($flash['message'] ?? '')); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">Завантаження плагіна (.zip)</div>
    <div class="card-body">
        <form method="POST" action="/admin/plugins/upload" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
            <div class="form-group">
                <label for="plugin_zip">ZIP файл (макс. <?php echo (int) $maxUploadSizeMb; ?> MB)</label>
                <input class="form-control" type="file" name="plugin_zip" id="plugin_zip" accept=".zip" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Завантажити</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Список плагінів</div>
    <div class="card-body">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Назва</th>
                    <th>Опис та версія</th>
                    <th>Статус</th>
                    <th>Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $plugin): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($plugin['name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($plugin['slug']); ?> · <?php echo htmlspecialchars($plugin['author']); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($plugin['description']); ?><br>
                            <small>Версія: <?php echo htmlspecialchars($plugin['version']); ?></small>
                            <?php if (!empty($plugin['requires_php']) || !empty($plugin['requires_core'])): ?>
                                <br><small>PHP: <?php echo htmlspecialchars($plugin['requires_php'] ?: 'n/a'); ?>, Core: <?php echo htmlspecialchars($plugin['requires_core'] ?: 'n/a'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($plugin['is_active'])): ?>
                                <span style="color: #16a34a; font-weight: 600;">Активний</span>
                            <?php else: ?>
                                <span style="color: #64748b; font-weight: 600;">Неактивний</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="/admin/plugins/toggle">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($plugin['slug']); ?>">
                                <?php if (!empty($plugin['is_active'])): ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <button class="btn btn-danger" type="submit">Вимкнути</button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="activate">
                                    <button class="btn btn-success" type="submit">Активувати</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
