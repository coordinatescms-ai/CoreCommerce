<form action="/admin/settings/save" method="POST">
    <input type="hidden" name="csrf" value="<?= \App\Core\Http\Csrf::token(); ?>">
    <input type="hidden" name="current_tab" value="payment">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">Методи оплати</h2>
    </div>

    <?php foreach ($methods as $method): ?>
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 text-success"><?= htmlspecialchars($method['name']) ?></h5>
                <div class="form-check form-switch">
                    <input type="hidden" name="methods[<?= $method['id'] ?>][is_active]" value="0">
                    <input class="form-check-input" type="checkbox" name="methods[<?= $method['id'] ?>][is_active]" value="1" <?= $method['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label">Увімкнено</label>
                </div>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <!-- Назва методу -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Назва методу для клієнта</label>
                        <input type="text" name="methods[<?= $method['id'] ?>][name]" 
                               value="<?= htmlspecialchars($method['name']) ?>" class="form-control">
                    </div>

                    <!-- Опис методу -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Опис при виборі оплати</label>
                        <input type="text" name="methods[<?= $method['id'] ?>][description]" 
                               value="<?= htmlspecialchars($method['description'] ?? '') ?>" class="form-control" placeholder="Наприклад: Комісія 2%">
                    </div>
                </div>

                <?php 
                $extra = json_decode($method['settings'] ?? '', true) ?: []; 
                ?>
                
                <!-- Специфічні налаштування для LiqPay -->
                <?php if ($method['code'] === 'liqpay'): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Public Key</label>
                            <input type="text" name="methods[<?= $method['id'] ?>][settings][public_key]" 
                                   value="<?= htmlspecialchars($extra['public_key'] ?? '') ?>" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Private Key</label>
                            <input type="password" name="methods[<?= $method['id'] ?>][settings][private_key]" 
                                   value="<?= htmlspecialchars($extra['private_key'] ?? '') ?>" class="form-control">
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="methods[<?= $method['id'] ?>][is_test_mode]" value="0">
                        <input class="form-check-input" type="checkbox" name="methods[<?= $method['id'] ?>][is_test_mode]" value="1" <?= ($method['is_test_mode'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label text-danger">Тестовий режим (Sandbox)</label>
                    </div>
                <?php endif; ?>

                <!-- Специфічні налаштування для Готівки/Післяплати -->
                <?php if ($method['code'] === 'cash'): ?>
                    <hr>
                    <div class="text-muted">
                        <small><i class="fas fa-info-circle"></i> Цей метод не потребує додаткових API налаштувань.</small>
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

