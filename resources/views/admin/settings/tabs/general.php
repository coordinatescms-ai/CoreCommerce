<form action="/admin/settings/save" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
    <input type="hidden" name="current_tab" value="general">

    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Основна інформація
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="site_name">Назва магазину</label>
                <input type="text" name="settings[site_name]" id="site_name" class="form-control" value="<?php echo htmlspecialchars(get_setting('site_name', '')); ?>">
            </div>
            <div class="form-group">
                <label for="site_description">Опис магазину (для SEO)</label>
                <textarea name="settings[site_description]" id="site_description" class="form-control" rows="3"><?php echo htmlspecialchars(get_setting('site_description', '')); ?></textarea>
            </div>
            <div class="form-group">
                <label for="store_status">Статус магазину</label>
                <select name="settings[store_status]" id="store_status" class="form-control">
                    <option value="open" <?php echo get_setting('store_status', 'open') === 'open' ? 'selected' : ''; ?>>Відкритий</option>
                    <option value="closed" <?php echo get_setting('store_status', 'open') === 'closed' ? 'selected' : ''; ?>>Закритий (Технічне обслуговування)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="maintenance_message">Повідомлення при закритті</label>
                <textarea name="settings[maintenance_message]" id="maintenance_message" class="form-control" rows="2"><?php echo htmlspecialchars(get_setting('maintenance_message', '')); ?></textarea>
            </div>
            <div class="form-group">
                <label for="logotype_file">Логотип магазину</label>
                <input type="file" name="logotype_file" id="logotype_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <small style="display:block; margin-top: 0.35rem; color: #6b7280;">Дозволені формати: JPG, PNG, WEBP. Максимум: 1MB.</small>
                <?php $activeLogotype = trim((string) get_setting('active_logotype', '')); ?>
                <?php if ($activeLogotype !== ''): ?>
                    <div style="margin-top: 0.5rem;">
                        <img src="<?php echo htmlspecialchars($activeLogotype); ?>" alt="Поточний логотип" style="max-height: 60px; width: auto; border: 1px solid #ddd; border-radius: 6px; padding: 4px; background: #fff;">
                    </div>
                <?php endif; ?>
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
                    <option value="ua" <?php echo get_setting('default_language', 'ua') === 'ua' ? 'selected' : ''; ?>>Українська</option>
                    <option value="en" <?php echo get_setting('default_language', 'ua') === 'en' ? 'selected' : ''; ?>>Англійська</option>
                </select>
            </div>
            <div class="form-group">
                <label for="default_currency">Основна валюта</label>
                <select name="settings[default_currency]" id="default_currency" class="form-control">
                    <option value="UAH" <?php echo get_setting('default_currency', 'UAH') === 'UAH' ? 'selected' : ''; ?>>Гривня (UAH)</option>
                    <option value="USD" <?php echo get_setting('default_currency', 'UAH') === 'USD' ? 'selected' : ''; ?>>Долар (USD)</option>
                    <option value="EUR" <?php echo get_setting('default_currency', 'UAH') === 'EUR' ? 'selected' : ''; ?>>Євро (EUR)</option>
                </select>
            </div>
        </div>
    </div>

<!-- Секція Локалізація -->
<div class="settings-card">
    <div class="card-header">
        <h3><i class="fa-solid fa-clock"></i> Регіональні налаштування</h3>
    </div>
    <div class="grid-inputs">
        <div class="input-group">
            <label>Часовий пояс (Timezone)</label>
            <select name="settings[site_timezone]" id="site_timezone" class="custom-select">
                <option value="Europe/Kiev" <?php echo get_setting('site_timezone', 'Europe/Kiev') === 'Europe/Kiev' ? 'selected' : ''; ?>>Kyiv (GMT+2 / GMT+3)</option>
                <option value="Europe/London" <?php echo get_setting('site_timezone', 'Europe/London') === 'Europe/London' ? 'selected' : ''; ?>>London (GMT+0 / GMT+1)</option>
                <option value="Europe/Warsaw">Warsaw (GMT+1 / GMT+2)</option>
                <option value="UTC">Universal Time (UTC)</option>
            </select>
            <small class="hint">Впливає на відображення дати та часу в замовленнях.</small>
        </div>
        <div class="input-group">
            <label>Формат дати</label>
            <input type="text" name="date_format" value="d.m.Y H:i" placeholder="d.m.Y H:i">
            <p class="hint">Наприклад: <code>11.04.2024 15:30</code></p>
        </div>
    </div>
</div>

<!-- Секція SMTP -->
<div class="settings-card">
    <div class="card-header">
        <h3><i class="fa-solid fa-envelope"></i> Налаштування пошти (SMTP)</h3>
    </div>
    <div class="grid-inputs">
        <div class="input-group">
            <label>SMTP Хост</label>
            <input type="text" id="smtp" name="settings[smtr]" value="<?php echo htmlspecialchars(get_setting('smtr', '')); ?>">
        </div>
        <div class="input-group">
            <label>Порт</label>
            <input type="number" id="smtp_port" name="settings[smtp_port]" value="<?php echo htmlspecialchars(get_setting('smtp_port', '')); ?>">
        </div>
        <div class="input-group">
            <label>Користувач (Email)</label>
            <input type="email" id="email" name="settings[email]" value="<?php echo htmlspecialchars(get_setting('email', '')); ?>">
        </div>
        <div class="input-group">
            <label>Пароль</label>
            <input type="password" id="smtp_pass" name="settings[smtp_pass]" value="<?php echo htmlspecialchars(get_setting('smtp_pass', '')); ?>">
        </div>
    </div>
</div>

<!-- Секція SEO -->
<div class="settings-card">
    <div class="card-header">
        <h3><i class="fa-solid fa-search"></i> SEO-шаблони для товарів</h3>
    </div>
    <div class="input-group">
        <label>Шаблон Title</label>
        <input type="text" id="seo_title_template" name="settings[seo_title_template]" value="<?php echo htmlspecialchars(get_setting('seo_title_template', '')); ?>">
        <p class="hint">Доступні маски: <code>{name}</code>, <code>{price}</code>, <code>{category}</code></p>
    </div>
    <div class="input-group">
        <label>Шаблон Description</label>
        <textarea id="seo_desc_template" name="settings[seo_desc_template]" rows="3"><?php echo htmlspecialchars(get_setting('seo_desc_template', '')); ?></textarea>
        <p class="hint">Якщо для конкретного товару відсутнє seo-налаштування, тоді застосовуються автоматичні шаблони для всіх товарів</p>
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
                        <option value="<?php echo htmlspecialchars($theme); ?>" <?php echo get_setting('active_theme', '') === $theme ? 'selected' : ''; ?>>
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
                <input type="email" name="settings[contact_email]" id="contact_email" class="form-control" value="<?php echo htmlspecialchars(get_setting('contact_email', '')); ?>">
            </div>
            <div class="form-group">
                <label for="contact_phone">Контактний телефон</label>
                <input type="text" name="settings[contact_phone]" id="contact_phone" class="form-control" value="<?php echo htmlspecialchars(get_setting('contact_phone', '')); ?>">
            </div>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> Зберегти всі налаштування
        </button>
    </div>
</form>
