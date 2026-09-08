<div class="page-header">
    <h1 class="page-title"><?= __('category_edit') ?>: <?php echo htmlspecialchars($category['name']); ?></h1>
    <a href="/admin/categories" class="btn btn-outline" style="border: 1px solid #ddd; color: #64748b;">
        <i class="fas fa-arrow-left"></i> <?= __('back_to_list') ?>
    </a>
</div>

<form action="/admin/categories/update/<?php echo $category['id']; ?>" method="POST">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
    
    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> <?= __('basic_info') ?>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="name"><?= __('category_name') ?></label>
                <input type="text" name="name" id="name" class="form-control" required value="<?php echo htmlspecialchars($category['name']); ?>">
            </div>
            <div class="form-group">
                <label for="slug"><?= __('create_category_slug') ?></label>
                <input type="text" name="slug" id="slug" class="form-control" required value="<?php echo htmlspecialchars($category['slug']); ?>">
            </div>
            <div class="form-group">
                <label for="parent_id"><?= __('category_parent') ?></label>
                <select name="parent_id" id="parent_id" class="form-control">
                    <option value=""><?= __('category_no_parent_option') ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat['id'] == $category['id']) continue; // Не можна бути батьком самого себе ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $category['parent_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="description"><?= __('category_description') ?></label>
                <textarea name="description" id="description" class="form-control" rows="4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-search"></i> <?= __('seo_settings') ?>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="meta_title"><?= __('meta_title') ?></label>
                <input type="text" name="meta_title" id="meta_title" class="form-control" value="<?php echo htmlspecialchars($category['meta_title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="meta_description"><?= __('meta_description') ?></label>
                <textarea name="meta_description" id="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($category['meta_description'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> <?= __('save_changes') ?>
        </button>
    </div>
</form>
