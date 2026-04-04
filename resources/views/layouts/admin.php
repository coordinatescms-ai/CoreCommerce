<!DOCTYPE html>
<html lang="<?= get_current_language() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >
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
        .page-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .page-title { font-size: 1.4rem; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; overflow: hidden; }
        .card-body { padding: 1rem; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.45rem 0.75rem;
            border-radius: 6px;
            border: 1px solid transparent;
            text-decoration: none;
            line-height: 1;
            cursor: pointer;
            transition: background-color .2s ease, border-color .2s ease, color .2s ease;
        }
        .btn i { pointer-events: none; }
        .btn-primary { background: #2563eb; border-color: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; text-decoration: none; }
        .btn-outline { background: #fff; border-color: #d1d5db; color: #374151; }
        .btn-outline:hover { background: #f9fafb; border-color: #9ca3af; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; }
        th { font-weight: 600; color: #111827; background: #f9fafb; }
        @media (max-width: 900px) {
            .container { padding: 0 0.75rem; }
            main { padding: 1rem; }
        }
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
