<?php
$fullName = (string) ($crmData['profile']['full_name'] ?? __('crm_unknown_name'));
$userGroup = (string) ($crmData['group'] ?? 'regular');
$groupLabels = [
    'regular' => __('crm_user_group_regular'),
    'vip' => __('crm_user_group_vip'),
    'wholesale' => __('crm_user_group_wholesale'),
];
$isBlocked = !empty($crmData['security']['is_blocked']);
$registeredAtRaw = (string) ($crmData['profile']['registered_at'] ?? '');
$registeredAt = $registeredAtRaw !== '' ? date('d.m.Y H:i', strtotime($registeredAtRaw)) : '—';
$lastOrderRaw = (string) ($crmData['stats']['last_order_at'] ?? '');
$lastOrderAt = $lastOrderRaw !== '' ? date('d.m.Y H:i', strtotime($lastOrderRaw)) : '—';
?>

<style>
.crm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1rem;
}
.crm-card-title {
    margin: 0 0 1rem;
    font-size: 1.05rem;
}
.crm-list {
    margin: 0;
    padding-left: 1.2rem;
    color: #334155;
}
.crm-kv {
    display: grid;
    grid-template-columns: minmax(140px, 180px) 1fr;
    gap: .4rem .75rem;
    margin: 0;
}
.crm-kv dt {
    color: #64748b;
    font-weight: 600;
}
.crm-kv dd {
    margin: 0;
    color: #0f172a;
}
.crm-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}
.crm-actions .btn {
    font-size: .9rem;
}
.crm-note {
    margin-top: .75rem;
    color: #64748b;
    font-size: .85rem;
}
</style>

<div class="page-header">
    <h1 class="page-title"><?php echo __('crm_page_title'); ?> #<?php echo (int) $user['id']; ?></h1>
    <a href="/admin/users" class="btn btn-outline" style="border: 1px solid #ddd; color: #334155;">
        <i class="fas fa-arrow-left"></i> <?php echo __('crm_back'); ?>
    </a>
</div>

<div class="crm-grid">
    <div class="card">
        <div class="card-body">
            <h2 class="crm-card-title"><?php echo __('crm_info_card_title'); ?></h2>
            <dl class="crm-kv">
                <dt><?php echo __('crm_profile_name'); ?></dt>
                <dd><?php echo htmlspecialchars($fullName); ?></dd>
                <dt><?php echo __('crm_profile_email'); ?></dt>
                <dd><?php echo htmlspecialchars((string) ($user['email'] ?? '')); ?></dd>
                <dt><?php echo __('crm_profile_phone'); ?></dt>
                <dd>
                    <?php echo htmlspecialchars((string) ($user['phone'] ?? '—')); ?>
                    <?php if (!empty($user['phone'])): ?>
                        <a href="tel:<?php echo urlencode((string) $user['phone']); ?>" class="btn btn-outline" style="margin-left: .5rem;"><?php echo __('crm_call_now'); ?></a>
                    <?php endif; ?>
                </dd>
                <dt><?php echo __('crm_profile_registered_at'); ?></dt>
                <dd><?php echo htmlspecialchars($registeredAt); ?></dd>
                <dt><?php echo __('crm_location_primary'); ?></dt>
                <dd><?php echo htmlspecialchars((string) ($crmData['locations']['primary'] ?? '—')); ?></dd>
                <dt><?php echo __('crm_location_additional'); ?></dt>
                <dd>
                    <ul class="crm-list">
                        <?php foreach (($crmData['locations']['additional'] ?? []) as $address): ?>
                            <li><?php echo htmlspecialchars((string) $address); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </dd>
                <dt><?php echo __('crm_user_group'); ?></dt>
                <dd><?php echo htmlspecialchars((string) ($groupLabels[$userGroup] ?? __('crm_user_group_regular'))); ?></dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="crm-card-title"><?php echo __('crm_statistics_title'); ?></h2>
            <dl class="crm-kv">
                <dt><?php echo __('crm_stats_orders_count'); ?></dt>
                <dd><?php echo (int) ($crmData['stats']['orders_count'] ?? 0); ?></dd>
                <dt><?php echo __('crm_stats_ltv'); ?></dt>
                <dd><?php echo number_format((float) ($crmData['stats']['ltv'] ?? 0), 2, '.', ' '); ?></dd>
                <dt><?php echo __('crm_stats_avg_check'); ?></dt>
                <dd><?php echo number_format((float) ($crmData['stats']['average_check'] ?? 0), 2, '.', ' '); ?></dd>
                <dt><?php echo __('crm_stats_last_order'); ?></dt>
                <dd><?php echo htmlspecialchars($lastOrderAt); ?></dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="crm-card-title"><?php echo __('crm_orders_management_title'); ?></h2>
            <h3 style="margin: 0 0 .5rem;"><?php echo __('crm_orders_history'); ?></h3>
            <ul class="crm-list" style="margin-bottom: 1rem;">
                <?php foreach (($crmData['orders'] ?? []) as $order): ?>
                    <li>
                        <a href="/admin/orders" title="<?php echo __('crm_open_orders_list'); ?>">
                            #<?php echo (int) ($order['id'] ?? 0); ?>
                        </a>
                        — <?php echo htmlspecialchars((string) ($order['date'] ?? '')); ?>,
                        <?php echo number_format((float) ($order['total'] ?? 0), 2, '.', ' '); ?>,
                        <?php echo htmlspecialchars((string) ($order['status'] ?? '')); ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h3 style="margin: 0 0 .5rem;"><?php echo __('crm_live_cart'); ?></h3>
            <ul class="crm-list" id="crm-live-cart" style="margin-bottom: 1rem;">
                <?php foreach (($crmData['live_cart'] ?? []) as $item): ?>
                    <li><?php echo htmlspecialchars((string) ($item['product'] ?? '')); ?> ×<?php echo (int) ($item['qty'] ?? 0); ?></li>
                <?php endforeach; ?>
            </ul>
            <div class="crm-note"><?php echo __('crm_live_cart_auto_refresh'); ?>: <?php echo (int) ($cartRefreshSeconds ?? 15); ?>s.</div>

            <h3 style="margin: 1rem 0 .5rem;"><?php echo __('crm_wishlist_title'); ?></h3>
            <ul class="crm-list">
                <?php foreach (($crmData['wishlist'] ?? []) as $wishItem): ?>
                    <li><?php echo htmlspecialchars((string) $wishItem); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="crm-card-title"><?php echo __('crm_actions_title'); ?></h2>
            <div class="crm-actions">
                <a href="#user-edit-form" class="btn btn-outline"><?php echo __('crm_action_edit_user'); ?></a>
                <a href="/checkout?admin_user_id=<?php echo (int) $user['id']; ?>&prefill_email=<?php echo urlencode((string) ($user['email'] ?? '')); ?>" class="btn btn-primary"><?php echo __('crm_action_create_order'); ?></a>
                <a href="mailto:<?php echo urlencode((string) ($user['email'] ?? '')); ?>" class="btn btn-outline"><?php echo __('crm_action_send_email'); ?></a>
            </div>

            <h3 style="margin: 1rem 0 .5rem;"><?php echo __('crm_activity_log_title'); ?></h3>
            <ul class="crm-list">
                <?php foreach (($crmData['activity_log'] ?? []) as $entry): ?>
                    <li><?php echo htmlspecialchars((string) $entry); ?></li>
                <?php endforeach; ?>
            </ul>

            <h3 style="margin: 1rem 0 .5rem;"><?php echo __('crm_bonus_title'); ?></h3>
            <div class="crm-actions">
                <button type="button" class="btn btn-outline" data-bonus-adjust="+50"><?php echo __('crm_bonus_add_points'); ?></button>
                <button type="button" class="btn btn-outline" data-bonus-adjust="-50"><?php echo __('crm_bonus_subtract_points'); ?></button>
                <span id="crm-bonus-balance"><?php echo __('crm_bonus_balance'); ?>: <?php echo (int) ($crmData['bonus']['balance'] ?? 0); ?></span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="crm-card-title"><?php echo __('crm_security_title'); ?></h2>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="crm-is-blocked" <?php echo $isBlocked ? 'checked' : ''; ?>>
                    <?php echo __('crm_security_block_user'); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="crm-subscribe-email" <?php echo !empty($crmData['subscriptions']['marketing_email']) ? 'checked' : ''; ?>>
                    <?php echo __('crm_subscriptions_email'); ?>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="crm-subscribe-sms" <?php echo !empty($crmData['subscriptions']['marketing_sms']) ? 'checked' : ''; ?>>
                    <?php echo __('crm_subscriptions_sms'); ?>
                </label>
            </div>
            <div class="crm-note"><?php echo __('crm_mock_note'); ?></div>
        </div>
    </div>
</div>

<div class="card" style="margin-top: 1rem; max-width: 760px;">
    <div class="card-body">
        <h2 class="crm-card-title" id="user-edit-form"><?php echo __('crm_edit_form_title'); ?></h2>
        <form action="/admin/users/update/<?php echo (int) $user['id']; ?>" method="POST">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

            <div class="form-group">
                <label for="email"><?php echo __('crm_profile_email'); ?></label>
                <input class="form-control" type="email" id="email" name="email" required value="<?php echo htmlspecialchars((string) ($user['email'] ?? '')); ?>">
            </div>

            <div class="form-group">
                <label for="phone"><?php echo __('crm_profile_phone'); ?></label>
                <input class="form-control" type="text" id="phone" name="phone" value="<?php echo htmlspecialchars((string) ($user['phone'] ?? '')); ?>">
            </div>

            <div class="form-group">
                <label for="role_id"><?php echo __('crm_role_label'); ?></label>
                <select class="form-control" id="role_id" name="role_id" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo (int) $role['id']; ?>" <?php echo ((int) $role['id'] === (int) ($user['role_id'] ?? 0)) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $role['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="password"><?php echo __('crm_change_password'); ?></label>
                <input class="form-control" type="password" id="password" name="password" minlength="8" placeholder="<?php echo __('crm_change_password_placeholder'); ?>">
                <small style="display: block; margin-top: 0.4rem; color: #64748b;"><?php echo __('crm_password_minimum_note'); ?></small>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('crm_save'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const refreshSeconds = <?php echo (int) ($cartRefreshSeconds ?? 15); ?>;
    const liveCart = document.getElementById('crm-live-cart');
    const bonusBalance = document.getElementById('crm-bonus-balance');
    let points = <?php echo (int) ($crmData['bonus']['balance'] ?? 0); ?>;

    function mockRefreshCart() {
        if (!liveCart) return;
        liveCart.dataset.lastUpdated = String(Date.now());
    }

    setInterval(mockRefreshCart, refreshSeconds * 1000);

    document.querySelectorAll('[data-bonus-adjust]').forEach((button) => {
        button.addEventListener('click', function () {
            const delta = Number(this.getAttribute('data-bonus-adjust') || 0);
            points += delta;
            if (bonusBalance) {
                bonusBalance.textContent = "<?php echo addslashes(__('crm_bonus_balance')); ?>: " + points;
            }
        });
    });
})();
</script>
