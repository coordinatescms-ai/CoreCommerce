<!DOCTYPE html>
<html lang="<?= get_current_language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f6fa; color: #1f2937; }
        header { background: #111827; color: #fff; padding: 1rem 1.25rem; }
        nav { display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem; }
        nav a { color: #e5e7eb; text-decoration: none; }
        nav a:hover { color: #fff; text-decoration: underline; }
        .container { max-width: 1200px; margin: 1.5rem auto; padding: 0 1rem; }
        .flash {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }
        .flash-success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
        .flash-error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        main { background: #fff; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); }
    </style>
</head>
<body>
    <header>
        <h1>Admin Panel</h1>
        <nav>
            <a href="/admin/products">Products</a>
            <a href="/admin/categories">Categories</a>
            <a href="/admin/themes">Themes</a>
            <a href="/">Back to site</a>
        </nav>
    </header>
    <div class="container">
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="flash flash-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="flash flash-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <main>
            <?= $content ?>
        </main>
    </div>
</body>
</html>
