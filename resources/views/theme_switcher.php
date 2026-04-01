<h2><?php echo function_exists('__') ? __('available_themes') : 'Available Themes'; ?></h2>

<p><?php echo function_exists('__') ? __('select_theme_to_preview') : 'Select a theme to preview:'; ?></p>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
    <?php foreach ($themes as $theme): ?>
        <div style="border: 2px solid <?php echo $theme['id'] === $active_theme ? '#2563eb' : '#ddd'; ?>; padding: 1.5rem; border-radius: 8px; background: <?php echo $theme['id'] === $active_theme ? '#f0f7ff' : '#fff'; ?>;">
            <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($theme['name']); ?></h3>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($theme['description']); ?>
            </p>
            
            <div style="margin-bottom: 1rem; font-size: 0.85rem; color: #999;">
                <p><strong><?php echo function_exists('__') ? __('version') : 'Version'; ?>:</strong> <?php echo htmlspecialchars($theme['version']); ?></p>
                <p><strong><?php echo function_exists('__') ? __('author') : 'Author'; ?>:</strong> <?php echo htmlspecialchars($theme['author']); ?></p>
            </div>
            
            <?php if ($theme['id'] === $active_theme): ?>
                <div style="background: #2563eb; color: white; padding: 0.75rem; border-radius: 4px; text-align: center; font-weight: bold;">
                    ✓ <?php echo function_exists('__') ? __('active_theme') : 'Active Theme'; ?>
                </div>
            <?php else: ?>
                <a href="/theme/<?php echo htmlspecialchars($theme['id']); ?>" 
                   style="display: block; background: #2563eb; color: white; padding: 0.75rem; border-radius: 4px; text-align: center; text-decoration: none; font-weight: bold; transition: background 0.3s;">
                    <?php echo function_exists('__') ? __('activate') : 'Activate'; ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div style="margin-top: 2rem; padding: 1rem; background: #f0f7ff; border-left: 4px solid #2563eb; border-radius: 4px;">
    <h4><?php echo function_exists('__') ? __('theme_switcher_info') : 'Theme Switcher Information'; ?></h4>
    <p><?php echo function_exists('__') ? __('theme_selection_persists') : 'Your theme selection will be saved in your browser and will persist across sessions.'; ?></p>
</div>
