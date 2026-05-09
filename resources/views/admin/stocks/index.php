<div class="page-header">
    <h1 class="page-title">Склад</h1>
</div>

<div class="card">
    <div class="card-header">Швидке коригування</div>
    <div class="card-body">
        <form method="POST" action="/admin/stocks/adjust" style="display:grid;grid-template-columns:2fr 1fr 1fr 2fr auto;gap:12px;align-items:end;">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
            <div><label>SKU</label><input class="form-control" name="sku" required></div>
            <div><label>Кількість</label><input class="form-control" type="number" min="1" name="qty" required></div>
            <div><label>Тип</label><select class="form-control" name="type"><option value="add">Додати</option><option value="remove">Списати</option></select></div>
            <div><label>Коментар</label><input class="form-control" name="comment"></div>
            <button class="btn btn-primary" type="submit">Застосувати</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Поточні залишки</div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>ID</th><th>Назва</th><th>SKU</th><th>К-сть</th><th>Резерв</th><th>Доступно</th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo (int) $product['id']; ?></td>
                    <td><?php echo htmlspecialchars((string) $product['name']); ?></td>
                    <td><?php echo htmlspecialchars((string) $product['sku']); ?></td>
                    <td><?php echo (int) $product['quantity']; ?></td>
                    <td><?php echo (int) $product['reserved']; ?></td>
                    <td><?php echo max(0, (int) $product['quantity'] - (int) $product['reserved']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">Останні 10 рухів</div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>Дата</th><th>SKU</th><th>Тип</th><th>К-сть</th><th>Коментар</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr><td><?php echo htmlspecialchars((string) $log['created_at']); ?></td><td><?php echo htmlspecialchars((string) $log['sku']); ?></td><td><?php echo htmlspecialchars((string) $log['event_type']); ?></td><td><?php echo (int) $log['qty']; ?></td><td><?php echo htmlspecialchars((string) $log['comment']); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
