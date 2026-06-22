<div class="card">
    <div class="card-header">
        <i class="fas fa-sync"></i> Система оновлення ядра
    </div>
    <div class="card-body">
        <input type="hidden" id="update-csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
        <div id="update-info">
            <p><strong><?= __('update_current_version') ?>:</strong> <span id="current-version"><?php echo htmlspecialchars($current_version ?? '1.0.0'); ?></span></p>
            <div id="update-check-container">
                <button type="button" id="check-update-btn" class="btn btn-primary">
                    <i class="fas fa-search"></i> Перевірити наявність оновлень
                </button>
            </div>
        </div>

        <div id="update-available-container" style="display: none; margin-top: 1.5rem; border-top: 1px solid #eee; padding-top: 1.5rem;">
            <div class="alert alert-info">
                <h4><?= __('update_new_available') ?>: <span id="new-version-id"></span></h4>
                <div id="changelog-container" style="margin-top: 1rem; background: #f8fafc; padding: 1rem; border-radius: 4px; border-left: 4px solid #3b82f6;">
                    <strong><?= __('update_changelog') ?>:</strong>
                    <pre id="changelog-text" style="white-space: pre-wrap; margin-top: 0.5rem; font-family: inherit;"></pre>
                </div>
            </div>

            <div id="readiness-checklist" style="margin: 1.5rem 0;">
                <h5><?= __('update_checklist') ?>:</h5>
                <ul style="list-style: none; padding-left: 0;">
                    <li id="check-writable"><i class="fas fa-circle-notch fa-spin"></i> Перевірка прав на запис...</li>
                    <li id="check-php"><i class="fas fa-circle-notch fa-spin"></i> Перевірка версії PHP...</li>
                </ul>
            </div>

            <div id="auth-confirm" style="margin-bottom: 1.5rem; padding: 1rem; background: #fff5f5; border: 1px solid #feb2b2; border-radius: 4px;">
                <p style="color: #c53030; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> Підтвердіть дію</p>
                <div class="form-group">
                    <label><?= __('update_password_prompt') ?>:</label>
                    <input type="password" id="admin-password" class="form-control" placeholder="Пароль">
                </div>
                <button type="button" id="start-update-btn" class="btn btn-danger" style="margin-top: 0.5rem;">
                    <i class="fas fa-download"></i> Почати оновлення
                </button>
            </div>
        </div>

        <div id="update-progress-container" style="display: none; margin-top: 1.5rem;">
            <h5 id="update-status-text"><?= __('update_preparing') ?></h5>
            <div class="progress" style="height: 25px; background: #e2e8f0; border-radius: 12px; overflow: hidden; margin: 1rem 0;">
                <div id="update-progress-bar" class="progress-bar" style="width: 0%; height: 100%; background: #3b82f6; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">0%</div>
            </div>
            <div id="update-log-container" style="background: #1e293b; color: #f1f5f9; padding: 1rem; border-radius: 4px; font-family: monospace; font-size: 0.85rem; max-height: 200px; overflow-y: auto;">
                <div>> Очікування старту...</div>
            </div>
        </div>
    </div>
</div>
