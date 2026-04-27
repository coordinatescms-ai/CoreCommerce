<div class="page-header">
    <h1 class="page-title">Управління контентом</h1>
    <a href="/admin/content/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Новий контент
    </a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Заголовок</th>
            <th>Slug (URL)</th>
            <th>Статус</th>
            <th>Дата</th>
            <th>Дії</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pages as $page): ?>
        <tr>
            <td><?= $page['id'] ?></td>
            <td><strong><?= htmlspecialchars($page['title']) ?></strong></td>
            <td><code>/<?= htmlspecialchars($page['slug']) ?></code></td>
            <td>
                <?php if ($page['is_active']): ?>
                    <span class="badge badge-success">Опубліковано</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Чернетка</span>
                <?php endif; ?>
            </td>
            <td><?= date('d.m.Y', strtotime($page['created_at'])) ?></td>
            <td class="actions">
                <a href="/admin/content/edit/<?= $page['id'] ?>" class="btn-edit" title="Редагувати">
                    <i class="fas fa-edit"></i>
                </a>
                
                <form action="/admin/content/delete/<?= $page['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('Ви впевнені?')">
                    <button type="submit" class="btn-delete" title="Видалити">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .admin-table th, .admin-table td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
    .badge-success { color: green; font-weight: bold; }
    .badge-secondary { color: gray; }
    .btn-edit { color: #f39c12; margin-right: 10px; }
    .btn-delete { color: #e74c3c; border: none; background: none; cursor: pointer; }
    .btn-add { background: #2ecc71; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; }
</style>