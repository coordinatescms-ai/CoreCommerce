(function () {
    const cfg      = window.CRON_TASKS_CONFIG || {};
    const endpoint = cfg.endpoint || '/cron_tasks_ajax.php';
    const csrf     = cfg.csrf || '';
    const lang     = cfg.lang || {};
    const table    = document.getElementById('cronTasksTable');
    if (!table) return;

    const modal    = document.getElementById('cronModalBackdrop');
    const addModal = document.getElementById('cronAddModalBackdrop');

    const fields = {
        id:       document.getElementById('cronTaskId'),
        name:     document.getElementById('cronTaskName'),
        schedule: document.getElementById('cronTaskSchedule'),
        command:  document.getElementById('cronTaskCommand'),
        params:   document.getElementById('cronTaskParams'),
    };

    const addFields = {
        name:     document.getElementById('cronAddName'),
        schedule: document.getElementById('cronAddSchedule'),
        command:  document.getElementById('cronAddCommand'),
        params:   document.getElementById('cronAddParams'),
        status:   document.getElementById('cronAddStatus'),
    };

    const post = (payload) => fetch(endpoint, {
        method:  'POST',
        headers: {'Content-Type': 'application/json'},
        body:    JSON.stringify(Object.assign({csrf}, payload)),
    }).then(r => r.json());

    // ── Кнопки в рядках таблиці ────────────────────────────────────────
    table.addEventListener('click', async (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const row  = btn.closest('tr');
        if (!row) return;
        const task = JSON.parse(row.dataset.task || '{}');

        // Редагувати
        if (btn.classList.contains('js-edit-task')) {
            fields.id.value       = task.id || '';
            fields.name.value     = task.name || '';
            fields.schedule.value = task.schedule || '';
            fields.command.value  = task.command || '';
            fields.params.value   = task.params || '';
            modal.classList.add('active');
            return;
        }

        // Увімкнути / вимкнути
        if (btn.classList.contains('js-toggle-task')) {
            const res = await post({action: 'toggle', id: task.id});
            if (!res.success) { alert(res.message || lang.error || 'Помилка'); return; }
            const s = res.task.status;
            row.querySelector('.cron-status').textContent  = s;
            row.querySelector('.cron-status').className    = 'badge ' + s + ' cron-status';
            btn.textContent = res.task.toggle_label || (s === 'active' ? (lang.disable || 'Disable') : (lang.enable || 'Enable'));
            task.status     = s;
            row.dataset.task = JSON.stringify(task);
            return;
        }

        // Запустити зараз
        if (btn.classList.contains('js-run-task')) {
            row.querySelector('.cron-result').textContent = lang.running || 'running';
            row.querySelector('.cron-result').className   = 'badge running cron-result';
            const res = await post({action: 'run_now', id: task.id});
            const result = res.task?.last_result || (res.success ? (lang.success || 'success') : (lang.failed || 'failed'));
            row.querySelector('.cron-result').textContent = result;
            row.querySelector('.cron-result').className   = 'badge ' + result + ' cron-result';
            row.querySelector('.cron-error').textContent  = res.task?.error_message || (res.success ? '' : (res.message || lang.error || 'Помилка'));
            return;
        }

        // Видалити
        if (btn.classList.contains('js-delete-task')) {
            const confirmMsg = lang.delete_confirm || 'Видалити це Cron-завдання?';
            if (!confirm(confirmMsg)) return;
            const res = await post({action: 'delete', id: task.id});
            if (!res.success) { alert(res.message || lang.delete_error || 'Помилка видалення'); return; }
            row.remove();
            return;
        }
    });

    // ── Модал редагування ───────────────────────────────────────────────
    document.getElementById('cronModalCancel').addEventListener('click', () => modal.classList.remove('active'));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('active'); });

    document.getElementById('cronModalSave').addEventListener('click', async () => {
        const name     = fields.name.value.trim();
        const schedule = fields.schedule.value.trim();
        const command  = fields.command.value.trim();
        if (!name || !schedule || !command) { alert(lang.fill_required || 'Заповніть обовязкові поля'); return; }

        const res = await post({
            action:   'update',
            id:       fields.id.value,
            name,
            schedule,
            command,
            params:   fields.params.value,
        });
        if (!res.success) { alert(res.message || lang.save_error || 'Помилка збереження'); return; }

        const taskId = Number(fields.id.value);
        const row    = table.querySelector(`tr[data-task*='"id":${taskId}']`)
                    || table.querySelector(`tr[data-task*='"id": ${taskId}']`);
        if (row) {
            const task    = JSON.parse(row.dataset.task || '{}');
            task.name     = name;
            task.schedule = schedule;
            task.command  = command;
            task.params   = fields.params.value;
            row.dataset.task           = JSON.stringify(task);
            row.children[0].textContent = name;
            row.querySelector('.cron-schedule').textContent = schedule;
        }
        modal.classList.remove('active');
    });

    // ── Модал додавання ─────────────────────────────────────────────────
    window.cronOpenAddModal = () => {
        Object.values(addFields).forEach(f => { if (f.tagName !== 'SELECT') f.value = ''; });
        addFields.status.value = 'active';
        addModal.classList.add('active');
    };

    document.getElementById('cronAddModalCancel').addEventListener('click', () => addModal.classList.remove('active'));
    addModal.addEventListener('click', (e) => { if (e.target === addModal) addModal.classList.remove('active'); });

    document.getElementById('cronAddModalSave').addEventListener('click', async () => {
        // Перевіряємо що addFields елементи існують
        if (!addFields.name || !addFields.schedule || !addFields.command) {
            alert(lang.form_not_found || 'Помилка: поля форми не знайдені в DOM');
            return;
        }

        const name     = addFields.name.value.trim();
        const schedule = addFields.schedule.value.trim();
        const command  = addFields.command.value.trim();

        if (!name || !schedule || !command) {
            alert(lang.fill_all_required || 'Заповніть обовязкові поля: Назва, Розклад, Файл скрипту');
            return;
        }

        let res;
        try {
            res = await post({
                action:   'create',
                name,
                schedule,
                command,
                params:   addFields.params.value.trim(),
                status:   addFields.status.value,
            });
        } catch (err) {
            alert((lang.request_error || 'Помилка запиту') + ': ' + err.message);
            return;
        }

        if (!res.success) { alert(res.message || lang.create_error || 'Помилка створення'); return; }

        // Додаємо новий рядок в таблицю
        const t    = res.task;
        const tbody = table.querySelector('tbody');
        const tr    = document.createElement('tr');
        tr.dataset.task = JSON.stringify(t);
        tr.innerHTML = `
            <td>${escHtml(t.name)}</td>
            <td class="cron-schedule">${escHtml(t.schedule)}</td>
            <td>${escHtml(t.last_run || '—')}</td>
            <td>${escHtml(t.next_run || '—')}</td>
            <td><span class="badge ${escHtml(t.status)} cron-status">${escHtml(t.status)}</span></td>
            <td>
                <span class="badge success cron-result">—</span>
                <div class="cron-error" style="font-size:12px;color:#991b1b;"></div>
            </td>
            <td>
                <div class="cron-actions">
                    <button class="btn btn-primary js-edit-task" type="button"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-secondary js-toggle-task" type="button">${t.status === 'active' ? (lang.disable || 'Disable') : (lang.enable || 'Enable')}</button>
                    <button class="btn btn-success js-run-task" type="button"><i class="fas fa-play"></i></button>
                    <button class="btn btn-danger js-delete-task" type="button"><i class="fas fa-trash"></i></button>
                </div>
            </td>`;
        tbody.appendChild(tr);
        addModal.classList.remove('active');
    });

    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
