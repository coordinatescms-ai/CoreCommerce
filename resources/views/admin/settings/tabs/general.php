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
            <i class="fas fa-info-circle"></i> <?= __("admin_settings_general_info") ?></div>
        <div class="card-body">
            <div class="form-group">
                <label for="site_name"><?= __('settings_shop_name') ?></label>
                <input type="text" name="settings[site_name]" id="site_name" class="form-control" value="<?php echo htmlspecialchars(get_setting('site_name', '')); ?>">
            </div>
            <div class="form-group">
                <label for="site_url"><?= __("admin_settings_site_url") ?></label>
                <input type="text" name="settings[site_url]" id="site_url" class="form-control" value="<?php echo htmlspecialchars(get_setting('site_url', '')); ?>" placeholder="<?= __("admin_settings_site_url_placeholder") ?>">
                <small class="text-muted"><?= __("admin_settings_site_url_hint") ?></small>
            </div>
            <div class="form-group">
                <label for="site_description"><?= __('settings_shop_desc') ?></label>
                <textarea name="settings[site_description]" id="site_description" class="form-control" rows="3"><?php echo htmlspecialchars(get_setting('site_description', '')); ?></textarea>
            </div>
            <div class="form-group">
                <label for="store_status"><?= __("admin_settings_store_status") ?></label>
                <select name="settings[store_status]" id="store_status" class="form-control">
                    <option value="open" <?php echo get_setting('store_status', 'open') === 'open' ? 'selected' : ''; ?>><?= __("admin_settings_store_open") ?></option>
                    <option value="closed" <?php echo get_setting('store_status', 'open') === 'closed' ? 'selected' : ''; ?>><?= __("admin_settings_store_closed") ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="maintenance_message"><?= __("admin_settings_maintenance_message") ?></label>
                <textarea name="settings[maintenance_message]" id="maintenance_message" class="form-control" rows="2"><?php echo htmlspecialchars(get_setting('maintenance_message', '')); ?></textarea>
            </div>
            <div class="form-group">
                <label for="phone_mask"><?= __('settings_phone_mask') ?></label>
                <input type="text" name="settings[phone_mask]" id="phone_mask" class="form-control" value="<?php echo htmlspecialchars(get_setting('phone_mask', '+38 (###) ###-##-##')); ?>">
                <small style="display:block; margin-top: 0.35rem; color: #6b7280;"><?= __("admin_settings_phone_mask_format") ?></small>
            </div>
            <div class="form-group">
                <label for="logotype_file"><?= __('settings_logo') ?></label>
                <input type="file" name="logotype_file" id="logotype_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <small style="display:block; margin-top: 0.35rem; color: #6b7280;"><?= __("admin_settings_allowed_formats") ?></small>
                <?php $activeLogotype = trim((string) get_setting('active_logotype', '')); ?>
                <?php if ($activeLogotype !== ''): ?>
                    <div style="margin-top: 0.5rem;">
                        <img src="<?php echo htmlspecialchars($activeLogotype); ?>" alt="<?= __("admin_settings_current_logo_alt") ?>" style="max-height: 60px; width: auto; border: 1px solid #ddd; border-radius: 6px; padding: 4px; background: #fff;">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-globe"></i> <?= __("admin_settings_localization") ?></div>
        <div class="card-body">
            <div class="form-group">
                <label for="default_language"><?= __('settings_default_lang') ?></label>
                <select name="settings[default_language]" id="default_language" class="form-control">
                    <?php foreach (get_language_names() as $langCode => $langName): ?>
                        <option value="<?= htmlspecialchars($langCode) ?>" <?php echo get_setting('default_language', 'ua') === $langCode ? 'selected' : ''; ?>><?= htmlspecialchars($langName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Блок перерахунку валюти (ВІЗУАЛЬНО ТУТ, АЛЕ ПРИВ'ЯЗАНИЙ ДО currencyUpdateForm) -->
    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fas fa-coins"></i> <?= __("admin_settings_currency_title") ?><?php if ($activeCurrency): ?>
                <span style="margin-left:.75rem; background:#dcfce7; color:#166534;
                             font-size:.75rem; font-weight:700; padding:2px 10px;
                             border-radius:20px; vertical-align:middle;"><?= __("admin_settings_currency_active") ?>: <?= htmlspecialchars($activeCurrency["code"]) ?> (<?= htmlspecialchars($activeCurrency["symbol"]) ?>) В· <?= __("admin_settings_rate_label") ?> <?= number_format((float)$activeCurrency["rate"], 4) ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="target_currency"><?= __("admin_settings_currency_switch") ?></label>
                <select name="target_currency" id="target_currency" class="form-control" form="currencyUpdateForm">
                    <?php foreach ($allCurrencies as $cur): ?>
                        <option value="<?= htmlspecialchars($cur['code']) ?>"
                            <?= (int)$cur['is_active'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cur['code']) ?>
                            (<?= htmlspecialchars($cur['symbol']) ?>)
                            <?= (int)$cur['is_active'] ? ' — ' . __('current') : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#64748b; font-size:.8rem; margin-top:.25rem; display:block;"><?= __("admin_settings_currency_recalculate_hint") ?></small>
            </div>

            <div class="form-group">
                <label style="display:flex; align-items:center; gap:.75rem; cursor:pointer; font-weight:600;">                     <?= __("admin_settings_rate_source") ?><label style="display:flex; align-items:center; gap:.3rem; font-weight:400; cursor:pointer;">
                        <input type="radio" name="currency_source" value="manual"
                               id="src_manual"
                               form="currencyUpdateForm"
                               <?= $currencySource !== 'nbu_api' ? 'checked' : '' ?>
                               onchange="toggleRateSource()">                         <?= __("admin_settings_manual_rate") ?>                     </label>
                    <label style="display:flex; align-items:center; gap:.3rem; font-weight:400; cursor:pointer;">
                        <input type="radio" name="currency_source" value="api"
                               id="src_api"
                               form="currencyUpdateForm"
                               <?= $currencySource === 'nbu_api' ? 'checked' : '' ?>
                               onchange="toggleRateSource()">
                        <?= __('currency_nbu_auto') ?>
                    </label>
                </label>
            </div>

            <!-- Поле для ручного курсу -->
            <div class="form-group" id="manual_rate_group"
                 style="<?= $currencySource === 'nbu_api' ? 'display:none;' : '' ?>">
                <label for="manual_rate"><?= __("admin_settings_manual_rate_label") ?></label>
                <input type="number" name="manual_rate" id="manual_rate"
                       class="form-control"
                       form="currencyUpdateForm"
                       min="0.0001" step="0.0001"
                       placeholder="<?= __("admin_settings_manual_rate_placeholder") ?>"
                       value="">
                <small style="color:#64748b; font-size:.8rem; display:block; margin-top:.25rem;"><?= __("admin_settings_manual_rate_hint") ?></small>
            </div>

            <!-- Інфо про НБУ API -->
            <div class="form-group" id="api_rate_group"
                 style="<?= $currencySource !== 'nbu_api' ? 'display:none;' : '' ?>">
                <div class="alert alert-info" style="font-size:.875rem; margin-bottom:0;">
                    <i class="fas fa-info-circle"></i> <strong><?= __("admin_settings_nbu_api") ?></strong> вЂ” <?= __("admin_settings_nbu_api_desc") ?> <?= __("admin_settings_nbu_api_source") ?> <code>bank.gov.ua</code>.
                </div>
            </div>

            <button type="submit" class="btn btn-primary"
                    form="currencyUpdateForm"
                    onclick="return confirm(<?= json_encode(__("admin_settings_currency_confirm")) ?>)">
                <i class="fas fa-sync-alt"></i> <?= __("admin_settings_currency_update") ?>
            </button>
        </div>
    </div>

    <!-- Секція Локалізація -->
    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fa-solid fa-clock"></i> <?= __("admin_settings_regional") ?></div>
        <div class="card-body">
            <div class="form-group">
                <label><?= __('settings_timezone') ?></label>
                <select name="settings[site_timezone]" id="site_timezone" class="form-control">
                    <option value="Europe/Kiev" <?php echo get_setting('site_timezone', 'Europe/Kiev') === 'Europe/Kiev' ? 'selected' : ''; ?>><?= __("admin_settings_timezone_kyiv") ?></option>
                    <option value="Europe/London" <?php echo get_setting('site_timezone', 'Europe/London') === 'Europe/London' ? 'selected' : ''; ?>><?= __("admin_settings_timezone_london") ?></option>
                    <option value="Europe/Warsaw" <?php echo get_setting('site_timezone', '') === 'Europe/Warsaw' ? 'selected' : ''; ?>><?= __("admin_settings_timezone_warsaw") ?></option>
                    <option value="UTC" <?php echo get_setting('site_timezone', '') === 'UTC' ? 'selected' : ''; ?>><?= __("admin_settings_timezone_utc") ?></option>
                </select>
                <small class="hint"><?= __("settings_timezone_hint") ?></small>
            </div>
            <div class="form-group">
                <label><?= __('settings_date_format') ?></label>
                <input type="text" name="settings[date_format]" class="form-control" value="<?php echo htmlspecialchars(get_setting('date_format', 'd.m.Y H:i')); ?>" placeholder="d.m.Y H:i">
                <small class="hint"><?= __('settings_phone_mask_eg') ?>: <code>11.04.2024 15:30</code></small>
            </div>
        </div>
    </div>

    <!-- Секція SMTP -->
    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fa-solid fa-envelope"></i> <?= __('smtp_settings_title') ?>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 1.25rem;">
                <div class="form-group">
                    <label for="smtp_host"><?= __('smtp_host') ?></label>
                    <input type="text" id="smtp_host" name="settings[smtp_host]" class="form-control"
                           placeholder="smtp.gmail.com"
                           value="<?php echo htmlspecialchars(get_setting('smtp_host', '')); ?>">
                </div>
                <div class="form-group">
                    <label for="smtp_port"><?= __('smtp_port') ?></label>
                    <input type="number" id="smtp_port" name="settings[smtp_port]" class="form-control"
                           placeholder="587"
                           value="<?php echo htmlspecialchars(get_setting('smtp_port', '587')); ?>">
                </div>
                <div class="form-group">
                    <label for="smtp_username"><?= __('smtp_user') ?></label>
                    <input type="text" id="smtp_username" name="settings[smtp_username]" class="form-control"
                           placeholder="your@email.com"
                           value="<?php echo htmlspecialchars(get_setting('smtp_username', '')); ?>">
                </div>
                <div class="form-group">
                    <label for="smtp_pass"><?= __('smtp_pass') ?></label>
                    <input type="password" id="smtp_pass" name="settings[smtp_pass]" class="form-control"
                           placeholder="••••••••"
                           value="<?php echo htmlspecialchars(get_setting('smtp_pass', '')); ?>">
                </div>
                <div class="form-group">
                    <label for="smtp_encryption"><?= __('smtp_encryption') ?></label>
                    <select id="smtp_encryption" name="settings[smtp_encryption]" class="form-control">
                        <?php
                        $currentEnc = get_setting('smtp_encryption', 'tls');
                        foreach (['tls' => __('smtp_enc_tls'), 'ssl' => __('smtp_enc_ssl'), '' => __('smtp_enc_none')] as $val => $label):
                        ?>
                            <option value="<?php echo $val; ?>" <?php echo $currentEnc === $val ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1; border-top:1px solid #e2e8f0; padding-top:.75rem; margin-top:.25rem;">
                    <p style="font-size:.85rem; color:#64748b; margin:0 0 .75rem;">
                        <i class="fas fa-info-circle"></i> <?= __("smtp_from_hint") ?></p>
                </div>
                <div class="form-group">
                    <label for="smtp_from_email"><?= __('smtp_from_email') ?></label>
                    <input type="email" id="smtp_from_email" name="settings[smtp_from_email]" class="form-control"
                           placeholder="no-reply@mysite.com"
                           value="<?php echo htmlspecialchars(get_setting('smtp_from_email', '')); ?>">
                </div>
                <div class="form-group">
                    <label for="smtp_from_name"><?= __('smtp_from_name') ?></label>
                    <input type="text" id="smtp_from_name" name="settings[smtp_from_name]" class="form-control"
                           placeholder="Мій магазин"
                           value="<?php echo htmlspecialchars(get_setting('smtp_from_name', '')); ?>">
                </div>
            </div>
            <p style="font-size:.8rem; color:#94a3b8; margin:.5rem 0 0;">
                <i class="fas fa-shield-alt"></i> <?= __('admin_settings_smtp_goto_system') ?>
                <a href="/admin/system?tab=mail"><?= __('smtp_goto_system') ?></a>.
            </p>
        </div>
    </div>

    <!-- Секція SEO -->
    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fa-solid fa-magnifying-glass"></i> <?= __('seo_templates') ?>
        </div>
        <div class="card-body">
            <p style="font-size:.85rem; color:#64748b; margin:0 0 1rem;">
                <?= __('seo_available_masks') ?>:
                <code>{name}</code>, <code>{price}</code>, <code>{category}</code>,
                <code>{shop_name}</code>, <code>{slug}</code>
            </p>

            <fieldset style="border:1px solid #e2e8f0; border-radius:6px; padding:1rem; margin-bottom:1rem;">
                <legend style="font-weight:600; font-size:.9rem; padding:0 .5rem;">
                    <i class="fas fa-box"></i> <?= __('admin_products') ?>
                </legend>
                <div class="form-group">
                    <label><?= __('seo_title_template') ?></label>
                    <input type="text" name="settings[seo_title_template]" class="form-control"
                           placeholder="{name} — <?= __('buy') ?> в {shop_name}"
                           value="<?= htmlspecialchars(get_setting('seo_title_template', '')) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label><?= __('seo_desc_template') ?></label>
                    <textarea name="settings[seo_desc_template]" class="form-control" rows="2"
                              placeholder="{name}. <?= __('seo_price_hint') ?> {price} грн."><?= htmlspecialchars(get_setting('seo_desc_template', '')) ?></textarea>
                    <small class="hint"><?= __('seo_auto_hint') ?></small>
                </div>
            </fieldset>

            <fieldset style="border:1px solid #e2e8f0; border-radius:6px; padding:1rem; margin-bottom:1rem;">
                <legend style="font-weight:600; font-size:.9rem; padding:0 .5rem;">
                    <i class="fas fa-list"></i> <?= __('admin_categories') ?>
                </legend>
                <div class="form-group">
                    <label><?= __('seo_title_template') ?></label>
                    <input type="text" name="settings[seo_category_title_template]" class="form-control"
                           placeholder="{name} — {shop_name}"
                           value="<?= htmlspecialchars(get_setting('seo_category_title_template', '')) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label><?= __('seo_desc_template') ?></label>
                    <textarea name="settings[seo_category_desc_template]" class="form-control" rows="2"
                              placeholder="{name} — <?= __('seo_catalog_hint') ?>"><?= htmlspecialchars(get_setting('seo_category_desc_template', '')) ?></textarea>
                </div>
            </fieldset>

            <fieldset style="border:1px solid #e2e8f0; border-radius:6px; padding:1rem; margin-bottom:1rem;">
                <legend style="font-weight:600; font-size:.9rem; padding:0 .5rem;">
                    <i class="fas fa-file-lines"></i> <?= __('admin_content') ?>
                </legend>
                <div class="form-group">
                    <label><?= __('seo_title_template') ?></label>
                    <input type="text" name="settings[seo_page_title_template]" class="form-control"
                           placeholder="{name} — {shop_name}"
                           value="<?= htmlspecialchars(get_setting('seo_page_title_template', '')) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label><?= __('seo_desc_template') ?></label>
                    <textarea name="settings[seo_page_desc_template]" class="form-control" rows="2"><?= htmlspecialchars(get_setting('seo_page_desc_template', '')) ?></textarea>
                </div>
            </fieldset>

            <fieldset style="border:1px solid #e2e8f0; border-radius:6px; padding:1rem;">
                <legend style="font-weight:600; font-size:.9rem; padding:0 .5rem;">
                    <i class="fas fa-home"></i> <?= __('seo_home_page') ?>
                </legend>
                <div class="form-group">
                    <label><?= __('seo_title_template') ?></label>
                    <input type="text" name="settings[seo_home_title]" class="form-control"
                           value="<?= htmlspecialchars(get_setting('seo_home_title', '')) ?>">
                </div>
                <div class="form-group">
                    <label><?= __('seo_desc_template') ?></label>
                    <textarea name="settings[seo_home_description]" class="form-control" rows="2"><?= htmlspecialchars(get_setting('seo_home_description', '')) ?></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label><?= __('meta_keywords') ?></label>
                    <input type="text" name="settings[seo_home_keywords]" class="form-control"
                           value="<?= htmlspecialchars(get_setting('seo_home_keywords', '')) ?>">
                </div>
            </fieldset>
        </div>
    </div>

    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fas fa-palette"></i> <?= __("admin_settings_appearance") ?></div>
        <div class="card-body">
            <div class="form-group">
                <label for="active_theme"><?= __("admin_settings_active_theme") ?></label>
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
            <i class="fas fa-address-book"></i> <?= __("admin_settings_contact_details") ?></div>
        <div class="card-body">
            <div class="form-group">
                <label for="contact_email"><?= __('settings_contact_email') ?></label>
                <input type="email" name="settings[contact_email]" id="contact_email" class="form-control" value="<?php echo htmlspecialchars(get_setting('contact_email', '')); ?>">
            </div>
            <div class="form-group">
                <label for="contact_phone"><?= __('settings_contact_phone') ?></label>
                <input type="text" name="settings[contact_phone]" id="contact_phone" class="form-control" value="<?php echo htmlspecialchars(get_setting('contact_phone', '')); ?>">
            </div>
            <div class="form-group">
                <label for="contact_address"><?= __('settings_contact_address') ?></label>
                <input type="text" name="settings[contact_address]" id="contact_address" class="form-control"
                       maxlength="250"
                       placeholder="<?= __('admin_settings_contact_address_placeholder') ?>"
                       value="<?php echo htmlspecialchars(get_setting('contact_address', '')); ?>">
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <i class="fas fa-user-shield"></i> <?= __("admin_settings_social_login") ?></div>
        <div class="card-body">
            <h4 style="margin-top:0;">Google</h4>
            <div class="form-group">
                <label for="google_auth_enabled"><?= __("admin_settings_status") ?></label>
                <select name="settings[google_auth_enabled]" id="google_auth_enabled" class="form-control">
                    <option value="0" <?php echo get_setting('google_auth_enabled', '0') === '0' ? 'selected' : ''; ?>><?= __("disabled") ?></option>
                    <option value="1" <?php echo get_setting('google_auth_enabled', '0') === '1' ? 'selected' : ''; ?>><?= __("enabled") ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="google_client_id"><?= __("admin_settings_client_id") ?></label>
                <input type="text" name="settings[google_client_id]" id="google_client_id" class="form-control" value="<?php echo htmlspecialchars(get_setting('google_client_id', '')); ?>">
            </div>
            <div class="form-group">
                <label for="google_client_secret"><?= __("admin_settings_client_secret") ?></label>
                <input type="password" name="settings[google_client_secret]" id="google_client_secret" class="form-control" value="<?php echo htmlspecialchars(get_setting('google_client_secret', '')); ?>">
            </div>
            <div class="form-group">
                <label for="google_redirect_url"><?= __("admin_settings_callback_url") ?></label>
                <input type="text" name="settings[google_redirect_url]" id="google_redirect_url" class="form-control" value="<?php echo htmlspecialchars(get_setting('google_redirect_url', '')); ?>" placeholder="https://yourdomain.com/auth/google/callback">
                <small class="text-muted"><?= __("admin_settings_site_url_hint") ?></small>
            </div>

            <h4 style="margin-top:1rem;">Facebook</h4>
            <div class="form-group">
                <label for="facebook_auth_enabled"><?= __("admin_settings_status") ?></label>
                <select name="settings[facebook_auth_enabled]" id="facebook_auth_enabled" class="form-control">
                    <option value="0" <?php echo get_setting('facebook_auth_enabled', '0') === '0' ? 'selected' : ''; ?>><?= __("disabled") ?></option>
                    <option value="1" <?php echo get_setting('facebook_auth_enabled', '0') === '1' ? 'selected' : ''; ?>><?= __("enabled") ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="facebook_client_id"><?= __("admin_settings_client_id") ?></label>
                <input type="text" name="settings[facebook_client_id]" id="facebook_client_id" class="form-control" value="<?php echo htmlspecialchars(get_setting('facebook_client_id', '')); ?>">
            </div>
            <div class="form-group">
                <label for="facebook_client_secret"><?= __("admin_settings_client_secret") ?></label>
                <input type="password" name="settings[facebook_client_secret]" id="facebook_client_secret" class="form-control" value="<?php echo htmlspecialchars(get_setting('facebook_client_secret', '')); ?>">
            </div>
            <div class="form-group">
                <label for="facebook_redirect_url"><?= __("admin_settings_callback_url") ?></label>
                <input type="text" name="settings[facebook_redirect_url]" id="facebook_redirect_url" class="form-control" value="<?php echo htmlspecialchars(get_setting('facebook_redirect_url', '')); ?>" placeholder="https://yourdomain.com/auth/facebook/callback">
                <small class="text-muted"><?= __("admin_settings_site_url_hint") ?></small>
            </div>
        </div>
    </div>

    <div style="margin-top: 2rem; margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> <?= __("save_settings") ?>
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
