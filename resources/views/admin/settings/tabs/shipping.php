<form action="/admin/settings/save" method="POST">
    <input type="hidden" name="csrf" value="<?= \App\Core\Http\Csrf::token(); ?>">
    <input type="hidden" name="current_tab" value="shipping">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">Налаштування доставки</h2>
    </div>

    <?php foreach ($methods as $method): ?>
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 text-primary"><?= htmlspecialchars($method['name']) ?></h5>
                <div class="form-check form-switch">
                    <input type="hidden" name="methods[<?= $method['id'] ?>][is_active]" value="0">
                    <input class="form-check-input" type="checkbox" name="methods[<?= $method['id'] ?>][is_active]" value="1" <?= $method['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label">Увімкнено</label>
                </div>
                <!-- Поле для сортування -->
            </div>
            
            <div class="card-body">
                <div class="row">
                    <!-- Поле для зміни НАЗВИ -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Назва методу (для покупця)</label>
                        <input type="text" name="methods[<?= $method['id'] ?>][name]" 
                               value="<?= htmlspecialchars($method['name']) ?>" class="form-control">
                    </div>

                    <!-- Поле для зміни ОПИСУ -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Короткий опис</label>
                        <input type="text" name="methods[<?= $method['id'] ?>][description]" 
                               value="<?= htmlspecialchars($method['description'] ?? '') ?>" class="form-control" placeholder="Наприклад: Доставка протягом 1-3 днів">
                    </div>
                </div>

                <?php 
                $extra = json_decode($method['settings'] ?? '', true) ?: []; 
                ?>
                
                <!-- Специфічні налаштування для JSON полів -->
                <?php if ($method['code'] === 'nova_poshta'): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">API Ключ (Нова Пошта)</label>
                            <input type="text" name="methods[<?= $method['id'] ?>][settings][api_key]" 
                                   value="<?= htmlspecialchars($extra['api_key'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Фіксована вартість, грн</label>
                            <input type="number" name="methods[<?= $method['id'] ?>][settings][cost]" 
                                   value="<?= htmlspecialchars($extra['cost'] ?? '') ?>" class="form-control">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($method['code'] === 'self_pickup'): ?>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Адреса магазину для самовивозу</label>
                        <input type="text" name="methods[<?= $method['id'] ?>][settings][address]" 
                               value="<?= htmlspecialchars($extra['address'] ?? '') ?>" class="form-control">
                    </div>
                <?php endif; ?>
                <br />
                <div class="mb-3" style="max-width: 150px;">
                    <label class="form-label fw-bold">Порядок (№)</label>
                    <input type="number" name="methods[<?= $method['id'] ?>][sort_order]" 
                        value="<?= $method['sort_order'] ?>" class="form-control" min="0">
                    <small class="text-muted">Менше число — вище у списку</small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary">Зберегти всі зміни</button>
</form>

