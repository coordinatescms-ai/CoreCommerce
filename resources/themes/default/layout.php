<!DOCTYPE html>
<html lang="<?= get_current_language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars((string) ($pageSeo['meta_title'] ?? get_setting('site_name', 'My Shop'))) ?></title>

    <?php if (!empty($pageSeo['meta_description'])): ?>
        <meta name="description" content="<?= htmlspecialchars((string) $pageSeo['meta_description']) ?>">
    <?php endif; ?>
    <?php if (!empty($pageSeo['meta_keywords'])): ?>
        <meta name="keywords" content="<?= htmlspecialchars((string) $pageSeo['meta_keywords']) ?>">
    <?php endif; ?>
    <?php if (!empty($pageSeo['robots'])): ?>
        <meta name="robots" content="<?= htmlspecialchars((string) $pageSeo['robots']) ?>">
    <?php endif; ?>
    <?php if (!empty($pageSeo['canonical'])): ?>
        <link rel="canonical" href="<?= htmlspecialchars((string) $pageSeo['canonical']) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type"        content="<?= htmlspecialchars((string) ($pageSeo['og_type'] ?? 'website')) ?>">
    <meta property="og:title"       content="<?= htmlspecialchars((string) ($pageSeo['og_title'] ?? $pageSeo['meta_title'] ?? '')) ?>">
    <?php if (!empty($pageSeo['og_description'])): ?>
        <meta property="og:description" content="<?= htmlspecialchars((string) $pageSeo['og_description']) ?>">
    <?php endif; ?>
    <?php if (!empty($pageSeo['og_image'])): ?>
        <meta property="og:image" content="<?= htmlspecialchars((string) $pageSeo['og_image']) ?>">
    <?php endif; ?>
    <?php if (!empty($pageSeo['canonical'])): ?>
        <meta property="og:url" content="<?= htmlspecialchars((string) $pageSeo['canonical']) ?>">
    <?php endif; ?>
    <meta property="og:site_name" content="<?= htmlspecialchars((string) ($pageSeo['shop_name'] ?? get_setting('site_name', ''))) ?>">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
        nav { background: #f4f4f4; padding: 1rem; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        nav a { margin-right: 1rem; text-decoration: none; color: #333; }
        nav a:hover { color: #007bff; }
        .nav-brand { display: inline-flex; align-items: center; gap: 0.45rem; margin-right: 1rem; text-decoration: none; color: #333; font-weight: 700; }
        .nav-brand:hover { color: #007bff; text-decoration: none; }
        .nav-brand-logo { max-height: 34px; width: auto; display: block; border-radius: 4px; }
        .nav-links { display: flex; align-items: center; flex-wrap: wrap; gap: 0.35rem; flex: 1; }
        .nav-cart-link { margin-left: auto; display: inline-flex; align-items: center; gap: 0.35rem; }
        .cart-counter { display: inline-flex; min-width: 20px; height: 20px; border-radius: 999px; align-items: center; justify-content: center; background: #007bff; color: #fff; font-size: 11px; font-weight: 700; padding: 0 6px; line-height: 1; }
        .nav-links > a { margin-right: 0; }
        .nav-separator { color: #999; margin: 0 0.2rem; }
        .nav-dropdown { position: relative; }
        .nav-dropdown-toggle {
            border: 0;
            background: transparent;
            color: #333;
            cursor: pointer;
            font: inherit;
            padding: 0;
        }
        .nav-dropdown-toggle:hover,
        .nav-dropdown-toggle:focus-visible { color: #007bff; outline: none; }
        .nav-dropdown-menu {
            list-style: none;
            margin: 0;
            padding: 0.6rem 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 220px;
            max-height: 320px;
            overflow-y: auto;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            display: none;
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            z-index: 30;
        }
        .nav-dropdown:hover > .nav-dropdown-menu,
        .nav-dropdown:focus-within > .nav-dropdown-menu { display: block; }
        .nav-dropdown-menu li { margin: 0; }
        .nav-dropdown-menu a {
            display: block;
            padding: 0.4rem 0.9rem;
            margin-right: 0;
            color: #333;
        }
        .nav-dropdown-menu a:hover { background: #f4f8ff; }
        .nav-dropdown-submenu { padding-left: 1rem; }
        .language-selector { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .language-selector a { margin-left: 0.5rem; margin-right: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
        main { background: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        footer { background: #f4f4f4; padding: 1rem; text-align: center; border-top: 1px solid #ddd; margin-top: 2rem; }
        h1, h2, h3 { margin-bottom: 1rem; color: #333; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Breadcrumb */
        .breadcrumb { margin: 0 0 1.25rem; }
        .breadcrumb-list { display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; list-style: none; padding: 0; margin: 0; font-size: .84rem; line-height: 1.4; gap: 0; }
        .breadcrumb-list li { list-style: none; }
        .breadcrumb-list li::marker { content: none; }
        .breadcrumb-item { display: inline-flex; align-items: center; gap: .25rem; }
        .breadcrumb-item + .breadcrumb-item::before { content: '›'; margin: 0 .4rem; color: #b0bec5; font-size: 1rem; line-height: 1; }
        .breadcrumb-item a { display: inline-flex; align-items: center; gap: .25rem; color: #64748b; text-decoration: none; }
        .breadcrumb-item a:hover { color: #007bff; text-decoration: none; }
        .breadcrumb-item a .fa-home { font-size: .78rem; }
        .breadcrumb-item--current { color: #1e293b; font-weight: 500; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        @media (max-width: 640px) { .breadcrumb-list { font-size: .78rem; } .breadcrumb-item--current { max-width: 150px; } }

        @media (max-width: 768px) {
            nav { flex-direction: column; gap: 1rem; }
            .nav-links { justify-content: center; width: 100%; }
            .nav-dropdown { width: 100%; text-align: center; }
            .nav-dropdown-menu {
                position: static;
                width: 100%;
                max-height: none;
                margin-top: 0.5rem;
            }
            .nav-dropdown.is-open > .nav-dropdown-menu { display: block; }
            .nav-dropdown:hover > .nav-dropdown-menu { display: none; }
            .language-selector { justify-content: center; }
        }
    </style>
    <?php do_action('theme.head'); ?>
    <script>
        var currencySymbol = <?= json_encode(
            \App\Core\Database\DB::query('SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1')
                ->fetchColumn() ?: '₴'
        ) ?>;
        window.CURRENCY_SYMBOL = currencySymbol;
    </script>
</head>
<body>
    <?php
    if (!function_exists('renderDefaultThemeHeaderCategories')) {
        function renderDefaultThemeHeaderCategories(array $categories, int $depth = 0, int $maxDepth = 3, string $rootId = ''): void
        {
            if (empty($categories) || $depth > $maxDepth) {
                return;
            }

            $submenuClass = $depth === 0 ? 'nav-dropdown-menu' : 'nav-dropdown-submenu';
            $idAttribute = $depth === 0 && $rootId !== '' ? ' id="' . htmlspecialchars($rootId) . '"' : '';
            echo '<ul class="' . $submenuClass . '"' . $idAttribute . '>';

            foreach ($categories as $category) {
                echo '<li>';
                echo '<a href="/category/' . htmlspecialchars(ltrim($category['path'] ?? $category['slug'], '/')) . '">' . htmlspecialchars($category['name']) . '</a>';

                if (!empty($category['children']) && $depth < $maxDepth) {
                    renderDefaultThemeHeaderCategories($category['children'], $depth + 1, $maxDepth, $rootId);
                }

                echo '</li>';
            }

            echo '</ul>';
        }
    }
    ?>
    <nav>
        <div class="nav-links">
            <?php $headerLogo = trim((string) get_setting('active_logotype', '')); ?>
            <a href="/" class="nav-brand">
                <?php if ($headerLogo !== ''): ?>
                    <img src="<?= htmlspecialchars($headerLogo) ?>" alt="<?= htmlspecialchars((string) get_setting('site_name', 'Мій Магазин')) ?>" class="nav-brand-logo">
                <?php endif; ?>
                <span><?= htmlspecialchars((string) get_setting('site_name', 'Мій Магазин')) ?></span>
            </a>
            <a href="/"><?= __('home') ?></a> | 
            <a href="/products"><?= __('products') ?></a> |
            <div class="nav-dropdown" data-nav-dropdown>
                <button class="nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="default-nav-categories">
                    <?= __('categories') ?>
                </button>
                <?php
                $headerCategories = $headerCategories ?? [];
                if (!empty($headerCategories)) {
                    renderDefaultThemeHeaderCategories($headerCategories, 0, 3, 'default-nav-categories');
                }
                ?>
            </div>
            <span class="nav-separator">|</span>
            <?php if (!empty($_SESSION['user'])): ?>
                <a href="/profile"><?= $_SESSION['user']['first_name'] ?? $_SESSION['user']['email'] ?></a> |
                <form action="/logout" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                    <button type="submit" style="border: none; background: none; padding: 0; color: inherit; cursor: pointer; text-decoration: underline;"><?= __('logout') ?></button>
                </form>
            <?php else: ?>
                <a href="/login"><?= __('login') ?></a> |
                <a href="/register"><?= __('register') ?></a>
            <?php endif; ?>
            <span class="nav-separator">|</span>
            <a href="/cart" class="nav-cart-link" data-cart-link><?= __('cart') ?><span class="cart-counter" data-cart-count>0</span></a>
        </div>

        <!-- Пошук -->
        <div class="nav-search" style="position:relative;flex:1 1 220px;max-width:340px;">
            <form action="/search" method="GET" role="search" style="display:flex;align-items:center;border:1px solid #ddd;border-radius:6px;overflow:hidden;background:#fff;">
                <input
                    type="search" name="q"
                    class="nav-search-input"
                    value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="<?= htmlspecialchars(__('search_placeholder') ?: 'Пошук...') ?>"
                    autocomplete="off"
                    style="flex:1;border:none;outline:none;padding:.4rem .6rem;font-size:.88rem;min-width:0;"
                    id="nav-search-input">
                <button type="submit" style="padding:.4rem .7rem;background:none;border:none;cursor:pointer;color:#666;">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <!-- Автодоповнення -->
            <div id="nav-search-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #ddd;border-top:none;border-radius:0 0 6px 6px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.1);"></div>
        </div>

        <div class="language-selector">
            <span><?= __('language') ?>:</span>
            <?php foreach (get_supported_languages() as $lang): ?>
                <?php if ($lang === get_current_language()): ?>
                    <strong><?= $lang === 'ua' ? __('ukrainian') : __('english') ?></strong>
                <?php else: ?>
                    <a href="/language/<?= $lang ?>"><?= $lang === 'ua' ? __('ukrainian') : __('english') ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
    <div class="container">
        <main>
            <?= $content ?>
        </main>
    </div>
    <footer>
    <div class="footer-links">
        <?php foreach ($footerPages as $page): ?>
            <a href="/<?= $page['slug'] ?>"><?= htmlspecialchars($page['title']) ?></a>
        <?php endforeach; ?>
    </div>
        <p>&copy; 2024 MySite. <?= __('all_rights_reserved') ?? 'All rights reserved.' ?></p>
        <?php do_action('theme.footer'); ?>
    </footer>
    <script>
        (() => {
            if (window.matchMedia('(max-width: 768px)').matches === false) {
                return;
            }

            document.querySelectorAll('[data-nav-dropdown]').forEach((dropdown) => {
                const button = dropdown.querySelector('.nav-dropdown-toggle');
                if (!button) {
                    return;
                }

                button.addEventListener('click', () => {
                    const isOpen = dropdown.classList.toggle('is-open');
                    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
            });
        })();

        (() => {
            const badge = document.querySelector('[data-cart-count]');
            if (!badge) return;

            const syncCartCount = async () => {
                try {
                    const response = await fetch('/cart/count', {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                    if (!response.ok) return;
                    const payload = await response.json();
                    badge.textContent = String(payload.count ?? 0);
                } catch (e) {
                    // noop
                }
            };

            syncCartCount();
            setInterval(syncCartCount, 15000);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) syncCartCount();
            });
        })();
    </script>
<script>
(function () {
    const input    = document.getElementById('nav-search-input');
    const dropdown = document.getElementById('nav-search-dropdown');
    if (!input || !dropdown) return;

    let timer = null;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { dropdown.style.display = 'none'; return; }

        timer = setTimeout(async () => {
            try {
                const res  = await fetch('/search/autocomplete?q=' + encodeURIComponent(q));
                const data = await res.json();
                if (!data.length) { dropdown.style.display = 'none'; return; }

                dropdown.innerHTML = data.map(item => `
                    <a href="${item.url}" style="display:flex;align-items:center;gap:.6rem;padding:.5rem .75rem;text-decoration:none;color:#1e293b;border-bottom:1px solid #f1f5f9;font-size:.88rem;">
                        ${item.image ? `<img src="${item.image}" style="width:36px;height:36px;object-fit:cover;border-radius:4px;flex-shrink:0;">` : '<span style="width:36px;height:36px;background:#f1f5f9;border-radius:4px;flex-shrink:0;display:block;"></span>'}
                        <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${item.name}</span>
                        <span style="font-weight:600;color:#3b82f6;flex-shrink:0;">${item.price}</span>
                    </a>
                `).join('') + `<a href="/search?q=${encodeURIComponent(q)}" style="display:block;padding:.5rem .75rem;text-align:center;font-size:.83rem;color:#64748b;text-decoration:none;">🔍 Всі результати</a>`;

                dropdown.style.display = 'block';
            } catch {}
        }, 280);
    });

    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') dropdown.style.display = 'none';
    });
})();
</script>
</body>
</html>
