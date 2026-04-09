<div class="page-header">
    <h1 class="page-title">Редагування користувача #<?php echo (int) $user['id']; ?></h1>
    <a href="/admin/users" class="btn btn-outline" style="border: 1px solid #ddd; color: #334155;">
        <i class="fas fa-arrow-left"></i> Назад
    </a>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-body">
        <form action="/admin/users/update/<?php echo (int) $user['id']; ?>" method="POST">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" required value="<?php echo htmlspecialchars((string) ($user['email'] ?? '')); ?>">
            </div>

            <div class="form-group">
                <label for="phone">Телефон</label>
                <input class="form-control" type="text" id="phone" name="phone" value="<?php echo htmlspecialchars((string) ($user['phone'] ?? '')); ?>">
            </div>

            <div class="form-group">
                <label for="role_id">Роль</label>
                <select class="form-control" id="role_id" name="role_id" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo (int) $role['id']; ?>" <?php echo ((int) $role['id'] === (int) ($user['role_id'] ?? 0)) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string) $role['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Новий пароль (необов'язково)</label>
                <input class="form-control" type="password" id="password" name="password" minlength="8" placeholder="Залиште пустим, щоб не змінювати">
                <small style="display: block; margin-top: 0.4rem; color: #64748b;">Мінімум 8 символів.</small>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Зберегти
                </button>
            </div>
        </form>
    </div>
</div>
