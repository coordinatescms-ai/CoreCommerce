<div class="profile-container">
    <h1><?php echo __('profile'); ?></h1>
    
    <div class="profile-layout">
        <!-- Ліва бічна панель -->
        <aside class="profile-sidebar">
            <nav class="profile-nav">
                <a href="/orders" class="nav-link">
                    <svg xmlns="http://w3.org" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <?php echo __('Мої замовлення'); ?>
                </a>
                <a href="/favorites" class="nav-link">
                    <svg xmlns="http://w3.org" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    <?php echo __('Обране'); ?>
                </a>
                <a href="/profile/edit" class="nav-link active">
                    <svg xmlns="http://w3.org" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <?php echo __('Редагувати'); ?>
                </a>
            </nav>
        </aside>

        <!-- Основна картка (Правий блок) -->
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
                <form action="/logout" method="POST" style="display:inline-block;">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
                    <button type="submit" class="btn btn-danger"><?php echo __('logout'); ?></button>
                </form>
            </div>
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

    /* Контейнер профілю - задаємо Poppins тут, щоб не чіпати body */
.profile-container {
    font-family: 'Poppins', sans-serif;
    max-width: 1000px; /* Трохи розширив для двоколонкового вигляду */
    margin: 0 auto;
    padding: 2rem 1rem;
}

.profile-container h1 {
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #1e293b;
}

.profile-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
    align-items: start;
}

/* Посилений скид для сайдбару */
.profile-container .profile-sidebar {
    background: white !important;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 0 !important; /* Прибираємо внутрішні відступи контейнера */
    overflow: hidden;
}

.profile-container .profile-nav {
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important; /* Розтягуємо посилання на всю ширину */
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    list-style: none !important;
}

/* Посилений стиль для посилань */
.profile-container .nav-link {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important; /* Текст тільки зліва */
    width: 100% !important;
    box-sizing: border-box !important;
    padding: 12px 20px !important;
    margin: 0 !important;
    text-align: left !important; /* Чітке вирівнювання ліворуч */
    color: #64748b !important;
    text-decoration: none !important;
    font-size: 0.95rem !important;
    border-left: 4px solid transparent !important;
    background: transparent !important;
    transition: all 0.2s ease !important;
}

/* Стан при наведенні */
.profile-container .nav-link:hover {
    background-color: #f8fafc !important;
    color: #2563eb !important;
}

/* Активний стан (Редагувати) */
.profile-container .nav-link.active {
    background-color: #eff6ff !important; /* Світло-блакитний фон на всю ширину */
    color: #2563eb !important;
    border-left: 4px solid #2563eb !important;
    font-weight: 600 !important;
}



/* Картка профілю */
.profile-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    padding: 2.5rem;
}

.profile-info h2 {
    font-weight: 600;
    font-size: 1.5rem;
    margin: 0 0 0.5rem 0;
}

/* Деталі профілю (використовуємо Segoe UI для значень) */
.detail-group label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #94a3b8;
    font-weight: 600;
}

.detail-group span {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    font-weight: 500;
    color: #334155;
}

/* Кнопки */
.btn {
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    padding: 0.6rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    border: none;
    transition: filter 0.2s;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn:hover {
    filter: brightness(0.9);
}

/* Стиль іконок всередині посилань */
.profile-container .nav-link svg {
    margin-right: 12px;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}

/* Роздільники між пунктами */
.profile-container .nav-link {
    border-bottom: 1px solid #f1f5f9 !important; /* Лінія під кожним пунктом */
}

/* Прибираємо останню лінію, щоб не псувала радіус кутів */
.profile-container .nav-link:last-child {
    border-bottom: none !important;
}

/* Анімація іконки при наведенні */
.profile-container .nav-link:hover svg {
    transform: scale(1.1);
    color: #2563eb;
}

/* Активний пункт - іконка теж синя */
.profile-container .nav-link.active svg {
    color: #2563eb;
}

/* Адаптивність */
@media (max-width: 992px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }
    
    .profile-nav {
        flex-direction: row;
        border-bottom: 1px solid #eee;
    }
    
    .nav-link {
        border-left: none;
        border-bottom: 3px solid transparent;
        flex: 1;
        text-align: center;
    }
    
    .nav-link.active {
        border-bottom-color: #2563eb;
    }
}
</style>
