<?php

?>

<div class="page-header">
    <h1 class="page-title">Панель керування</h1>
</div>

<!-- Контейнер для графіка -->
<i class="fa-solid fa-chart-line"></i> Динаміка продажів
<div style="width: 100%; max-width: 800px; margin: 20px auto;">
    <canvas id="myChart"></canvas>
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
            <p><?php echo number_format($stats['total_sales'], 2); ?></p>
        </div>
    </div>
</div>

<div class="recent-orders-card">
    <div class="card-header">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Останні замовлення</h3>
        <a href="/admin/orders" class="view-all" title="Дивитись всі"><i class="fas fa-eye" aria-hidden="true"></i></a>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Клієнт</th>
                <th>Дата</th>
                <th>Сума</th>
                <th>Статус</th>
                <th>Дія</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_orders)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">
                        Замовлень поки немає
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><strong><?= htmlspecialchars($order['customer_name'] ?? 'Гість') ?></strong></td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td><?= number_format($order['total'], 2, '.', ' ') ?> ₴</td>
                    <td>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= $order['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="order_details.php?id=<?= $order['id'] ?>" class="btn-edit">
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

<script>
if (typeof Chart !== 'undefined') {
    const ctx = document.getElementById('myChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(54, 162, 235, 0.4)');
    gradient.addColorStop(1, 'rgba(54, 162, 235, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($final_labels); ?>, // Дні тижня: Пн (07.04)
            datasets: [{
                label: 'Продажі за тиждень',
                data: <?php echo json_encode($final_values); ?>, // Реальні суми
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
                        callback: function(value) { return value.toLocaleString() + ' ₴'; }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>