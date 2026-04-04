<div class="page-header">
    <h1 class="page-title">Налаштування магазину</h1>
</div>

<form action="/admin/settings/save" method="POST">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
    
    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Основна інформація
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="site_name">Назва магазину</label>
                <input type="text" name="settings[site_name]" id="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['general'][0]['value'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="site_description">Опис магазину (для SEO)</label>
                <textarea name="settings[site_description]" id="site_description" class="form-control" rows="3"><?php echo htmlspecialchars($settings['general'][1]['value'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="store_status">Статус магазину</label>
                <select name="settings[store_status]" id="store_status" class="form-control">
                    <option value="open" <?php echo ($settings['general'][2]['value'] ?? '') === 'open' ? 'selected' : ''; ?>>Відкритий</option>
                    <option value="closed" <?php echo ($settings['general'][2]['value'] ?? '') === 'closed' ? 'selected' : ''; ?>>Закритий (Технічне обслуговування)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="maintenance_message">Повідомлення при закритті</label>
                <textarea name="settings[maintenance_message]" id="maintenance_message" class="form-control" rows="2"><?php echo htmlspecialchars($settings['general'][3]['value'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-globe"></i> Локалізація та Валюта
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="default_language">Мова за замовчуванням</label>
                <select name="settings[default_language]" id="default_language" class="form-control">
                    <option value="ua" <?php echo ($settings['localization'][0]['value'] ?? '') === 'ua' ? 'selected' : ''; ?>>Українська</option>
                    <option value="en" <?php echo ($settings['localization'][0]['value'] ?? '') === 'en' ? 'selected' : ''; ?>>Англійська</option>
                </select>
            </div>
            <div class="form-group">
                <label for="default_currency">Основна валюта</label>
                <select name="settings[default_currency]" id="default_currency" class="form-control">
                    <option value="UAH" <?php echo ($settings['localization'][1]['value'] ?? '') === 'UAH' ? 'selected' : ''; ?>>Гривня (UAH)</option>
                    <option value="USD" <?php echo ($settings['localization'][1]['value'] ?? '') === 'USD' ? 'selected' : ''; ?>>Долар (USD)</option>
                    <option value="EUR" <?php echo ($settings['localization'][1]['value'] ?? '') === 'EUR' ? 'selected' : ''; ?>>Євро (EUR)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-palette"></i> Зовнішній вигляд
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="active_theme">Активна тема оформлення</label>
                <select name="settings[active_theme]" id="active_theme" class="form-control">
                    <?php foreach ($themes as $theme): ?>
                        <option value="<?php echo htmlspecialchars($theme); ?>" <?php echo ($settings['appearance'][0]['value'] ?? '') === $theme ? 'selected' : ''; ?>>
                            <?php echo ucfirst(htmlspecialchars($theme)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-address-book"></i> Контактні дані
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="contact_email">Контактний Email</label>
                <input type="email" name="settings[contact_email]" id="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact'][0]['value'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="contact_phone">Контактний телефон</label>
                <input type="text" name="settings[contact_phone]" id="contact_phone" class="form-control" value="<?php echo htmlspecialchars($settings['contact'][1]['value'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> Зберегти всі налаштування
        </button>
    </div>
</form>
