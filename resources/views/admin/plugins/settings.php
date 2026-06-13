<?php
/**
 * @var array       $plugin    — метадані плагіна
 * @var array       $settings  — ['key' => ['label','type','value','options','required','hint']]
 * @var array|null  $flash
 */
?>

<div class="page-header" style="margin-bottom:1.25rem;">
    <div>
        <h1 class="page-title">
            <i class="fas fa-cog" style="color:#6366f1;"></i>
            Налаштування: <?= htmlspecialchars($plugin['name']) ?>
        </h1>
        <div style="font-size:.85rem; color:#64748b; margin-top:.2rem;">
            v<?= htmlspecialchars($plugin['version']) ?>
            &nbsp;·&nbsp; <?= htmlspecialchars($plugin['author']) ?>
        </div>
    </div>
    <a href="/admin/plugins" class="btn btn-outline" style="border:1px solid #ddd; color:#334155;">
        <i class="fas fa-arrow-left"></i> До списку плагінів
    </a>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert <?= !empty($flash['success']) ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars((string)($flash['message'] ?? '')) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">Параметри плагіна</div>
    <div class="card-body">
        <form method="POST" action="/admin/plugins/settings/<?= htmlspecialchars($plugin['slug']) ?>">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">

            <?php foreach ($settings as $key => $field): ?>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label for="plug_<?= htmlspecialchars($key) ?>"
                           style="display:block; font-weight:600; font-size:.875rem; color:#334155; margin-bottom:.35rem;">
                        <?= htmlspecialchars($field['label']) ?>
                        <?php if ($field['required']): ?>
                            <span style="color:#ef4444;">*</span>
                        <?php endif; ?>
                    </label>

                    <?php if ($field['type'] === 'textarea'): ?>
                        <textarea name="<?= htmlspecialchars($key) ?>"
                                  id="plug_<?= htmlspecialchars($key) ?>"
                                  class="form-control"
                                  rows="4"
                                  <?= $field['required'] ? 'required' : '' ?>
                        ><?= htmlspecialchars((string)$field['value']) ?></textarea>

                    <?php elseif ($field['type'] === 'select'): ?>
                        <select name="<?= htmlspecialchars($key) ?>"
                                id="plug_<?= htmlspecialchars($key) ?>"
                                class="form-control"
                                <?= $field['required'] ? 'required' : '' ?>>
                            <?php foreach ($field['options'] as $optVal => $optLabel): ?>
                                <option value="<?= htmlspecialchars((string)$optVal) ?>"
                                    <?= (string)$field['value'] === (string)$optVal ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$optLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($field['type'] === 'checkbox'): ?>
                        <label style="display:flex; align-items:center; gap:.5rem; font-weight:400; cursor:pointer;">
                            <input type="checkbox"
                                   name="<?= htmlspecialchars($key) ?>"
                                   id="plug_<?= htmlspecialchars($key) ?>"
                                   value="1"
                                   <?= $field['value'] ? 'checked' : '' ?>>
                            Увімкнено
                        </label>

                    <?php else: ?>
                        <input type="<?= in_array($field['type'], ['text','password','number','email','url'], true) ? $field['type'] : 'text' ?>"
                               name="<?= htmlspecialchars($key) ?>"
                               id="plug_<?= htmlspecialchars($key) ?>"
                               class="form-control"
                               value="<?= htmlspecialchars((string)$field['value']) ?>"
                               placeholder="<?= htmlspecialchars((string)$field['default']) ?>"
                               <?= $field['required'] ? 'required' : '' ?>>
                    <?php endif; ?>

                    <?php if ($field['hint'] !== ''): ?>
                        <small style="color:#64748b; font-size:.8rem; display:block; margin-top:.3rem;">
                            <?= htmlspecialchars($field['hint']) ?>
                        </small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div style="display:flex; gap:.75rem; margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Зберегти налаштування
                </button>
                <a href="/admin/plugins" class="btn btn-outline" style="border:1px solid #ddd;">
                    Скасувати
                </a>
            </div>
        </form>
    </div>
</div>
