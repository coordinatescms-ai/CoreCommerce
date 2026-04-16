<div class="page-header">
    <h1 class="page-title">Управління атрибутами</h1>
    <a href="/admin/attributes/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Новий атрибут
    </a>
</div>

<div class="card">
    <div class="card-body">
        <p style="margin-top: 0; color: #64748b;">
            Якщо атрибут відв’язати від категорії, значення товарів <strong>не видаляються</strong> — вони просто не показуються для цієї категорії.
        </p>

        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; text-align: left;">
                    <th style="padding: 1rem;">Назва</th>
                    <th style="padding: 1rem;">Тип</th>
                    <th style="padding: 1rem;">Slug</th>
                    <th style="padding: 1rem;">Категорій</th>
                    <th style="padding: 1rem;">Товарів</th>
                    <th style="padding: 1rem; text-align: right;">Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($attributes ?? []) as $attribute): ?>
                    <?php
                        $storedType = (string)($attribute['type'] ?? 'text');
                        $typeLabel = $storedType === 'range' ? 'Число' : ($storedType === 'select' ? 'Список (select)' : 'Текст');
                    ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;">
                            <strong><?php echo htmlspecialchars($attribute['name']); ?></strong>
                            <?php if (!empty($attribute['description'])): ?>
                                <div style="margin-top: 0.35rem; color: #64748b; font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($attribute['description']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem;"><?php echo htmlspecialchars($typeLabel); ?></td>
                        <td style="padding: 1rem; color:#64748b;"><?php echo htmlspecialchars($attribute['slug']); ?></td>
                        <td style="padding: 1rem;"><?php echo (int)($attribute['categories_count'] ?? 0); ?></td>
                        <td style="padding: 1rem;"><?php echo (int)($attribute['products_count'] ?? 0); ?></td>
                        <td style="padding: 1rem; text-align: right;">
                            <a href="/admin/attributes/edit/<?php echo (int)$attribute['id']; ?>" class="btn btn-outline" style="border: 1px solid #ddd; color: #2563eb;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="/admin/attributes/delete/<?php echo (int)$attribute['id']; ?>" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Ви впевнені, що хочете видалити цей атрибут?')">
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                                <button type="submit"
                                        class="btn btn-outline"
                                        style="border: 1px solid #ddd; color: #ef4444;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($attributes)): ?>
                    <tr>
                        <td colspan="6" style="padding: 2rem; text-align: center; color: #64748b;">
                            Атрибутів поки що немає.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
