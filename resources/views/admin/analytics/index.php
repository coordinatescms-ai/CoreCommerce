<div class="page-header">
    <h1 class="page-title">Аналітика</h1>
</div>

<!-- Блок керування (кнопки та форма) -->
<div class="report-controls">
    <div class="filter-group">
        <i class="fas fa-hand-holding-usd" style="color: #64748b; margin-right: 10px;"></i>
        <a href="/admin/analytics/week" class="filter-btn <?= $period == 'week' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-day"></i> Тиждень
        </a>
        <a href="/admin/analytics/month" class="filter-btn <?= $period == 'month' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-week"></i> Місяць
        </a>
        <a href="/admin/analytics/year" class="filter-btn <?= $period == 'year' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-days"></i> Рік
        </a>
        <a href="?period=<?= $period ?>&export=csv" class="filter-btn" style="background: #22c55e; color: #fff;">
           <i class="fa-solid fa-file-csv"></i> Експорт CSV
        </a>
    </div>

    <form class="date-range-form" method="GET">
        <input type="date" name="from" value="<?= $_GET['from'] ?? '' ?>">
        <input type="date" name="to" value="<?= $_GET['to'] ?? '' ?>">
        <button type="submit" class="apply-btn">Застосувати</button>
    </form>
</div>

<!-- Обгортка графіка (ТЕПЕР ОКРЕМО) -->
<div class="chart-wrapper" style="margin-top: 20px;">
    <canvas id="myChart"></canvas>
</div>

<div class="recent-orders-card" style="margin-top: 30px;">
    <div class="card-header">
        <h3><i class="fa-solid fa-table-list"></i> Деталізація: <?= htmlspecialchars($title_text ?? '') ?></h3>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Період</th>
                <th>Замовлень</th> <!-- Нова колонка -->
                <th>Сума виручки</th>
                <th style="text-align: right;">Частка</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_sum = array_sum($values);
            $total_orders = array_sum($counts); // Рахуємо загальну кількість
            
            if ($total_sum > 0): 
                foreach ($labels as $key => $label): 
                    $val = $values[$key];
                    $count = $counts[$key]; // Беремо кількість із масиву
                    $percent = round(($val / $total_sum) * 100, 1);
            ?>
            <tr>
                <td><strong><?= $label ?></strong></td>
                <td><span style="color: #64748b;"><i class="fa-solid fa-box-open"></i></span> <?= $count ?> шт.</td>
                <td><?= number_format($val, 2, '.', ' ') ?> ₴</td>
                <td style="text-align: right;">
                    <small style="color: #36a2eb; font-weight: 600;"><?= $percent ?>%</small>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <tr style="background: #f8fafc; font-weight: bold; border-top: 2px solid #e2e8f0;">
                <td>РАЗОМ</td>
                <td><?= $total_orders ?> шт.</td>
                <td><?= number_format($total_sum, 2, '.', ' ') ?> ₴</td>
                <td style="text-align: right;">100%</td>
            </tr>
            
            <?php else: ?>
            <tr>
                <td colspan="4" style="text-align: center; padding: 20px; color: #94a3b8;">Дані відсутні</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="recent-orders-card" style="margin-top: 30px;">
    <div class="card-header">
        <h3><i class="fa-solid fa-fire" style="color: #f97316;"></i> Популярні товари (ТОП-5)</h3>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Товар</th>
                <th style="text-align: center;">Продано (шт)</th>
                <th style="text-align: right;">Виручка</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($popular_products)): ?>
                <tr><td colspan="3" style="text-align: center; padding: 20px;">Даних за цей період немає</td></tr>
            <?php else: ?>
                <?php foreach ($popular_products as $item): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #334155;"><?= htmlspecialchars($item['name']) ?></div>
                        <small style="color: #94a3b8;">ID: <?= $item['id'] ?></small>
                    </td>
                    <td style="text-align: center;">
                        <span class="status-badge" style="background: #f1f5f9; color: #475569;">
                            <?= $item['total_qty'] ?> шт.
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: bold; color: #059669;">
                        <?= number_format($item['total_revenue'], 2, '.', ' ') ?> ₴
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Перевіряємо, чи завантажена бібліотека
        if (typeof Chart === 'undefined') {
            console.error("Помилка: Бібліотека Chart.js не знайдена. Перевірте шлях до файлу chart.umd.js");
            return;
        }

        // Отримуємо дані з PHP безпечним способом
        // Якщо PHP видасть помилку, JS отримає порожній масив замість поломки скрипта
        const rawLabels = <?php echo json_encode($labels ?? []); ?>;
        const rawData = <?php echo json_encode($values ?? []); ?>;

        // Перевірка на наявність даних (якщо порожньо — покажемо пусту сітку для краси)
        const labels = rawLabels.length > 0 ? rawLabels : ['Немає даних'];
        const data = rawData.length > 0 ? rawData : [0];

        const ctx = document.getElementById('myChart');
        
        if (!ctx) {
            console.error("Помилка: Елемент <canvas id='myChart'> не знайдено на сторінці.");
            return;
        }

        // Налаштування градієнта
        const chartCtx = ctx.getContext('2d');
        const gradient = chartCtx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(54, 162, 235, 0.4)');
        gradient.addColorStop(1, 'rgba(54, 162, 235, 0)');

        // Ініціалізація графіка
        new Chart(chartCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Виручка (грн)',
                    data: data,
                    borderColor: '#36a2eb',
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#36a2eb',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    backgroundColor: gradient,
                    tension: 0.4 // Плавні лінії
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Обов'язково, щоб графік зайняв висоту chart-wrapper
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Сума: ' + context.parsed.y.toLocaleString() + ' ₴';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' ₴';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>