<?php
/**
 * @var array                          $kanbanColumns
 * @var array                          $kanbanOrders
 * @var \App\Core\Pagination\Paginator $kanbanPager
 * @var array                          $tableOrders
 * @var \App\Core\Pagination\Paginator $tablePager
 * @var array                          $allStatuses
 * @var string                         $statusFilter
 * @var string                         $searchFilter
 * @var string                         $activeView
 */

$statusLabels = [
    'pending'    => 'Очікує оплати',
    'new'        => 'Новий',
    'confirmed'  => 'Підтверджено',
    'processing' => 'Комплектується',
    'shipped'    => 'Відправлено',
    'delivered'  => 'Доставлено',
    'completed'  => 'Завершено',
    'cancelled'  => 'Скасовано',
    'returned'   => 'Повернено',
];

$statusColors = [
    'pending'    => '#f59e0b',
    'new'        => '#3b82f6',
    'confirmed'  => '#8b5cf6',
    'processing' => '#f59e0b',
    'shipped'    => '#06b6d4',
    'delivered'  => '#10b981',
    'completed'  => '#22c55e',
    'cancelled'  => '#ef4444',
    'returned'   => '#f97316',
];

?>
<style>
    .orders-page { max-width:100%; }
    .orders-header { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
    .orders-header h1 { margin:0; font-size:24px; display:flex; align-items:center; gap:.5rem; }
    .orders-toolbar  { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .orders-switcher { display:inline-flex; background:#fff; border:1px solid #d9e1ef; border-radius:10px; padding:4px; }
    .orders-switcher button { border:0; background:transparent; padding:9px 16px; cursor:pointer; border-radius:8px; font-weight:700; color:#6b7280; font-size:.875rem; }
    .orders-switcher button.active { color:#3b82f6; background:#dbeafe; }
    .btn { border:0; background:#2563eb; color:#fff; padding:9px 16px; border-radius:8px; cursor:pointer; font-weight:600; font-size:.875rem; }
    .btn.secondary { background:#fff; color:#1f2937; border:1px solid #d9e1ef; }

    /* Views */
    .orders-view { display:none; }
    .orders-view.active { display:block; }

    /* ── КАНБАН ── */
    .orders-kanban { display:grid; grid-template-columns:repeat(4,minmax(220px,1fr)); gap:14px; align-items:start; }
    .orders-column { background:#fff; border:1px solid #d9e1ef; border-radius:12px; overflow:hidden; }
    .orders-column.drag-over { border-color:#2563eb; box-shadow:inset 0 0 0 1px #2563eb; }
    .orders-column__head { padding:11px 14px; border-bottom:1px solid #d9e1ef; font-weight:700; display:flex; justify-content:space-between; align-items:center; font-size:.9rem; }
    .orders-badge { display:inline-flex; align-items:center; justify-content:center; min-width:24px; height:24px; padding:0 8px; border-radius:999px; font-size:12px; color:#3b82f6; background:#dbeafe; }
    .orders-column__body { padding:10px; display:grid; gap:10px; min-height:280px; }
    .order-card { border:1px solid #d9e1ef; border-radius:10px; background:#fff; padding:10px 12px; box-shadow:0 1px 3px rgba(0,0,0,.05); cursor:pointer; transition:box-shadow .15s; }
    .order-card:hover { box-shadow:0 3px 10px rgba(0,0,0,.1); }
    .order-card.dragging { opacity:.45; cursor:grabbing; }
    .order-card__id { font-weight:700; margin-bottom:5px; font-size:.95rem; }
    .order-card__line { font-size:.82rem; color:#6b7280; margin-top:2px; }

    /* Kanban пагінація */
    .kanban-pag-wrap { margin-top:14px; }

    /* ── ТАБЛИЦЯ ── */
    .orders-table-box { background:#fff; border:1px solid #d9e1ef; border-radius:12px; overflow:hidden; }
    .orders-table-toolbar { display:flex; gap:8px; padding:12px 14px; border-bottom:1px solid #e5e7eb; flex-wrap:wrap; align-items:center; }
    .orders-table-toolbar form { display:flex; gap:8px; flex:1; flex-wrap:wrap; }
    .orders-table-toolbar input[type=text],
    .orders-table-toolbar select { padding:.45rem .75rem; border:1px solid #d9e1ef; border-radius:8px; font-size:.875rem; }
    .orders-table-toolbar input[type=text] { flex:1; min-width:160px; }
    .orders-table { width:100%; border-collapse:collapse; font-size:.875rem; }
    .orders-table thead th { background:#f8fafc; color:#64748b; font-weight:700; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; padding:.7rem 1rem; text-align:left; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
    .orders-table tbody td { padding:.7rem 1rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    .orders-table tbody tr:last-child td { border-bottom:none; }
    .orders-table tbody tr { cursor:pointer; }
    .orders-table tbody tr:hover { background:#f8fafc; }
    .status-chip { display:inline-flex; align-items:center; padding:.25rem .7rem; border-radius:20px; font-size:.75rem; font-weight:700; white-space:nowrap; }

    /* Shared pagination */
    .pag-wrap { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; border-top:1px solid #f1f5f9; flex-wrap:wrap; gap:.5rem; }
    .pag-info { font-size:.82rem; color:#64748b; }
    .pag-links { display:flex; gap:.3rem; flex-wrap:wrap; }
    .pag-btn { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 .5rem; border-radius:7px; border:1px solid #e2e8f0; background:#fff; text-decoration:none; color:#334155; font-size:.83rem; font-weight:500; transition:.15s; }
    .pag-btn:hover { border-color:#3b82f6; color:#3b82f6; }
    .pag-btn.active { background:#3b82f6; color:#fff; border-color:#3b82f6; }
    .pag-btn.pag-disabled { opacity:.38; pointer-events:none; }
    .pag-dots { display:inline-flex; align-items:center; padding:0 .35rem; color:#94a3b8; font-size:.85rem; }

    /* Modal */
    .orders-modal { position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:1000; }
    .orders-modal.open { display:flex; }
    .orders-modal__dialog { background:#fff; width:min(980px,95vw); max-height:92vh; overflow:auto; border-radius:12px; padding:18px; }
    .modal-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px 14px; }
    .modal-grid label { display:block; font-size:13px; color:#6b7280; margin-bottom:4px; }
    .modal-grid input,.modal-grid select,.modal-grid textarea { width:100%; border:1px solid #d9e1ef; border-radius:8px; padding:8px 10px; box-sizing:border-box; }
    .modal-section { margin-top:16px; }
    .modal-items-table { width:100%; border-collapse:collapse; }
    .modal-items-table th,.modal-items-table td { border-bottom:1px solid #e5e7eb; padding:8px; }
    .modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:14px; }
    .hidden { display:none!important; }

    @media(max-width:1200px) { .orders-kanban { grid-template-columns:repeat(2,minmax(220px,1fr)); } }
    @media(max-width:768px)  { .modal-grid { grid-template-columns:1fr; } .orders-kanban { grid-template-columns:1fr; } }
</style>

<div class="orders-page">
    <div class="orders-header">
        <h1><i class="fas fa-shopping-bag" style="color:#3b82f6;"></i> Замовлення</h1>
        <div class="orders-toolbar">
            <button type="button" class="btn secondary" id="syncLogisticsBtn">
                <i class="fas fa-sync-alt"></i> Синхронізувати ТТН
            </button>
            <button type="button" class="btn" id="createOrderBtn">
                + Нове замовлення
            </button>
            <div class="orders-switcher" role="tablist">
                <button type="button" data-view="kanban" class="<?= ($activeView ?? 'kanban') !== 'table' ? 'active' : '' ?>">
                    КАНБАН
                </button>
                <button type="button" data-view="table" class="<?= ($activeView ?? 'kanban') === 'table' ? 'active' : '' ?>">
                    ТАБЛИЦЯ
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════ КАНБАН ═══════════════ -->
    <section id="ordersKanbanView"
             class="orders-view <?= ($activeView ?? 'kanban') !== 'table' ? 'active' : '' ?>"
             aria-label="Канбан режим замовлень">

        <div class="orders-kanban">
            <?php foreach (($kanbanColumns ?? []) as $statusCode => $statusLabel):
                $cardsInColumn = array_values(array_filter(
                    $kanbanOrders ?? [],
                    static fn(array $c): bool => ($c['status'] ?? '') === $statusCode
                ));
            ?>
                <article class="orders-column" data-status="<?= htmlspecialchars($statusCode) ?>">
                    <div class="orders-column__head">
                        <span><?= htmlspecialchars($statusLabel) ?></span>
                        <span class="orders-badge"><?= count($cardsInColumn) ?></span>
                    </div>
                    <div class="orders-column__body">
                        <?php foreach ($cardsInColumn as $order): ?>
                            <div class="order-card" draggable="true"
                                 data-order-id="<?= (int)$order['id'] ?>"
                                 data-status="<?= htmlspecialchars((string)($order['status'] ?? '')) ?>">
                                <div class="order-card__id">#<?= (int)$order['id'] ?></div>
                                <div class="order-card__line">
                                    <i class="fas fa-user" style="width:12px;"></i>
                                    <?= htmlspecialchars((string)($order['customer_name'] ?? '—')) ?>
                                </div>
                                <div class="order-card__line">
                                    <i class="fas fa-phone" style="width:12px;"></i>
                                    <?= htmlspecialchars((string)($order['customer_phone'] ?? '—')) ?>
                                </div>
                                <div class="order-card__line" style="font-weight:600; color:#0f172a; margin-top:5px;">
                                    <?= format_price((float)($order['total'] ?? 0)) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Канбан пагінація -->
        <?php if ($kanbanPager->hasPages()): ?>
        <div style="margin-top:14px;">
            <?= $kanbanPager->render(['show_info' => true]) ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ═══════════════ ТАБЛИЦЯ ═══════════════ -->
    <section id="ordersTableView"
             class="orders-view <?= ($activeView ?? 'kanban') === 'table' ? 'active' : '' ?>"
             aria-label="Табличний режим замовлень">

        <div class="orders-table-box">
            <!-- Фільтри -->
            <div class="orders-table-toolbar">
                <form method="GET" action="/admin/orders">
                    <input type="hidden" name="view" value="table">
                    <input type="text" name="search"
                           value="<?= htmlspecialchars($searchFilter ?? '') ?>"
                           placeholder="Пошук: ім'я, телефон, ID…">
                    <select name="status">
                        <option value="">Всі статуси</option>
                        <?php foreach (($allStatuses ?? []) as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"
                                <?= ($statusFilter ?? '') === $s ? 'selected' : '' ?>>
                                <?= htmlspecialchars($statusLabels[$s] ?? $s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn">Застосувати</button>
                    <?php if (($statusFilter ?? '') !== '' || ($searchFilter ?? '') !== ''): ?>
                        <a href="<?= ordersUrl(['view' => 'table', 'status' => '', 'search' => '', 'tpage' => '']) ?>"
                           class="btn secondary">Скинути</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Клієнт</th>
                        <th>Телефон</th>
                        <th>Сума</th>
                        <th>Статус</th>
                        <th>Оплата</th>
                        <th>Доставка</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <?php if (empty($tableOrders)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:2rem; color:#94a3b8;">
                                <i class="fas fa-inbox" style="font-size:1.5rem; display:block; margin-bottom:.5rem;"></i>
                                Замовлень не знайдено
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tableOrders as $row): ?>
                            <?php
                                $color = $statusColors[$row['status'] ?? ''] ?? '#64748b';
                                $bg    = $color . '18';
                            ?>
                            <tr data-order-id="<?= (int)$row['id'] ?>"
                                data-status="<?= htmlspecialchars((string)($row['status'] ?? '')) ?>">
                                <td style="font-weight:700; color:#3b82f6;">#<?= (int)$row['id'] ?></td>
                                <td style="font-weight:500;"><?= htmlspecialchars((string)($row['customer_name'] ?? '—')) ?></td>
                                <td style="color:#64748b;"><?= htmlspecialchars((string)($row['customer_phone'] ?? '—')) ?></td>
                                <td style="font-weight:600;"><?= format_price((float)($row['total'] ?? 0)) ?></td>
                                <td>
                                    <span class="status-chip"
                                          style="color:<?= $color ?>; background:<?= $bg ?>;">
                                        <?= htmlspecialchars($statusLabels[$row['status'] ?? ''] ?? ($row['status'] ?? '—')) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars((string)($row['payment_method_name'] ?? $row['payment_method'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string)($row['delivery_method_name'] ?? $row['delivery_method'] ?? '—')) ?></td>
                                <td style="white-space:nowrap; color:#64748b; font-size:.82rem;">
                                    <?= date('d.m.Y', strtotime((string)($row['created_at'] ?? 'now'))) ?>
                                    <div style="color:#94a3b8;"><?= date('H:i', strtotime((string)($row['created_at'] ?? 'now'))) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Таблиця пагінація -->
            <?= $tablePager->render(['show_info' => true]) ?>
        </div>
    </section>
</div>

<!-- ═══════════════ MODAL ═══════════════ -->
<div class="orders-modal" id="orderModal">
    <div class="orders-modal__dialog">
        <h2 id="modalTitle">Деталі замовлення</h2>
        <form id="orderForm">
            <input type="hidden" name="id" id="orderIdField">
            <div class="modal-grid">
                <div><label>Ім'я</label><input required name="customer_name" id="customerName"></div>
                <div><label>Телефон</label><input required name="customer_phone" id="customerPhone"></div>
                <div><label>Email</label><input name="customer_email" id="customerEmail"></div>
                <div><label>Статус</label>
                    <select name="status" id="orderStatus">
                        <?php foreach (($allStatuses ?? []) as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>">
                                <?= htmlspecialchars($statusLabels[$status] ?? $status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
                    <thead><tr><th>Product ID</th><th>Назва</th><th>Опції</th><th>К-сть</th><th>Ціна</th><th></th></tr></thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn secondary" id="addItemRowBtn" style="margin-top:8px;">+ Додати товар</button>
                <p><b>Загальна сума: <span id="orderComputedTotal">0.00</span> <?= htmlspecialchars($activeCurrencySymbol ?? '₴') ?></b></p>
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
    const modal        = document.getElementById('orderModal');
    const form         = document.getElementById('orderForm');
    const itemsBody    = document.querySelector('#orderItemsTable tbody');

    const money = (n) => Number(n || 0).toFixed(2);
    const openModal  = () => modal.classList.add('open');
    const closeModal = () => modal.classList.remove('open');

    document.getElementById('closeOrderModal').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    // ── View switcher (зберігає в URL) ──
    document.querySelectorAll('.orders-switcher button').forEach((btn) => {
        btn.addEventListener('click', () => {
            const view = btn.dataset.view;
            const url  = new URL(window.location.href);
            url.searchParams.set('view', view);
            window.location.href = url.toString();
        });
    });

    // ── Recalc total ──
    const recalcTotal = () => {
        let total = 0;
        itemsBody.querySelectorAll('tr').forEach((row) => {
            const qty   = Number(row.querySelector('[name="item_qty[]"]').value || 0);
            const price = Number(row.querySelector('[name="item_price[]"]').value || 0);
            total += qty * price;
        });
        document.getElementById('orderComputedTotal').textContent = money(total);
    };

    const formatSelectedOptions = (opts = []) => {
        if (!Array.isArray(opts) || !opts.length) return '—';
        return opts.map((o) => `${o?.name || '—'}: ${o?.value || '—'}`).join(', ');
    };

    const addItemRow = (item = {}) => {
        const opts    = Array.isArray(item.selected_options) ? item.selected_options : [];
        const optsJson = JSON.stringify(opts);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input required name="item_product_id[]" type="number" min="1" value="${item.product_id || ''}"></td>
            <td>${item.product_name || '—'}</td>
            <td>
                <span>${formatSelectedOptions(opts)}</span>
                <input type="hidden" name="item_selected_options[]" value='${optsJson.replace(/'/g,"&#39;")}'>
            </td>
            <td><input required name="item_qty[]" type="number" min="1" value="${item.qty || 1}"></td>
            <td><input required name="item_price[]" type="number" min="0" step="0.01" value="${item.price || 0}"></td>
            <td><button type="button" class="btn secondary js-remove-item" style="padding:4px 8px;">✕</button></td>
        `;
        tr.querySelectorAll('input').forEach((i) => i.addEventListener('input', recalcTotal));
        tr.querySelector('.js-remove-item').addEventListener('click', () => { tr.remove(); recalcTotal(); });
        itemsBody.appendChild(tr);
        recalcTotal();
    };

    document.getElementById('addItemRowBtn').addEventListener('click', () => addItemRow());

    const fillForm = (data, isCreate = false) => {
        document.getElementById('modalTitle').textContent = isCreate ? 'Нове замовлення' : `Замовлення #${data.order.id}`;
        document.getElementById('orderIdField').value     = data.order.id || '';
        document.getElementById('customerName').value    = data.order.customer_name || '';
        document.getElementById('customerPhone').value   = data.order.customer_phone || '';
        document.getElementById('customerEmail').value   = data.order.customer_email || '';
        document.getElementById('orderStatus').value     = data.order.status || 'new';
        document.getElementById('deliveryMethod').value  = data.order.delivery_method_name || data.order.delivery_method || '';
        document.getElementById('paymentMethod').value   = data.order.payment_method_name  || data.order.payment_method  || '';
        document.getElementById('deliveryCity').value    = data.order.delivery_city      || '';
        document.getElementById('deliveryWarehouse').value = data.order.delivery_warehouse || '';
        document.getElementById('deliveryAddress').value = data.order.delivery_address   || '';
        document.getElementById('orderComment').value    = data.order.comment            || '';

        itemsBody.innerHTML = '';
        (data.items || []).forEach(addItemRow);
        if (!(data.items || []).length) addItemRow();

        const historySection = document.getElementById('historySection');
        const historyBox     = document.getElementById('statusHistory');
        if (isCreate) {
            historySection.classList.add('hidden');
        } else {
            historySection.classList.remove('hidden');
            const rows = (data.history || []).map((h) =>
                `<div style="font-size:.85rem; padding:.3rem 0; border-bottom:1px solid #f1f5f9;">
                    ${h.changed_at}: <b>${statusLabels[h.old_status] || h.old_status || '—'}</b>
                    → <b>${statusLabels[h.new_status] || h.new_status}</b>
                    ${h.ttn_code ? ` (ТТН: ${h.ttn_code})` : ''}
                </div>`);
            historyBox.innerHTML = rows.length ? rows.join('') : '<div style="color:#94a3b8;">Історія порожня.</div>';
        }

        document.getElementById('orderComputedTotal').textContent = money(data.computed_total || 0);
        openModal();
    };

    const fetchOrderDetails = async (id) => {
        const res     = await fetch(`/admin/orders/details/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const rawText = await res.text();
        let data;
        try { data = JSON.parse(rawText); } catch { throw new Error('Сервер повернув некоректну відповідь.'); }
        if (!res.ok || !data.success) throw new Error(data.message || 'Не вдалося завантажити замовлення');
        return data;
    };

    // Клік по картці канбан або рядку таблиці
    document.addEventListener('click', async (e) => {
        const el = e.target.closest('.order-card, #ordersTableBody tr[data-order-id]');
        if (!el) return;
        const id = el.dataset.orderId;
        if (!id) return;
        try {
            const data = await fetchOrderDetails(id);
            fillForm(data, false);
        } catch (err) { alert(err.message); }
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
                qty:        Number(row.querySelector('[name="item_qty[]"]').value || 0),
                price:      Number(row.querySelector('[name="item_price[]"]').value || 0),
                selected_options: JSON.parse(row.querySelector('[name="item_selected_options[]"]').value || '[]'),
            });
        });
        const payload = {
            id: Number(document.getElementById('orderIdField').value || 0),
            customer_name:      document.getElementById('customerName').value.trim(),
            customer_phone:     document.getElementById('customerPhone').value.trim(),
            customer_email:     document.getElementById('customerEmail').value.trim(),
            status:             document.getElementById('orderStatus').value,
            delivery_method:    document.getElementById('deliveryMethod').value.trim(),
            payment_method:     document.getElementById('paymentMethod').value.trim(),
            delivery_city:      document.getElementById('deliveryCity').value.trim(),
            delivery_warehouse: document.getElementById('deliveryWarehouse').value.trim(),
            delivery_address:   document.getElementById('deliveryAddress').value.trim(),
            comment:            document.getElementById('orderComment').value.trim(),
            items,
        };
        const res  = await fetch('/admin/orders/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok || !data.success) { alert(data.message || 'Помилка збереження'); return; }
        alert(data.message);
        closeModal();
        location.reload();
    });

    // ── Drag-and-drop канбан ──
    const columns = document.querySelectorAll('.orders-column');
    let draggedCard = null;

    const updateBadges = () => columns.forEach((col) => {
        const badge = col.querySelector('.orders-badge');
        if (badge) badge.textContent = String(col.querySelectorAll('.order-card').length);
    });

    const updateOrderStatus = async (orderId, status, ttnCode = '') => {
        const res    = await fetch('/admin/orders/update-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ order_id: Number(orderId), status, ttn_code: ttnCode }),
        });
        const result = await res.json();
        if (!res.ok || !result.success) throw new Error(result.message || 'Не вдалося оновити статус');
        return result;
    };

    document.querySelectorAll('.order-card[draggable="true"]').forEach((card) => {
        card.addEventListener('dragstart', () => { draggedCard = card; card.classList.add('dragging'); });
        card.addEventListener('dragend',   () => { card.classList.remove('dragging'); draggedCard = null; });
    });

    columns.forEach((column) => {
        const body = column.querySelector('.orders-column__body');
        if (!body) return;
        body.addEventListener('dragover',  (e) => { e.preventDefault(); column.classList.add('drag-over'); });
        body.addEventListener('dragleave', ()  => column.classList.remove('drag-over'));
        body.addEventListener('drop', async (e) => {
            e.preventDefault();
            column.classList.remove('drag-over');
            if (!draggedCard) return;
            const card         = draggedCard;
            const targetStatus = column.dataset.status;
            const prevBody     = card.closest('.orders-column__body');
            const orderId      = card.dataset.orderId;
            const currentStatus = card.dataset.status;
            if (!targetStatus || !orderId || targetStatus === currentStatus) return;

            let ttnCode = '';
            if (targetStatus === 'shipped') {
                ttnCode = window.prompt('Введіть ТТН для відправлення:') || '';
                if (!ttnCode.trim()) return alert('ТТН обов\'язкова для статусу «Відправлено».');
            }

            card.style.pointerEvents = 'none';
            try {
                await updateOrderStatus(orderId, targetStatus, ttnCode.trim());
                body.appendChild(card);
                card.dataset.status = targetStatus;
                updateBadges();
            } catch (err) {
                if (prevBody && card.parentElement !== prevBody) prevBody.appendChild(card);
                alert(err.message);
            } finally { card.style.pointerEvents = ''; }
        });
    });

    // ── Синхронізація ТТН ──
    document.getElementById('syncLogisticsBtn').addEventListener('click', async () => {
        const res  = await fetch('/admin/orders/sync-logistics', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
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
