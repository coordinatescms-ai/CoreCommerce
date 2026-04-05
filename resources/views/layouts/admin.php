<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'ua'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo htmlspecialchars($site_name ?? 'MySite'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --bg-color: #f8fafc;
            --sidebar-bg: #1e293b;
            --sidebar-text: #f1f5f9;
            --text-color: #334155;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--bg-color); color: var(--text-color); display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; position: fixed; height: 100vh; transition: all 0.3s; z-index: 1000; }
        .sidebar-header { padding: 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 1.2rem; font-weight: bold; }
        .sidebar-menu { flex: 1; padding: 1rem 0; overflow-y: auto; }
        .menu-item { display: flex; align-items: center; padding: 0.75rem 1.5rem; color: var(--sidebar-text); text-decoration: none; transition: 0.2s; border-left: 4px solid transparent; }
        .menu-item:hover { background: rgba(255,255,255,0.05); border-left-color: var(--primary-color); }
        .menu-item.active { background: rgba(255,255,255,0.1); border-left-color: var(--primary-color); }
        .menu-item i { margin-right: 0.75rem; width: 20px; text-align: center; }

        /* Main Content */
        .main-content { flex: 1; margin-left: 260px; display: flex; flex-direction: column; width: calc(100% - 260px); }
        
        /* Top Header */
        .top-header { background: white; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 900; }
        .user-nav { display: flex; align-items: center; gap: 1rem; }
        .user-nav a { text-decoration: none; color: var(--text-color); font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; }

        /* Content Area */
        .content-body { padding: 2rem; flex: 1; }
        .page-header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.5rem; font-weight: bold; color: #0f172a; }

        /* Dashboard Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid var(--border-color); display: flex; align-items: center; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 1rem; }
        .stat-info h3 { font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem; }
        .stat-info p { font-size: 1.5rem; font-weight: bold; color: #1e293b; }

        /* Forms & Cards */
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid var(--border-color); margin-bottom: 1.5rem; }
        .card-header { padding: 1.25rem; border-bottom: 1px solid var(--border-color); font-weight: bold; }
        .card-body { padding: 1.5rem; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; cursor: pointer; transition: 0.2s; text-decoration: none; border: 1px solid transparent; font-size: 0.9rem; gap: 0.5rem; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--secondary-color); }
        .btn-success { background: var(--success-color); color: white; }
        .btn-danger { background: var(--danger-color); color: white; }

        /* Alerts */
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Form Controls */
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 0.625rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shopping-cart"></i> MySite Admin
        </div>
        <nav class="sidebar-menu">
            <a href="/admin" class="menu-item <?php echo $request_uri === '/admin' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/admin/orders" class="menu-item">
                <i class="fas fa-shopping-bag"></i> Замовлення
            </a>
            <a href="/admin/products" class="menu-item">
                <i class="fas fa-box"></i> Товари
            </a>
            <a href="/admin/categories" class="menu-item">
                <i class="fas fa-list"></i> Категорії
            </a>
            <a href="/admin/attributes" class="menu-item <?php echo strpos($request_uri, '/admin/attributes') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> Атрибути
            </a>
            <a href="/admin/users" class="menu-item">
                <i class="fas fa-users"></i> Користувачі
            </a>
            <a href="/admin/plugins" class="menu-item">
                <i class="fas fa-plug"></i> Плагіни
            </a>
            <a href="/admin/themes" class="menu-item">
                <i class="fas fa-adjust"></i> Теми
            </a>
            <a href="/admin/settings" class="menu-item <?php echo $request_uri === '/admin/settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Налаштування
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <a href="/" target="_blank" class="btn btn-outline" style="border: 1px solid #ddd;">
                    <i class="fas fa-external-link-alt"></i> Перейти на сайт
                </a>
            </div>
            <div class="user-nav">
                <span>Привіт, <strong><?php echo htmlspecialchars($_SESSION['user']['first_name']); ?></strong></span>
                <a href="/logout"><i class="fas fa-sign-out-alt"></i> Вийти</a>
            </div>
        </header>

        <div class="content-body">
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php echo $content; ?>
        </div>
    </main>
</body>
</html>
