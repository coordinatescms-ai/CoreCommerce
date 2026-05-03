<h2><?= function_exists('__') ? (__('edit_theme') ?? 'Edit Theme') : 'Edit Theme' ?>: <?= htmlspecialchars($theme['name']) ?></h2>

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

<div style="background: #fff; padding: 2rem; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 2rem;">
    <form action="/admin/themes/update/<?= htmlspecialchars($theme['id']) ?>" method="POST">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?? '' ?>">
        
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;"><?= __('theme_name') ?? 'Theme Name' ?></label>
            <input type="text" name="name" value="<?= htmlspecialchars($theme['name']) ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;"><?= __('description') ?? 'Description' ?></label>
            <textarea name="description" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; height: 100px;"><?= htmlspecialchars($theme['description']) ?></textarea>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;"><?= __('parent_theme') ?? 'Parent Theme' ?></label>
            <select name="parent" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px;">
                <option value=""><?= __('no_parent') ?? 'No Parent (Standalone)' ?></option>
                <?php 
                $all_themes = \App\Core\Theme\ThemeManager::getAvailableThemes();
                foreach ($all_themes as $t): 
                    if ($t['id'] === $theme['id']) continue;
                ?>
                    <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($theme['parent'] ?? '') === $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['id']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color: #666; display: block; mt: 0.25rem;"><?= __('parent_theme_hint') ?? 'Child themes will inherit layout and styles from the parent theme if they are missing locally.' ?></small>
        </div>

        <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #eee;">

        <h3><?= __('theme_colors') ?? 'Theme Colors' ?></h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <?php 
            $colors = $config['colors'] ?? [
                'primary' => '#2563eb',
                'secondary' => '#64748b',
                'success' => '#10b981',
                'danger' => '#ef4444',
                'warning' => '#f59e0b',
                'info' => '#3b82f6',
                'light' => '#f8fafc',
                'dark' => '#1e293b'
            ];
            foreach ($colors as $key => $value): 
            ?>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: capitalize;"><?= str_replace('_', ' ', $key) ?></label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="color" name="colors[<?= $key ?>]" value="<?= htmlspecialchars($value) ?>" style="height: 40px; width: 60px; padding: 2px; border: 1px solid #ccc; border-radius: 4px;">
                        <input type="text" value="<?= htmlspecialchars($value) ?>" readonly style="flex: 1; padding: 0.5rem; border: 1px solid #eee; background: #f9f9f9; border-radius: 4px; font-family: monospace;">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #eee;">

        <h3><?= __('theme_fonts') ?? 'Theme Fonts' ?></h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <?php 
            $fonts = $config['fonts'] ?? [
                'primary' => 'Poppins',
                'secondary' => 'Segoe UI'
            ];
            foreach ($fonts as $key => $value): 
            ?>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: capitalize;"><?= str_replace('_', ' ', $key) ?> Font</label>
                    <input type="text" name="fonts[<?= $key ?>]" value="<?= htmlspecialchars($value) ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px;" placeholder="e.g. Arial, sans-serif">
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" style="background: #28a745; color: white; padding: 0.75rem 2rem; border-radius: 4px; border: none; font-weight: bold; cursor: pointer;">
                <?= __('save_changes') ?? 'Save Changes' ?>
            </button>
            <a href="/admin/themes" style="background: #6c757d; color: white; padding: 0.75rem 2rem; border-radius: 4px; text-decoration: none; font-weight: bold; text-align: center;">
                <?= __('cancel') ?? 'Cancel' ?>
            </a>
        </div>
    </form>
</div>
