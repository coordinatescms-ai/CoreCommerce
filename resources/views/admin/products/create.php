<div class="page-header">
    <h1 class="page-title"><?php echo __('product_new'); ?></h1>
    <a href="/admin/products" class="btn btn-outline" style="border: 1px solid #ddd; color: #64748b;">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list'); ?>
    </a>
</div>

<?php
$formData = $formData ?? [];
$attributeRows = $attributeRows ?? [];
?>

<form action="/admin/products/store" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> <?php echo __('basic_info'); ?>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="name"><?php echo __('product_name'); ?> <span style="color:#dc2626;">*</span></label>
                <input type="text" name="name" id="name" class="form-control" required placeholder="<?php echo __('settings_phone_mask_eg'); ?>: iPhone 15 Pro Max" value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>">
            </div>


            <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 1rem;">
                <div class="form-group">
                    <label for="sku">SKU (<?php echo __('product_sku'); ?>)</label>
                    <input type="text" name="sku" id="sku" class="form-control" placeholder="<?php echo __('product_sku_auto'); ?>: SKU-000001" value="<?php echo htmlspecialchars($formData['sku'] ?? ($product['sku'] ?? '')); ?>">
                </div>
                <div class="form-group">
                    <label for="stock_qty"><?php echo __('products_quantity'); ?></label>
                    <input type="number" min="1" step="1" name="stock_qty" id="stock_qty" class="form-control" value="<?php echo htmlspecialchars((string) ($formData['stock_qty'] ?? '')); ?>">
                </div>
                <div class="form-group">
                    <label for="stock_comment"><?php echo __('stock_comment'); ?></label>
                    <input type="text" name="stock_comment" id="stock_comment" class="form-control" value="<?php echo htmlspecialchars((string) ($formData['stock_comment'] ?? '')); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="vendor"><?php echo __('product_vendor'); ?></label>
                <input type="text" name="vendor" id="vendor" class="form-control" placeholder="<?php echo __('settings_phone_mask_eg'); ?>: Apple, Samsung, Xiaomi" value="<?php echo htmlspecialchars($formData['vendor'] ?? ''); ?>">
                <small style="color:#64748b;"><?php echo __('product_vendor_hint'); ?></small>
            </div>
            <?php do_action('admin.product_form.extra_fields', null, $formData ?? null); ?>
            <div class="form-group">
                <label for="slug"><?php echo __('create_category_slug'); ?></label>
                <input type="text" name="slug" id="slug" class="form-control" placeholder="<?php echo __('category_slug_placeholder'); ?>" value="<?php echo htmlspecialchars($formData['slug'] ?? ''); ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="price"><?php echo __('products_price'); ?> <span style="color:#dc2626;">*</span></label>
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
                        value="<?php echo htmlspecialchars($formData['price'] ?? ''); ?>"
                    >
                </div>
                <div class="form-group">
                    <label for="category_id"><?php echo __('products_category'); ?> <span style="color:#dc2626;">*</span></label>
                    <select name="category_id" id="category_id" class="form-control" required>
                        <option value=""><?php echo __('products_no_category'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ((int) $cat['id'] === (int) ($formData['category_id'] ?? 0)) ? 'selected' : ''; ?>>
                                <?php echo str_repeat('— ', $cat['level'] ?? 0) . htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description"><?php echo __('product_description'); ?></label>
                <textarea name="description" id="description" class="form-control" rows="6" placeholder="<?php echo __('product_description_placeholder'); ?>..."><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer;">
                    <input type="checkbox" name="is_visible" value="1" <?php echo !isset($formData['is_visible']) || (int) ($formData['is_visible'] ?? 0) === 1 ? 'checked' : ''; ?>>
                    <span><?php echo __('product_show_in_store'); ?></span>
                </label>
            </div>
        </div>
    </div>

  <div class="card">
    <div class="card-header">
        <i class="fas fa-list"></i> <?php echo __('product_attributes'); ?>
    </div>
    <div class="card-body">
        <p style="margin-top: 0; color: #64748b;"><?php echo __('product_category_hint'); ?></p>
        <div id="attributes-warning" style="display:none; margin-bottom: 0.75rem; color:#b45309; background:#fffbeb; border:1px solid #fde68a; padding:0.5rem 0.75rem; border-radius:6px;"></div>

        <div id="attribute-rows" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php if (empty($attributeRows)): ?>
                <div class="attribute-row" style="border: 1px solid #e2e8f0; padding: 1rem; border-radius: 8px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <select name="attribute_id[]" class="form-control attribute-id-select">
                            <option value=""><?php echo __('product_select_category_first'); ?></option>
                        </select>
                        <div class="attribute-value-wrap">
                            <input type="text" name="attribute_value[]" class="form-control" placeholder="<?php echo __('product_attribute_value_placeholder'); ?>">
                        </div>
                        <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;" title="<?php echo __('remove'); ?>">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="attribute-selectable-wrap" style="display:flex; align-items:center; gap:0.75rem; flex-wrap: wrap;">
                        <input type="hidden" name="attribute_is_selectable[]" class="attribute-is-selectable-hidden" value="0">
                        <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer; min-width: 150px;">
                            <input type="checkbox" class="attribute-is-selectable-checkbox" value="1">
                            <span style="font-weight: 500;"><?php echo __('product_option_select'); ?></span>
                        </label>
                        <select name="attribute_price_operation[]" class="form-control attribute-price-operation" style="max-width:70px; display:none;">
                            <option value="+">+</option>
                            <option value="-">-</option>
                        </select>
                        <input type="number" min="0" step="0.01" name="attribute_price_modifier[]" class="form-control attribute-price-modifier" placeholder="<?php echo __('product_price_modifier'); ?>" style="max-width:150px; display:none;" value="">
                        <input type="number" min="0" step="1" name="attribute_stock_quantity[]" class="form-control attribute-stock-quantity" placeholder="<?php echo __('product_stock_quantity'); ?>" style="max-width:200px; display:none;" value="">
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($attributeRows as $row): ?>
                    <div class="attribute-row" style="border: 1px solid #e2e8f0; padding: 1rem; border-radius: 8px;">
                        <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <?php $rowAttributeId = (int) ($row['attribute_id'] ?? 0); ?>
                            <select name="attribute_id[]" class="form-control attribute-id-select">
                                <option value="">-- <?php echo __('product_select_attribute'); ?> --</option>
                                <?php foreach (($allowedAttributes ?? []) as $attribute): ?>
                                    <?php $attributeId = (int) $attribute['id']; ?>
                                    <option value="<?php echo $attributeId; ?>" <?php echo ($attributeId === $rowAttributeId) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($attribute['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="attribute-value-wrap">
                                <input type="text" name="attribute_value[]" class="form-control" value="<?php echo htmlspecialchars((string) ($row['value'] ?? '')); ?>" placeholder="<?php echo __('product_attribute_value_placeholder'); ?>">
                            </div>
                            <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;" title="<?php echo __('remove'); ?>">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="attribute-selectable-wrap" style="display:flex; align-items:center; gap:0.75rem; flex-wrap: wrap;">
                            <?php $isSelectable = !empty($row['is_selectable']); ?>
                            <input type="hidden" name="attribute_is_selectable[]" class="attribute-is-selectable-hidden" value="<?php echo $isSelectable ? '1' : '0'; ?>">
                            <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer; min-width: 150px;">
                                <input type="checkbox" class="attribute-is-selectable-checkbox" value="1" <?php echo $isSelectable ? 'checked' : ''; ?>>
                                <span style="font-weight: 500;"><?php echo __('product_option_select'); ?></span>
                            </label>
                            <select name="attribute_price_operation[]" class="form-control attribute-price-operation" style="max-width:70px; <?php echo $isSelectable ? '' : 'display:none;'; ?>">
                                <option value="+" <?php echo (($row['price_operation'] ?? '+') === '+') ? 'selected' : ''; ?>>+</option>
                                <option value="-" <?php echo (($row['price_operation'] ?? '+') === '-') ? 'selected' : ''; ?>>-</option>
                            </select>
                            <input type="number" min="0" step="0.01" name="attribute_price_modifier[]" class="form-control attribute-price-modifier" placeholder="<?php echo __('product_price_modifier'); ?>" style="max-width:150px; <?php echo $isSelectable ? '' : 'display:none;'; ?>" value="<?php echo htmlspecialchars((string) ($row['price_modifier'] ?? '')); ?>">
                            <input type="number" min="0" step="1" name="attribute_stock_quantity[]" class="form-control attribute-stock-quantity" placeholder="<?php echo __('product_stock_quantity'); ?>" style="max-width:200px; <?php echo $isSelectable ? '' : 'display:none;'; ?>" value="<?php echo htmlspecialchars((string) ($row['stock_quantity'] ?? '')); ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="button" id="add-attribute-row" class="btn btn-outline" style="border: 1px solid #ddd; color: #2563eb;">
                <i class="fas fa-plus"></i> <?php echo __('product_add_attribute'); ?>
            </button>
        </div>
    </div>
</div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-image"></i> <?php echo __('product_image'); ?>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="images"><?php echo __('product_gallery'); ?> (<?php echo __('product_gallery_photos_limit'); ?> <?php echo (int) ($galleryLimit ?? 5); ?> <?php echo __('product_gallery_photo'); ?>)</label>
                <input type="file" name="images[]" id="images" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
                <small style="color:#64748b; display:block; margin-top:0.5rem;"><?php echo __('product_gallery_hint'); ?></small>
            </div>
            <div id="gallery-preview" style="display:grid; grid-template-columns: repeat(auto-fill,minmax(110px,1fr)); gap:0.75rem;"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-search"></i> <?php echo __('seo_settings'); ?>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="meta_title">Meta Title</label>
                <input type="text" name="meta_title" id="meta_title" class="form-control" value="<?php echo htmlspecialchars($formData['meta_title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="meta_description">Meta Description</label>
                <textarea name="meta_description" id="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($formData['meta_description'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> <?php echo __('create_product'); ?>
        </button>
    </div>
</form>

<script>
    (function () {
        const form = document.querySelector('form[action="/admin/products/store"]');
        const categorySelect = document.getElementById('category_id');
        const rowsContainer = document.getElementById('attribute-rows');
        const addRowButton = document.getElementById('add-attribute-row');
        const warningBox = document.getElementById('attributes-warning');
        const imagesInput = document.getElementById('images');
        const galleryPreview = document.getElementById('gallery-preview');
        const galleryLimit = <?php echo (int) ($galleryLimit ?? 5); ?>;
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
                return '<option value="">' . __('product_select_category_first') . '</option>';
            }
            if (!allowedAttributes.length) {
                return '<option value="">' . __('product_no_attributes') . '</option>';
            }
            const normalizedSelectedId = String(selectedId || '');
            return '<option value="">-- ' . __('product_select_attribute') . ' --</option>' + allowedAttributes.map(function (attribute) {
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
                container.innerHTML = '<input type="number" step="0.01" inputmode="decimal" name="attribute_value[]" class="form-control" placeholder="' . __('product_numeric_value') . '" value="' + safeValue + '">';
                return;
            }
            if (attribute && ['select', 'multiselect', 'color'].includes(attribute.type)) {
                const listId = 'attr-options-' + Math.random().toString(36).slice(2);
                const options = Array.isArray(attribute.options) ? attribute.options : [];
                const optionsHtml = options.map(function (option) {
                    const value = escapeHtml(option.name || option.value || '');
                    return '<option value="' + value + '"></option>';
                }).join('');

                container.innerHTML = '<input type="text" name="attribute_value[]" class="form-control" list="' + listId + '" placeholder="' . __('product_select_or_enter_value') . '" value="' + safeValue + '"><datalist id="' + listId + '">' + optionsHtml + '</datalist>';
                return;
            }
            container.innerHTML = '<input type="text" name="attribute_value[]" class="form-control" placeholder="' . __('product_attribute_value_placeholder') . '" value="' + safeValue + '">';
        }

        function bindRemoveButton(button) {
            if (!button) return;
            button.addEventListener('click', function () {
                const rows = rowsContainer.querySelectorAll('.attribute-row');
                if (rows.length === 1) {
                    rows[0].querySelectorAll('input').forEach(input => {
                        if (input.type === 'checkbox') { input.checked = false; return; }
                        input.value = '';
                    });
                    const firstSelect = rows[0].querySelector('.attribute-id-select');
                    if (firstSelect) firstSelect.value = '';
                    syncSelectableHidden(rows[0]);
                    syncSelectableFields(rows[0]);
                    return;
                }
                button.closest('.attribute-row').remove();
            });
        }

        function bindAttributeSelectProtection(select) {
            if (!select) return;
            select.addEventListener('focus', function () {
                if (!hasCategory()) {
                    showWarning('<?= __('product_select_category_first_hint') ?>');
                    select.blur();
                }
            });
        }

        function syncSelectableHidden(row) {
            const checkbox = row.querySelector('.attribute-is-selectable-checkbox');
            const hidden = row.querySelector('.attribute-is-selectable-hidden');
            if (checkbox && hidden) hidden.value = checkbox.checked ? '1' : '0';
        }

        function syncSelectableFields(row) {
            const checkbox = row.querySelector('.attribute-is-selectable-checkbox');
            const op = row.querySelector('.attribute-price-operation');
            const modifier = row.querySelector('.attribute-price-modifier');
            const stock = row.querySelector('.attribute-stock-quantity');
            const isVisible = !!(checkbox && checkbox.checked);

            [op, modifier, stock].forEach(function (field) {
                if (field) field.style.display = isVisible ? '' : 'none';
            });
        }

        function createRow(attributeId = '', value = '', isSelectable = false, priceOperation = '+', priceModifier = '0', stockQuantity = '') {
            const row = document.createElement('div');
            row.className = 'attribute-row';
            row.style.border = '1px solid #e2e8f0';
            row.style.padding = '1rem';
            row.style.borderRadius = '8px';
            row.style.marginBottom = '1rem';
            row.style.display = 'block';

            row.innerHTML = `
                <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <select name="attribute_id[]" class="form-control attribute-id-select">${buildAttributeOptions(attributeId)}</select>
                    <div class="attribute-value-wrap"></div>
                    <button type="button" class="btn btn-outline attribute-remove-btn" style="border: 1px solid #ddd; color: #ef4444;" title="<?= __('remove') ?>">
                        <i class="fas fa-trash" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="attribute-selectable-wrap" style="display:flex; align-items:center; gap:0.75rem; flex-wrap: wrap;">
                    <input type="hidden" name="attribute_is_selectable[]" class="attribute-is-selectable-hidden" value="${isSelectable ? '1' : '0'}">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer; min-width: 150px;">
                        <input type="checkbox" class="attribute-is-selectable-checkbox" value="1" ${isSelectable ? 'checked' : ''}>
                        <span style="font-weight: 500;"><?= __('product_option_select') ?></span>
                    </label>
                    <select name="attribute_price_operation[]" class="form-control attribute-price-operation" style="max-width:70px; ${isSelectable ? '' : 'display:none;'}">
                        <option value="+" ${priceOperation === '+' ? 'selected' : ''}>+</option>
                        <option value="-" ${priceOperation === '-' ? 'selected' : ''}>-</option>
                    </select>
                    <input type="number" min="0" step="0.01" name="attribute_price_modifier[]" class="form-control attribute-price-modifier" placeholder="Націнка" style="max-width:160px; ${isSelectable ? '' : 'display:none;'}" value="${priceModifier == '0' ? '' : escapeHtml(priceModifier)}">
                    <input type="number" min="0" step="1" name="attribute_stock_quantity[]" class="form-control attribute-stock-quantity" placeholder="Кількість на складі" style="max-width:180px; ${isSelectable ? '' : 'display:none;'}" value="${escapeHtml(stockQuantity)}">
                </div>
            `;

            const removeBtn = row.querySelector('.attribute-remove-btn');
            bindRemoveButton(removeBtn);
            const select = row.querySelector('.attribute-id-select');
            bindAttributeSelectProtection(select);
            select.addEventListener('change', function () {
                renderValueInput(row, select.value, '');
            });
            const checkbox = row.querySelector('.attribute-is-selectable-checkbox');
            checkbox.addEventListener('change', function () {
                syncSelectableHidden(row);
                syncSelectableFields(row);
            });

            renderValueInput(row, attributeId, value);
            syncSelectableHidden(row);
            syncSelectableFields(row);
            return row;
        }

        addRowButton.addEventListener('click', function () {
            if (!hasCategory()) {
                showWarning('<?= __('product_select_category_first_hint') ?>');
                return;
            }
            if (!allowedAttributes.length) {
                showWarning('<?= __('product_select_category_first_hint') ?>');
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
                syncSelectableFields(row);
            });
        }

        function fetchAllowedAttributes() {
            const categoryId = Number(categorySelect.value || 0);
            if (categoryId <= 0) {
                allowedAttributes = [];
                refreshAllRows();
                showWarning('<?= __('product_select_category_first_hint2') ?>');
                return;
            }
            fetch('/admin/products/allowed-attributes/' + categoryId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Error');
                allowedAttributes = Array.isArray(data.attributes) ? data.attributes : [];
                refreshAllRows();
                clearWarning();
                if (!allowedAttributes.length) showWarning('<?= __('no_attributes_for_category') ?>');
            })
            .catch(() => {
                allowedAttributes = [];
                refreshAllRows();
                showWarning('<?= __('error_loading_attributes') ?>');
            });
        }

        categorySelect.addEventListener('change', fetchAllowedAttributes);
        
        function updateGalleryPreview(files) {
            if (!galleryPreview) return;
            galleryPreview.innerHTML = '';
            Array.from(files || []).forEach(function (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const card = document.createElement('div');
                    card.style.border = '1px solid #e2e8f0';
                    card.style.borderRadius = '8px';
                    card.style.overflow = 'hidden';
                    card.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:90px;object-fit:cover;display:block;"><div style="padding:0.35rem;font-size:0.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + file.name + '</div>';
                    galleryPreview.appendChild(card);
                };
                reader.readAsDataURL(file);
            });
        }

        if (imagesInput) {
            imagesInput.addEventListener('change', function () {
                if (imagesInput.files.length > galleryLimit) {
                    alert('Максимум ' + galleryLimit + ' фото.');
                    imagesInput.value = '';
                    updateGalleryPreview([]);
                    return;
                }
                updateGalleryPreview(imagesInput.files);
            });
        }

        // Початкова ініціалізація існуючих рядків
        rowsContainer.querySelectorAll('.attribute-row').forEach(function (row) {
            const select = row.querySelector('.attribute-id-select');
            const valueInput = row.querySelector('input[name="attribute_value[]"]');
            const valueText = valueInput ? valueInput.value : '';
            
            bindAttributeSelectProtection(select);
            bindRemoveButton(row.querySelector('.attribute-remove-btn'));
            
            select.addEventListener('change', function () {
                renderValueInput(row, select.value, '');
            });
            
            const checkbox = row.querySelector('.attribute-is-selectable-checkbox');
            if (checkbox) {
                checkbox.addEventListener('change', function () {
                    syncSelectableHidden(row);
                    syncSelectableFields(row);
                });
            }
            renderValueInput(row, select.value, valueText);
            syncSelectableHidden(row);
            syncSelectableFields(row);
        });

        form.addEventListener('submit', function (event) {
            const rows = rowsContainer.querySelectorAll('.attribute-row');
            for (const row of rows) {
                const select = row.querySelector('.attribute-id-select');
                const valueInput = row.querySelector('input[name="attribute_value[]"]');
                if (select && select.value > 0 && (!valueInput || valueInput.value.trim() === '')) {
                    event.preventDefault();
                    showWarning('<?= __('fill_value_field') ?>');
                    if (valueInput) valueInput.focus();
                    return;
                }
            }
        });

        fetchAllowedAttributes();
    })();
</script>
