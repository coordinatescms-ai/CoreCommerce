(function () {
    const cfg = window.CRON_TASKS_CONFIG || {};
    const endpoint = cfg.endpoint || '/cron_tasks_ajax.php';
    const csrf = cfg.csrf || '';
    const table = document.getElementById('cronTasksTable');
    if (!table) return;

    const modal = document.getElementById('cronModalBackdrop');
    const fields = {
        id: document.getElementById('cronTaskId'),
        name: document.getElementById('cronTaskName'),
        schedule: document.getElementById('cronTaskSchedule'),
        params: document.getElementById('cronTaskParams')
    };

    const post = (payload) => fetch(endpoint, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.assign({csrf: csrf}, payload))
    }).then(r => r.json());

    table.addEventListener('click', async (e) => {
        const row = e.target.closest('tr');
        if (!row) return;
        const task = JSON.parse(row.dataset.task || '{}');

        if (e.target.classList.contains('js-edit-task')) {
            fields.id.value = task.id || '';
            fields.name.value = task.name || '';
            fields.schedule.value = task.schedule || '';
            fields.params.value = task.params || '';
            modal.classList.add('active');
        }

        if (e.target.classList.contains('js-toggle-task')) {
            const res = await post({action: 'toggle', id: task.id});
            if (!res.success) return alert(res.message || 'Помилка');
            const newStatus = res.task.status;
            row.querySelector('.cron-status').textContent = newStatus;
            row.querySelector('.cron-status').className = 'badge ' + newStatus + ' cron-status';
            e.target.textContent = newStatus === 'active' ? 'Disable' : 'Enable';
            task.status = newStatus;
            row.dataset.task = JSON.stringify(task);
        }

        if (e.target.classList.contains('js-run-task')) {
            row.querySelector('.cron-result').textContent = 'running';
            row.querySelector('.cron-result').className = 'badge running cron-result';
            const res = await post({action: 'run_now', id: task.id});
            if (!res.success) {
                row.querySelector('.cron-result').textContent = 'failed';
                row.querySelector('.cron-result').className = 'badge failed cron-result';
                row.querySelector('.cron-error').textContent = res.message || 'Помилка';
                return;
            }
            row.querySelector('.cron-result').textContent = res.task.last_result;
            row.querySelector('.cron-result').className = 'badge ' + res.task.last_result + ' cron-result';
            row.querySelector('.cron-error').textContent = res.task.error_message || '';
        }
    });

    document.getElementById('cronModalCancel').addEventListener('click', () => modal.classList.remove('active'));
    document.getElementById('cronModalSave').addEventListener('click', async () => {
        const res = await post({
            action: 'update',
            id: fields.id.value,
            name: fields.name.value,
            schedule: fields.schedule.value,
            params: fields.params.value
        });
        if (!res.success) return alert(res.message || 'Помилка збереження');

        const row = table.querySelector('tr[data-task*="\"id\":' + Number(fields.id.value) + '"]');
        if (row) {
            const task = JSON.parse(row.dataset.task || '{}');
            task.name = fields.name.value;
            task.schedule = fields.schedule.value;
            task.params = fields.params.value;
            row.dataset.task = JSON.stringify(task);
            row.children[0].textContent = fields.name.value;
            row.querySelector('.cron-schedule').textContent = fields.schedule.value;
        }
        modal.classList.remove('active');
    });
})();
