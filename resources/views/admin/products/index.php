<div class="page-header">
    <h1 class="page-title">Управління товарами</h1>
    <a href="/admin/products/create" class="btn btn-primary">
        <i class="fas fa-plus"></i> Новий товар
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #eee; text-align: left;">
                    <th style="padding: 1rem;">Товар</th>
                    <th style="padding: 1rem;">Категорія</th>
                    <th style="padding: 1rem;">Ціна</th>
                    <th style="padding: 1rem;">Slug</th>
                    <th style="padding: 1rem; text-align: right;">Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;">
                            <div style="display:flex; gap: 0.75rem; align-items: center;">
                                <?php if (!empty($product['image'])): ?>
                                    <img
                                        src="<?php echo htmlspecialchars(product_image_variant_path((string) $product['image'], 'thumb')); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        style="width: 52px; height: 52px; border-radius: 6px; object-fit: cover; border: 1px solid #e2e8f0;"
                                    >
                                <?php else: ?>
                                    <div style="width: 52px; height: 52px; border-radius: 6px; display:flex; align-items:center; justify-content:center; background:#f8fafc; border: 1px solid #e2e8f0; color:#94a3b8;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <div style="font-size: 0.8rem; color: #64748b;">ID: <?php echo (int)$product['id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <?php if (!empty($product['category_name'])): ?>
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            <?php else: ?>
                                <span style="color: #94a3b8;">Без категорії</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; font-weight: 600;">
                            <?php echo number_format((float)$product['price'], 2, ',', ' '); ?> грн
                        </td>
                        <td style="padding: 1rem; color: #64748b; font-size: 0.85rem;">
                            /product/<?php echo htmlspecialchars($product['slug']); ?>
                        </td>
                        <td style="padding: 1rem; text-align: right; white-space: nowrap;">
                            <a href="/admin/products/show/<?php echo $product['id']; ?>"class="btn btn-outline" style="border: 1px solid #ddd; color: #0f766e;" title="Перегляд">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </a>
                            <a href="/admin/products/edit/<?php echo $product['id']; ?>" class="btn btn-outline" style="border: 1px solid #ddd; color: #2563eb;" title="Редагувати">
                                <i class="fas fa-edit" aria-hidden="true"></i>
                            </a>
                            <form
                                action="/admin/products/delete/<?php echo (int)$product['id']; ?>"
                                method="POST"
                                style="display: inline-block; margin: 0;"
                                onsubmit="return confirm('Ви впевнені, що хочете видалити цей товар?')"
                            >
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                                <button
                                    type="submit"
                                    class="btn btn-outline"
                                    style="border: 1px solid #ddd; color: #ef4444;"
                                    title="Видалити"
                                >
                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: #64748b;">
                            Товарів поки що не створено.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
