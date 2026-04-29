<div class="profile-container">
    <h1><?php echo __('profile'); ?></h1>

    <div class="profile-layout">
        <aside class="profile-sidebar">
            <nav class="profile-nav">
                <a href="/profile/orders" class="nav-link <?php echo ($activeTab ?? '') === 'orders' ? 'active' : ''; ?>"><?php echo __('profile_my_orders'); ?></a>
                <a href="/profile/favorites" class="nav-link <?php echo ($activeTab ?? '') === 'favorites' ? 'active' : ''; ?>"><?php echo __('profile_favorites'); ?></a>
                <a href="/profile/edit" class="nav-link <?php echo ($activeTab ?? '') === 'edit' ? 'active' : ''; ?>"><?php echo __('profile_edit'); ?></a>
                <?php if (in_array($user['role'] ?? '', ['admin', 'moderator'], true)): ?>
                    <a href="/admin" class="nav-link"><?php echo __('profile_admin_panel'); ?></a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="profile-card">
            <?php if (!empty($_SESSION['errors'])): ?>
                <div class="alert alert-danger"><?php foreach ($_SESSION['errors'] as $error): ?><div><?php echo htmlspecialchars($error); ?></div><?php endforeach; unset($_SESSION['errors']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (($activeTab ?? '') === 'orders'): ?>
                <h2><?php echo __('profile_my_orders'); ?></h2>
                <?php if (!empty($orders)): ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">№ замовлення</th>
                                    <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Дата</th>
                                    <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Товари</th>
                                    <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Сума</th>
                                    <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td style="padding:10px; border-bottom:1px solid #f1f5f9;">#<?php echo (int) ($order['id'] ?? 0); ?></td>
                                        <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo htmlspecialchars((string) ($order['created_at'] ?? '')); ?></td>
                                        <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                            <?php if (!empty($order['items'])): ?>
                                                <ul style="margin:0; padding-left:18px;">
                                                    <?php foreach ((array) $order['items'] as $item): ?>
                                                        <li>
                                                            <?php echo htmlspecialchars((string) ($item['product_name'] ?? '')); ?>
                                                            — <?php echo (int) ($item['qty'] ?? 0); ?> шт.
                                                            × <?php echo number_format((float) ($item['price'] ?? 0), 2, '.', ' '); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <span style="color:#6b7280;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo number_format((float) ($order['total'] ?? 0), 2, '.', ' '); ?></td>
                                        <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo htmlspecialchars((string) ($order['status_label'] ?? $order['status'] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p><?php echo __('profile_no_orders'); ?></p>
                <?php endif; ?>
            <?php elseif (($activeTab ?? '') === 'favorites'): ?>
                <h2><?php echo __('profile_favorites'); ?></h2>
                <?php if (!empty($favorites)): foreach ($favorites as $item): ?>
                    <div><a href="/product/<?php echo urlencode($item['slug']); ?>"><?php echo htmlspecialchars((string) $item['name']); ?></a> — <?php echo number_format((float) $item['price'], 2); ?></div>
                <?php endforeach; else: ?>
                    <p><?php echo __('profile_no_favorites'); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <h2><?php echo __('profile_edit'); ?></h2>
                <form method="POST" action="/profile/edit">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                    <label><?php echo __('first_name'); ?> <input type="text" name="first_name" maxlength="100" required value="<?php echo htmlspecialchars((string) ($user['first_name'] ?? '')); ?>"></label>
                    <label><?php echo __('last_name'); ?> <input type="text" name="last_name" maxlength="100" required value="<?php echo htmlspecialchars((string) ($user['last_name'] ?? '')); ?>"></label>
                    <label><?php echo __('phone'); ?> <input type="text" name="phone" maxlength="20" required value="<?php echo htmlspecialchars((string) ($user['phone'] ?? '')); ?>"></label>
                    <label><?php echo __('email'); ?> <input type="email" name="email" maxlength="255" required value="<?php echo htmlspecialchars((string) ($user['email'] ?? '')); ?>"></label>
                    <button type="submit" class="btn"><?php echo __('crm_save'); ?></button>
                </form>
            <?php endif; ?>

            <form action="/logout" method="POST" style="margin-top:16px;">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <button type="submit" class="btn"><?php echo __('logout'); ?></button>
            </form>
        </div>
    </div>
</div>
