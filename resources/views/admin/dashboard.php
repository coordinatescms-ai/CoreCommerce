<?php

?>

<div class="page-header">
    <h1 class="page-title"><?= __('admin_dashboard') ?></h1>
</div>

<!-- Контейнер для графіка -->
<i class="fa-solid fa-chart-line"></i> <?= __('dashboard_sales_week') ?>
<div style="width: 100%; max-width: 800px; margin: 20px auto;">
    <canvas id="myChart"></canvas>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?= __('admin_users') ?></h3>
            <p><?php echo $stats['users_count']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #dcfce7; color: #15803d;">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <div class="stat-info">
            <h3><?= __('admin_orders') ?></h3>
            <p><?php echo $stats['orders_count']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #fef9c3; color: #a16207;">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-info">
            <h3><?= __('admin_products') ?></h3>
            <p><?php echo $stats['products_count']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #f3e8ff; color: #7e22ce;">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info">
            <h3><?= __('dashboard_sales') ?></h3>
            <p><?php echo number_format($stats['total_sales'], 2); ?></p>
        </div>
    </div>
</div>

<div class="recent-orders-card">
    <div class="card-header">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> <?= __('admin_orders') ?></h3>
        <a href="/admin/orders" class="view-all" title="<?= __('all') ?>"><i class="fas fa-eye" aria-hidden="true"></i></a>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th><?= __('order_client') ?></th>
                <th><?= __('date') ?></th>
                <th><?= __('order_sum') ?></th>
                <th><?= __('status') ?></th>
                <th><?= __('action') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentOrders)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">
                        <?= __('nothing_found') ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><strong><?= htmlspecialchars($order['customer_name'] ?? __('order_guest')) ?></strong></td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td><?= format_price($order['total']) ?></td>
                    <td>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= $order['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="/admin/orders" class="btn-edit">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-rocket"></i> <?= __('dashboard_quick_actions') ?>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="/admin/products/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?= __('product_new') ?>
            </a>
            <a href="/admin/categories/create" class="btn btn-primary">
                <i class="fas fa-folder-plus"></i> <?= __('category_new') ?>
            </a>
            <a href="/admin/settings" class="btn btn-primary">
                <i class="fas fa-cog"></i> <?= __('settings_shop_name') ?>
            </a>
            <a href="/admin/plugins" class="btn btn-primary">
                <i class="fas fa-plug"></i> <?= __('plugins_manage') ?>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-info-circle"></i> <?= __('system_title') ?>
    </div>
    <div class="card-body">
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 0.75rem 0; color: #64748b;"><?= __('system_php_version') ?>:</td>
                <td style="padding: 0.75rem 0; font-weight: bold;"><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 0.75rem 0; color: #64748b;"><?= __('system_server') ?>:</td>
                <td style="padding: 0.75rem 0; font-weight: bold;"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 0.75rem 0; color: #64748b;"><?= __('content_shop_mode') ?>:</td>
                <td style="padding: 0.75rem 0;">
                    <span class="badge" style="background: #dcfce7; color: #15803d; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;"><?= __('content_opened') ?></span>
                </td>
            </tr>
        </table>
        <div style="margin-top: 1rem;">
            <form action="/admin/clear-cache" method="POST" style="display:inline;">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <button type="submit" class="btn" style="background:#f59e0b;color:#fff;border-color:#d97706;">
                    <i class="fas fa-broom"></i> <?= __('system_actions') ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
if (typeof Chart !== 'undefined') {
    const ctx = document.getElementById('myChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(54, 162, 235, 0.4)');
    gradient.addColorStop(1, 'rgba(54, 162, 235, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($final_labels); ?>,
            datasets: [{
                label: '<?= __('dashboard_sales_week') ?>',
                data: <?php echo json_encode($final_values); ?>,
                borderColor: '#36a2eb',
                borderWidth: 3,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#36a2eb',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true,
                backgroundColor: gradient,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return value.toLocaleString() + ' ' + currencySymbol; }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>
