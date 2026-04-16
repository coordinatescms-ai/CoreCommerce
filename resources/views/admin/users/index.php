<div class="page-header">
    <h1 class="page-title">Користувачі</h1>
</div>

<div class="card">
    <div class="card-body">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; text-align: left;">
                    <th style="padding: 1rem;">ID</th>
                    <th style="padding: 1rem;">Email</th>
                    <th style="padding: 1rem;">Дата реєстрації</th>
                    <th style="padding: 1rem;">Роль</th>
                    <th style="padding: 1rem;">Телефон</th>
                    <th style="padding: 1rem; text-align: right;">Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;"><?php echo (int) $user['id']; ?></td>
                        <td style="padding: 1rem;"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td style="padding: 1rem;"><?php echo htmlspecialchars((string) $user['created_at']); ?></td>
                        <td style="padding: 1rem;"><?php echo htmlspecialchars((string) ($user['role_name'] ?? '—')); ?></td>
                        <td style="padding: 1rem;"><?php echo htmlspecialchars((string) ($user['phone'] ?? '—')); ?></td>
                        <td style="padding: 1rem; text-align: right; white-space: nowrap;">
                            <a href="/admin/users/edit/<?php echo (int) $user['id']; ?>" class="btn btn-outline" style="border: 1px solid #ddd; color: #2563eb;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="/admin/users/delete/<?php echo (int) $user['id']; ?>" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Ви впевнені, що хочете видалити цього користувача?')">
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                                <button type="submit" class="btn btn-outline" style="border: 1px solid #ddd; color: #ef4444;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="padding: 2rem; text-align: center; color: #64748b;">Користувачів не знайдено.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
