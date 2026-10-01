<div class="card">
    <div class="card-header">
        <i class="fas fa-palette"></i> <?= __('settings_appearance') ?>
    </div>
    <div class="card-body">
        <form action="/admin/settings/save" method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" name="current_tab" value="appearance">

            <div class="form-group">
                <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer;">
                    <input type="hidden" name="settings[show_compare_on_frontend]" value="0">
                    <input type="checkbox" name="settings[show_compare_on_frontend]" value="1" <?= ((string) \App\Models\Setting::get('show_compare_on_frontend', '1') === '1') ? 'checked' : ''; ?>>
                    <span><?= __('settings_appearance_compare_toggle') ?></span>
                </label>
                <small style="color:#64748b; display:block; margin-top:0.5rem;"><?= __('settings_appearance_compare_hint') ?></small>
            </div>

            <div class="form-group">
                <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer;">
                    <input type="hidden" name="settings[show_language_switcher]" value="0">
                    <input type="checkbox" name="settings[show_language_switcher]" value="1" <?= ((string) \App\Models\Setting::get('show_language_switcher', '1') === '1') ? 'checked' : ''; ?>>
                    <span><?= __('settings_appearance_language_toggle') ?></span>
                </label>
                <small style="color:#64748b; display:block; margin-top:0.5rem;"><?= __('settings_appearance_language_hint') ?></small>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= __('crm_save') ?>
                </button>
            </div>
        </form>
    </div>
</div>
