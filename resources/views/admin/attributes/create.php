<div class="page-header">
    <h1 class="page-title"><?= __('attribute_new') ?></h1>
    <a href="/admin/attributes" class="btn btn-outline" style="border: 1px solid #ddd; color: #64748b;">
        <i class="fas fa-arrow-left"></i> <?= __('back_to_list') ?>
    </a>
</div>

<form action="/admin/attributes/store" method="POST">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> <?= __('attribute_basic_info') ?>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="name"><?= __('name') ?></label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="slug"><?= __('slug') ?></label>
                    <input type="text" name="slug" id="slug" class="form-control" placeholder="<?= __('attribute_slug_placeholder') ?>">
                </div>
                <div class="form-group">
                    <label for="type"><?= __('type') ?></label>
                    <select name="type" id="type" class="form-control">
                        <?php foreach (($attributeTypes ?? []) as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="sort_order"><?= __('sort_order') ?></label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                </div>
                <div class="form-group" style="display: flex; align-items: end;">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin:0;">
                        <input type="checkbox" name="is_filterable" value="1" checked> <?= __('attribute_filterable') ?>
                    </label>
                </div>
                <div class="form-group" style="display: flex; align-items: end;">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin:0;">
                        <input type="checkbox" name="is_visible" value="1" checked> <?= __('attribute_visible') ?>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="description"><?= __('description') ?></label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group" id="select-options-wrap" style="display:none;">
                <label for="options_text"><?= __('product_option_list') ?></label>
                <textarea name="options_text" id="options_text" class="form-control" rows="6" placeholder="<?= __('product_option_list_placeholder') ?>"></textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-link"></i> <?= __('attribute_category_link') ?>
        </div>
        <div class="card-body" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem 1.5rem;">
            <?php foreach (($categories ?? []) as $category): ?>
                <label style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" name="category_ids[]" value="<?php echo (int)$category['id']; ?>">
                    <span><?php echo str_repeat('— ', (int)($category['level'] ?? 0)) . htmlspecialchars($category['name']); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> <?= __('create_attribute') ?>
        </button>
    </div>
</form>

<script>
    (function () {
        const typeSelect = document.getElementById('type');
        const optionsWrap = document.getElementById('select-options-wrap');

        function refreshTypeState() {
            optionsWrap.style.display = typeSelect.value === 'select' ? 'block' : 'none';
        }

        typeSelect.addEventListener('change', refreshTypeState);
        refreshTypeState();
    })();
</script>
