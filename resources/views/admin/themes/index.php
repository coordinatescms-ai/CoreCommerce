<h2><?= __('themes') ?? 'Themes' ?></h2>

<div style="margin: 2rem 0;">
    <h3><?= __('available_themes') ?? 'Available Themes' ?></h3>
    
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
                        <p><strong><?= __('version') ?? 'Version' ?>:</strong> <?= htmlspecialchars($theme['version']) ?></p>
                        <p><strong><?= __('author') ?? 'Author' ?>:</strong> <?= htmlspecialchars($theme['author']) ?></p>
                    </div>
                    
                    <?php if ($theme['id'] === $active_theme): ?>
                        <div style="background: #007bff; color: white; padding: 0.75rem; border-radius: 4px; text-align: center; font-weight: bold;">
                            ✓ <?= __('active_theme') ?? 'Active Theme' ?>
                        </div>
                    <?php else: ?>
                        <a href="/theme/<?= htmlspecialchars($theme['id']) ?>" 
                           style="display: block; background: #007bff; color: white; padding: 0.75rem; border-radius: 4px; text-align: center; text-decoration: none; font-weight: bold; transition: background 0.3s;">
                            <?= __('activate') ?? 'Activate' ?>
                        </a>
                    <?php endif; ?>
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
