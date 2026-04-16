<div class="page-header">
    <h1 class="page-title">Перегляд товару</h1>
    <div style="display: flex; gap: 0.75rem;">
        <a href="/admin/products/edit/<?php echo $product['id']; ?>" class="btn btn-outline" style="border: 1px solid #ddd; color: #2563eb;">
            <i class="fas fa-edit"></i> Редагувати
        </a>
        <a href="/admin/products" class="btn btn-outline" style="border: 1px solid #ddd; color: #64748b;">
            <i class="fas fa-arrow-left"></i> До списку
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-box"></i> <?php echo htmlspecialchars($product['name']); ?>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 220px 1fr; gap: 1.5rem; align-items: start;">
            <div>
                <?php if (!empty($product['image'])): ?>
                    <img
                        src="<?php echo htmlspecialchars(product_image_variant_path((string) $product['image'], 'thumb')); ?>"
                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                        style="width: 100%; max-width: 220px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: cover;"
                    >
                <?php else: ?>
                    <div style="width: 220px; height: 220px; border-radius: 8px; display:flex; align-items:center; justify-content:center; background:#f8fafc; border: 1px solid #e2e8f0; color:#94a3b8;">
                        <i class="fas fa-image fa-2x"></i>
                    </div>
                <?php endif; ?>

                <?php if (!empty($galleryImages)): ?>
                    <div style="margin-top:0.75rem; display:grid; grid-template-columns: repeat(3, 1fr); gap:0.5rem;">
                        <?php foreach ($galleryImages as $galleryImage): ?>
                            <img src="<?php echo htmlspecialchars(product_image_variant_path((string) ($galleryImage['image_path'] ?? ''), 'thumb')); ?>" alt="" style="width:100%; height:64px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0;">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div style="display:grid; grid-template-columns: 180px 1fr; row-gap:0.75rem; column-gap:1rem;">
                    <div style="color:#64748b;">ID:</div>
                    <div><strong><?php echo (int)$product['id']; ?></strong></div>

                    <div style="color:#64748b;">Slug:</div>
                    <div>/product/<?php echo htmlspecialchars($product['slug']); ?></div>

                    <div style="color:#64748b;">Категорія:</div>
                    <div>
                        <?php if (!empty($product['category_name'])): ?>
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        <?php else: ?>
                            <span style="color:#94a3b8;">Без категорії</span>
                        <?php endif; ?>
                    </div>

                    <div style="color:#64748b;">Ціна:</div>
                    <div style="font-size: 1.2rem; font-weight: 700;"><?php echo number_format((float)$product['price'], 2, ',', ' '); ?> грн</div>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 1.25rem 0;">

                <div style="color:#64748b; margin-bottom:0.5rem;">Опис:</div>
                <div style="white-space: pre-wrap; line-height: 1.5;">
                    <?php echo !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : '<span style="color:#94a3b8;">Опис відсутній</span>'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-search"></i> SEO налаштування
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns: 180px 1fr; row-gap:0.75rem; column-gap:1rem;">
            <div style="color:#64748b;">Meta Title:</div>
            <div><?php echo !empty($product['meta_title']) ? htmlspecialchars($product['meta_title']) : '<span style="color:#94a3b8;">Не вказано</span>'; ?></div>

            <div style="color:#64748b;">Meta Description:</div>
            <div><?php echo !empty($product['meta_description']) ? htmlspecialchars($product['meta_description']) : '<span style="color:#94a3b8;">Не вказано</span>'; ?></div>
        </div>
    </div>
</div>

<div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
    <form action="/admin/products/delete/<?php echo (int)$product['id']; ?>" method="POST" style="margin: 0;" onsubmit="return confirm('Ви впевнені, що хочете видалити цей товар?')">
        <input type="hidden" name="_method" value="DELETE">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
        <button
            type="submit"
            class="btn btn-outline"
            style="border: 1px solid #ddd; color: #ef4444;"
        >
            <i class="fas fa-trash"></i> Видалити товар
        </button>
    </form>
</div>
