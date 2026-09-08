<div class="mb-4 text-end">
    <form action="/admin/settings/methods/add" method="POST" style="display:inline;">
        <input type="hidden" name="csrf" value="<?= \App\Core\Http\Csrf::token(); ?>">
        <input type="hidden" name="type" value="shipping">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus"></i> <?= __("admin_settings_add_delivery_method") ?>
        </button>
    </form>
</div>

<form action="/admin/settings/save" method="POST">
    <input type="hidden" name="csrf" value="<?= \App\Core\Http\Csrf::token(); ?>">
    <input type="hidden" name="current_tab" value="shipping">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><?= __("admin_settings_delivery_title") ?></h2>
    </div>

    <?php foreach ($methods as $method): ?>
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 text-primary"><?= htmlspecialchars($method['name']) ?></h5>

                <button type="submit" form="delete-form-<?= $method['id'] ?>"
                    onclick="return confirm(window.LANG.confirm_delete_method)"
                    class="btn btn-sm btn-outline-danger"><?= __("admin_settings_delete_method") ?></button>
                <div class="form-check form-switch">
                    <input type="hidden" name="methods[<?= $method['id'] ?>][is_active]" value="0">
                    <input class="form-check-input" type="checkbox" name="methods[<?= $method['id'] ?>][is_active]" value="1" <?= $method['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label"><?= __("admin_settings_method_enabled") ?></label>
                </div>
                <!-- Поле для сортування -->
            </div>
            
            <div class="card-body">
                <div class="row">
                    <!-- Поле для зміни НАЗВИ -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold"><?= __('settings_payment_name') ?></label>
                        <input type="text" name="methods[<?= $method['id'] ?>][name]" 
                               value="<?= htmlspecialchars($method['name']) ?>" class="form-control">
                    </div>

                    <!-- Поле для зміни ОПИСУ -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold"><?= __("admin_settings_method_description") ?></label>
                        <input type="text" name="methods[<?= $method['id'] ?>][description]" 
                               value="<?= htmlspecialchars($method['description'] ?? '') ?>" class="form-control" placeholder="<?= __("admin_settings_method_description_placeholder") ?>">
                    </div>
                </div>

                <?php 
                $extra = json_decode($method['settings'] ?? '', true) ?: []; 
                ?>
                
                <!-- Специфічні налаштування для JSON полів -->
                <?php if ($method['code'] === 'nova_poshta'): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label"><?= __("admin_settings_nova_poshta_api_key") ?> <span class="text-danger">*</span></label>
                            <input type="text" name="methods[<?= $method['id'] ?>][settings][api_key]" 
                                   value="<?= htmlspecialchars($extra['api_key'] ?? '') ?>" class="form-control"
                                   placeholder="<?= __("admin_settings_nova_poshta_api_placeholder") ?>">
                            <small class="text-muted"><?= __("admin_settings_nova_poshta_hint") ?></small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= __('settings_delivery_cost') ?></label>
                            <input type="number" name="methods[<?= $method['id'] ?>][settings][cost]" 
                                   value="<?= htmlspecialchars($extra['cost'] ?? '') ?>" class="form-control">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($method['code'] === 'self_pickup'): ?>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label"><?= __("admin_settings_pickup_address") ?></label>
                        <input type="text" name="methods[<?= $method['id'] ?>][settings][address]" 
                               value="<?= htmlspecialchars($extra['address'] ?? '') ?>" class="form-control">
                    </div>
                <?php endif; ?>

                <?php do_action('admin.shipping_method_settings', $method, $extra); ?>
                <br />
                <div class="mb-3" style="max-width: 150px;">
                    <label class="form-label fw-bold"><?= __("admin_settings_sort_order") ?></label>
                    <input type="number" name="methods[<?= $method['id'] ?>][sort_order]" 
                        value="<?= $method['sort_order'] ?>" class="form-control" min="0">
                    <small class="text-muted"><?= __("admin_settings_sort_hint") ?></small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
        <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> <?= __("admin_settings_save_changes") ?>
        </button>
    </div>
</form>

<?php foreach ($methods as $method): ?>
<form id="delete-form-<?= $method['id'] ?>" action="/admin/settings/methods/delete/<?= $method['id'] ?>" method="POST" style="display:none;">
    <input type="hidden" name="csrf" value="<?= \App\Core\Http\Csrf::token(); ?>">
    <input type="hidden" name="type" value="shipping">
</form>
<?php endforeach; ?>
