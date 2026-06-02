<div class="profile-container">
    <h1><?php echo __('profile'); ?></h1>

    <div class="profile-layout">
        <aside class="profile-sidebar">
            <nav class="profile-nav">
                <a href="/profile/orders" class="nav-link <?php echo ($activeTab ?? '') === 'orders' ? 'active' : ''; ?>">
                    <svg style = "width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <!-- Ручка пакета -->
                        <path d="M8 10V7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <!-- Сам пакет -->
                        <path d="M4 9H20V19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5V9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                    <?php echo __('profile_my_orders'); ?>
                </a>
                <a href="/profile/favorites" class="nav-link <?php echo ($activeTab ?? '') === 'favorites' ? 'active' : ''; ?>">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <!-- Серце (Обране) -->
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo __('profile_favorites'); ?>
                </a>
                <a href="/profile/edit" class="nav-link <?php echo ($activeTab ?? '') === 'edit' ? 'active' : ''; ?>">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <!-- Олівець (Редагувати) -->
                        <path d="M11 4H4C2.89543 4 2 4.89543 2 6V20C2 21.1046 2.89543 22 4 22H18C19.1046 22 20 21.1046 20 20V13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18.5 2.5C19.3284 1.67157 20.6716 1.67157 21.5 2.5C22.3284 3.32843 22.3284 4.67157 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo __('profile_edit'); ?>
                </a>
                <?php if (in_array($user['role'] ?? '', ['admin', 'moderator'], true)): ?>
                    <a href="/admin" class="nav-link">
                        <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <!-- Адмін панель (Шестерня) -->
                            <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php echo __('profile_admin_panel'); ?>
                    </a>
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
                <?php if (!empty($favorites)): ?>
                    <div>
                        <table style="width:100%; border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Товар</th>
                                    <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Ціна</th>
                                    <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Додав</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($favorites as $item): ?>
                                    <tr>
                                        <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                            <a href="/product/<?php echo urlencode($item['slug']); ?>"><?php echo htmlspecialchars((string) $item['name']); ?></a>
                                        </td>
                                        <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                            <?php echo number_format((float) $item['price'], 2); ?>
                                        </td>
                                        <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                            <?php echo htmlspecialchars((string) ($item['added_at'] ?? '')); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php  else: ?>
                    <p><?php echo __('profile_no_favorites'); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <h2><?php echo __('profile_edit'); ?></h2>
                <form method="POST" action="/profile/edit">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                    <label><?php echo __('first_name'); ?> <input type="text" name="first_name" maxlength="100" required value="<?php echo htmlspecialchars((string) ($user['first_name'] ?? '')); ?>"></label>
                    <label><?php echo __('last_name'); ?> <input type="text" name="last_name" maxlength="100" required value="<?php echo htmlspecialchars((string) ($user['last_name'] ?? '')); ?>"></label>
                    <label><?php echo __('phone'); ?> <input type="text" name="phone" required inputmode="numeric" pattern="[0-9]+" oninput="this.value=this.value.replace(/[^0-9]/g,'')" value="<?php echo htmlspecialchars((string) ($user['phone'] ?? '')); ?>"></label>
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

