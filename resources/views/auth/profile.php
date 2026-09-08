<div class="profile-page">
    <div class="profile-header-section">
        <div class="profile-user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <h1><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></h1>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars((string) ($user['email'] ?? '')); ?></p>
            </div>
        </div>
        <div class="profile-header-actions">
            <form action="/logout" method="POST">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?>
                </button>
            </form>
        </div>
    </div>

    <div class="profile-layout">
        <aside class="profile-sidebar">
            <nav class="profile-nav">
                <a href="/profile/orders" class="nav-link <?php echo ($activeTab ?? '') === 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-bag"></i>
                    <span><?php echo __('profile_my_orders'); ?></span>
                </a>
                <a href="/profile/favorites" class="nav-link <?php echo ($activeTab ?? '') === 'favorites' ? 'active' : ''; ?>">
                    <i class="fas fa-heart"></i>
                    <span><?php echo __('profile_favorites'); ?></span>
                </a>
                <a href="/profile/edit" class="nav-link <?php echo ($activeTab ?? '') === 'edit' || !isset($activeTab) ? 'active' : ''; ?>">
                    <i class="fas fa-user-edit"></i>
                    <span><?php echo __('profile_edit'); ?></span>
                </a>
                <?php if (in_array($user['role'] ?? '', ['admin', 'moderator'], true)): ?>
                    <a href="/admin" class="nav-link admin-link">
                        <i class="fas fa-user-shield"></i>
                        <span><?php echo __('profile_admin_panel'); ?></span>
                    </a>
                <?php endif; ?>
            </nav>
        </aside>

        <main class="profile-main-content">
            <div class="content-card">
                <?php if (!empty($_SESSION['errors'])): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($_SESSION['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; unset($_SESSION['errors']); ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php do_action('profile.content.before', $user); ?>

                <?php if (($activeTab ?? '') === 'orders'): ?>
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> <?php echo __('profile_my_orders'); ?></h2>
                    </div>
                    <?php if (!empty($orders)): ?>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th><?php echo __('profile_number_orders'); ?></th>
                                        <th><?php echo __('date'); ?></th>
                                        <th><?php echo __('breadcrumb_products'); ?></th>
                                        <th><?php echo __('sum'); ?></th>
                                        <th><?php echo __('status'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="order-row" onclick="toggleOrderDetails(<?php echo $order['id']; ?>)">
                                            <td class="order-id">
                                                <i class="fas fa-chevron-right toggle-icon" id="icon-<?php echo $order['id']; ?>"></i>
                                                #<?php echo (int) ($order['id'] ?? 0); ?>
                                            </td>
                                            <td class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td class="order-items">
                                                <span class="text-muted"><?php echo count($order['items'] ?? []); ?> <?php echo __('breadcrumb_products'); ?></span>
                                            </td>
                                            <td class="order-total"><?php echo number_format((float) ($order['total'] ?? 0), 2, '.', ' '); ?> ₴</td>
                                            <td class="order-status">
                                                <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                                    <?php echo htmlspecialchars((string) ($order['status_label'] ?? $order['status'] ?? '')); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr class="order-details-row" id="details-<?php echo $order['id']; ?>" style="display: none;">
                                            <td colspan="5">
                                                <div class="order-details-expanded">
                                                    <div class="details-grid">
                                                        <div class="details-col">
                                                            <h5><i class="fas fa-shopping-cart"></i> <?php echo __('breadcrumb_products'); ?></h5>
                                                            <div class="items-list-detailed">
                                                                <?php foreach ((array) ($order['items'] ?? []) as $item): ?>
                                                                    <div class="item-detail">
                                                                        <span class="item-name"><?php echo htmlspecialchars((string) ($item['product_name'] ?? '')); ?></span>
                                                                        <span class="item-info"><?php echo (int) ($item['qty'] ?? 0); ?> шт. × <?php echo number_format((float) ($item['price'] ?? 0), 2, '.', ' '); ?> ₴</span>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                        <div class="details-col">
                                                            <h5><i class="fas fa-truck"></i> <?php echo __('delivery'); ?></h5>
                                                            <p><strong><?php echo __('method'); ?>:</strong> <?php echo htmlspecialchars($order['delivery_method'] ?? '—'); ?></p>
                                                            <p><strong><?php echo __('city'); ?>:</strong> <?php echo htmlspecialchars($order['delivery_city'] ?? '—'); ?></p>
                                                            <p><strong><?php echo __('address'); ?>:</strong> <?php echo htmlspecialchars(($order['delivery_warehouse'] ?: $order['delivery_address']) ?: '—'); ?></p>
                                                        </div>
                                                        <div class="details-col">
                                                            <h5><i class="fas fa-credit-card"></i> <?php echo __('payment'); ?></h5>
                                                            <p><strong><?php echo __('method'); ?>:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? '—'); ?></p>
                                                            <?php if (!empty($order['comment'])): ?>
                                                                <h5><i class="fas fa-comment-alt"></i> <?php echo __('comment'); ?></h5>
                                                                <p class="order-comment"><?php echo nl2br(htmlspecialchars($order['comment'])); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p><?php echo __('profile_no_orders'); ?></p>
                            <a href="/products" class="btn btn-primary"><?php echo __('shop_now'); ?></a>
                        </div>
                    <?php endif; ?>

                <?php elseif (($activeTab ?? '') === 'favorites'): ?>
                    <div class="section-header">
                        <h2><i class="fas fa-heart"></i> <?php echo __('profile_favorites'); ?></h2>
                    </div>
                    <?php if (!empty($favorites)): ?>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th><?php echo __('product'); ?></th>
                                        <th><?php echo __('price'); ?></th>
                                        <th><?php echo __('profile_added'); ?></th>
                                        <th><?php echo __('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($favorites as $item): ?>
                                        <tr>
                                            <td>
                                                <a href="/product/<?php echo urlencode($item['slug']); ?>" class="product-link">
                                                    <?php echo htmlspecialchars((string) $item['name']); ?>
                                                </a>
                                            </td>
                                            <td class="fw-bold"><?php echo render_product_price($item); ?></td>
                                            <td class="text-muted"><?php echo date('d.m.Y', strtotime($item['added_at'])); ?></td>
                                            <td>
                                                <a href="/product/<?php echo urlencode($item['slug']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-heart-broken"></i>
                            <p><?php echo __('profile_no_favorites'); ?></p>
                            <a href="/products" class="btn btn-primary"><?php echo __('shop_now'); ?></a>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="section-header">
                        <h2><i class="fas fa-user-edit"></i> <?php echo __('profile_edit'); ?></h2>
                    </div>
                    <form method="POST" action="/profile/edit" class="modern-form">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name"><?php echo __('first_name'); ?></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="first_name" name="first_name" maxlength="100" required value="<?php echo htmlspecialchars((string) ($user['first_name'] ?? '')); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="last_name"><?php echo __('last_name'); ?></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="last_name" name="last_name" maxlength="100" required value="<?php echo htmlspecialchars((string) ($user['last_name'] ?? '')); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="profile-phone"><?php echo __('phone'); ?></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-phone"></i>
                                    <input type="text" name="phone" id="profile-phone" required
                                        data-phone-mask="<?php echo htmlspecialchars(normalize_phone_mask((string) get_setting('phone_mask', '+38 (###) ###-##-##'))); ?>"
                                        placeholder="<?php echo htmlspecialchars(normalize_phone_mask((string) get_setting('phone_mask', '+38 (###) ###-##-##'))); ?>"
                                        value="<?php echo htmlspecialchars((string) ($user['phone'] ?? '')); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email"><?php echo __('email'); ?></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="email" name="email" maxlength="255" required value="<?php echo htmlspecialchars((string) ($user['email'] ?? '')); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> <?php echo __('crm_save'); ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<style>
    .profile-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1rem 0;
    }

    .profile-header-section {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border-left: 5px solid var(--primary);
    }

    .profile-user-info {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .user-avatar {
        width: 80px;
        height: 80px;
        background: var(--light);
        color: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        border: 3px solid #fff;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .user-details h1 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
    }

    .user-details p {
        margin: 0.3rem 0 0;
        color: #64748b;
        font-size: 1rem;
    }

    .profile-layout {
        display: flex;
        gap: 2rem;
        align-items: flex-start;
    }

    .profile-sidebar {
        flex: 0 0 280px;
        position: sticky;
        top: 100px;
    }

    .profile-nav {
        background: white;
        padding: 1rem;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .profile-nav .nav-link {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.2rem;
        border-radius: 10px;
        text-decoration: none;
        color: #475569;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .profile-nav .nav-link i {
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
    }

    .profile-nav .nav-link:hover {
        background-color: #f8fafc;
        color: var(--primary);
        transform: translateX(5px);
    }

    .profile-nav .nav-link.active {
        background-color: var(--primary);
        color: white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }

    .profile-nav .admin-link {
        margin-top: 1rem;
        border-top: 1px solid #f1f5f9;
        padding-top: 1.5rem;
        color: #6366f1;
    }

    .profile-main-content {
        flex: 1;
        min-width: 0;
    }

    .content-card {
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .section-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .section-header h2 {
        margin: 0;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        color: var(--dark);
    }

    /* Modern Table */
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
        margin-top: -10px;
    }

    .modern-table th {
        padding: 1rem;
        text-align: left;
        color: #64748b;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modern-table td {
        padding: 1.2rem 1rem;
        background: #fff;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
    }

    .modern-table tr td:first-child {
        border-left: 1px solid #f1f5f9;
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
    }

    .modern-table tr td:last-child {
        border-right: 1px solid #f1f5f9;
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
    }

    .order-id { font-weight: 700; color: var(--primary); }
    .order-total { font-weight: 700; font-size: 1.1rem; }

    .item-badge {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        padding: 0.3rem 0.7rem;
        border-radius: 6px;
        font-size: 0.85rem;
        margin: 2px;
    }

    .item-qty {
        margin-left: 0.5rem;
        color: var(--primary);
        font-weight: 700;
    }

    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-pending { background: #fef3c7; color: #92400e; }
    .status-completed { background: #d1fae5; color: #065f46; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }

    /* Order Details Expansion */
    .order-row { cursor: pointer; transition: background 0.2s; }
    .order-row:hover { background-color: #f8fafc !important; }
    .toggle-icon { font-size: 0.8rem; margin-right: 0.5rem; transition: transform 0.3s; color: #94a3b8; }
    .order-row.active .toggle-icon { transform: rotate(90deg); color: var(--primary); }
    
    .order-details-row td { padding: 0 !important; border: none !important; }
    .order-details-expanded { 
        background: #f8fafc; 
        padding: 2rem; 
        border-bottom: 1px solid #f1f5f9;
        border-left: 1px solid #f1f5f9;
        border-right: 1px solid #f1f5f9;
        border-bottom-left-radius: 10px;
        border-bottom-right-radius: 10px;
        margin: 0 10px 10px 10px;
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .details-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 2rem; }
    .details-col h5 { font-size: 0.95rem; margin-bottom: 1rem; color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
    .details-col p { font-size: 0.9rem; margin-bottom: 0.5rem; }
    .items-list-detailed { display: flex; flex-direction: column; gap: 0.8rem; }
    .item-detail { display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px dotted #e2e8f0; }
    .item-name { font-weight: 500; font-size: 0.9rem; }
    .item-info { font-size: 0.85rem; color: #64748b; }
    .order-comment { font-style: italic; color: #64748b; font-size: 0.85rem; }

    @media (max-width: 768px) {
        .details-grid { grid-template-columns: 1fr; gap: 1.5rem; }
    }

    /* Modern Form */
    .modern-form .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .input-with-icon {
        position: relative;
    }

    .input-with-icon i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .input-with-icon input {
        padding-left: 2.8rem !important;
    }

    .form-actions {
        margin-top: 2.5rem;
        padding-top: 2rem;
        border-top: 1px solid #f1f5f9;
    }

    .btn-lg {
        padding: 1rem 2.5rem;
        font-size: 1.1rem;
    }

    .btn-outline-danger {
        border: 2px solid #ef4444;
        color: #ef4444;
        background: transparent;
    }

    .btn-outline-danger:hover {
        background: #ef4444;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-state i {
        font-size: 4rem;
        color: #e2e8f0;
        margin-bottom: 1.5rem;
    }

    .empty-state p {
        font-size: 1.2rem;
        color: #64748b;
        margin-bottom: 2rem;
    }

    @media (max-width: 992px) {
        .profile-layout { flex-direction: column; }
        .profile-sidebar { flex: none; width: 100%; position: static; }
        .profile-nav { flex-direction: row; flex-wrap: wrap; }
        .profile-nav .nav-link { flex: 1; min-width: 150px; justify-content: center; }
        .modern-form .form-row { grid-template-columns: 1fr; }
    }

    @media (max-width: 576px) {
        .profile-header-section { flex-direction: column; text-align: center; gap: 1.5rem; }
        .profile-user-info { flex-direction: column; }
        .profile-nav .nav-link { width: 100%; }
    }
</style>

<script src="/js/phone-mask.js"></script>
<script>
    function toggleOrderDetails(orderId) {
        const detailsRow = document.getElementById('details-' + orderId);
        const icon = document.getElementById('icon-' + orderId);
        const row = icon.closest('.order-row');
        
        if (detailsRow.style.display === 'none') {
            detailsRow.style.display = 'table-row';
            row.classList.add('active');
        } else {
            detailsRow.style.display = 'none';
            row.classList.remove('active');
        }
    }
</script>
