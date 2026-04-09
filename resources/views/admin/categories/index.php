<div class="page-header">
    <h1 class="page-title">Управління категоріями</h1>
    <a href="/admin/categories/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Нова категорія
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; text-align: left;">
                    <th style="padding: 1rem;">Назва</th>
                    <th style="padding: 1rem;">Slug</th>
                    <th style="padding: 1rem;">Батьківська</th>
                    <th style="padding: 1rem;">Товарів</th>
                    <th style="padding: 1rem; text-align: right;">Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;">
                            <?php echo str_repeat('&mdash; ', $category['level'] ?? 0); ?>
                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                        </td>
                        <td style="padding: 1rem; color: #64748b; font-size: 0.85rem;">
                            /category/<?php echo htmlspecialchars($category['slug']); ?>
                        </td>
                        <td style="padding: 1rem;">
                            <?php 
                                $parent = $category['parent_id'] ? \App\Models\Category::findById($category['parent_id']) : null;
                                echo $parent ? htmlspecialchars($parent['name']) : '<span style="color: #94a3b8;">Немає</span>';
                            ?>
                        </td>
                        <td style="padding: 1rem;">
                            <?php echo \App\Models\Category::getProductCount($category['id']); ?>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <a href="/admin/categories/edit/<?php echo $category['id']; ?>" class="btn btn-outline" style="border: 1px solid #ddd; color: #2563eb;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="/admin/categories/delete/<?php echo (int)$category['id']; ?>" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Ви впевнені, що хочете видалити цю категорію?')">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                                <button type="submit" class="btn btn-outline" style="border: 1px solid #ddd; color: #ef4444;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: #64748b;">
                            Категорій поки що не створено.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
