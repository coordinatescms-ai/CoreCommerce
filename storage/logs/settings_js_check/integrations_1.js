
(function () {
    const CSRF = x;

    // ── Toggle вмикання ───────────────────────────────────────────────────────
    const toggle    = document.getElementById('promEnabled');
    const body      = document.getElementById('promBody');
    const badge     = document.getElementById('enabledBadge');

    toggle.addEventListener('change', function () {
        const on = this.checked;
        body.classList.toggle('locked', !on);
        badge.textContent = on ? window.LANG.enabled : window.LANG.disabled;
        badge.style.background = on ? '#dcfce7' : '#f1f5f9';
        badge.style.color      = on ? '#166534' : '#64748b';
    });

    // ── Перевірити з'єднання ─────────────────────────────────────────────────
    document.getElementById('testConnBtn').addEventListener('click', async function () {
        const btn    = this;
        const result = document.getElementById('connResult');
        const apiKey = document.getElementById('promApiKey').value.trim();

        btn.disabled    = true;
        btn.innerHTML   = `<span class="spin"></span> ${window.LANG.checking}`;
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
                + (data.message || window.LANG.unknown_error);
        } catch {
            result.className  = 'conn-result fail';
            result.textContent = x;
        }

        btn.disabled  = false;
        btn.innerHTML = `<i class="fas fa-plug"></i> ${window.LANG.check_connection}`;
    });

    // ── x ─────────────────────────────────────────────────
    document.getElementById('savePromBtn').addEventListener('click', async function () {
        const btn    = this;
        const result = document.getElementById('saveResult');

        btn.disabled  = true;
        btn.innerHTML = `<span class="spin"></span> ${window.LANG.saving}`;
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
            result.textContent = x;
        }

        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-save"></i> x';
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
        btn.innerHTML = `<span class="spin"></span> ${window.LANG.generating}`;

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
            result.textContent = x;
        }

        btn.disabled  = false;
        btn.innerHTML = `<i class="fas fa-file-export"></i> ${window.LANG.generate_xml}`;
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
            result.textContent = x;
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
