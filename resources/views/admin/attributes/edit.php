<div class="page-header">
    <h1 class="page-title">Редагування атрибута: <?php echo htmlspecialchars($attribute['name']); ?></h1>
    <a href="/admin/attributes" class="btn btn-outline" style="border: 1px solid #ddd; color: #64748b;">
        <i class="fas fa-arrow-left"></i> Назад до списку
    </a>
</div>

<form action="/admin/attributes/update/<?php echo (int)$attribute['id']; ?>" method="POST">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Основні дані атрибута
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="name">Назва</label>
                <input type="text" name="name" id="name" class="form-control" required value="<?php echo htmlspecialchars($attribute['name']); ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" value="<?php echo htmlspecialchars($attribute['slug']); ?>">
                </div>
                <div class="form-group">
                    <label for="type">Тип</label>
                    <select name="type" id="type" class="form-control">
                        <?php foreach (($attributeTypes ?? []) as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($displayType === $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="sort_order">Порядок</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?php echo (int)($attribute['sort_order'] ?? 0); ?>">
                </div>
                <div class="form-group" style="display: flex; align-items: end;">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin:0;">
                        <input type="checkbox" name="is_filterable" value="1" <?php echo !empty($attribute['is_filterable']) ? 'checked' : ''; ?>> Доступний у фільтрах
                    </label>
                </div>
                <div class="form-group" style="display: flex; align-items: end;">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin:0;">
                        <input type="checkbox" name="is_visible" value="1" <?php echo !empty($attribute['is_visible']) ? 'checked' : ''; ?>> Видимий на вітрині
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Опис</label>
                <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($attribute['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group" id="select-options-wrap" style="display:none;">
                <label for="options_text">Опції списку (по 1 значенню на рядок)</label>
                <textarea name="options_text" id="options_text" class="form-control" rows="6"><?php echo htmlspecialchars($optionsText ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-link"></i> Прив'язка до категорій
        </div>
        <div class="card-body">
            <p style="margin-top: 0; color: #64748b;">При відв'язці від категорії історичні значення атрибута в товарах зберігаються, але стають недоступними у формах цієї категорії.</p>
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem 1.5rem;">
                <?php $assigned = array_flip($assignedCategoryIds ?? []); ?>
                <?php foreach (($categories ?? []) as $category): ?>
                    <?php $categoryId = (int)$category['id']; ?>
                    <label style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="checkbox" name="category_ids[]" value="<?php echo $categoryId; ?>" <?php echo isset($assigned[$categoryId]) ? 'checked' : ''; ?>>
                        <span><?php echo str_repeat('— ', (int)($category['level'] ?? 0)) . htmlspecialchars($category['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> Зберегти зміни
        </button>
    </div>
</form>

<script>
    (function () {
        const typeSelect = document.getElementById('type');
        const optionsWrap = document.getElementById('select-options-wrap');

        function refreshTypeState() {
            optionsWrap.style.display = typeSelect.value === 'select' ? 'block' : 'none';
        }

        typeSelect.addEventListener('change', refreshTypeState);
        refreshTypeState();
    })();
</script>
