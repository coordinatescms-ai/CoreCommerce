<?php
/**
 * Breadcrumb компонент зі Schema.org BreadcrumbList
 *
 * Змінні:
 *   $breadcrumbs — масив ['id','name','url','path']
 *   $current     — назва поточної сторінки (товар, категорія)
 *   $currentUrl  — URL поточної сторінки (для Schema.org)
 *   $showHome    — показувати "Головна" (default: true)
 */

$breadcrumbs = $breadcrumbs ?? [];
$current     = $current     ?? null;
$currentUrl  = $currentUrl  ?? null;
$showHome    = $showHome    ?? true;
$crumbCount  = count($breadcrumbs);

// Будуємо список для Schema.org JSON-LD
$schemaItems = [];
if ($showHome) {
    $schemaItems[] = ['name' => __('home') ?: 'Головна', 'url' => '/'];
}
foreach ($breadcrumbs as $crumb) {
    $schemaItems[] = [
        'name' => $crumb['name'],
        'url'  => $crumb['url'] ?? ('/category/' . ltrim($crumb['path'] ?? $crumb['slug'] ?? '', '/')),
    ];
}
if ($current !== null) {
    $schemaItems[] = ['name' => $current, 'url' => $currentUrl ?? ''];
}

// Нічого не виводимо якщо лише "Головна"
if (count($schemaItems) <= 1 && $current === null) {
    return;
}

$siteUrl = rtrim((string) get_setting('site_url', ''), '/');
?>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [<?php
    $ldItems = [];
    foreach ($schemaItems as $pos => $item) {
        $ldItems[] = json_encode([
            '@type'    => 'ListItem',
            'position' => $pos + 1,
            'name'     => $item['name'],
            'item'     => $siteUrl . ($item['url'] ?: '/'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo implode(',', $ldItems);
    ?>]
}
</script>

<nav class="breadcrumb" aria-label="<?= htmlspecialchars(__('breadcrumb') ?: 'Навігація') ?>">
    <ul class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">

        <?php if ($showHome): ?>
        <li class="breadcrumb-item"
            itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="/" itemprop="item">
                <i class="fas fa-home" aria-hidden="true"></i>
                <span itemprop="name"><?= htmlspecialchars(__('home') ?: 'Головна') ?></span>
            </a>
            <meta itemprop="position" content="1">
        </li>
        <?php endif; ?>

        <?php
        $pos = $showHome ? 2 : 1;
        foreach ($breadcrumbs as $i => $crumb):
            $crumbUrl = $crumb['url']
                ?? ('/category/' . ltrim($crumb['path'] ?? $crumb['slug'] ?? '', '/'));
            $isLast   = ($i === $crumbCount - 1) && $current === null;
        ?>
        <li class="breadcrumb-item<?= $isLast ? ' breadcrumb-item--current' : '' ?>"
            itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <?php if ($isLast): ?>
                <span itemprop="name"><?= htmlspecialchars($crumb['name']) ?></span>
                <meta itemprop="item" content="<?= htmlspecialchars($siteUrl . $crumbUrl) ?>">
            <?php else: ?>
                <a href="<?= htmlspecialchars($crumbUrl) ?>" itemprop="item">
                    <span itemprop="name"><?= htmlspecialchars($crumb['name']) ?></span>
                </a>
            <?php endif; ?>
            <meta itemprop="position" content="<?= $pos++ ?>">
        </li>
        <?php endforeach; ?>

        <?php if ($current !== null): ?>
        <li class="breadcrumb-item breadcrumb-item--current"
            itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name"><?= htmlspecialchars($current) ?></span>
            <?php if ($currentUrl): ?>
                <meta itemprop="item" content="<?= htmlspecialchars($siteUrl . $currentUrl) ?>">
            <?php endif; ?>
            <meta itemprop="position" content="<?= $pos ?>">
        </li>
        <?php endif; ?>

    </ul>
</nav>
