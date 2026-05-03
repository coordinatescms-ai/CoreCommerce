<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2><?= function_exists('__') ? (__('themes') ?? 'Themes') : 'Themes' ?></h2>
    
    <div style="background: #fff; padding: 1rem; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <form action="/admin/themes/upload" method="POST" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 1rem;">
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?? '' ?>">
            <label style="font-weight: bold; font-size: 0.9rem;"><?= __('upload_new_theme') ?? 'Upload New Theme (ZIP)' ?>:</label>
            <input type="file" name="theme_zip" accept=".zip" required style="font-size: 0.8rem;">
            <button type="submit" style="background: #007bff; color: white; padding: 0.5rem 1rem; border-radius: 4px; border: none; font-weight: bold; cursor: pointer; font-size: 0.85rem;">
                <?= __('upload') ?? 'Upload' ?>
            </button>
        </form>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; border: 1px solid #c3e6cb;">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; border: 1px solid #f5c6cb;">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['preview_theme'])): ?>
    <div style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; border: 1px solid #ffeeba; display: flex; justify-content: space-between; align-items: center;">
        <span>
            <strong><?= __('preview_mode') ?? 'Preview Mode' ?>:</strong> 
            <?= __('currently_previewing') ?? 'Currently previewing theme' ?> "<?= htmlspecialchars($_SESSION['preview_theme']) ?>".
        </span>
        <a href="/admin/themes/cancel-preview" style="background: #856404; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: bold;">
            <?= __('cancel_preview') ?? 'Cancel Preview' ?>
        </a>
    </div>
<?php endif; ?>

<div style="margin: 2rem 0;">
    <h3><?= function_exists('__') ? (__('available_themes') ?? 'Available Themes') : 'Available Themes' ?></h3>
    
    <?php if (empty($themes)): ?>
        <p><?= __('no_themes_found') ?? 'No themes found.' ?></p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
            <?php foreach ($themes as $theme): ?>
                <div style="border: 2px solid <?= $theme['id'] === $active_theme ? '#007bff' : '#ddd' ?>; padding: 1.5rem; border-radius: 8px; background: <?= $theme['id'] === $active_theme ? '#f0f7ff' : '#fff' ?>;">
                    <h4 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($theme['name']) ?></h4>
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                        <?= htmlspecialchars($theme['description']) ?>
                    </p>
                    
                    <div style="margin-bottom: 1rem; font-size: 0.85rem; color: #999;">
                        <p><strong><?= function_exists('__') ? (__('version') ?? 'Version') : 'Version' ?>:</strong> <?= htmlspecialchars($theme['version']) ?></p>
                        <p><strong><?= function_exists('__') ? (__('author') ?? 'Author') : 'Author' ?>:</strong> <?= htmlspecialchars($theme['author']) ?></p>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <?php if ($theme['id'] === $active_theme): ?>
                            <div style="flex: 1; background: #28a745; color: white; padding: 0.75rem; border-radius: 4px; text-align: center; font-weight: bold;">
                                ✓ <?= function_exists('__') ? (__('active_theme') ?? 'Active Theme') : 'Active Theme' ?>
                            </div>
                        <?php else: ?>
                            <a href="/admin/theme/switch/<?= htmlspecialchars($theme['id']) ?>" 
                               style="flex: 1; background: #007bff; color: white; padding: 0.75rem; border-radius: 4px; text-align: center; text-decoration: none; font-weight: bold; transition: background 0.3s;">
                                <?= function_exists('__') ? (__('activate') ?? 'Activate') : 'Activate' ?>
                            </a>
                            <a href="/admin/themes/preview/<?= htmlspecialchars($theme['id']) ?>" 
                               title="<?= __('preview') ?? 'Preview' ?>"
                               style="background: #17a2b8; color: white; padding: 0.75rem; border-radius: 4px; text-align: center; text-decoration: none; width: 50px;">
                                👁
                            </a>
                        <?php endif; ?>
                        
                        <a href="/admin/themes/edit/<?= htmlspecialchars($theme['id']) ?>" 
                           title="<?= __('edit') ?? 'Edit' ?>"
                           style="background: #6c757d; color: white; padding: 0.75rem; border-radius: 4px; text-align: center; text-decoration: none; width: 50px;">
                            ⚙
                        </a>

                        <?php if ($theme['id'] !== $active_theme && $theme['id'] !== 'default'): ?>
                            <form action="/admin/themes/delete/<?= htmlspecialchars($theme['id']) ?>" method="POST" onsubmit="return confirm('<?= __('confirm_delete_theme') ?? 'Are you sure you want to delete this theme?' ?>');">
                                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?? '' ?>">
                                <button type="submit" title="<?= __('delete') ?? 'Delete' ?>"
                                        style="background: #dc3545; color: white; padding: 0.75rem; border-radius: 4px; border: none; cursor: pointer; width: 50px;">
                                    🗑
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    h2 {
        margin-bottom: 1.5rem;
        color: #333;
    }
    
    h3 {
        margin-bottom: 1rem;
        color: #555;
    }
    
    h4 {
        color: #333;
    }
    
    a[href*="/theme/"] {
        transition: all 0.3s ease;
    }
    
    a[href*="/theme/"]:hover {
        background: #0056b3 !important;
        text-decoration: none;
    }
</style>
