<!DOCTYPE html>
<html lang="<?= get_current_language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySite</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
        nav { background: #f4f4f4; padding: 1rem; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        nav a { margin-right: 1rem; text-decoration: none; color: #333; }
        nav a:hover { color: #007bff; }
        .nav-links { display: flex; align-items: center; flex-wrap: wrap; gap: 0.35rem; }
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
                echo '<a href="/category/' . htmlspecialchars($category['slug']) . '">' . htmlspecialchars($category['name']) . '</a>';

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
            <a href="/"><?= __('home') ?></a> | 
            <a href="/products"><?= __('products') ?></a> | 
            <a href="/cart"><?= __('cart') ?></a> |
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
        <p>&copy; 2024 MySite. <?= __('all_rights_reserved') ?? 'All rights reserved.' ?></p>
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
