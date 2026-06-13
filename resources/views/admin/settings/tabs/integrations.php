<?php
/**
 * @var array  $prom       Налаштування: prom_enabled, prom_api_key, prom_sync_method, ...
 * @var string $siteUrl    Базовий URL сайту
 * @var array  $queueStats Статистика черги: pending, processing, done, failed
 */

$isEnabled  = $prom['prom_enabled']     === '1';
$syncMethod = $prom['prom_sync_method'] ?? 'xml';
$lastSync   = $prom['prom_last_sync']   ?? '';
$feedUrl    = rtrim($siteUrl, '/') . '/prom/feed.xml';
$webhookUrl = rtrim($siteUrl, '/') . '/prom/webhook';
?>

<style>
.int-section      { margin-bottom:1.25rem; }
.int-toggle       { display:flex; align-items:center; gap:1rem; padding:1rem 1.25rem;
                    background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; }
.int-toggle-label { font-weight:700; font-size:1rem; color:#0f172a; }
.int-toggle-desc  { font-size:.85rem; color:#64748b; margin-top:.15rem; }
/* Switch */
.sw               { position:relative; display:inline-block; width:48px; height:26px; flex-shrink:0; }
.sw input         { opacity:0; width:0; height:0; }
.sw-slider        { position:absolute; inset:0; background:#cbd5e1; border-radius:26px;
                    cursor:pointer; transition:.2s; }
.sw-slider:before { content:''; position:absolute; width:20px; height:20px; left:3px; bottom:3px;
                    background:#fff; border-radius:50%; transition:.2s; }
.sw input:checked + .sw-slider        { background:#10b981; }
.sw input:checked + .sw-slider:before { transform:translateX(22px); }
/* Disabled state */
.prom-body        { transition:opacity .2s; }
.prom-body.locked { opacity:.45; pointer-events:none; }
/* URL copy field */
.url-field        { display:flex; gap:.5rem; align-items:center; }
.url-field input  { flex:1; font-family:monospace; font-size:.82rem;
                    background:#f8fafc; border:1px solid #e2e8f0; }
/* Sync method tabs */
.sync-tabs        { display:flex; gap:.35rem; margin-bottom:1rem; }
.sync-tab         { padding:.4rem 1rem; border-radius:7px; border:1px solid #e2e8f0;
                    background:#fff; cursor:pointer; font-size:.875rem; font-weight:600;
                    color:#64748b; transition:.15s; }
.sync-tab.active  { background:#6366f1; color:#fff; border-color:#6366f1; }
.sync-panel       { display:none; }
.sync-panel.active { display:block; }
/* Queue stats */
.queue-grid       { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1rem; }
.queue-stat       { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;
                    padding:.75rem 1rem; text-align:center; }
.queue-stat .num  { font-size:1.5rem; font-weight:800; color:#0f172a; }
.queue-stat .lbl  { font-size:.75rem; color:#64748b; margin-top:.1rem; }
.queue-stat.pending   .num { color:#f59e0b; }
.queue-stat.done      .num { color:#10b981; }
.queue-stat.failed    .num { color:#ef4444; }
/* Test connection result */
.conn-result      { display:none; margin-top:.75rem; padding:.6rem 1rem;
                    border-radius:7px; font-size:.875rem; font-weight:600; }
.conn-result.ok   { background:#dcfce7; color:#166534; display:block; }
.conn-result.fail { background:#fee2e2; color:#991b1b; display:block; }
/* Spinner */
.spin             { display:inline-block; width:14px; height:14px; border:2px solid currentColor;
                    border-top-color:transparent; border-radius:50%;
                    animation:spin .6s linear infinite; vertical-align:middle; }
@keyframes spin   { to { transform:rotate(360deg); } }
</style>

<!-- Перемикач увімкнення інтеграції -->
<div class="int-section">
    <div class="int-toggle">
        <label class="sw">
            <input type="checkbox" id="promEnabled" <?= $isEnabled ? 'checked' : '' ?>>
            <span class="sw-slider"></span>
        </label>
        <div>
            <div class="int-toggle-label">
                Інтеграція з Prom.ua
                <span id="enabledBadge" style="margin-left:.5rem; font-size:.75rem; font-weight:700;
                      padding:2px 10px; border-radius:20px;
                      background:<?= $isEnabled ? '#dcfce7' : '#f1f5f9' ?>;
                      color:<?= $isEnabled ? '#166534' : '#64748b' ?>;">
                    <?= $isEnabled ? 'Увімкнено' : 'Вимкнено' ?>
                </span>
            </div>
            <div class="int-toggle-desc">
                Отримання замовлень через вебхук, синхронізація товарів та статусів із маркетплейсом Prom.ua.
            </div>
        </div>
    </div>
</div>

<!-- Основне тіло (блокується якщо вимкнено) -->
<div class="prom-body <?= !$isEnabled ? 'locked' : '' ?>" id="promBody">

    <!-- API ключ -->
    <div class="card int-section">
        <div class="card-header"><i class="fas fa-key"></i> API ключ Prom.ua</div>
        <div class="card-body">
            <div class="form-group">
                <label>Ваш API ключ</label>
                <div style="display:flex; gap:.5rem;">
                    <input type="text" id="promApiKey" class="form-control"
                           value="<?= htmlspecialchars($prom['prom_api_key']) ?>"
                           placeholder="Вставте API ключ з кабінету Prom.ua"
                           style="font-family:monospace; flex:1;">
                    <button type="button" class="btn btn-outline" id="testConnBtn"
                            style="border:1px solid #ddd; white-space:nowrap;">
                        <i class="fas fa-plug"></i> Перевірити зв'язок
                    </button>
                </div>
                <div class="conn-result" id="connResult"></div>
                <small style="color:#64748b; font-size:.8rem; display:block; margin-top:.35rem;">
                    Знайти ключ: Кабінет Prom.ua → Налаштування → API → Згенерувати токен.
                </small>
            </div>

            <div class="form-group">
                <label>Webhook Secret (необов'язково)</label>
                <input type="text" id="promWebhookSecret" class="form-control"
                       value="<?= htmlspecialchars($prom['prom_webhook_secret']) ?>"
                       placeholder="Секретний ключ для верифікації підпису вебхука"
                       style="font-family:monospace;">
                <small style="color:#64748b; font-size:.8rem; display:block; margin-top:.3rem;">
                    Якщо заповнено — кожен вебхук від Prom перевіряється через HMAC-SHA256.
                </small>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label>URL вебхука — вкажіть у кабінеті Prom.ua</label>
                <div class="url-field">
                    <input type="text" class="form-control" readonly
                           value="<?= htmlspecialchars($webhookUrl) ?>" id="webhookUrlField">
                    <button type="button" class="btn btn-outline"
                            style="border:1px solid #ddd;"
                            onclick="copyField('webhookUrlField', this)">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Синхронізація товарів -->
    <div class="card int-section">
        <div class="card-header"><i class="fas fa-sync-alt"></i> Синхронізація товарів</div>
        <div class="card-body">
            <?php if ($lastSync): ?>
                <div style="font-size:.82rem; color:#64748b; margin-bottom:1rem;">
                    <i class="fas fa-clock"></i> Остання синхронізація:
                    <strong><?= htmlspecialchars(date('d.m.Y H:i', strtotime($lastSync))) ?></strong>
                </div>
            <?php endif; ?>

            <div class="sync-tabs">
                <button type="button" class="sync-tab <?= $syncMethod === 'xml' ? 'active' : '' ?>"
                        onclick="switchSync('xml', this)">
                    <i class="fas fa-file-code"></i> Підхід А: XML/YML фід
                </button>
                <button type="button" class="sync-tab <?= $syncMethod === 'api' ? 'active' : '' ?>"
                        onclick="switchSync('api', this)">
                    <i class="fas fa-bolt"></i> Підхід Б: API (миттєво)
                </button>
            </div>
            <input type="hidden" id="promSyncMethod" value="<?= htmlspecialchars($syncMethod) ?>">

            <!-- Підхід А -->
            <div class="sync-panel <?= $syncMethod === 'xml' ? 'active' : '' ?>" id="panel-xml">
                <p style="font-size:.875rem; color:#475569; margin-bottom:1rem;">
                    Prom сам завантажує ваш каталог за посиланням. Рекомендовано для магазинів з великим каталогом.
                    Вкажіть URL фіду в кабінеті Prom: <strong>Товари → Імпорт → YML</strong>.
                </p>
                <div class="form-group">
                    <label>URL вашого XML-фіду</label>
                    <div class="url-field">
                        <input type="text" class="form-control" readonly
                               value="<?= htmlspecialchars($feedUrl) ?>" id="feedUrlField">
                        <button type="button" class="btn btn-outline"
                                style="border:1px solid #ddd;"
                                onclick="copyField('feedUrlField', this)">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" id="generateFeedBtn">
                    <i class="fas fa-file-export"></i> Згенерувати XML зараз
                </button>
                <div id="feedResult" style="margin-top:.75rem; font-size:.875rem;"></div>
            </div>

            <!-- Підхід Б -->
            <div class="sync-panel <?= $syncMethod === 'api' ? 'active' : '' ?>" id="panel-api">
                <p style="font-size:.875rem; color:#475569; margin-bottom:1rem;">
                    Зміни цін та залишків ставляться в чергу і надсилаються в Prom через API.
                    Підходить для миттєвого оновлення окремих позицій.
                </p>

                <?php if (!empty($queueStats)): ?>
                <div class="queue-grid">
                    <div class="queue-stat pending">
                        <div class="num" id="qPending"><?= (int)$queueStats['pending'] ?></div>
                        <div class="lbl">В черзі</div>
                    </div>
                    <div class="queue-stat">
                        <div class="num" id="qProcessing"><?= (int)$queueStats['processing'] ?></div>
                        <div class="lbl">Обробляється</div>
                    </div>
                    <div class="queue-stat done">
                        <div class="num" id="qDone"><?= (int)$queueStats['done'] ?></div>
                        <div class="lbl">Виконано</div>
                    </div>
                    <div class="queue-stat failed">
                        <div class="num" id="qFailed"><?= (int)$queueStats['failed'] ?></div>
                        <div class="lbl">Помилок</div>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem;">
                    <button type="button" class="btn btn-primary" id="enqueueBtn">
                        <i class="fas fa-layer-group"></i> Додати всі товари в чергу
                    </button>
                    <button type="button" class="btn btn-outline"
                            style="border:1px solid #6366f1; color:#6366f1;" id="processQueueBtn">
                        <i class="fas fa-play"></i> Обробити чергу (50 шт.)
                    </button>
                    <button type="button" class="btn btn-outline"
                            style="border:1px solid #ddd; color:#94a3b8;" id="clearQueueBtn">
                        <i class="fas fa-trash"></i> Очистити виконані
                    </button>
                </div>
                <div id="queueResult" style="font-size:.875rem;"></div>
            </div>
        </div>
    </div>

    <!-- Кнопка збереження -->
    <button type="button" class="btn btn-primary" id="savePromBtn" style="min-width:180px;">
        <i class="fas fa-save"></i> Зберегти налаштування
    </button>
    <div id="saveResult" style="display:inline-block; margin-left:.75rem; font-size:.875rem;"></div>

</div><!-- /prom-body -->

<script>
(function () {
    const CSRF = <?= json_encode($_SESSION['csrf'] ?? '') ?>;

    // ── Toggle вмикання ───────────────────────────────────────────────────────
    const toggle    = document.getElementById('promEnabled');
    const body      = document.getElementById('promBody');
    const badge     = document.getElementById('enabledBadge');

    toggle.addEventListener('change', function () {
        const on = this.checked;
        body.classList.toggle('locked', !on);
        badge.textContent = on ? 'Увімкнено' : 'Вимкнено';
        badge.style.background = on ? '#dcfce7' : '#f1f5f9';
        badge.style.color      = on ? '#166534' : '#64748b';
    });

    // ── Перевірити з'єднання ─────────────────────────────────────────────────
    document.getElementById('testConnBtn').addEventListener('click', async function () {
        const btn    = this;
        const result = document.getElementById('connResult');
        const apiKey = document.getElementById('promApiKey').value.trim();

        btn.disabled    = true;
        btn.innerHTML   = '<span class="spin"></span> Перевірка…';
        result.className = 'conn-result';

        try {
            const res  = await fetch('/admin/prom/test', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ csrf: CSRF, prom_api_key: apiKey }),
            });
            const data = await res.json();

            result.className = 'conn-result ' + (data.success ? 'ok' : 'fail');
            result.innerHTML = (data.success
                ? '<i class="fas fa-check-circle"></i> '
                : '<i class="fas fa-times-circle"></i> ')
                + (data.message || 'Невідома відповідь');
        } catch {
            result.className  = 'conn-result fail';
            result.textContent = 'Помилка мережі.';
        }

        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-plug"></i> Перевірити зв\'язок';
    });

    // ── Зберегти налаштування ─────────────────────────────────────────────────
    document.getElementById('savePromBtn').addEventListener('click', async function () {
        const btn    = this;
        const result = document.getElementById('saveResult');

        btn.disabled  = true;
        btn.innerHTML = '<span class="spin"></span> Збереження…';
        result.textContent = '';

        const body = new URLSearchParams({
            csrf:                CSRF,
            prom_enabled:        document.getElementById('promEnabled').checked ? '1' : '0',
            prom_api_key:        document.getElementById('promApiKey').value.trim(),
            prom_sync_method:    document.getElementById('promSyncMethod').value,
            prom_webhook_secret: document.getElementById('promWebhookSecret').value.trim(),
        });

        try {
            const res  = await fetch('/admin/prom/save', { method: 'POST', body });
            const data = await res.json();
            result.style.color = data.success ? '#10b981' : '#ef4444';
            result.textContent = data.message;
        } catch {
            result.style.color = '#ef4444';
            result.textContent = 'Помилка мережі.';
        }

        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Зберегти налаштування';
    });

    // ── Перемикач методу синхронізації ────────────────────────────────────────
    window.switchSync = function (method, btn) {
        document.querySelectorAll('.sync-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.sync-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('panel-' + method).classList.add('active');
        document.getElementById('promSyncMethod').value = method;
    };

    // ── XML фід ──────────────────────────────────────────────────────────────
    document.getElementById('generateFeedBtn').addEventListener('click', async function () {
        const btn    = this;
        const result = document.getElementById('feedResult');

        btn.disabled  = true;
        btn.innerHTML = '<span class="spin"></span> Генерація…';

        try {
            const res  = await fetch('/admin/prom/generate-feed', {
                method: 'POST',
                body: new URLSearchParams({ csrf: CSRF }),
            });
            const data = await res.json();
            result.style.color = data.success ? '#10b981' : '#ef4444';
            result.innerHTML   = (data.success
                ? '<i class="fas fa-check-circle"></i> '
                : '<i class="fas fa-times-circle"></i> ')
                + data.message;
        } catch {
            result.style.color = '#ef4444';
            result.textContent = 'Помилка мережі.';
        }

        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-file-export"></i> Згенерувати XML зараз';
    });

    // ── API черга ─────────────────────────────────────────────────────────────
    async function queueAction(url, extraParams, btnId, loadingText) {
        const btn    = document.getElementById(btnId);
        const result = document.getElementById('queueResult');

        btn.disabled  = true;
        btn.innerHTML = '<span class="spin"></span> ' + loadingText;

        try {
            const res  = await fetch(url, {
                method: 'POST',
                body: new URLSearchParams({ csrf: CSRF, ...extraParams }),
            });
            const data = await res.json();
            result.style.color = data.success ? '#10b981' : '#ef4444';
            result.innerHTML   = (data.success
                ? '<i class="fas fa-check-circle"></i> '
                : '<i class="fas fa-times-circle"></i> ')
                + data.message;

            // Оновлюємо лічильники черги
            if (data.stats) {
                const s = data.stats;
                ['Pending','Processing','Done','Failed'].forEach(k => {
                    const el = document.getElementById('q' + k);
                    if (el && s[k.toLowerCase()] !== undefined) {
                        el.textContent = s[k.toLowerCase()];
                    }
                });
            }
        } catch {
            result.style.color = '#ef4444';
            result.textContent = 'Помилка мережі.';
        }

        btn.disabled = false;
    }

    document.getElementById('enqueueBtn')
        ?.addEventListener('click', () =>
            queueAction('/admin/prom/enqueue', { action: 'both' }, 'enqueueBtn', 'Додавання…'));

    document.getElementById('processQueueBtn')
        ?.addEventListener('click', () =>
            queueAction('/admin/prom/process-queue', {}, 'processQueueBtn', 'Обробка…'));

    document.getElementById('clearQueueBtn')
        ?.addEventListener('click', () =>
            queueAction('/admin/prom/clear-queue', { status: 'done' }, 'clearQueueBtn', 'Очищення…'));

    // ── Копіювання URL ────────────────────────────────────────────────────────
    window.copyField = function (fieldId, btn) {
        const input = document.getElementById(fieldId);
        input.select();
        document.execCommand('copy');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    };
})();
</script>
