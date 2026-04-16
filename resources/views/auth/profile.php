<div class="profile-container">
    <h1><?php echo __('profile'); ?></h1>
    
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
                <?php else: ?>
                    <div class="avatar-placeholder"><?php echo strtoupper(substr($user['first_name'] ?? $user['email'], 0, 1)); ?></div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></h2>
                <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>
                <p class="role"><span class="badge"><?php echo htmlspecialchars($user['role'] ?? 'customer'); ?></span></p>
            </div>
        </div>
        
        <div class="profile-details">
            <div class="detail-group">
                <label><?php echo __('phone'); ?>:</label>
                <span><?php echo htmlspecialchars($user['phone'] ?? '—'); ?></span>
            </div>
            <div class="detail-group">
                <label><?php echo __('last_login'); ?>:</label>
                <span><?php echo htmlspecialchars($user['last_login'] ?? '—'); ?></span>
            </div>
            <div class="detail-group">
                <label><?php echo __('member_since'); ?>:</label>
                <span><?php echo htmlspecialchars($user['created_at']); ?></span>
            </div>
        </div>
        
        <div class="profile-actions">
            <a href="/profile/edit" class="btn btn-primary"><?php echo __('edit_profile'); ?></a>
            <form action="/logout" method="POST" style="display:inline-block;">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                <button type="submit" class="btn btn-danger"><?php echo __('logout'); ?></button>
            </form>
        </div>
    </div>
</div>

<style>
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem 0;
    }
    .profile-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 2rem;
    }
    .profile-header {
        display: flex;
        align-items: center;
        gap: 2rem;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #eee;
    }
    .avatar-placeholder {
        width: 100px;
        height: 100px;
        background: #2563eb;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        border-radius: 50%;
    }
    .profile-info h2 {
        margin: 0 0 0.5rem 0;
    }
    .profile-info .email {
        color: #64748b;
        margin-bottom: 0.5rem;
    }
    .badge {
        background: #e2e8f0;
        padding: 0.2rem 0.6rem;
        border-radius: 4px;
        font-size: 0.8rem;
        text-transform: uppercase;
    }
    .profile-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .detail-group label {
        display: block;
        font-weight: bold;
        color: #64748b;
        margin-bottom: 0.2rem;
    }
    .profile-actions {
        display: flex;
        gap: 1rem;
    }
</style>
