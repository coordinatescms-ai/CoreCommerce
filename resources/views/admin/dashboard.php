<div class="page-header">
    <h1 class="page-title">Панель керування</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3>Користувачі</h3>
            <p><?php echo $stats['users_count']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #dcfce7; color: #15803d;">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <div class="stat-info">
            <h3>Замовлення</h3>
            <p><?php echo $stats['orders_count']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fef9c3; color: #a16207;">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <h3>Товари</h3>
            <p><?php echo $stats['products_count']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #f3e8ff; color: #7e22ce;">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info">
            <h3>Продажі</h3>
            <p><?php echo number_format($stats['total_sales'], 2); ?> грн</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-rocket"></i> Швидкі дії
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="/admin/products/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Додати товар
            </a>
            <a href="/admin/categories/create" class="btn btn-primary">
                <i class="fas fa-folder-plus"></i> Нова категорія
            </a>
            <a href="/admin/settings" class="btn btn-primary">
                <i class="fas fa-cog"></i> Налаштування магазину
            </a>
            <a href="/admin/plugins" class="btn btn-primary">
                <i class="fas fa-plug"></i> Керування плагінами
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-info-circle"></i> Стан системи
    </div>
    <div class="card-body">
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 0.75rem 0; color: #64748b;">Версія PHP:</td>
                <td style="padding: 0.75rem 0; font-weight: bold;"><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 0.75rem 0; color: #64748b;">Сервер:</td>
                <td style="padding: 0.75rem 0; font-weight: bold;"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 0.75rem 0; color: #64748b;">Режим магазину:</td>
                <td style="padding: 0.75rem 0;">
                    <span class="badge" style="background: #dcfce7; color: #15803d; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Відкритий</span>
                </td>
            </tr>
        </table>
    </div>
</div>
