<?php
$statusLabels = [
    'new' => 'Новий',
    'confirmed' => 'Підтверджено',
    'processing' => 'Комплектується',
    'shipped' => 'Відправлено',
    'delivered' => 'Доставлено',
    'completed' => 'Завершено',
    'cancelled' => 'Скасовано',
    'returned' => 'Повернено',
];
?>
<style>
    .orders-page { max-width: 100%; }
    .orders-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; }
    .orders-header h1 { margin: 0; font-size: 24px; }
    .orders-toolbar { display: flex; align-items: center; gap: 12px; }
    .orders-switcher { display: inline-flex; background: #fff; border: 1px solid #d9e1ef; border-radius: 10px; padding: 4px; }
    .orders-switcher button, .btn { border: 0; background: transparent; padding: 10px 14px; cursor: pointer; border-radius: 8px; font-weight: 600; color: #6b7280; }
    .orders-switcher button.active { color: #3b82f6; background: #dbeafe; }
    .btn { background: #2563eb; color: #fff; }
    .btn.secondary { background: #fff; color: #1f2937; border: 1px solid #d9e1ef; }
    .orders-view { display: none; }
    .orders-view.active { display: block; }
    .orders-kanban { display: grid; grid-template-columns: repeat(4, minmax(220px, 1fr)); gap: 14px; align-items: start; }
    .orders-column { background: #fff; border: 1px solid #d9e1ef; border-radius: 12px; min-height: 380px; overflow: hidden; }
    .orders-column.drag-over { border-color: #2563eb; box-shadow: inset 0 0 0 1px #2563eb; }
    .orders-column__head { padding: 12px 14px; border-bottom: 1px solid #d9e1ef; font-weight: 700; display: flex; justify-content: space-between; align-items: center; }
    .orders-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 8px; border-radius: 999px; font-size: 12px; color: #3b82f6; background: #dbeafe; }
    .orders-column__body { padding: 10px; display: grid; gap: 10px; min-height: 320px; }
    .order-card { border: 1px solid #d9e1ef; border-radius: 10px; background: #fff; padding: 10px; box-shadow: 0 1px 2px rgba(0,0,0,.04); cursor: pointer; }
    .order-card.dragging { opacity: 0.5; cursor: grabbing; }
    .order-card__id { font-weight: 700; margin-bottom: 6px; }
    .order-card__line { font-size: 14px; color: #6b7280; }
    .orders-table-box { background: #fff; border: 1px solid #d9e1ef; border-radius: 12px; padding: 12px; }
    .orders-table-filters { display: flex; gap: 8px; margin-bottom: 12px; }
    .orders-table { width: 100%; border-collapse: collapse; }
    .orders-table th, .orders-table td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; }
    .orders-table tbody tr { cursor: pointer; }
    .orders-modal { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: none; align-items: center; justify-content: center; z-index: 1000; }
    .orders-modal.open { display: flex; }
    .orders-modal__dialog { background: #fff; width: min(980px,95vw); max-height: 92vh; overflow: auto; border-radius: 12px; padding: 18px; }
    .modal-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 10px 14px; }
    .modal-grid label { display: block; font-size: 13px; color: #6b7280; margin-bottom: 4px; }
    .modal-grid input,.modal-grid select,.modal-grid textarea { width: 100%; border: 1px solid #d9e1ef; border-radius: 8px; padding: 8px 10px; }
    .modal-section { margin-top: 16px; }
    .modal-items-table { width: 100%; border-collapse: collapse; }
    .modal-items-table th,.modal-items-table td { border-bottom: 1px solid #e5e7eb; padding: 8px; }
    .modal-actions { display:flex; gap:8px; justify-content: flex-end; margin-top: 14px; }
    .hidden { display:none!important; }
    @media (max-width: 1200px) { .orders-kanban { grid-template-columns: repeat(2,minmax(220px,1fr)); } }
    @media (max-width: 768px) { .modal-grid { grid-template-columns: 1fr; } }
</style>

<div class="orders-page">
    <div class="orders-header">
        <h1>Замовлення</h1>
        <div class="orders-toolbar">
            <button type="button" class="btn secondary" id="syncLogisticsBtn">Синхронізувати ТТН</button>
            <button type="button" class="btn" id="createOrderBtn">+ Нове замовлення</button>
            <div class="orders-switcher" role="tablist" aria-label="Перемикач режиму перегляду замовлень">
                <button type="button" class="active" data-target="ordersKanbanView">КАНБАН</button>
                <button type="button" data-target="ordersTableView">ТАБЛИЦЯ</button>
            </div>
        </div>
    </div>

    <section id="ordersKanbanView" class="orders-view active" aria-label="Канбан режим замовлень">
        <div class="orders-kanban">
            <?php foreach (($kanbanColumns ?? []) as $statusCode => $statusLabel): ?>
                <?php $cardsInColumn = array_values(array_filter($orders ?? [], static fn(array $card): bool => ($card['status'] ?? '') === $statusCode)); ?>
                <article class="orders-column" data-status="<?= htmlspecialchars((string)$statusCode, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="orders-column__head"><span><?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?></span><span class="orders-badge"><?= count($cardsInColumn) ?></span></div>
                    <div class="orders-column__body">
                        <?php foreach ($cardsInColumn as $order): ?>
                            <div class="order-card" draggable="true" data-order-id="<?= (int)$order['id'] ?>" data-status="<?= htmlspecialchars((string)($order['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="order-card__id">#<?= (int)$order['id'] ?></div>
                                <div class="order-card__line">Клієнт: <?= htmlspecialchars((string)($order['customer_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="order-card__line">Сума: <?= number_format((float)($order['total'] ?? 0), 2, '.', ' ') ?> ₴</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="ordersTableView" class="orders-view" aria-label="Табличний режим замовлень">
        <div class="orders-table-box">
            <div class="orders-table-filters">
                <select id="statusFilter">
                    <option value="">Всі статуси</option>
                    <?php foreach (($allStatuses ?? []) as $status): ?>
                        <option value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <table class="orders-table">
                <thead><tr><th>ID</th><th>Клієнт</th><th>Телефон</th><th>Сума</th><th>Статус</th><th>Оплата</th><th>Доставка</th><th>Дата</th></tr></thead>
                <tbody id="ordersTableBody">
                    <?php foreach (($orders ?? []) as $row): ?>
                        <tr data-order-id="<?= (int)$row['id'] ?>" data-status="<?= htmlspecialchars((string)($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td>#<?= (int)$row['id'] ?></td>
                            <td><?= htmlspecialchars((string)($row['customer_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($row['customer_phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)($row['total'] ?? 0), 2, '.', ' ') ?> ₴</td>
                            <td><?= htmlspecialchars((string)($statusLabels[$row['status']] ?? $row['status'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($row['payment_method'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($row['delivery_method'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($row['created_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="orders-modal" id="orderModal">
    <div class="orders-modal__dialog">
        <h2 id="modalTitle">Деталі замовлення</h2>
        <form id="orderForm">
            <input type="hidden" name="id" id="orderIdField">
            <div class="modal-grid">
                <div><label>Ім'я</label><input required name="customer_name" id="customerName"></div>
                <div><label>Телефон</label><input required name="customer_phone" id="customerPhone"></div>
                <div><label>Email</label><input name="customer_email" id="customerEmail"></div>
                <div><label>Статус</label><select name="status" id="orderStatus">
                    <?php foreach (($allStatuses ?? []) as $status): ?><option value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
                <div><label>Доставка</label><input name="delivery_method" id="deliveryMethod"></div>
                <div><label>Оплата</label><input name="payment_method" id="paymentMethod"></div>
                <div><label>Місто</label><input name="delivery_city" id="deliveryCity"></div>
                <div><label>Відділення</label><input name="delivery_warehouse" id="deliveryWarehouse"></div>
                <div style="grid-column:1/-1"><label>Адреса</label><input name="delivery_address" id="deliveryAddress"></div>
                <div style="grid-column:1/-1"><label>Коментар</label><textarea name="comment" id="orderComment" rows="2"></textarea></div>
            </div>

            <div class="modal-section">
                <h3>Товари</h3>
                <table class="modal-items-table" id="orderItemsTable">
                    <thead><tr><th>Product ID</th><th>Назва</th><th>К-сть</th><th>Ціна</th><th></th></tr></thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn secondary" id="addItemRowBtn">+ Додати товар</button>
                <p><b>Загальна сума: <span id="orderComputedTotal">0.00</span> ₴</b></p>
            </div>

            <div class="modal-section" id="historySection">
                <h3>Історія статусів</h3>
                <div id="statusHistory"></div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn secondary" id="closeOrderModal">Закрити</button>
                <button type="submit" class="btn">Зберегти</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const statusLabels = <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE) ?>;
    const switcher = document.querySelector('.orders-switcher');
    const modal = document.getElementById('orderModal');
    const form = document.getElementById('orderForm');
    const itemsBody = document.querySelector('#orderItemsTable tbody');

    const money = (n) => Number(n || 0).toFixed(2);

    const openModal = () => modal.classList.add('open');
    const closeModal = () => modal.classList.remove('open');

    document.getElementById('closeOrderModal').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    if (switcher) {
        const buttons = switcher.querySelectorAll('button');
        const views = document.querySelectorAll('.orders-view');
        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                buttons.forEach((btn) => btn.classList.remove('active'));
                views.forEach((view) => view.classList.remove('active'));
                button.classList.add('active');
                const target = document.getElementById(button.dataset.target);
                if (target) target.classList.add('active');
            });
        });
    }

    const recalcTotal = () => {
        let total = 0;
        itemsBody.querySelectorAll('tr').forEach((row) => {
            const qty = Number(row.querySelector('[name="item_qty[]"]').value || 0);
            const price = Number(row.querySelector('[name="item_price[]"]').value || 0);
            total += qty * price;
        });
        document.getElementById('orderComputedTotal').textContent = money(total);
    };

    const addItemRow = (item = {}) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input required name="item_product_id[]" type="number" min="1" value="${item.product_id || ''}"></td>
            <td>${item.product_name || '—'}</td>
            <td><input required name="item_qty[]" type="number" min="1" value="${item.qty || 1}"></td>
            <td><input required name="item_price[]" type="number" min="0" step="0.01" value="${item.price || 0}"></td>
            <td><button type="button" class="btn secondary js-remove-item">Видалити</button></td>
        `;
        tr.querySelectorAll('input').forEach((input) => input.addEventListener('input', recalcTotal));
        tr.querySelector('.js-remove-item').addEventListener('click', () => { tr.remove(); recalcTotal(); });
        itemsBody.appendChild(tr);
        recalcTotal();
    };

    document.getElementById('addItemRowBtn').addEventListener('click', () => addItemRow());

    const fillForm = (data, isCreate = false) => {
        document.getElementById('modalTitle').textContent = isCreate ? 'Нове замовлення' : `Замовлення #${data.order.id}`;
        document.getElementById('orderIdField').value = data.order.id || '';
        document.getElementById('customerName').value = data.order.customer_name || '';
        document.getElementById('customerPhone').value = data.order.customer_phone || '';
        document.getElementById('customerEmail').value = data.order.customer_email || '';
        document.getElementById('orderStatus').value = data.order.status || 'new';
        document.getElementById('deliveryMethod').value = data.order.delivery_method || '';
        document.getElementById('paymentMethod').value = data.order.payment_method || '';
        document.getElementById('deliveryCity').value = data.order.delivery_city || '';
        document.getElementById('deliveryWarehouse').value = data.order.delivery_warehouse || '';
        document.getElementById('deliveryAddress').value = data.order.delivery_address || '';
        document.getElementById('orderComment').value = data.order.comment || '';

        itemsBody.innerHTML = '';
        (data.items || []).forEach(addItemRow);
        if ((data.items || []).length === 0) addItemRow();

        const historyBox = document.getElementById('statusHistory');
        const historySection = document.getElementById('historySection');
        if (isCreate) {
            historySection.classList.add('hidden');
        } else {
            historySection.classList.remove('hidden');
            const rows = (data.history || []).map((h) => `<div>${h.changed_at}: ${statusLabels[h.old_status] || h.old_status || '—'} → ${statusLabels[h.new_status] || h.new_status}${h.ttn_code ? ` (ТТН: ${h.ttn_code})` : ''}</div>`);
            historyBox.innerHTML = rows.length ? rows.join('') : '<div>Історія порожня.</div>';
        }

        document.getElementById('orderComputedTotal').textContent = money(data.computed_total || 0);
        openModal();
    };

    const fetchOrderDetails = async (id) => {
        const response = await fetch(`/admin/orders/details/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Не вдалося завантажити замовлення');
        return data;
    };

    document.querySelectorAll('.order-card, .orders-table tbody tr').forEach((el) => {
        el.addEventListener('click', async () => {
            const id = el.dataset.orderId;
            if (!id) return;
            try {
                const data = await fetchOrderDetails(id);
                fillForm(data, false);
            } catch (err) { alert(err.message); }
        });
    });

    document.getElementById('createOrderBtn').addEventListener('click', () => {
        fillForm({ order: { status: 'new' }, items: [], history: [], computed_total: 0 }, true);
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const items = [];
        itemsBody.querySelectorAll('tr').forEach((row) => {
            items.push({
                product_id: Number(row.querySelector('[name="item_product_id[]"]').value || 0),
                qty: Number(row.querySelector('[name="item_qty[]"]').value || 0),
                price: Number(row.querySelector('[name="item_price[]"]').value || 0)
            });
        });

        const payload = {
            id: Number(document.getElementById('orderIdField').value || 0),
            customer_name: document.getElementById('customerName').value.trim(),
            customer_phone: document.getElementById('customerPhone').value.trim(),
            customer_email: document.getElementById('customerEmail').value.trim(),
            status: document.getElementById('orderStatus').value,
            delivery_method: document.getElementById('deliveryMethod').value.trim(),
            payment_method: document.getElementById('paymentMethod').value.trim(),
            delivery_city: document.getElementById('deliveryCity').value.trim(),
            delivery_warehouse: document.getElementById('deliveryWarehouse').value.trim(),
            delivery_address: document.getElementById('deliveryAddress').value.trim(),
            comment: document.getElementById('orderComment').value.trim(),
            items
        };

        const res = await fetch('/admin/orders/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok || !data.success) { alert(data.message || 'Помилка збереження'); return; }

        alert(data.message + '. Оновіть сторінку, щоб побачити зміни у списку.');
        closeModal();
    });

    document.getElementById('statusFilter').addEventListener('change', (e) => {
        const value = e.target.value;
        document.querySelectorAll('#ordersTableBody tr').forEach((row) => {
            row.style.display = (!value || row.dataset.status === value) ? '' : 'none';
        });
    });

    const columns = document.querySelectorAll('.orders-column');
    let draggedCard = null;
    const updateBadges = () => columns.forEach((column) => {
        const badge = column.querySelector('.orders-badge');
        if (badge) badge.textContent = String(column.querySelectorAll('.order-card').length);
    });

    const updateOrderStatus = async (orderId, status, ttnCode = '') => {
        const response = await fetch('/update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ order_id: Number(orderId), status, ttn_code: ttnCode })
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Не вдалося оновити статус');
        return result;
    };

    document.querySelectorAll('.order-card[draggable="true"]').forEach((card) => {
        card.addEventListener('dragstart', () => { draggedCard = card; card.classList.add('dragging'); });
        card.addEventListener('dragend', () => { card.classList.remove('dragging'); draggedCard = null; });
    });

    columns.forEach((column) => {
        const body = column.querySelector('.orders-column__body');
        if (!body) return;
        body.addEventListener('dragover', (event) => { event.preventDefault(); column.classList.add('drag-over'); });
        body.addEventListener('dragleave', () => column.classList.remove('drag-over'));
        body.addEventListener('drop', async (event) => {
            event.preventDefault();
            column.classList.remove('drag-over');
            if (!draggedCard) return;

            const card = draggedCard;
            const targetStatus = column.dataset.status;
            const previousColumnBody = card.closest('.orders-column__body');
            const orderId = card.dataset.orderId;
            const currentStatus = card.dataset.status;
            if (!targetStatus || !orderId || targetStatus === currentStatus) return;

            let ttnCode = '';
            if (targetStatus === 'shipped') {
                ttnCode = window.prompt('Введіть ТТН для відправлення:', '') || '';
                if (!ttnCode.trim()) return alert('ТТН обов\'язкова для статусу «Відправлено».');
            }

            card.style.pointerEvents = 'none';
            try {
                await updateOrderStatus(orderId, targetStatus, ttnCode.trim());
                body.appendChild(card);
                card.dataset.status = targetStatus;
                updateBadges();
            } catch (error) {
                if (previousColumnBody && card.parentElement !== previousColumnBody) previousColumnBody.appendChild(card);
                alert(error.message);
            } finally { card.style.pointerEvents = ''; }
        });
    });

    document.getElementById('syncLogisticsBtn').addEventListener('click', async () => {
        const res = await fetch('/admin/orders/sync-logistics', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        if (!res.ok || !data.success) return alert(data.message || 'Помилка синхронізації');

        (data.updated || []).forEach((change) => {
            if (change.to === 'completed') {
                const card = document.querySelector(`.order-card[data-order-id="${change.order_id}"]`);
                if (card) card.remove();
            }
        });
        updateBadges();
        alert(data.message);
    });
})();
</script>
