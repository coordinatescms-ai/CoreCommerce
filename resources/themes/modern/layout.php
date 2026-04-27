<!DOCTYPE html>
<html lang="<?php echo function_exists('get_current_language') ? get_current_language() : 'ua'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($pageSeo['meta_title'] ?? 'Мій Магазин')) ?></title>
    <?php if (!empty($pageSeo['meta_description'])): ?>
        <meta name="description" content="<?= htmlspecialchars((string) $pageSeo['meta_description']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo class_exists('App\\Core\\View\\View') ? \App\Core\View\View::getThemeStyle() : '/resources/themes/modern/style.css'; ?>">
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

        .language-selector {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .language-selector span {
            font-weight: 500;
        }

        .language-selector a,
        .language-selector strong {
            padding: 0.4rem 0.8rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: white;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .language-selector a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .language-selector strong {
            background-color: rgba(255, 255, 255, 0.3);
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
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                order: 3;
                width: 100%;
                justify-content: center;
                gap: 1rem;
            }

            .nav-dropdown {
                width: 100%;
                text-align: center;
            }

            .nav-dropdown-menu {
                position: static;
                max-height: none;
                margin-top: 0.5rem;
            }

            .nav-dropdown.is-open > .nav-dropdown-menu {
                display: block;
            }

            .nav-dropdown:hover > .nav-dropdown-menu {
                display: none;
            }

            .language-selector {
                order: 2;
                justify-content: center;
            }

            h1 {
                font-size: 1.8rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }

            .container {
                padding: 1rem;
            }

            main {
                padding: 1rem;
            }
        }



        @media (max-width: 480px) {
            .nav-links {
                gap: 0.5rem;
            }

            .nav-links a {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            h2 {
                font-size: 1.2rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                echo '<a href="/category/' . htmlspecialchars($category['slug']) . '">' . htmlspecialchars($category['name']) . '</a>';

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
            <div class="nav-links">
                <a href="/products"><?php echo function_exists('__') ? __('products') : 'Products'; ?></a>
                <a href="/cart"><svg xmlns="http://www.w3.org/2000/svg" height="20" width="22" viewBox="0 0 576 512" fill="white" style="margin-right: 10px; vertical-align: middle;">
                    <path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1-96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/>
                </svg><?php echo function_exists('__') ? __('cart') : 'Cart'; ?></a>
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
                <?php if (!empty($_SESSION['user'])): ?>
                    <a href="/profile"><?php echo function_exists('__') ? __('profile') : 'Profile'; ?> (<?php echo htmlspecialchars($_SESSION['user']['first_name'] ?? $_SESSION['user']['email']); ?>)</a>
                    <form action="/logout" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                        <button type="submit" style="border:none; background:none; color:inherit; cursor:pointer; padding:0; text-decoration:underline;">
                            <?php echo function_exists('__') ? __('logout') : 'Logout'; ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="/login"><?php echo function_exists('__') ? __('login') : 'Login'; ?></a>
                    <a href="/register"><?php echo function_exists('__') ? __('register') : 'Register'; ?></a>
                <?php endif; ?>
            </div>
            <div class="language-selector">
                <span><?php echo function_exists('__') ? __('language') : 'Language'; ?>:</span>
                <?php if (function_exists('get_supported_languages')): ?>
                    <?php foreach (get_supported_languages() as $lang): ?>
                        <?php if ($lang === get_current_language()): ?>
                            <strong><?php echo $lang === 'ua' ? (function_exists('__') ? __('ukrainian') : 'Ukrainian') : (function_exists('__') ? __('english') : 'English'); ?></strong>
                        <?php else: ?>
                            <a href="/language/<?php echo $lang; ?>"><?php echo $lang === 'ua' ? (function_exists('__') ? __('ukrainian') : 'Ukrainian') : (function_exists('__') ? __('english') : 'English'); ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
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
    </script>
</body>
</html>
