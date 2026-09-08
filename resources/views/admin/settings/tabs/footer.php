<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= __('settings_footer') ?></h3>
    </div>
    <div class="card-body">
        <form action="/admin/settings/save" method="POST">
            <input type="hidden" name="csrf" value="<?= \App\Core\Http\Csrf::token() ?>">
            <input type="hidden" name="current_tab" value="footer">

            <!-- Footer Text Section -->
            <div class="form-group mb-4">
                <label class="form-label fw-bold"><?= __('footer_text') ?></label>
                <textarea name="settings[footer_about_text]" class="form-control" rows="4" placeholder="<?= __('footer_text_help') ?>"><?= htmlspecialchars(\App\Models\Setting::get('footer_about_text', '')) ?></textarea>
                <div class="form-text text-muted"><?= __('footer_text_help') ?></div>
            </div>

            <hr class="my-4">

            <!-- Social Networks Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><?= __('social_networks') ?></h4>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 180px;"><?= __('social_name') ?></th>
                            <th><?= __('social_url') ?></th>
                            <th style="width: 100px;" class="text-center"><?= __('social_is_active') ?></th>
                            <th style="width: 100px;" class="text-center"><?= __('social_sort') ?></th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($socialLinks as $link): ?>
                            <tr>
                                <td>
                                    <i class="fab fa-<?= htmlspecialchars($link['slug']) ?> fa-lg text-primary"></i>
                                </td>
                                <td>
                                    <input type="text" name="social[<?= $link['id'] ?>][name]" value="<?= htmlspecialchars($link['name']) ?>" class="form-control form-control-sm">
                                </td>
                                <td>
                                    <input type="url" name="social[<?= $link['id'] ?>][url]" value="<?= htmlspecialchars($link['url']) ?>" class="form-control form-control-sm" placeholder="<?= __('social_url_placeholder') ?>">
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input type="hidden" name="social[<?= $link['id'] ?>][is_active]" value="0">
                                        <input type="checkbox" name="social[<?= $link['id'] ?>][is_active]" value="1" class="form-check-input" <?= $link['is_active'] ? 'checked' : '' ?>>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" name="social[<?= $link['id'] ?>][sort_order]" value="<?= (int)$link['sort_order'] ?>" class="form-control form-control-sm text-center">
                                </td>
                                <td>
                                    <!-- Slug is hidden to preserve it for icons -->
                                    <input type="hidden" name="social[<?= $link['id'] ?>][slug]" value="<?= htmlspecialchars($link['slug']) ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= __('crm_save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .form-switch .form-check-input {
        width: 2.5em;
        height: 1.25em;
        cursor: pointer;
    }
    .table th {
        font-size: 0.85rem;
        text-transform: uppercase;
        color: #64748b;
    }
</style>
