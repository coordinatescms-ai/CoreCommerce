<div class="page-header">
    <h1 class="page-title">Редагування товару: <?php echo htmlspecialchars($product['name']); ?></h1>
    <a href="/admin/products" class="btn btn-outline" style="border: 1px solid #ddd; color: #64748b;">
        <i class="fas fa-arrow-left"></i> Назад до списку
    </a>
</div>

<form action="/admin/products/update/<?php echo $product['id']; ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Основна інформація
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="name">Назва товару</label>
                <input type="text" name="name" id="name" class="form-control" required value="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="form-group">
                <label for="slug">Slug (URL посилання)</label>
                <input type="text" name="slug" id="slug" class="form-control" required value="<?php echo htmlspecialchars($product['slug']); ?>">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="price">Ціна (грн)</label>
                    <input type="number" min="0" step="0.01" inputmode="decimal" name="price" id="price" class="form-control" required value="<?php echo number_format((float)$product['price'], 2, '.', ''); ?>">
                </div>
                <div class="form-group">
                    <label for="category_id">Категорія</label>
                    <select name="category_id" id="category_id" class="form-control">
                        <option value="">-- Без категорії --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ((int)$cat['id'] === (int)$product['category_id']) ? 'selected' : ''; ?>>
                                <?php echo str_repeat('— ', $cat['level'] ?? 0) . htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="description">Опис товару</label>
                <textarea name="description" id="description" class="form-control" rows="6"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Характеристики товару
        </div>
        <div class="card-body">
            <p style="margin-top: 0; color: #64748b;">Редагуйте характеристики або додавайте нові прямо на цій сторінці.</p>

            <datalist id="attribute-name-suggestions">
                <?php foreach (($attributeNameSuggestions ?? []) as $attributeName): ?>
                    <option value="<?php echo htmlspecialchars($attributeName); ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <div id="attribute-rows" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php $rows = $existingAttributes ?? []; ?>
                <?php if (empty($rows)): ?>
                    <div class="attribute-row" style="display:grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem;">
                        <input type="text" name="attribute_name[]" class="form-control" list="attribute-name-suggestions" placeholder="Назва атрибута (напр. Колір)">
                        <input type="text" name="attribute_value[]" class="form-control" placeholder="Значення (напр. Чорний)">
                        <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;">Видалити</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <div class="attribute-row" style="display:grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem;">
                            <input type="text" name="attribute_name[]" class="form-control" list="attribute-name-suggestions" value="<?php echo htmlspecialchars($row['attribute_name'] ?? ''); ?>" placeholder="Назва атрибута (напр. Колір)">
                            <input type="text" name="attribute_value[]" class="form-control" value="<?php echo htmlspecialchars($row['value'] ?? ''); ?>" placeholder="Значення (напр. Чорний)">
                            <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;">Видалити</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="margin-top: 1rem;">
                <button type="button" id="add-attribute-row" class="btn btn-outline" style="border: 1px solid #ddd; color: #2563eb;">
                    <i class="fas fa-plus"></i> Додати характеристику
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-image"></i> Зображення товару
        </div>
        <div class="card-body">
            <?php if (!empty($product['image'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width: 150px; border-radius: 4px; border: 1px solid #ddd;">
                    <p style="font-size: 0.85rem; color: #64748b;">Поточне фото</p>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="image">Змінити головне фото</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*">
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-search"></i> SEO налаштування
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="meta_title">Meta Title</label>
                <input type="text" name="meta_title" id="meta_title" class="form-control" value="<?php echo htmlspecialchars($product['meta_title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="meta_description">Meta Description</label>
                <textarea name="meta_description" id="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($product['meta_description'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
        <a href="/admin/products/show/<?php echo $product['id']; ?>" class="btn btn-outline" style="border: 1px solid #ddd; color: #0f766e;">
            <i class="fas fa-eye"></i> Перегляд
        </a>
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> Зберегти зміни
        </button>
    </div>
</form>

<script>
    (function () {
        const rowsContainer = document.getElementById('attribute-rows');
        const addRowButton = document.getElementById('add-attribute-row');

        function bindRemoveButton(button) {
            button.addEventListener('click', function () {
                const rows = rowsContainer.querySelectorAll('.attribute-row');
                if (rows.length === 1) {
                    rows[0].querySelectorAll('input').forEach(input => {
                        input.value = '';
                    });
                    return;
                }

                button.closest('.attribute-row').remove();
            });
        }

        function createRow() {
            const row = document.createElement('div');
            row.className = 'attribute-row';
            row.style.display = 'grid';
            row.style.gridTemplateColumns = '1fr 1fr auto';
            row.style.gap = '0.75rem';

            row.innerHTML = `
                <input type="text" name="attribute_name[]" class="form-control" list="attribute-name-suggestions" placeholder="Назва атрибута (напр. Колір)">
                <input type="text" name="attribute_value[]" class="form-control" placeholder="Значення (напр. Чорний)">
                <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;">Видалити</button>
            `;

            const removeButton = row.querySelector('.attribute-remove-btn');
            bindRemoveButton(removeButton);

            return row;
        }

        addRowButton.addEventListener('click', function () {
            rowsContainer.appendChild(createRow());
        });

        rowsContainer.querySelectorAll('.attribute-remove-btn').forEach(bindRemoveButton);
    })();
</script>
