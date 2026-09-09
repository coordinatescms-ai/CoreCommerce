<?php
$headerLogo = get_setting('active_logotype', '');
$siteName = get_setting('site_name', 'Premium Store');
$currentLang = get_current_language();
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/uploads/logotypes/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/uploads/logotypes/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#008080">
    <title><?= isset($pageSeo['meta_title']) ? htmlspecialchars($pageSeo['meta_title']) : htmlspecialchars($siteName) ?></title>
    <?php if (isset($pageSeo['meta_description'])): ?>
        <meta name="description" content="<?= htmlspecialchars($pageSeo['meta_description']) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type"        content="<?= htmlspecialchars((string) ($pageSeo['og_type'] ?? 'website')) ?>">
    <meta property="og:title"       content="<?= htmlspecialchars((string) ($pageSeo['og_title'] ?? $pageSeo['meta_title'] ?? $siteName)) ?>">
    <meta property="og:description" content="<?= htmlspecialchars((string) ($pageSeo['og_description'] ?? $pageSeo['meta_description'] ?? '')) ?>">
    <meta property="og:image"       content="<?= !empty($pageSeo['og_image']) ? htmlspecialchars((string) $pageSeo['og_image']) : get_setting('site_url') . '/uploads/logotypes/og-image.png' ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url"         content="<?= get_setting('site_url') . ($_SERVER['REQUEST_URI'] ?? '/') ?>">
    <meta property="og:site_name"   content="<?= htmlspecialchars($siteName) ?>">

    <!-- Twitter Cards -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= htmlspecialchars((string) ($pageSeo['og_title'] ?? $pageSeo['meta_title'] ?? $siteName)) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars((string) ($pageSeo['og_description'] ?? $pageSeo['meta_description'] ?? '')) ?>">
    <meta name="twitter:image"       content="<?= !empty($pageSeo['og_image']) ? htmlspecialchars((string) $pageSeo['og_image']) : get_setting('site_url') . '/uploads/logotypes/og-image.png' ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Premium Luxury Theme Styles */
        :root {
            --primary: #b89552;
            --primary-hover: #a68446;
            --dark: #0a0a0a;
            --dark-accent: #1a1a1a;
            --light: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --text-main: #2c3e50;
            --text-muted: #6c757d;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            --border-radius: 4px;
        }

        /* Reset & Base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-main);
            background-color: #fcfcfc;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; font-weight: 700; color: var(--dark); margin-bottom: 1rem; }
        a { text-decoration: none; color: inherit; transition: var(--transition); }
        ul { list-style: none; }

        /* Layout Containers */
        .container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .mt-1 { margin-top: 0.5rem; } .mt-2 { margin-top: 1rem; } .mt-3 { margin-top: 1.5rem; } .mt-4 { margin-top: 2rem; }
        .mb-1 { margin-bottom: 0.5rem; } .mb-2 { margin-bottom: 1rem; } .mb-3 { margin-bottom: 1.5rem; } .mb-4 { margin-bottom: 2rem; }
        .p-1 { padding: 0.5rem; } .p-2 { padding: 1rem; } .p-3 { padding: 1.5rem; } .p-4 { padding: 2rem; }
        .flex { display: flex; }
        .flex-center { display: flex; align-items: center; justify-content: center; }
        .flex-between { display: flex; align-items: center; justify-content: space-between; }
        .gap-1 { gap: 0.5rem; } .gap-2 { gap: 1rem; } .gap-3 { gap: 1.5rem; }
        .w-full { width: 100%; }
        .h-full { height: 100%; }
        .rounded { border-radius: var(--border-radius); }
        .shadow { box-shadow: var(--shadow); }
        .border { border: 1px solid var(--gray-200); }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .badge-primary { background-color: var(--primary); color: white; }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-danger { background-color: #fee2e2; color: #7f1d1d; }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid transparent;
        }
        .btn-primary { background: var(--dark); color: white; }
        .btn-primary:hover { background: var(--primary); transform: translateY(-2px); }
        .btn-outline { border: 1px solid var(--dark); color: var(--dark); background: transparent; }
        .btn-outline:hover { background: var(--dark); color: white; }

        /* Forms */
        input, textarea, select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-family: inherit;
            transition: var(--transition);
        }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(184, 149, 82, 0.1); }

        /* Header */
        .premium-header {
            background: var(--light);
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-top {
            padding: 0.5rem 0;
            background: var(--dark);
            color: var(--light);
            font-size: 0.7rem;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .header-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }
        .nav-brand { display: flex; align-items: center; gap: 1rem; }
        .nav-brand-logo { max-height: 45px; width: auto; }
        .nav-brand span { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 800; color: var(--dark); }

        .header-actions { display: flex; align-items: center; gap: 1.5rem; }
        .search-bar { position: relative; }
        .search-input { padding: 0.5rem 1rem 0.5rem 2.5rem; border-radius: 30px; width: 200px; font-size: 0.85rem; }
        .search-input:focus { width: 300px; }
        .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray-600); }

        .action-link { font-size: 1.1rem; color: var(--dark); position: relative; }
        .cart-count {
            position: absolute; top: -8px; right: -10px;
            background: var(--primary); color: white;
            font-size: 0.65rem; padding: 2px 5px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }

        /* Navigation */
        .premium-nav { border-top: 1px solid var(--gray-100); padding: 0.8rem 0; }
        .nav-list { display: flex; justify-content: center; gap: 2.5rem; }
        .nav-item a { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-600); }
        .nav-item a:hover { color: var(--primary); }

        /* Breadcrumbs */
        .breadcrumb-list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            list-style: none;
            padding: 0;
        }
        .breadcrumb-item {
            display: flex;
            align-items: center;
            color: var(--text-muted);
        }
        .breadcrumb-item a {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .breadcrumb-item a:hover {
            color: var(--primary);
        }
        .breadcrumb-item:not(:last-child)::after {
            content: '/';
            margin-left: 0.5rem;
            color: var(--gray-300);
            font-size: 0.8rem;
        }
        .breadcrumb-item--current {
            color: var(--dark);
            font-weight: 600;
        }

        /* Products Grid & Cards */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2.5rem;
            margin: 2rem 0;
        }
        .product-card {
            background: white;
            transition: var(--transition);
            border: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
        .product-card img { width: 100%; aspect-ratio: 1; object-fit: cover; }
        .product-card-body { padding: 1.5rem; text-align: center; flex: 1; display: flex; flex-direction: column; }
        .product-title { font-family: 'Playfair Display', serif; font-size: 1.1rem; margin-bottom: 0.5rem; flex: 1; }
        .product-price { color: var(--primary); font-weight: 700; font-size: 1.2rem; }

        /* Footer */
        .premium-footer { background: var(--dark); color: white; padding: 4rem 0 2rem; margin-top: 4rem; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1.2fr; gap: 3rem; margin-bottom: 3rem; }
        .footer-title { color: white; font-size: 1rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 1.5rem; }
        .footer-text { color: #999; font-size: 0.85rem; }
        .footer-links a { color: #999; font-size: 0.85rem; display: block; margin-bottom: 0.8rem; }
        .footer-links a:hover { color: var(--primary); padding-left: 5px; }
        .social-icons { display: flex; gap: 1rem; }
        .social-link { width: 36px; height: 36px; border: 1px solid #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; }
        .social-link:hover { background: var(--primary); border-color: var(--primary); }
        .footer-bottom { border-top: 1px solid #222; padding-top: 2rem; display: flex; justify-content: space-between; font-size: 0.75rem; color: #666; }

        /* Mobile Styles */
        @media (max-width: 992px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .nav-list { display: none; }
            .search-input { width: 140px; }
            .search-input:focus { width: 180px; }
        }
        @media (max-width: 576px) {
            .footer-grid { grid-template-columns: 1fr; }
            .header-main { flex-direction: column; gap: 1rem; }
            .container { padding: 0 1rem; }
            .products-grid { grid-template-columns: 1fr; }
        }
    </style>
    
    <?php do_action('theme.head'); ?>
</head>
<body>
    <header class="premium-header">
        <div class="header-top">
             
        </div>
        
        <div class="container">
            <div class="header-main">
                <a href="/" class="nav-brand">
                    <?php if ($headerLogo !== ''): ?>
                        <img src="<?= htmlspecialchars($headerLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="nav-brand-logo">
                    <?php endif; ?>
                    <span><?= htmlspecialchars($siteName) ?></span>
                </a>

                <div class="header-actions">
                    <form action="/search" method="GET" class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="q" id="premium-search-input" class="search-input" placeholder="<?= __('search_placeholder') ?>" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off">
                        <div id="premium-search-dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #eee; box-shadow: var(--shadow); z-index: 1002; border-radius: 10px; margin-top: 5px; overflow: hidden;"></div>
                    </form>

                    <div class="dropdown" style="position: relative;">
                        <a href="#" class="action-link" id="langSwitcher">
                            <i class="fas fa-globe"></i>
                        </a>
                        <div class="dropdown-menu" id="langMenu" style="display:none; position:absolute; right:0; background:white; border:1px solid #eee; padding:0.5rem; min-width:120px; box-shadow: var(--shadow); z-index: 1001; border-radius: 8px;">
                            <?php foreach ((function_exists('get_language_names') ? get_language_names() : ['ua' => 'Українська', 'en' => 'English']) as $langCode => $langName): ?>
                                <?php if ($langCode !== $currentLang): ?>
                                    <a href="/language/<?= htmlspecialchars($langCode) ?>" style="display:block; padding:0.5rem; font-size:0.85rem; color: var(--dark);"><?= htmlspecialchars($langName) ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <a href="/profile" class="action-link">
                        <i class="far fa-user"></i>
                    </a>

                    <a href="/cart" class="action-link">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count" data-cart-count><?= $cartCount ?></span>
                    </a>
                </div>
            </div>

            <nav class="premium-nav">
                <ul class="nav-list">
                    <li class="nav-item"><a href="/"><?= __('home') ?></a></li>
                    <?php 
                    $navCategories = array_slice($headerCategories ?? [], 0, 5);
                    foreach ($navCategories as $cat): 
                    ?>
                        <li class="nav-item">
                            <a href="/category/<?= htmlspecialchars(ltrim($cat['path'] ?? $cat['slug'], '/')) ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li class="nav-item"><a href="/products"><?= __('view_all') ?></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main-content">
        <div class="container" style="padding-top: 2rem; padding-bottom: 4rem; min-height: 60vh;">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="badge badge-success w-full mb-4" style="padding: 1rem; border-radius: 8px; text-transform: none; display: block; background: #d1fae5; color: #065f46;">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="badge badge-danger w-full mb-4" style="padding: 1rem; border-radius: 8px; text-transform: none; display: block; background: #fee2e2; color: #7f1d1d;">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?= $content ?? '<p>No content available</p>' ?>
        </div>
    </main>

    <footer class="premium-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4 class="footer-title"><?= htmlspecialchars($siteName) ?></h4>
                    <p class="footer-text mb-4">
                        <?php 
                            $footerAbout = get_setting('footer_about_text');
                            echo $footerAbout ? nl2br(htmlspecialchars($footerAbout)) : __('footer_about_text_default'); 
                        ?>
                    </p>
                    <?php if (!empty($activeSocialLinks)): ?>
                        <div class="social-icons">
                            <?php foreach ($activeSocialLinks as $link): ?>
                                <a href="<?= htmlspecialchars($link['url']) ?>" class="social-link" target="_blank" rel="noopener noreferrer">
                                    <i class="fab fa-<?= htmlspecialchars($link['slug']) ?><?= $link['slug'] === 'telegram' ? '-plane' : '' ?>"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title"><?= __('categories') ?></h4>
                    <div class="footer-links">
                        <?php foreach (array_slice($headerCategories ?? [], 0, 5) as $cat): ?>
                            <a href="/category/<?= htmlspecialchars(ltrim($cat['path'] ?? $cat['slug'], '/')) ?>"><?= htmlspecialchars($cat['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title"><?= __('information') ?></h4>
                    <div class="footer-links">
                        <?php foreach ($footerPages ?? [] as $page): ?>
                            <a href="/<?= $page['slug'] ?>"><?= htmlspecialchars($page['title']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title"><?= __('contacts') ?></h4>
                    <div class="footer-text">
                        <p class="mb-2"><i class="fas fa-phone" style="margin-right: 8px; color: var(--primary);"></i> <?= htmlspecialchars(get_setting('contact_phone', '+38 (000) 000-00-00')) ?></p>
                        <p class="mb-2"><i class="fas fa-envelope" style="margin-right: 8px; color: var(--primary);"></i> <?= htmlspecialchars(get_setting('contact_email', 'info@premium.test')) ?></p>
                        <?php $contactAddress = trim((string) get_setting('contact_address', '')); ?>
                        <?php if ($contactAddress !== ''): ?>
                        <p><i class="fas fa-map-marker-alt" style="margin-right: 8px; color: var(--primary);"></i> <?= htmlspecialchars($contactAddress) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. <?= __('all_rights_reserved') ?></div>
                <div style="display: flex; gap: 1rem; font-size: 1.2rem; opacity: 0.5;">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-apple-pay"></i>
                </div>
            </div>
        </div>
        <?php do_action('theme.footer'); ?>
    </footer>

    <script>
        // Language Switcher
        const langSwitcher = document.getElementById('langSwitcher');
        const langMenu = document.getElementById('langMenu');
        if (langSwitcher && langMenu) {
            langSwitcher.addEventListener('click', (e) => {
                e.preventDefault();
                langMenu.style.display = langMenu.style.display === 'none' ? 'block' : 'none';
            });
            window.addEventListener('click', (e) => {
                if (!langSwitcher.contains(e.target)) langMenu.style.display = 'none';
            });
        }

        // Cart Count Sync
        (() => {
            const badge = document.querySelector('[data-cart-count]');
            if (!badge) return;
            const syncCartCount = async () => {
                try {
                    const response = await fetch('/cart/count', {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                    if (response.ok) {
                        const data = await response.json();
                        badge.textContent = String(data.count ?? 0);
                        badge.style.display = data.count > 0 ? 'flex' : 'none';
                    }
                } catch {}
            };
            syncCartCount();
            setInterval(syncCartCount, 30000);
        })();

        // Search Autocomplete
        (() => {
            const input = document.getElementById('premium-search-input');
            const dropdown = document.getElementById('premium-search-dropdown');
            if (!input || !dropdown) return;
            let timer = null;
            input.addEventListener('input', function() {
                clearTimeout(timer);
                const q = this.value.trim();
                if (q.length < 2) { dropdown.style.display = 'none'; return; }
                timer = setTimeout(async () => {
                    try {
                        const res = await fetch('/search/autocomplete?q=' + encodeURIComponent(q));
                        const data = await res.json();
                        if (!data.length) { dropdown.style.display = 'none'; return; }
                        dropdown.innerHTML = data.map(item => `
                            <a href="${item.url}" style="display:flex;align-items:center;gap:1rem;padding:0.8rem 1rem;text-decoration:none;color:var(--dark);border-bottom:1px solid #f8f9fa;">
                                ${item.image ? `<img src="${item.image}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">` : `<div style="width:40px;height:40px;background:#eee;border-radius:4px;"></div>`}
                                <div style="flex:1;overflow:hidden;">
                                    <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${item.name}</div>
                                    <div style="color:var(--primary);font-size:0.85rem;">${item.price}</div>
                                </div>
                            </a>
                        `).join('') + `<a href="/search?q=${encodeURIComponent(q)}" style="display:block;padding:0.8rem;text-align:center;font-size:0.8rem;background:#fcfcfc;color:var(--gray-600);"><?= __('view_all') ?></a>`;
                        dropdown.style.display = 'block';
                    } catch {}
                }, 300);
            });
            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.style.display = 'none';
            });
        })();
    </script>
</body>
</html>
