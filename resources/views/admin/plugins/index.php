<?php
/**
 * @var array  $plugins
 * @var array|null $flash
 * @var int    $maxUploadSizeMb
 */
?>

<style>
.plug-grid   { display:grid; gap:1rem; }
.plug-card   { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1.1rem 1.25rem;
               display:grid; grid-template-columns:1fr auto; gap:.75rem; align-items:start; }
.plug-card.active   { border-left:4px solid #10b981; }
.plug-card.inactive { border-left:4px solid #e2e8f0; opacity:.85; }
.plug-card.missing  { border-left:4px solid #ef4444; background:#fff5f5; }
.plug-name   { font-weight:700; font-size:1rem; color:#0f172a; margin-bottom:.2rem; }
.plug-slug   { font-size:.78rem; color:#94a3b8; margin-bottom:.4rem; font-family:monospace; }
.plug-desc   { font-size:.875rem; color:#475569; margin-bottom:.5rem; line-height:1.5; }
.plug-meta   { display:flex; gap:1rem; font-size:.78rem; color:#64748b; flex-wrap:wrap; }
.plug-meta span { display:flex; align-items:center; gap:.25rem; }
.plug-status { display:inline-flex; align-items:center; gap:.3rem;
               padding:.22rem .65rem; border-radius:20px; font-size:.75rem; font-weight:700; }
.plug-status.on  { background:#dcfce7; color:#166534; }
.plug-status.off { background:#f1f5f9; color:#64748b; }
.plug-status.err { background:#fee2e2; color:#991b1b; }
.plug-actions { display:flex; flex-direction:column; gap:.4rem; align-items:flex-end; }

/* Deps badge */
.plug-dep    { display:inline-flex; align-items:center; gap:.25rem; background:#eff6ff;
               color:#1d4ed8; font-size:.72rem; font-weight:600;
               padding:1px 8px; border-radius:20px; margin-top:.35rem; }
.plug-dep.missing { background:#fee2e2; color:#991b1b; }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-puzzle-piece" style="color:#6366f1;"></i> <?= __('plugins_manage') ?></h1>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert <?= !empty($flash['success']) ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars((string)($flash['message'] ?? '')) ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-header"><?= __('plugin_upload') ?></div>
    <div class="card-body">
        <form method="POST" action="/admin/plugins/upload" enctype="multipart/form-data"
              style="display:flex; gap:.75rem; align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <div class="form-group" style="margin:0; flex:1; min-width:240px;">
                <label for="plugin_zip">ZIP файл (макс. <?= (int)$maxUploadSizeMb ?> MB)</label>
                <input class="form-control" type="file" name="plugin_zip"
                       id="plugin_zip" accept=".zip" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Завантажити
            </button>
        </form>
    </div>
</div>

<div class="plug-grid">
    <?php if (empty($plugins)): ?>
        <div style="text-align:center; padding:2rem; color:#94a3b8;">
            <i class="fas fa-puzzle-piece" style="font-size:2rem; display:block; margin-bottom:.5rem;"></i>
            Плагінів не знайдено
        </div>
    <?php endif; ?>

    <?php foreach ($plugins as $p):
        $isActive  = (int)$p['is_active'] === 1;
        $isMissing = !empty($p['is_missing']);
        $cardClass = $isMissing ? 'missing' : ($isActive ? 'active' : 'inactive');
        $hasDeps   = !empty($p['requires']);
        $hasSettings = !empty($p['has_settings']);
    ?>
    <div class="plug-card <?= $cardClass ?>">
        <div>
            <div class="plug-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="plug-slug">
                <?= htmlspecialchars($p['slug']) ?>
                · v<?= htmlspecialchars($p['version']) ?>
                · <?= htmlspecialchars($p['author']) ?>
            </div>
            <div class="plug-desc"><?= htmlspecialchars($p['description']) ?></div>
            <div class="plug-meta">
                <?php if ($p['requires_php']): ?>
                    <span><i class="fab fa-php"></i> PHP <?= htmlspecialchars($p['requires_php']) ?>+</span>
                <?php endif; ?>
                <?php if ($p['requires_core']): ?>
                    <span><i class="fas fa-cubes"></i> Core <?= htmlspecialchars($p['requires_core']) ?>+</span>
                <?php endif; ?>
            </div>

            <!-- Залежності -->
            <?php if (!empty($p['requires'])): ?>
                <div style="margin-top:.5rem; display:flex; gap:.35rem; flex-wrap:wrap;">
                    <?php foreach ($p['requires'] as $depSlug => $depVer): ?>
                        <?php
                        $depPlugin = null;
                        foreach ($plugins as $dp) {
                            if ($dp['slug'] === $depSlug) { $depPlugin = $dp; break; }
                        }
                        $depActive  = $depPlugin && (int)$depPlugin['is_active'] === 1;
                        $depMissing = !$depPlugin;
                        ?>
                        <span class="plug-dep <?= (!$depActive || $depMissing) ? 'missing' : '' ?>">
                            <i class="fas fa-<?= $depActive ? 'link' : 'exclamation-circle' ?>"></i>
                            <?= htmlspecialchars($depSlug) ?>
                            <?= $depVer !== '*' ? ' ' . htmlspecialchars($depVer) : '' ?>
                            <?php if ($depMissing): ?>
                                <?= __('not_specified') ?>
                            <?php elseif (!$depActive): ?>
                                <?= __('inactive') ?>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($isMissing): ?>
                <div style="margin-top:.5rem; color:#ef4444; font-size:.82rem;">
                    <i class="fas fa-exclamation-triangle"></i> Файли плагіна відсутні на диску
                </div>
            <?php endif; ?>
        </div>

        <div class="plug-actions">
            <span class="plug-status <?= $isMissing ? 'err' : ($isActive ? 'on' : 'off') ?>">
                <i class="fas fa-<?= $isMissing ? 'times-circle' : ($isActive ? 'check-circle' : 'circle') ?>"></i>
                <?= $isMissing ? __('missing') : ($isActive ? __('active') : __('inactive')) ?>
            </span>

            <?php if (!$isMissing): ?>
                <form method="POST" action="/admin/plugins/toggle">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                    <input type="hidden" name="slug" value="<?= htmlspecialchars($p['slug']) ?>">
                    <?php if ($isActive): ?>
                        <input type="hidden" name="action" value="deactivate">
                        <button class="btn btn-outline"
                                style="border:1px solid #ddd; color:#ef4444; font-size:.82rem; padding:.35rem .8rem;">
                            <i class="fas fa-power-off"></i> Вимкнути
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="activate">
                        <button class="btn btn-primary"
                                style="font-size:.82rem; padding:.35rem .8rem;">
                            <i class="fas fa-power-off"></i> Активувати
                        </button>
                    <?php endif; ?>
                </form>

                <?php if ($p['has_settings']): ?>
                    <a href="/admin/plugins/settings/<?= htmlspecialchars($p['slug']) ?>"
                       class="btn btn-outline"
                       style="border:1px solid #ddd; color:#6366f1; font-size:.82rem; padding:.35rem .8rem; text-align:center;">
                        <i class="fas fa-cog"></i> Налаштування
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
