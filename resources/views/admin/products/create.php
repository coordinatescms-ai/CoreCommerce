<div class="page-header">
    <h1 class="page-title">Новий товар</h1>
    <a href="/admin/products" class="btn btn-outline" style="border: 1px solid #ddd; color: #64748b;">
        <i class="fas fa-arrow-left"></i> Назад до списку
    </a>
</div>

<form action="/admin/products/store" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Основна інформація
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="name">Назва товару</label>
                <input type="text" name="name" id="name" class="form-control" required placeholder="Наприклад: iPhone 15 Pro Max">
            </div>

            <div class="form-group">
                <label for="slug">Slug (URL посилання)</label>
                <input type="text" name="slug" id="slug" class="form-control" placeholder="Залиште порожнім для автогенерації">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="price">Ціна (грн)</label>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        name="price"
                        id="price"
                        class="form-control"
                        required
                        placeholder="0.00"
                    >
                </div>
                <div class="form-group">
                    <label for="category_id">Категорія</label>
                    <select name="category_id" id="category_id" class="form-control">
                        <option value="">-- Без категорії --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo str_repeat('— ', $cat['level'] ?? 0) . htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Опис товару</label>
                <textarea name="description" id="description" class="form-control" rows="6" placeholder="Короткий або детальний опис товару..."></textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Характеристики товару
        </div>
        <div class="card-body">
            <p style="margin-top: 0; color: #64748b;">Оберіть категорію, після чого стануть доступними лише дозволені для неї характеристики.</p>
            <div id="attributes-warning" style="display:none; margin-bottom: 0.75rem; color:#b45309; background:#fffbeb; border:1px solid #fde68a; padding:0.5rem 0.75rem; border-radius:6px;"></div>

            <div id="attribute-rows" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div class="attribute-row" style="display:grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem;">
                    <select name="attribute_id[]" class="form-control attribute-id-select">
                        <option value="">Спочатку оберіть категорію</option>
                    </select>
                    <input type="text" name="attribute_value[]" class="form-control" placeholder="Значення (напр. Чорний)">
                    <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;">Видалити</button>
                </div>
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
            <div class="form-group">
                <label for="image">Головне фото</label>
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
                <input type="text" name="meta_title" id="meta_title" class="form-control">
            </div>
            <div class="form-group">
                <label for="meta_description">Meta Description</label>
                <textarea name="meta_description" id="meta_description" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> Створити товар
        </button>
    </div>
</form>

<script>
    (function () {
        const categorySelect = document.getElementById('category_id');
        const rowsContainer = document.getElementById('attribute-rows');
        const addRowButton = document.getElementById('add-attribute-row');
        const warningBox = document.getElementById('attributes-warning');
        let allowedAttributes = <?php echo json_encode($allowedAttributes ?? [], JSON_UNESCAPED_UNICODE); ?>;

        function showWarning(message) {
            warningBox.textContent = message;
            warningBox.style.display = 'block';
        }

        function clearWarning() {
            warningBox.textContent = '';
            warningBox.style.display = 'none';
        }

        function hasCategory() {
            return Number(categorySelect.value || 0) > 0;
        }

        function buildAttributeOptions(selectedId = '') {
            if (!hasCategory()) {
                return '<option value="">Спочатку оберіть категорію</option>';
            }

            if (!allowedAttributes.length) {
                return '<option value="">Немає доступних характеристик</option>';
            }

            const normalizedSelectedId = String(selectedId || '');
            return '<option value="">-- Оберіть характеристику --</option>' + allowedAttributes.map(function (attribute) {
                const attributeId = String(attribute.id);
                const selected = attributeId === normalizedSelectedId ? ' selected' : '';
                return '<option value="' + attributeId + '"' + selected + '>' + attribute.name + '</option>';
            }).join('');
        }

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

        function bindAttributeSelectProtection(select) {
            select.addEventListener('focus', function () {
                if (!hasCategory()) {
                    showWarning('Спочатку потрібно вибрати категорію товару.');
                    select.blur();
                }
            });
        }

        function createRow(attributeId = '', value = '') {
            const row = document.createElement('div');
            row.className = 'attribute-row';
            row.style.display = 'grid';
            row.style.gridTemplateColumns = '1fr 1fr auto';
            row.style.gap = '0.75rem';

            row.innerHTML = `
                <select name="attribute_id[]" class="form-control attribute-id-select">${buildAttributeOptions(attributeId)}</select>
                <input type="text" name="attribute_value[]" class="form-control" placeholder="Значення (напр. Чорний)" value="${value}">
                <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;">Видалити</button>
            `;

            const removeButton = row.querySelector('.attribute-remove-btn');
            bindRemoveButton(removeButton);
            bindAttributeSelectProtection(row.querySelector('.attribute-id-select'));

            return row;
        }

        addRowButton.addEventListener('click', function () {
            if (!hasCategory()) {
                showWarning('Спочатку потрібно вибрати категорію товару.');
                return;
            }

            if (!allowedAttributes.length) {
                showWarning('Для вибраної категорії немає доступних характеристик.');
                return;
            }

            clearWarning();
            rowsContainer.appendChild(createRow());
        });

        function refreshAllRows() {
            rowsContainer.querySelectorAll('.attribute-id-select').forEach(function (select) {
                const currentValue = select.value;
                select.innerHTML = buildAttributeOptions(currentValue);
                if (currentValue && !select.value) {
                    const rowValueInput = select.closest('.attribute-row').querySelector('input[name="attribute_value[]"]');
                    rowValueInput.value = '';
                }
            });
        }

        function fetchAllowedAttributes() {
            const categoryId = Number(categorySelect.value || 0);
            if (categoryId <= 0) {
                allowedAttributes = [];
                refreshAllRows();
                showWarning('Щоб працювати з характеристиками, спочатку виберіть категорію товару.');
                return;
            }

            fetch('/admin/products/allowed-attributes/' + categoryId, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Не вдалося отримати характеристики категорії.');
                    }

                    allowedAttributes = Array.isArray(data.attributes) ? data.attributes : [];
                    refreshAllRows();
                    clearWarning();

                    if (!allowedAttributes.length) {
                        showWarning('Для цієї категорії ще не налаштовано жодної характеристики.');
                    }
                })
                .catch(() => {
                    allowedAttributes = [];
                    refreshAllRows();
                    showWarning('Сталася помилка при завантаженні характеристик. Спробуйте ще раз.');
                });
        }

        categorySelect.addEventListener('change', fetchAllowedAttributes);
        rowsContainer.querySelectorAll('.attribute-remove-btn').forEach(bindRemoveButton);
        rowsContainer.querySelectorAll('.attribute-id-select').forEach(bindAttributeSelectProtection);
        fetchAllowedAttributes();
    })();
</script>
