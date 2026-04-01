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
            .language-selector { justify-content: center; }
        }
    </style>
</head>
<body>
    <nav>
        <div>
            <a href="/"><?= __('home') ?></a> | 
            <a href="/products"><?= __('products') ?></a> | 
            <a href="/cart"><?= __('cart') ?></a> |
            <?php if (!empty($_SESSION['user'])): ?>
                <a href="/profile"><?= $_SESSION['user']['first_name'] ?? $_SESSION['user']['email'] ?></a> |
                <a href="/logout"><?= __('logout') ?></a>
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
</body>
</html>
