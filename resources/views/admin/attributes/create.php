<div class="page-header">
    <h1 class="page-title">Новий атрибут</h1>
    <a href="/admin/attributes" class="btn btn-outline" style="border: 1px solid #ddd; color: #64748b;">
        <i class="fas fa-arrow-left"></i> Назад до списку
    </a>
</div>

<form action="/admin/attributes/store" method="POST">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">

    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle"></i> Основні дані атрибута
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="name">Назва</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" placeholder="Порожньо = автогенерація">
                </div>
                <div class="form-group">
                    <label for="type">Тип</label>
                    <select name="type" id="type" class="form-control">
                        <?php foreach (($attributeTypes ?? []) as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="sort_order">Порядок</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                </div>
                <div class="form-group" style="display: flex; align-items: end;">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin:0;">
                        <input type="checkbox" name="is_filterable" value="1" checked> Доступний у фільтрах
                    </label>
                </div>
                <div class="form-group" style="display: flex; align-items: end;">
                    <label style="display:flex; align-items:center; gap:0.5rem; margin:0;">
                        <input type="checkbox" name="is_visible" value="1" checked> Видимий на вітрині
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Опис</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group" id="select-options-wrap" style="display:none;">
                <label for="options_text">Опції списку (по 1 значенню на рядок)</label>
                <textarea name="options_text" id="options_text" class="form-control" rows="6" placeholder="Чорний&#10;Білий&#10;Синій"></textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-link"></i> Прив'язка до категорій
        </div>
        <div class="card-body" style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem 1.5rem;">
            <?php foreach (($categories ?? []) as $category): ?>
                <label style="display:flex; align-items:center; gap:0.5rem;">
                    <input type="checkbox" name="category_ids[]" value="<?php echo (int)$category['id']; ?>">
                    <span><?php echo str_repeat('— ', (int)($category['level'] ?? 0)) . htmlspecialchars($category['name']); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> Створити атрибут
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
