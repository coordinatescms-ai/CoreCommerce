<?php
// Отримуємо всі валюти та поточні налаштування
$allCurrencies   = \App\Core\Database\DB::query('SELECT * FROM currencies ORDER BY is_active DESC, code ASC')->fetchAll(\PDO::FETCH_ASSOC);
$activeCurrency  = array_values(array_filter($allCurrencies, fn($c) => (int)$c['is_active'] === 1))[0] ?? null;
$currencySource  = get_setting('currency_source', 'manual');
?>

<!-- Окрема форма для валют (ПЕРША) -->
<form method="POST" action="/admin/currencies/update" id="currencyUpdateForm" style="display:none;">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
</form>

<!-- Основна форма налаштувань (ДРУГА) -->
<form action="/admin/settings/save" method="POST" enctype="multipart/form-data" id="mainSettingsForm">
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
                <label for="phone_mask">Маска номера телефону</label>
                <input type="text" name="settings[phone_mask]" id="phone_mask" class="form-control" value="<?php echo htmlspecialchars(get_setting('phone_mask', '+38 (###) ###-##-##')); ?>">
                <small style="display:block; margin-top: 0.35rem; color: #6b7280;">Використовуйте символ <code>#</code> для цифр. Приклад: <code>+38 (###) ###-##-##</code>.</small>
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
            <i class="fas fa-globe"></i> Локалізація
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="default_language">Мова за замовчуванням</label>
                <select name="settings[default_language]" id="default_language" class="form-control">
                    <option value="ua" <?php echo get_setting('default_language', 'ua') === 'ua' ? 'selected' : ''; ?>>Українська</option>
                    <option value="en" <?php echo get_setting('default_language', 'ua') === 'en' ? 'selected' : ''; ?>>Англійська</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Блок перерахунку валюти (ВІЗУАЛЬНО ТУТ, АЛЕ ПРИВ'ЯЗАНИЙ ДО currencyUpdateForm) -->
    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fas fa-coins"></i> Валюта та перерахунок цін
            <?php if ($activeCurrency): ?>
                <span style="margin-left:.75rem; background:#dcfce7; color:#166534;
                             font-size:.75rem; font-weight:700; padding:2px 10px;
                             border-radius:20px; vertical-align:middle;">
                    Активна: <?= htmlspecialchars($activeCurrency['code']) ?>
                    (<?= htmlspecialchars($activeCurrency['symbol']) ?>)
                    · курс <?= number_format((float)$activeCurrency['rate'], 4) ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="target_currency">Перемкнути сайт на валюту</label>
                <select name="target_currency" id="target_currency" class="form-control" form="currencyUpdateForm">
                    <?php foreach ($allCurrencies as $cur): ?>
                        <option value="<?= htmlspecialchars($cur['code']) ?>"
                            <?= (int)$cur['is_active'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cur['code']) ?>
                            (<?= htmlspecialchars($cur['symbol']) ?>)
                            <?= (int)$cur['is_active'] ? ' — поточна' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#64748b; font-size:.8rem; margin-top:.25rem; display:block;">
                    Ціни <strong>всіх товарів</strong> будуть фізично перераховані в базі даних.
                </small>
            </div>

            <div class="form-group">
                <label style="display:flex; align-items:center; gap:.75rem; cursor:pointer; font-weight:600;">
                    Джерело курсу:
                    <label style="display:flex; align-items:center; gap:.3rem; font-weight:400; cursor:pointer;">
                        <input type="radio" name="currency_source" value="manual"
                               id="src_manual"
                               form="currencyUpdateForm"
                               <?= $currencySource !== 'api' ? 'checked' : '' ?>
                               onchange="toggleRateSource()">
                        Мій курс
                    </label>
                    <label style="display:flex; align-items:center; gap:.3rem; font-weight:400; cursor:pointer;">
                        <input type="radio" name="currency_source" value="api"
                               id="src_api"
                               form="currencyUpdateForm"
                               <?= $currencySource === 'api' ? 'checked' : '' ?>
                               onchange="toggleRateSource()">
                        API НБУ (автоматично)
                    </label>
                </label>
            </div>

            <!-- Поле для ручного курсу -->
            <div class="form-group" id="manual_rate_group"
                 style="<?= $currencySource === 'api' ? 'display:none;' : '' ?>">
                <label for="manual_rate">Курс цільової валюти до UAH</label>
                <input type="number" name="manual_rate" id="manual_rate"
                       class="form-control"
                       form="currencyUpdateForm"
                       min="0.0001" step="0.0001"
                       placeholder="Напр. 41.5000"
                       value="">
                <small style="color:#64748b; font-size:.8rem; display:block; margin-top:.25rem;">
                    Скільки гривень коштує 1 одиниця цільової валюти.
                </small>
            </div>

            <!-- Інфо про API -->
            <div class="form-group" id="api_rate_group"
                 style="<?= $currencySource !== 'api' ? 'display:none;' : '' ?>">
                <label for="api_key_input">API-ключ НБУ</label>
                <?php
                $savedApiKey = (!in_array($currencySource, ['manual', 'api'], true)) ? $currencySource : '';
                ?>
                <input type="text" name="currency_api_key" id="api_key_input"
                       class="form-control"
                       form="currencyUpdateForm"
                       placeholder="Введіть ключ доступу до API НБУ"
                       value="<?= htmlspecialchars($savedApiKey) ?>">
                <small style="color:#64748b; font-size:.8rem; display:block; margin-top:.25rem;">
                    Ключ зберігається в БД і передається при кожному запиті до НБУ.
                </small>
            </div>

            <button type="submit" class="btn btn-primary"
                    form="currencyUpdateForm"
                    onclick="return confirm('Увага! Ціни всіх товарів будуть перераховані. Продовжити?')">
                <i class="fas fa-sync-alt"></i> Оновити курс та ціни
            </button>
        </div>
    </div>

    <!-- Секція Локалізація -->
    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fa-solid fa-clock"></i> Регіональні налаштування
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Часовий пояс (Timezone)</label>
                <select name="settings[site_timezone]" id="site_timezone" class="form-control">
                    <option value="Europe/Kiev" <?php echo get_setting('site_timezone', 'Europe/Kiev') === 'Europe/Kiev' ? 'selected' : ''; ?>>Kyiv (GMT+2 / GMT+3)</option>
                    <option value="Europe/London" <?php echo get_setting('site_timezone', 'Europe/London') === 'Europe/London' ? 'selected' : ''; ?>>London (GMT+0 / GMT+1)</option>
                    <option value="Europe/Warsaw" <?php echo get_setting('site_timezone', '') === 'Europe/Warsaw' ? 'selected' : ''; ?>>Warsaw (GMT+1 / GMT+2)</option>
                    <option value="UTC" <?php echo get_setting('site_timezone', '') === 'UTC' ? 'selected' : ''; ?>>Universal Time (UTC)</option>
                </select>
                <small class="hint">Впливає на відображення дати та часу в замовленнях.</small>
            </div>
            <div class="form-group">
                <label>Формат дати</label>
                <input type="text" name="settings[date_format]" class="form-control" value="<?php echo htmlspecialchars(get_setting('date_format', 'd.m.Y H:i')); ?>" placeholder="d.m.Y H:i">
                <small class="hint">Наприклад: <code>11.04.2024 15:30</code></small>
            </div>
        </div>
    </div>

    <!-- Секція SMTP -->
    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fa-solid fa-envelope"></i> Налаштування пошти (SMTP)
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>SMTP Хост</label>
                <input type="text" id="smtp" name="settings[smtr]" class="form-control" value="<?php echo htmlspecialchars(get_setting('smtr', '')); ?>">
            </div>
            <div class="form-group">
                <label>Порт</label>
                <input type="number" id="smtp_port" name="settings[smtp_port]" class="form-control" value="<?php echo htmlspecialchars(get_setting('smtp_port', '')); ?>">
            </div>
            <div class="form-group">
                <label>Користувач (Email)</label>
                <input type="email" id="email" name="settings[email]" class="form-control" value="<?php echo htmlspecialchars(get_setting('email', '')); ?>">
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" id="smtp_pass" name="settings[smtp_pass]" class="form-control" value="<?php echo htmlspecialchars(get_setting('smtp_pass', '')); ?>">
            </div>
        </div>
    </div>

    <!-- Секція SEO -->
    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fa-solid fa-search"></i> SEO-шаблони для товарів
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Шаблон Title</label>
                <input type="text" id="seo_title_template" name="settings[seo_title_template]" class="form-control" value="<?php echo htmlspecialchars(get_setting('seo_title_template', '')); ?>">
                <small class="hint">Доступні маски: <code>{name}</code>, <code>{price}</code>, <code>{category}</code></small>
            </div>
            <div class="form-group">
                <label>Шаблон Description</label>
                <textarea id="seo_desc_template" name="settings[seo_desc_template]" class="form-control" rows="3"><?php echo htmlspecialchars(get_setting('seo_desc_template', '')); ?></textarea>
                <small class="hint">Автоматичні шаблони для всіх товарів.</small>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fas fa-palette"></i> Зовнішній вигляд
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="active_theme">Активна тема оформлення</label>
                <?php
                $themesDir = __DIR__ . '/../../../../resources/themes';
                $themes = is_dir($themesDir) ? array_values(array_diff(scandir($themesDir), ['.', '..'])) : [];
                ?>
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

    <div class="card" style="margin-top:1rem;">
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

    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fas fa-user-shield"></i> Соціальний вхід
        </div>
        <div class="card-body">
            <h4 style="margin-top:0;">Google</h4>
            <div class="form-group">
                <label for="google_auth_enabled">Статус</label>
                <select name="settings[google_auth_enabled]" id="google_auth_enabled" class="form-control">
                    <option value="0" <?php echo get_setting('google_auth_enabled', '0') === '0' ? 'selected' : ''; ?>>Вимкнено</option>
                    <option value="1" <?php echo get_setting('google_auth_enabled', '0') === '1' ? 'selected' : ''; ?>>Увімкнено</option>
                </select>
            </div>
            <div class="form-group">
                <label for="google_client_id">Client ID</label>
                <input type="text" name="settings[google_client_id]" id="google_client_id" class="form-control" value="<?php echo htmlspecialchars(get_setting('google_client_id', '')); ?>">
            </div>
            <div class="form-group">
                <label for="google_client_secret">Client Secret</label>
                <input type="password" name="settings[google_client_secret]" id="google_client_secret" class="form-control" value="<?php echo htmlspecialchars(get_setting('google_client_secret', '')); ?>">
            </div>

            <h4 style="margin-top:1rem;">Facebook</h4>
            <div class="form-group">
                <label for="facebook_auth_enabled">Статус</label>
                <select name="settings[facebook_auth_enabled]" id="facebook_auth_enabled" class="form-control">
                    <option value="0" <?php echo get_setting('facebook_auth_enabled', '0') === '0' ? 'selected' : ''; ?>>Вимкнено</option>
                    <option value="1" <?php echo get_setting('facebook_auth_enabled', '0') === '1' ? 'selected' : ''; ?>>Увімкнено</option>
                </select>
            </div>
            <div class="form-group">
                <label for="facebook_client_id">Client ID</label>
                <input type="text" name="settings[facebook_client_id]" id="facebook_client_id" class="form-control" value="<?php echo htmlspecialchars(get_setting('facebook_client_id', '')); ?>">
            </div>
            <div class="form-group">
                <label for="facebook_client_secret">Client Secret</label>
                <input type="password" name="settings[facebook_client_secret]" id="facebook_client_secret" class="form-control" value="<?php echo htmlspecialchars(get_setting('facebook_client_secret', '')); ?>">
            </div>
        </div>
    </div>

    <div style="margin-top: 2rem; margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> Зберегти всі налаштування
        </button>
    </div>
</form>

<script>
function toggleRateSource() {
    const isApi = document.getElementById('src_api').checked;
    document.getElementById('manual_rate_group').style.display = isApi ? 'none' : '';
    document.getElementById('api_rate_group').style.display    = isApi ? '' : 'none';
    document.getElementById('manual_rate').required = !isApi;
}
toggleRateSource();
</script>
