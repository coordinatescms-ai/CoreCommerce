<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'ua'; ?>">
<head>
    <?php $assetVersion = urlencode((string) (get_setting('asset_version', '1'))); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo htmlspecialchars($site_name ?? 'MySite'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="/js/chart.umd.js?v=<?php echo $assetVersion; ?>"></script>
    <script src="/js/updater.js?v=<?php echo $assetVersion; ?>" defer></script>
    
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

    .recent-orders-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        margin-top: 30px;
    }
    .card-header {
        display: flex;
        /* ВИПРАВЛЕННЯ: притискаємо все вліво */
        justify-content: flex-start; 
        align-items: center;
        margin-bottom: 20px;
        /* Додаємо відступ між заголовком і посиланням */
        gap: 20px; 
    }
    .card-header h3 { 
        margin: 0; 
        font-size: 18px; 
        color: #334155; 
        /* Гарантуємо, що текст всередині h3 теж зліва */
        text-align: left; 
    }
    .view-all { 
        color: #36a2eb; 
        text-decoration: none; 
        font-size: 14px; 
        font-weight: 600; 
    }

    .admin-table { 
        width: 100%; 
        border-collapse: collapse; 
        /* Додаємо на всякий випадок */
        text-align: left; 
    }
    .admin-table th { 
        text-align: left; 
        padding: 12px; 
        border-bottom: 2px solid #f1f5f9; 
        color: #64748b; 
        font-size: 14px; 
    }
    .admin-table td { 
        text-align: left; 
        padding: 12px; 
        border-bottom: 1px solid #f1f5f9; 
        color: #334155; 
        font-size: 14px; 
    }

    .settings-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }
    .grid-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .input-group { margin-bottom: 15px; }
    .input-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #334155; }
    .input-group input, .input-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
    }
    .hint { font-size: 12px; color: #64748b; margin-top: 5px; }
    code { background: #f1f5f9; padding: 2px 4px; border-radius: 4px; color: #e11d48; }

    .custom-select {
        width: 100%;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background-color: #fff;
        color: #334155;
        cursor: pointer;
        appearance: none; /* Прибираємо стандартну стрілку браузера */
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://w3.org' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 16px;
    }
    .custom-select:focus {
        border-color: #36a2eb;
        outline: none;
    }

   .report-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        background: #fff;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .filter-btn {
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #64748b;
        font-weight: 500;
        margin-right: 5px;
        transition: 0.3s;
    }
    .filter-btn.active {
        background: #36a2eb;
        color: #fff;
    }
    .filter-btn:hover:not(.active) {
        background: #e2e8f0;
    }
    /* Стиль для форми вибору дат */
    .date-range-form input {
        padding: 7px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
    }

    /* Додаткові стилі для залишків */
    .stock-label {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
    }
    .low-stock { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
    .out-of-stock { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
    
    .btn-edit { color: #64748b; font-size: 18px; text-decoration: none; }
    .btn-edit:hover { color: #36a2eb; }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1100;
                box-shadow: 4px 0 20px rgba(0,0,0,.3);
            }
            .sidebar.is-open { transform: translateX(0); }
            .main-content { margin-left: 0 !important; width: 100%; }

            /* Overlay */
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.45);
                z-index: 1050;
            }
            .sidebar-overlay.is-visible { display: block; }

            /* Burger в топ-хедері */
            .admin-burger {
                display: flex !important;
            }

            /* Таблиці: горизонтальний скрол */
            .admin-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

            /* Картки в grid — по 1 колонці */
            .stats-grid { grid-template-columns: 1fr 1fr !important; }

            /* Форми — поля по всій ширині */
            .form-group { width: 100% !important; }

            /* Прибираємо зайві колонки в таблицях */
            .admin-table .hide-mobile { display: none !important; }

            /* Кнопки дій — менші */
            .btn-edit, .btn-delete { padding: .3rem .5rem; font-size: .8rem; }

            /* Page header */
            .page-header { flex-direction: column; align-items: flex-start; gap: .5rem; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr !important; }
            .content-body { padding: .75rem !important; }
            .card-body { padding: .75rem !important; }
            .top-header { padding: .5rem .75rem !important; }
            .page-title { font-size: 1.1rem !important; }
        }

        .admin-burger {
            display: none;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            background: none;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            color: #64748b;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .admin-burger:hover { background: #f1f5f9; }

        /* Таблиці — горизонтальний скрол на мобільних */
        @media (max-width: 768px) {
            .content-body table {
                min-width: 600px;
            }
            .content-body > *:has(table),
            .card-body:has(table),
            .orders-list,
            .admin-table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
    <script>
        var currencySymbol = <?= json_encode(
            \App\Core\Database\DB::query('SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1')
                ->fetchColumn() ?: '₴'
        ) ?>;
        window.CURRENCY_SYMBOL = currencySymbol;

        // Рядки для JS (alert, toast тощо)
        window.LANG = {
            mail_sent:                 <?= json_encode(__('mail_sent')) ?>,
            gallery_limit_reached:     <?= json_encode(__('gallery_limit_reached')) ?>,
            image_upload_error:        <?= json_encode(__('image_upload_error')) ?>,
            reason_required:           <?= json_encode(__('reason_required')) ?>,
            error:                     <?= json_encode(__('error')) ?>,
            unknown_error:             <?= json_encode(__('unknown_error')) ?>,
            enabled:                   <?= json_encode(__('enabled')) ?>,
            disabled:                  <?= json_encode(__('disabled')) ?>,
            collapse:                  <?= json_encode(__('collapse')) ?>,
            expand:                    <?= json_encode(__('expand')) ?>,
            no_data:                   <?= json_encode(__('no_data')) ?>,
            delete_action:             <?= json_encode(__('delete')) ?>,
            block_action:              <?= json_encode(__('block_action')) ?>,
            publish_action:            <?= json_encode(__('publish_action')) ?>,
            order_new:                 <?= json_encode(__('order')) ?>,
            no_attributes_for_category:<?= json_encode(__('no_attributes_for_category')) ?>,
            checking:                  <?= json_encode(__('checking')) ?>,
            generate_xml:              <?= json_encode(__('generate_xml')) ?>,
            check_connection:          <?= json_encode(__('check_connection')) ?>,
            generating:                <?= json_encode(__('generating')) ?>,
            saving:                    <?= json_encode(__('saving')) ?>,
            nothing_found:             <?= json_encode(__('nothing_found')) ?>,
            order_history_empty:       <?= json_encode(__('order_history_empty')) ?>,
            confirm_delete_review:     <?= json_encode(__('confirm_delete_review')) ?>,
            confirm_reset_migration:   <?= json_encode(__('confirm_reset_migration')) ?>,
            add_at_least_one_product:  <?= json_encode(__('add_at_least_one_product')) ?>,
            sync_error:                <?= json_encode(__('sync_error')) ?>,
            load_order_error:          <?= json_encode(__('load_order_error')) ?>,
            save_error:                <?= json_encode(__('save_error')) ?>,
            ban_reason_prompt:         <?= json_encode(__('ban_reason_prompt')) ?>,
            bonus_reason_prompt:       <?= json_encode(__('bonus_reason_prompt')) ?>,
        };
    </script>
</head>
<body>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
    <!-- Sidebar -->
    <aside class="sidebar" id="admin-sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shopping-cart"></i> MySite Admin
        </div>
        <nav class="sidebar-menu">
            <a href="/admin" class="menu-item <?php echo $request_uri === '/admin' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> <?= __('admin_dashboard') ?>
            </a>
            <a href="/admin/orders" class="menu-item <?php echo strpos($request_uri, '/admin/orders') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-shopping-bag"></i> <?= __('admin_orders') ?>
            </a>
            <a href="/admin/products" class="menu-item">
                <i class="fas fa-box"></i> <?= __('admin_products') ?>
            </a>
            <a href="/admin/categories" class="menu-item">
                <i class="fas fa-list"></i> <?= __('admin_categories') ?>
            </a>
            <a href="/admin/attributes" class="menu-item <?php echo strpos($request_uri, '/admin/attributes') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> <?= __('admin_attributes') ?>
            </a>
            <a href="/admin/users" class="menu-item <?php echo strpos($request_uri, '/admin/users') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> <?= __('admin_users') ?>
            </a>
            <a href="/admin/plugins" class="menu-item <?php echo strpos($request_uri, '/admin/plugins') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-plug"></i> <?= __('admin_plugins') ?>
            </a>
            <a href="/admin/themes" class="menu-item">
                <i class="fas fa-adjust"></i> <?= __('admin_themes') ?>
            </a>
            <a href="/admin/content" class="menu-item">
                <i class="fas fa-file-lines"></i> <?= __('admin_content') ?>
            </a>
            <a href="/admin/settings" class="menu-item <?php echo $request_uri === '/admin/settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> <?= __('admin_settings') ?>
            </a>
            <a href="/admin/system" class="menu-item <?php echo strpos($request_uri, '/admin/system') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-server"></i> <?= __('admin_system') ?>
            </a>
            <a href="/admin/analytics/week" class="menu-item <?php echo strpos($request_uri, '/admin/analytics') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> <?= __('admin_analytics') ?>
            </a>
            <a href="/admin/stocks" class="menu-item <?php echo strpos($request_uri, '/admin/stocks') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-warehouse"></i> <?= __('admin_stocks') ?>
            </a>
            <a href="/admin/reviews" class="menu-item <?php echo strpos($request_uri, '/admin/reviews') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-comments"></i> <?= __('admin_reviews') ?>
            </a>
            <a href="/admin/migrations" class="menu-item <?php echo strpos($request_uri, '/admin/migrations') === 0 ? 'active' : ''; ?>">
                <i class="fas fa-database"></i> <?= __('admin_migrations') ?>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="admin-burger" id="admin-burger" aria-label="Меню" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="/" target="_blank" class="btn btn-outline" style="border: 1px solid #ddd;">
                    <i class="fas fa-external-link-alt"></i> <?= __('view_site') ?>
                </a>
            </div>
            <div class="user-nav">
                <span><?= __('hi') ?> <strong><?php echo htmlspecialchars($_SESSION['user']['first_name']); ?></strong></span>
                <form action="/logout" method="POST" style="display:inline-block; margin:0;">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                    <button type="submit" class="btn btn-link" style="padding:0; border:none; background:none; color:inherit; cursor:pointer;">
                        <i class="fas fa-sign-out-alt"></i> <?= __('logout') ?>
                    </button>
                </form>
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
<script>
// ── Admin sidebar burger ───────────────────────────────────────────────────
(function () {
    const burger  = document.getElementById('admin-burger');
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (!burger || !sidebar) return;

    function open() {
        sidebar.classList.add('is-open');
        if (overlay) overlay.classList.add('is-visible');
        burger.setAttribute('aria-expanded', 'true');
    }
    function close() {
        sidebar.classList.remove('is-open');
        if (overlay) overlay.classList.remove('is-visible');
        burger.setAttribute('aria-expanded', 'false');
    }

    burger.addEventListener('click', function (e) {
        e.stopPropagation();
        sidebar.classList.contains('is-open') ? close() : open();
    });

    if (overlay) overlay.addEventListener('click', close);

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
</script>
</body>
</html>
