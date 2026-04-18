<style>
    .orders-page {
        max-width: 100%;
    }

    .orders-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .orders-header h1 {
        margin: 0;
        font-size: 24px;
    }

    .orders-switcher {
        display: inline-flex;
        background: #fff;
        border: 1px solid #d9e1ef;
        border-radius: 10px;
        padding: 4px;
    }

    .orders-switcher button {
        border: 0;
        background: transparent;
        padding: 10px 14px;
        cursor: pointer;
        border-radius: 8px;
        font-weight: 600;
        color: #6b7280;
    }

    .orders-switcher button.active {
        color: #3b82f6;
        background: #dbeafe;
    }

    .orders-view {
        display: none;
    }

    .orders-view.active {
        display: block;
    }

    .orders-kanban {
        display: grid;
        grid-template-columns: repeat(4, minmax(220px, 1fr));
        gap: 14px;
        align-items: start;
    }

    .orders-column {
        background: #fff;
        border: 1px solid #d9e1ef;
        border-radius: 12px;
        min-height: 380px;
        overflow: hidden;
    }

    .orders-column.drag-over {
        border-color: #2563eb;
        box-shadow: inset 0 0 0 1px #2563eb;
    }

    .orders-column__head {
        padding: 12px 14px;
        border-bottom: 1px solid #d9e1ef;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .orders-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        padding: 0 8px;
        border-radius: 999px;
        font-size: 12px;
        color: #3b82f6;
        background: #dbeafe;
    }

    .orders-column__body {
        padding: 10px;
        display: grid;
        gap: 10px;
        min-height: 320px;
    }

    .order-card {
        border: 1px solid #d9e1ef;
        border-radius: 10px;
        background: #fff;
        padding: 10px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        cursor: grab;
    }

    .order-card.dragging {
        opacity: 0.5;
        cursor: grabbing;
    }

    .order-card__id {
        font-weight: 700;
        margin-bottom: 6px;
    }

    .order-card__line {
        font-size: 14px;
        color: #6b7280;
    }

    .orders-table-placeholder {
        border: 1px dashed #d9e1ef;
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        color: #6b7280;
    }

    @media (max-width: 1200px) {
        .orders-kanban {
            grid-template-columns: repeat(2, minmax(220px, 1fr));
        }
    }

    @media (max-width: 640px) {
        .orders-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .orders-kanban {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="orders-page">
    <div class="orders-header">
        <h1>Замовлення</h1>

        <div class="orders-switcher" role="tablist" aria-label="Перемикач режиму перегляду замовлень">
            <button type="button" class="active" data-target="ordersKanbanView">КАНБАН</button>
            <button type="button" data-target="ordersTableView">ТАБЛИЦЯ</button>
        </div>
    </div>

    <section id="ordersKanbanView" class="orders-view active" aria-label="Канбан режим замовлень">
        <div class="orders-kanban">
            <?php foreach (($kanbanColumns ?? []) as $statusCode => $statusLabel): ?>
                <?php
                $cardsInColumn = array_values(array_filter(
                    $orders ?? [],
                    static fn(array $card): bool => ($card['status'] ?? '') === $statusCode
                ));
                ?>
                <article class="orders-column" data-status="<?= htmlspecialchars((string)$statusCode, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="orders-column__head">
                        <span><?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="orders-badge"><?= count($cardsInColumn) ?></span>
                    </div>
                    <div class="orders-column__body">
                        <?php foreach ($cardsInColumn as $order): ?>
                            <div class="order-card"
                                 draggable="true"
                                 data-order-id="<?= (int)$order['id'] ?>"
                                 data-status="<?= htmlspecialchars((string)($order['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
        <div class="orders-table-placeholder">
            Режим «ТАБЛИЦЯ» реалізуємо на наступному кроці (повний список статусів + фільтрація).
        </div>
    </section>
</div>

<script>
    (() => {
        const switcher = document.querySelector('.orders-switcher');
        if (!switcher) {
            return;
        }

        const buttons = switcher.querySelectorAll('button');
        const views = document.querySelectorAll('.orders-view');

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                buttons.forEach((btn) => btn.classList.remove('active'));
                views.forEach((view) => view.classList.remove('active'));

                button.classList.add('active');
                const target = document.getElementById(button.dataset.target);
                if (target) {
                    target.classList.add('active');
                }
            });
        });

        const columns = document.querySelectorAll('.orders-column');
        let draggedCard = null;

        const updateBadges = () => {
            columns.forEach((column) => {
                const badge = column.querySelector('.orders-badge');
                if (!badge) {
                    return;
                }
                badge.textContent = String(column.querySelectorAll('.order-card').length);
            });
        };

        const updateOrderStatus = async (orderId, status, ttnCode = '') => {
            const response = await fetch('/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    order_id: Number(orderId),
                    status,
                    ttn_code: ttnCode
                })
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Не вдалося оновити статус');
            }

            return result;
        };

        document.querySelectorAll('.order-card[draggable="true"]').forEach((card) => {
            card.addEventListener('dragstart', () => {
                draggedCard = card;
                card.classList.add('dragging');
            });

            card.addEventListener('dragend', () => {
                card.classList.remove('dragging');
                draggedCard = null;
            });
        });

        columns.forEach((column) => {
            const body = column.querySelector('.orders-column__body');
            if (!body) {
                return;
            }

            body.addEventListener('dragover', (event) => {
                event.preventDefault();
                column.classList.add('drag-over');
            });

            body.addEventListener('dragleave', () => {
                column.classList.remove('drag-over');
            });

            body.addEventListener('drop', async (event) => {
                event.preventDefault();
                column.classList.remove('drag-over');

                if (!draggedCard) {
                    return;
                }

                const card = draggedCard;
                const targetStatus = column.dataset.status;
                const previousColumnBody = card.closest('.orders-column__body');
                const orderId = card.dataset.orderId;
                const currentStatus = card.dataset.status;

                if (!targetStatus || !orderId || targetStatus === currentStatus) {
                    return;
                }

                let ttnCode = '';
                if (targetStatus === 'shipped') {
                    ttnCode = window.prompt('Введіть ТТН для відправлення:', '') || '';
                    if (!ttnCode.trim()) {
                        alert('ТТН обов\'язкова для статусу «Відправлено».');
                        return;
                    }
                }

                card.style.pointerEvents = 'none';
                try {
                    await updateOrderStatus(orderId, targetStatus, ttnCode.trim());
                    body.appendChild(card);
                    card.dataset.status = targetStatus;
                    updateBadges();
                } catch (error) {
                    if (previousColumnBody && card.parentElement !== previousColumnBody) {
                        previousColumnBody.appendChild(card);
                    }
                    alert(error.message);
                } finally {
                    card.style.pointerEvents = '';
                }
            });
        });
    })();
</script>
