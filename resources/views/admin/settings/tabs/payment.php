<div class="mb-4 text-end">
    <form action="/admin/settings/methods/add" method="POST" style="display:inline;">
        <input type="hidden" name="csrf" value="<?= \App\Core\Http\Csrf::token(); ?>">
        <input type="hidden" name="type" value="payment">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus"></i> Додати спосіб оплати
        </button>
    </form>
</div>

<form action="/admin/settings/save" method="POST">
    <input type="hidden" name="csrf" value="<?= \App\Core\Http\Csrf::token(); ?>">
    <input type="hidden" name="current_tab" value="payment">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0"><?= __('settings_payment') ?></h2>
    </div>

    <?php foreach ($methods as $method): ?>
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 text-success"><?= htmlspecialchars($method['name']) ?></h5>
                <a href="#" onclick="if(confirm(window.LANG.confirm_delete_method)) { document.getElementById('delete-form-<?= $method['id'] ?>').submit(); } return false;" class="btn btn-sm btn-outline-danger">
                    Видалити
                </a>

                <form id="delete-form-<?= $method['id'] ?>" action="/admin/settings/methods/delete/<?= $method['id'] ?>" method="POST" style="display:none;">
                    <input type="hidden" name="csrf" value="<?= \App\Core\Http\Csrf::token(); ?>">
                    <input type="hidden" name="type" value="payment">
                </form>
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
                        <label class="form-label fw-bold"><?= __('settings_payment_name_client') ?></label>
                        <input type="text" name="methods[<?= $method['id'] ?>][name]" 
                               value="<?= htmlspecialchars($method['name']) ?>" class="form-control">
                    </div>

                    <!-- Опис методу -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold"><?= __('plugin_payment_desc') ?></label>
                        <input type="text" name="methods[<?= $method['id'] ?>][description]" 
                               value="<?= htmlspecialchars($method['description'] ?? '') ?>" class="form-control" placeholder="Наприклад: Комісія 2%">
                    </div>
                </div>

                <?php
                $extra = json_decode($method['settings'] ?? '', true) ?: [];
                ?>

                <!-- Прив'язка до платіжного плагіна (PaymentManager) -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold"><?= __('plugin_payment_gateway') ?></label>
                        <input type="text"
                               name="methods[<?= $method['id'] ?>][settings][gateway_name]"
                               value="<?= htmlspecialchars($extra['gateway_name'] ?? $method['code']) ?>"
                               class="form-control"
                               placeholder="Наприклад: liqpay, wayforpay, cod">
                        <small class="text-muted">
                            Назва зареєстрованого платіжного плагіна (метод getName()).
                            Залиште порожнім для методів без онлайн-оплати.
                        </small>
                    </div>
                </div>

                <!-- Специфічні налаштування для LiqPay (зворотна сумісність) -->
                <?php if ($method['code'] === 'liqpay'): ?>
                    <hr>
                    <p class="text-muted small mb-2">
                        <i class="fas fa-puzzle-piece"></i>
                        Ці поля читає плагін <strong>LiqPayGateway</strong>.
                        Увімкніть плагін у розділі <a href="/admin/plugins">Керування плагінами</a>.
                    </p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Public Key</label>
                            <input type="text"
                                   name="methods[<?= $method['id'] ?>][settings][public_key]"
                                   value="<?= htmlspecialchars($extra['public_key'] ?? '') ?>"
                                   class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Private Key</label>
                            <input type="password"
                                   name="methods[<?= $method['id'] ?>][settings][private_key]"
                                   value="<?= htmlspecialchars($extra['private_key'] ?? '') ?>"
                                   class="form-control">
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="methods[<?= $method['id'] ?>][is_test_mode]" value="0">
                        <input class="form-check-input" type="checkbox"
                               name="methods[<?= $method['id'] ?>][is_test_mode]" value="1"
                               <?= ($method['is_test_mode'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label text-danger">Тестовий режим (Sandbox)</label>
                    </div>
                <?php endif; ?>

                <!-- Специфічні налаштування для Готівки/Післяплати -->
                <?php if ($method['code'] === 'cash'): ?>
                    <hr>
                    <div class="text-muted">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Цей метод не потребує додаткових API налаштувань.
                        </small>
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
        <div style="margin-bottom: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-lg" style="padding: 0.75rem 2rem; font-size: 1rem;">
            <i class="fas fa-save"></i> Зберегти всі зміни
        </button>
    </div>
</form>

