(function () {
    const cfg      = window.CRON_TASKS_CONFIG || {};
    const endpoint = cfg.endpoint || '/cron_tasks_ajax.php';
    const csrf     = cfg.csrf || '';
    const table    = document.getElementById('cronTasksTable');
    if (!table) return;

    const modal  = document.getElementById('cronModalBackdrop');
    const fields = {
        id:       document.getElementById('cronTaskId'),
        name:     document.getElementById('cronTaskName'),
        schedule: document.getElementById('cronTaskSchedule'),
        command:  document.getElementById('cronTaskCommand'),
        params:   document.getElementById('cronTaskParams'),
    };

    const LANG = window.LANG || {};

    const post = (payload) =>
        fetch(endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(Object.assign({ csrf }, payload)),
        }).then(r => r.json());

    table.addEventListener('click', async (e) => {
        // ── Редагувати ──────────────────────────────────────────────────────
        const editBtn = e.target.closest('.js-edit-task');
        if (editBtn) {
            const row  = editBtn.closest('tr');
            const task = JSON.parse(row.dataset.task || '{}');
            fields.id.value       = task.id       || '';
            fields.name.value     = task.name     || '';
            fields.schedule.value = task.schedule || '';
            fields.command.value  = task.command  || '';
            fields.params.value   = task.params   || '';
            modal.classList.add('active');
            return;
        }

        // ── Увімкнути / Вимкнути ────────────────────────────────────────────
        const toggleBtn = e.target.closest('.js-toggle-task');
        if (toggleBtn) {
            const row  = toggleBtn.closest('tr');
            const task = JSON.parse(row.dataset.task || '{}');
            const res  = await post({ action: 'toggle', id: task.id });
            if (!res.success) return alert(res.message || LANG.error || 'Error');
            const newStatus = res.task.status;
            const statusEl  = row.querySelector('.cron-status');
            statusEl.textContent = newStatus;
            statusEl.className   = 'badge ' + newStatus + ' cron-status';
            toggleBtn.textContent = newStatus === 'active' ? 'Disable' : 'Enable';
            task.status          = newStatus;
            row.dataset.task     = JSON.stringify(task);
            return;
        }

        // ── Запустити зараз ─────────────────────────────────────────────────
        const runBtn = e.target.closest('.js-run-task');
        if (runBtn) {
            const row    = runBtn.closest('tr');
            const task   = JSON.parse(row.dataset.task || '{}');
            const result = row.querySelector('.cron-result');
            result.textContent = 'running';
            result.className   = 'badge running cron-result';
            const res = await post({ action: 'run_now', id: task.id });
            if (!res.success) {
                result.textContent = 'failed';
                result.className   = 'badge failed cron-result';
                row.querySelector('.cron-error').textContent = res.message || LANG.error || 'Error';
                return;
            }
            result.textContent = res.task.last_result;
            result.className   = 'badge ' + res.task.last_result + ' cron-result';
            row.querySelector('.cron-error').textContent = res.task.error_message || '';
        }
    });

    // ── Кнопки модального вікна ─────────────────────────────────────────────
    document.getElementById('cronModalCancel')
        .addEventListener('click', () => modal.classList.remove('active'));

    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('active');
    });

    document.getElementById('cronModalSave')
        .addEventListener('click', async () => {
            const res = await post({
                action:   'update',
                id:       fields.id.value,
                name:     fields.name.value,
                schedule: fields.schedule.value,
                command:  fields.command.value,
                params:   fields.params.value,
            });
            if (!res.success) return alert(res.message || LANG.save_error || 'Save error');

            const row = table.querySelector(
                'tr[data-task*=\'"id":' + Number(fields.id.value) + '\']'
            );
            if (row) {
                const task      = JSON.parse(row.dataset.task || '{}');
                task.name       = fields.name.value;
                task.schedule   = fields.schedule.value;
                task.command    = fields.command.value;
                task.params     = fields.params.value;
                row.dataset.task                            = JSON.stringify(task);
                row.children[0].textContent                 = fields.name.value;
                row.querySelector('.cron-schedule').textContent = fields.schedule.value;
            }
            modal.classList.remove('active');
        });
})();
