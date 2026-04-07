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
                <label for="name">Назва товару <span style="color:#dc2626;">*</span></label>
                <input type="text" name="name" id="name" class="form-control" required value="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="form-group">
                <label for="slug">Slug (URL посилання)</label>
                <input type="text" name="slug" id="slug" class="form-control" required value="<?php echo htmlspecialchars($product['slug']); ?>">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="price">Ціна (грн) <span style="color:#dc2626;">*</span></label>
                    <input type="number" min="0" step="0.01" inputmode="decimal" name="price" id="price" class="form-control" required value="<?php echo number_format((float)$product['price'], 2, '.', ''); ?>">
                </div>
                <div class="form-group">
                    <label for="category_id">Категорія <span style="color:#dc2626;">*</span></label>
                    <select name="category_id" id="category_id" class="form-control" required>
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
            <p style="margin-top: 0; color: #64748b;">Доступні лише характеристики, прив'язані до обраної категорії (з урахуванням батьківських категорій).</p>
            <div id="attributes-warning" style="display:none; margin-bottom: 0.75rem; color:#b45309; background:#fffbeb; border:1px solid #fde68a; padding:0.5rem 0.75rem; border-radius:6px;"></div>

            <div id="attribute-rows" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php $rows = $existingAttributes ?? []; ?>
                <?php if (empty($rows)): ?>
                    <div class="attribute-row" style="display:grid; grid-template-columns: 1fr 1fr 180px auto; gap: 0.75rem;">
                        <select name="attribute_id[]" class="form-control attribute-id-select">
                            <?php if (empty($allowedAttributes)): ?>
                                <option value="">Спочатку оберіть категорію</option>
                            <?php else: ?>
                                <option value="">-- Оберіть характеристику --</option>
                                <?php foreach ($allowedAttributes as $attribute): ?>
                                    <option value="<?php echo (int) $attribute['id']; ?>"><?php echo htmlspecialchars($attribute['name']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="attribute-value-wrap">
                            <input type="text" name="attribute_value[]" class="form-control" placeholder="Значення (напр. Чорний)">
                        </div>
                        <div class="attribute-selectable-wrap" style="display:flex; align-items:center; gap:0.5rem; justify-content:flex-start;">
                            <input type="hidden" name="attribute_is_selectable[]" class="attribute-is-selectable-hidden" value="0">
                            <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer;">
                                <input type="checkbox" class="attribute-is-selectable-checkbox" value="1">
                                <span>Опція вибору</span>
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;">Видалити</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <div class="attribute-row" style="display:grid; grid-template-columns: 1fr 1fr 180px auto; gap: 0.75rem;">
                            <?php $rowAttributeId = (int)($row['attribute_id'] ?? 0); ?>
                            <?php $isAllowed = false; ?>
                            <select name="attribute_id[]" class="form-control attribute-id-select">
                                <option value="">-- Оберіть характеристику --</option>
                                <?php foreach (($allowedAttributes ?? []) as $attribute): ?>
                                    <?php $attributeId = (int)$attribute['id']; ?>
                                    <?php if ($attributeId === $rowAttributeId) { $isAllowed = true; } ?>
                                    <option value="<?php echo $attributeId; ?>" <?php echo ($attributeId === $rowAttributeId) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($attribute['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (!$isAllowed && !empty($rowAttributeId)): ?>
                                    <option value="<?php echo $rowAttributeId; ?>" selected>
                                        <?php echo htmlspecialchars(($row['attribute_name'] ?? ('ID ' . $rowAttributeId)) . ' (недоступний для поточної категорії)'); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <div class="attribute-value-wrap">
                                <input type="text" name="attribute_value[]" class="form-control" value="<?php echo htmlspecialchars($row['value'] ?? ''); ?>" placeholder="Значення (напр. Чорний)">
                            </div>
                            <div class="attribute-selectable-wrap" style="display:flex; align-items:center; gap:0.5rem; justify-content:flex-start;">
                                <?php $isSelectable = !empty($row['is_selectable']); ?>
                                <input type="hidden" name="attribute_is_selectable[]" class="attribute-is-selectable-hidden" value="<?php echo $isSelectable ? '1' : '0'; ?>">
                                <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer;">
                                    <input type="checkbox" class="attribute-is-selectable-checkbox" value="1" <?php echo $isSelectable ? 'checked' : ''; ?>>
                                    <span>Опція вибору</span>
                                </label>
                            </div>
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
        const form = document.querySelector('form[action^="/admin/products/update/"]');
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

        function findAttributeById(attributeId) {
            const id = String(attributeId || '');
            return allowedAttributes.find(item => String(item.id) === id) || null;
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderValueInput(row, attributeId = '', currentValue = '') {
            const container = row.querySelector('.attribute-value-wrap');
            const attribute = findAttributeById(attributeId);
            const safeValue = escapeHtml(currentValue);

            if (attribute && attribute.type === 'range') {
                container.innerHTML = '<input type=\"number\" step=\"0.01\" inputmode=\"decimal\" name=\"attribute_value[]\" class=\"form-control\" placeholder=\"Числове значення\" value=\"' + safeValue + '\">';
                return;
            }

            if (attribute && ['select', 'multiselect', 'color'].includes(attribute.type)) {
                const listId = 'attr-options-' + Math.random().toString(36).slice(2);
                const options = Array.isArray(attribute.options) ? attribute.options : [];
                const optionsHtml = options.map(function (option) {
                    const value = escapeHtml(option.name || option.value || '');
                    return '<option value=\"' + value + '\"></option>';
                }).join('');

                container.innerHTML = '<input type=\"text\" name=\"attribute_value[]\" class=\"form-control\" list=\"' + listId + '\" placeholder=\"Оберіть або введіть значення\" value=\"' + safeValue + '\"><datalist id=\"' + listId + '\">' + optionsHtml + '</datalist>';
                return;
            }

            container.innerHTML = '<input type=\"text\" name=\"attribute_value[]\" class=\"form-control\" placeholder=\"Значення (напр. Чорний)\" value=\"' + safeValue + '\">';
        }

        function bindRemoveButton(button) {
            button.addEventListener('click', function () {
                const rows = rowsContainer.querySelectorAll('.attribute-row');
                if (rows.length === 1) {
                    rows[0].querySelectorAll('input').forEach(input => {
                        if (input.type === 'checkbox') {
                            input.checked = false;
                            return;
                        }
                        input.value = '';
                    });
                    const firstSelect = rows[0].querySelector('.attribute-id-select');
                    if (firstSelect) {
                        firstSelect.value = '';
                    }
                    syncSelectableHidden(rows[0]);
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

        function syncSelectableHidden(row) {
            const checkbox = row.querySelector('.attribute-is-selectable-checkbox');
            const hidden = row.querySelector('.attribute-is-selectable-hidden');
            if (!checkbox || !hidden) {
                return;
            }

            hidden.value = checkbox.checked ? '1' : '0';
        }

        function createRow(attributeId = '', value = '', isSelectable = false) {
            const row = document.createElement('div');
            row.className = 'attribute-row';
            row.style.display = 'grid';
            row.style.gridTemplateColumns = '1fr 1fr 180px auto';
            row.style.gap = '0.75rem';

            row.innerHTML = `
                <select name="attribute_id[]" class="form-control attribute-id-select">${buildAttributeOptions(attributeId)}</select>
                <div class="attribute-value-wrap"></div>
                <div class="attribute-selectable-wrap" style="display:flex; align-items:center; gap:0.5rem; justify-content:flex-start;">
                    <input type="hidden" name="attribute_is_selectable[]" class="attribute-is-selectable-hidden" value="${isSelectable ? '1' : '0'}">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer;">
                        <input type="checkbox" class="attribute-is-selectable-checkbox" value="1" ${isSelectable ? 'checked' : ''}>
                        <span>Опція вибору</span>
                    </label>
                </div>
                <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;">Видалити</button>
            `;

            renderValueInput(row, attributeId, value);
            const removeButton = row.querySelector('.attribute-remove-btn');
            bindRemoveButton(removeButton);
            const attributeSelect = row.querySelector('.attribute-id-select');
            bindAttributeSelectProtection(attributeSelect);
            attributeSelect.addEventListener('change', function () {
                renderValueInput(row, attributeSelect.value, '');
            });
            const selectableCheckbox = row.querySelector('.attribute-is-selectable-checkbox');
            selectableCheckbox.addEventListener('change', function () {
                syncSelectableHidden(row);
            });
            syncSelectableHidden(row);

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
            rowsContainer.querySelectorAll('.attribute-row').forEach(function (row) {
                const select = row.querySelector('.attribute-id-select');
                const currentValue = select.value;
                const valueInput = row.querySelector('input[name="attribute_value[]"]');
                const currentValueText = valueInput ? valueInput.value : '';
                select.innerHTML = buildAttributeOptions(currentValue);
                if (currentValue && !select.value) {
                    renderValueInput(row, '', '');
                    syncSelectableHidden(row);
                    return;
                }
                renderValueInput(row, select.value, currentValueText);
                syncSelectableHidden(row);
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
        rowsContainer.querySelectorAll('.attribute-row').forEach(function (row) {
            const select = row.querySelector('.attribute-id-select');
            const valueInput = row.querySelector('input[name="attribute_value[]"]');
            const valueText = valueInput ? valueInput.value : '';
            const selectableCheckbox = row.querySelector('.attribute-is-selectable-checkbox');
            bindAttributeSelectProtection(select);
            select.addEventListener('change', function () {
                renderValueInput(row, select.value, '');
            });
            if (selectableCheckbox) {
                selectableCheckbox.addEventListener('change', function () {
                    syncSelectableHidden(row);
                });
            }
            renderValueInput(row, select.value, valueText);
            syncSelectableHidden(row);
        });

        form.addEventListener('submit', function (event) {
            const rows = rowsContainer.querySelectorAll('.attribute-row');
            for (const row of rows) {
                const attributeSelect = row.querySelector('.attribute-id-select');
                const valueInput = row.querySelector('input[name="attribute_value[]"]');
                const attributeId = Number(attributeSelect ? attributeSelect.value : 0);
                const value = valueInput ? valueInput.value.trim() : '';

                if (attributeId > 0 && value === '') {
                    event.preventDefault();
                    showWarning('Для обраної характеристики потрібно заповнити поле "Значення".');
                    valueInput && valueInput.focus();
                    return;
                }
            }
        });

        fetchAllowedAttributes();
    })();
</script>
