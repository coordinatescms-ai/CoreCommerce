<!DOCTYPE html>
<html lang="<?php echo function_exists('get_current_language') ? get_current_language() : 'ua'; ?>">
<head>
    <?php $assetVersion = urlencode((string) (get_setting('asset_version', '1'))); ?>
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

    <link rel="stylesheet" href="<?php echo class_exists('App\\Core\\View\\View') ? \App\Core\View\View::getThemeStyle() : '/resources/themes/modern/style.css'; ?>?v=<?php echo $assetVersion; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Segoe+UI:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --light: #f8fafc;
            --dark: #1e293b;
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: #f1f5f9;
        }

        /* Header & Navigation */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .nav-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 1.2rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            color: white;
            min-width: 0;
        }

        .nav-brand-logo {
            max-height: 36px;
            max-width: 140px;
            width: auto;
            display: block;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            padding: 2px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .nav-brand span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            flex: 1;
            justify-content: center;
            flex-wrap: wrap;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        .nav-cart-link {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
        }
        .cart-counter {
            display: inline-flex;
            min-width: 20px;
            height: 20px;
            border-radius: 999px;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.9);
            color: #1e40af;
            font-size: 11px;
            font-weight: 700;
            padding: 0 6px;
        }

        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown-toggle {
            color: white;
            border: 0;
            background: transparent;
            cursor: pointer;
            font: inherit;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .nav-dropdown-toggle:hover,
        .nav-dropdown-toggle:focus-visible {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            outline: none;
        }

        .nav-dropdown-menu,
        .nav-dropdown-submenu {
            list-style: none;
            margin: 0;
            padding: 0.4rem 0;
        }

        .nav-dropdown-menu {
            min-width: 250px;
            max-height: 360px;
            overflow-y: auto;
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: 0 12px 24px rgba(2, 6, 23, 0.2);
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            display: none;
            z-index: 40;
        }

        .nav-dropdown:hover > .nav-dropdown-menu,
        .nav-dropdown:focus-within > .nav-dropdown-menu {
            display: block;
        }

        .nav-dropdown-menu a,
        .nav-dropdown-submenu a {
            color: #0f172a;
            display: block;
            padding: 0.4rem 0.9rem;
            margin: 0;
            border-radius: 0;
        }

        .nav-dropdown-menu a:hover,
        .nav-dropdown-submenu a:hover {
            background: #eff6ff;
            transform: none;
        }

        .nav-dropdown-submenu {
            padding-left: 0.9rem;
        }

        /* Основний контейнер */
        .language-selector {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* Контейнер для випадаючого списку */
        .lang-dropdown {
            position: relative;
            display: inline-block;
        }

        /* Стиль головної кнопки (поточної мови) */
        .lang-dropbtn {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius, 6px);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition, all 0.2s ease);
        }

.lang-dropdown:hover .lang-dropbtn {
    background-color: rgba(255, 255, 255, 0.25);
}

/* Стрілочка біля мови */
.lang-dropbtn .arrow {
    font-size: 0.6rem;
    transition: transform 0.2s ease;
}

.lang-dropdown:hover .arrow {
    transform: rotate(180deg);
}

/* Випадаюче вікно з іншими мовами */
.dropdown-menu {
    display: block;
    position: absolute;
    top: 110%;
    left: 0;
    min-width: 120px;
    background-color: rgba(30, 30, 30, 0.85); /* Темний напівпрозорий фон */
    backdrop-filter: blur(10px); /* Ефект розмиття */
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: var(--border-radius, 6px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    opacity: 0;
    pointer-events: none;
    transform: translateY(-5px);
    transition: opacity 0.2s ease, transform 0.2s ease;
    z-index: 100;
}

/* Поява списку при наведенні */
.lang-dropdown:hover .dropdown-menu {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}

/* Посилання всередині меню */
.dropdown-menu a {
    color: rgba(255, 255, 255, 0.9);
    padding: 0.5rem 1rem;
    text-decoration: none;
    display: block;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.dropdown-menu a:first-child {
    border-radius: var(--border-radius, 6px) var(--border-radius, 6px) 0 0;
}

.dropdown-menu a:last-child {
    border-radius: 0 0 var(--border-radius, 6px) var(--border-radius, 6px);
}

/* Ефект наведення на пункт меню */
.dropdown-menu a:hover {
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    padding-left: 1.2rem; /* Легкий зсув вправо для інтерактивності */
}

/* Стилі для лінка кошика */
.nav-cart-link {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    border-radius: var(--border-radius, 6px);
    text-decoration: none;
    color: white;
    font-size: 0.9rem;
    transition: var(--transition, all 0.2s ease);
}

.nav-cart-link:hover {
    background-color: rgba(255, 255, 255, 0.15);
}

.cart-counter {
    background-color: #ff4757; /* Яскравий колір для лічильника */
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
    padding: 0.1rem 0.4rem;
    border-radius: 10px;
    margin-left: 0.2rem;
}


        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Main Content */
        main {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        p {
            margin-bottom: 1rem;
            color: #475569;
        }

        a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        a:hover {
            color: #1e40af;
            text-decoration: underline;
        }

        /* Product Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .product-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: var(--light);
        }

        .product-content {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #475569;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }

        footer p {
            color: #cbd5e1;
            margin: 0;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fca5a5;
        }

        .alert-warning {
            background-color: #fef3c7;
            color: #78350f;
            border: 1px solid #fcd34d;
        }

        .alert-info {
            background-color: #dbeafe;
            color: #0c2340;
            border: 1px solid #93c5fd;
        }

        /* Responsive */
        /* Nav actions (search + lang + cart) */
        .nav-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-shrink: 0;
        }

        /* Search */
        .nav-search-wrap { position: relative; }
        .nav-search-form {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
            width: 220px;
            transition: width .25s;
        }
        .nav-search-form:focus-within { width: 280px; }
        .nav-search-input {
            flex: 1;
            border: none;
            outline: none;
            padding: .42rem .55rem;
            font-size: .88rem;
            background: transparent;
            color: #333;
            min-width: 0;
        }
        .nav-search-btn {
            padding: .42rem .65rem;
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .nav-search-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 0 0 8px 8px;
            z-index: 9999;
            box-shadow: 0 4px 16px rgba(0,0,0,.15);
        }

        /* Burger button */
        .nav-burger {
            display: none;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 8px;
            cursor: pointer;
            padding: 8px;
            margin-left: auto;
            flex-shrink: 0;
        }
        .nav-burger-line {
            display: block;
            width: 100%;
            height: 2px;
            background: #fff;
            border-radius: 2px;
            transition: all .25s;
        }
        .nav-burger[aria-expanded="true"] .nav-burger-line:nth-child(1) {
            transform: translateY(7px) rotate(45deg);
        }
        .nav-burger[aria-expanded="true"] .nav-burger-line:nth-child(2) {
            opacity: 0;
        }
        .nav-burger[aria-expanded="true"] .nav-burger-line:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg);
        }

        @media (max-width: 900px) {
            .nav-search-form { width: 160px; }
            .nav-search-form:focus-within { width: 200px; }
        }

        @media (max-width: 768px) {
            nav {
                flex-wrap: wrap;
                padding: .75rem 1rem;
                gap: .5rem;
            }

            .nav-burger { display: flex; }

            /* Мобільне меню — приховано за замовчуванням */
            .nav-links {
                display: none;
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
                order: 10;
                background: rgba(255,255,255,.06);
                border-radius: 10px;
                padding: .5rem 0;
                gap: 0;
            }
            .nav-links.is-open { display: flex; }

            .nav-links a,
            .nav-dropdown-toggle {
                width: 100%;
                padding: .65rem 1rem;
                border-radius: 0;
                text-align: left;
                font-size: .95rem;
            }

            .nav-dropdown { width: 100%; }
            .nav-dropdown-menu {
                position: static;
                box-shadow: none;
                border: none;
                border-radius: 0;
                background: rgba(0,0,0,.1);
                max-height: none;
                display: none;
                padding: 0;
            }
            .nav-dropdown.is-open > .nav-dropdown-menu { display: block; }
            .nav-dropdown:hover > .nav-dropdown-menu { display: none; }
            .nav-dropdown.is-open:hover > .nav-dropdown-menu { display: block; }

            .nav-dropdown-menu a { color: rgba(255,255,255,.9) !important; padding: .5rem 1.5rem; }
            .nav-dropdown-menu a:hover { background: rgba(255,255,255,.1) !important; }

            /* Actions — пошук + кошик залишаються в рядку поруч з burger */
            .nav-actions {
                margin-left: auto;
                gap: .5rem;
            }
            .nav-search-form { width: 130px; }
            .nav-search-form:focus-within { width: 160px; }
            .lang-dropdown { display: none; } /* Мову ховаємо — є в меню */
        }

        @media (max-width: 480px) {
            .nav-search-form { width: 100px; }
            .nav-search-form:focus-within { width: 130px; }
            h1 { font-size: 1.5rem; }
            h2 { font-size: 1.2rem; }
            .products-grid { grid-template-columns: 1fr; }
        }

        /* Breadcrumb */
        .breadcrumb { margin: 0 0 1.25rem; }
        .breadcrumb-list { display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; list-style: none; padding: 0; margin: 0; font-size: .84rem; line-height: 1.4; gap: 0; }
        .breadcrumb-list li { list-style: none; }
        .breadcrumb-list li::marker { content: none; }
        .breadcrumb-item { display: inline-flex; align-items: center; gap: .25rem; }
        .breadcrumb-item + .breadcrumb-item::before { content: '›'; margin: 0 .4rem; color: #b0bec5; font-size: 1rem; line-height: 1; }
        .breadcrumb-item a { display: inline-flex; align-items: center; gap: .25rem; color: #64748b; text-decoration: none; }
        .breadcrumb-item a:hover { color: var(--primary, #2563eb); text-decoration: none; }
        .breadcrumb-item a .fa-home { font-size: .78rem; }
        .breadcrumb-item--current { color: #1e293b; font-weight: 500; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        @media (max-width: 640px) { .breadcrumb-list { font-size: .78rem; } .breadcrumb-item--current { max-width: 150px; } }
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
    if (!function_exists('renderModernThemeHeaderCategories')) {
        function renderModernThemeHeaderCategories(array $categories, int $depth = 0, int $maxDepth = 3, string $rootId = ''): void
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
                    renderModernThemeHeaderCategories($category['children'], $depth + 1, $maxDepth, $rootId);
                }

                echo '</li>';
            }

            echo '</ul>';
        }
    }
    ?>
    <header>
        <nav>
            <?php $headerLogo = trim((string) get_setting('active_logotype', '')); ?>
            <a href="/" class="nav-brand">
                <?php if ($headerLogo !== ''): ?>
                    <img src="<?php echo htmlspecialchars($headerLogo); ?>" alt="<?php echo htmlspecialchars((string) get_setting('site_name', 'Мій Магазин')); ?>" class="nav-brand-logo">
                <?php endif; ?>
                <span><?php echo htmlspecialchars((string) get_setting('site_name', 'Мій Магазин')); ?></span>
            </a>

            <!-- Burger button (mobile only) -->
            <button class="nav-burger" id="nav-burger" aria-label="Меню" aria-expanded="false" aria-controls="nav-mobile-panel" type="button">
                <span class="nav-burger-line"></span>
                <span class="nav-burger-line"></span>
                <span class="nav-burger-line"></span>
            </button>

            <!-- Desktop nav links -->
            <div class="nav-links" id="nav-mobile-panel">
                <div class="nav-dropdown" data-nav-dropdown>
                    <button class="nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="modern-nav-categories">
                        <?= __('categories') ?>
                    </button>
                    <?php
                    $headerCategories = $headerCategories ?? [];
                    if (!empty($headerCategories)) {
                        renderModernThemeHeaderCategories($headerCategories, 0, 3, 'modern-nav-categories');
                    }
                    ?>
                </div>
                <a href="/products"><?php echo function_exists('__') ? __('products') : 'Products'; ?></a>
                <?php if (!empty($_SESSION['user'])): ?>
                    <a href="/profile">(<?php echo htmlspecialchars($_SESSION['user']['first_name'] ?? $_SESSION['user']['email']); ?>)</a>
                    <form action="/logout" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                        <button type="submit" style="border:none; background:none; color:inherit; cursor:pointer; padding:0; ">
                            <?php echo function_exists('__') ? __('logout') : 'Logout'; ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="/login"><?php echo function_exists('__') ? __('login') : 'Login'; ?></a>
                    <a href="/register"><?php echo function_exists('__') ? __('register') : 'Register'; ?></a>
                <?php endif; ?>
            </div>

            <div class="nav-actions">
                <!-- Мова -->
                <?php if (function_exists('get_supported_languages')): ?>
                <div class="lang-dropdown">
                    <button class="lang-dropbtn">
                        <?php
                        $current = get_current_language();
                        echo $current === 'ua' ? (function_exists('__') ? __('ukrainian') : 'UA') : (function_exists('__') ? __('english') : 'EN');
                        ?>
                        <span class="arrow">▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <?php foreach (get_supported_languages() as $lang): ?>
                            <?php if ($lang !== $current): ?>
                                <a href="/language/<?php echo $lang; ?>">
                                    <?php echo $lang === 'ua' ? (function_exists('__') ? __('ukrainian') : 'Ukrainian') : (function_exists('__') ? __('english') : 'English'); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Пошук -->
                <div class="nav-search-wrap">
                    <form action="/search" method="GET" role="search" class="nav-search-form">
                        <input type="search" name="q"
                            id="modern-search-input"
                            value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="<?= htmlspecialchars(__('search_placeholder') ?: 'Пошук...') ?>"
                            autocomplete="off"
                            class="nav-search-input">
                        <button type="submit" class="nav-search-btn" aria-label="Пошук">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#555" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                            </svg>
                        </button>
                    </form>
                    <div id="modern-search-dropdown" class="nav-search-dropdown"></div>
                </div>

                <!-- Кошик -->
                <a href="/cart" class="nav-cart-link" data-cart-link>
                    <svg xmlns="http://www.w3.org/2000/svg" height="20" width="22" viewBox="0 0 576 512" fill="white">
                        <path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1-96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/>
                    </svg>
                    <?php echo function_exists('__') ? __('cart') : 'Cart'; ?>
                    <span class="cart-counter" data-cart-count>0</span>
                </a>
            </div>

        </nav>
    </header>

    <div class="container">
        <main>
            <?php echo isset($content) ? $content : '<p>No content available</p>'; ?>
        </main>
    </div>

    <footer>
    <div class="footer-links">
        <?php foreach ($footerPages as $page): ?>
            <a href="/<?= $page['slug'] ?>"><?= htmlspecialchars($page['title']) ?></a>
        <?php endforeach; ?>
    </div>
        <p>&copy; 2026 MySite. <?php echo function_exists('__') ? (__('all_rights_reserved') ?? 'All rights reserved.') : 'All rights reserved.'; ?></p>
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
    const input    = document.getElementById('modern-search-input');
    const dropdown = document.getElementById('modern-search-dropdown');
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
                        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${item.name}</span>
                        <span style="font-weight:600;color:#2563eb;flex-shrink:0;">${item.price}</span>
                    </a>
                `).join('') + `<a href="/search?q=${encodeURIComponent(q)}" style="display:block;padding:.5rem;text-align:center;font-size:.83rem;color:#64748b;">🔍 Всі результати</a>`;
                dropdown.style.display = 'block';
            } catch {}
        }, 280);
    });
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.style.display = 'none';
    });
    input.addEventListener('keydown', (e) => { if (e.key === 'Escape') dropdown.style.display = 'none'; });
})();
</script>

<script>
// ── Burger menu ────────────────────────────────────────────────────────────
(function () {
    const burger = document.getElementById('nav-burger');
    const panel  = document.getElementById('nav-mobile-panel');
    if (!burger || !panel) return;

    burger.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = panel.classList.toggle('is-open');
        burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function (e) {
        if (!burger.contains(e.target) && !panel.contains(e.target)) {
            panel.classList.remove('is-open');
            burger.setAttribute('aria-expanded', 'false');
        }
    });

    // Dropdown у мобільному меню — toggle по кліку
    panel.querySelectorAll('[data-nav-dropdown]').forEach(function (dd) {
        const btn = dd.querySelector('.nav-dropdown-toggle');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = dd.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });
})();
</script>
</body>
</html>
