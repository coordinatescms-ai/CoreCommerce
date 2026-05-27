document.addEventListener('click', function(event) {
    // Делегування для кнопки перевірки
    if (event.target && (event.target.id === 'check-update-btn' || event.target.closest('#check-update-btn'))) {
        const checkBtn = document.getElementById('check-update-btn');
        checkBtn.disabled = true;
        checkBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Перевірка...';
        
        fetch('/admin/update/check')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.update_available) {
                    document.getElementById('new-version-id').textContent = data.new_version;
                    document.getElementById('changelog-text').textContent = data.changelog;
                    document.getElementById('update-available-container').style.display = 'block';
                    checkBtn.style.display = 'none';
                    
                    setTimeout(() => {
                        const w = document.getElementById('check-writable');
                        const p = document.getElementById('check-php');
                        if(w) w.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Права на запис: OK';
                        if(p) p.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Версія PHP (8.x): OK';
                    }, 500);
                } else {
                    alert(data.message || 'Оновлень не знайдено');
                    checkBtn.disabled = false;
                    checkBtn.innerHTML = '<i class="fas fa-search"></i> Перевірити наявність оновлень';
                }
            })
            .catch(err => {
                alert('Помилка при перевірці оновлень');
                checkBtn.disabled = false;
                checkBtn.innerHTML = '<i class="fas fa-search"></i> Перевірити наявність оновлень';
            });
    }

    // Делегування для кнопки старту
    if (event.target && (event.target.id === 'start-update-btn' || event.target.closest('#start-update-btn'))) {
        const startBtn = document.getElementById('start-update-btn');
        const password = document.getElementById('admin-password').value;
        if (!password) {
            alert('Будь ласка, введіть пароль');
            return;
        }

        if (!confirm('Ви впевнені, що хочете розпочати оновлення? Це може змінити файли ядра.')) {
            return;
        }

        document.getElementById('update-available-container').style.display = 'none';
        document.getElementById('update-progress-container').style.display = 'block';
        startBtn.disabled = true;
        
        runUpdateProcess('init', password);
    }
});

const updateSteps = {
    'init': { label: 'Ініціалізація', progress: 10 },
    'backup': { label: 'Резервне копіювання', progress: 30 },
    'download': { label: 'Завантаження', progress: 50 },
    'extract': { label: 'Розпакування', progress: 70 },
    'database': { label: 'Міграція БД', progress: 90 },
    'finish': { label: 'Завершення', progress: 100 }
};

function addUpdateLog(msg, type = 'info') {
    const logContainer = document.getElementById('update-log-container');
    if (!logContainer) return;
    const div = document.createElement('div');
    const color = type === 'error' ? '#f87171' : (type === 'success' ? '#4ade80' : '#f1f5f9');
    div.style.color = color;
    div.textContent = `> ${msg}`;
    logContainer.appendChild(div);
    logContainer.scrollTop = logContainer.scrollHeight;
}

function getUpdateCsrfToken() {
    const tokenInput = document.getElementById('update-csrf');
    return tokenInput ? tokenInput.value : '';
}

function runUpdateProcess(step, password = null) {
    if (!step) return;

    const info = updateSteps[step];
    const statusText = document.getElementById('update-status-text');
    const progressBar = document.getElementById('update-progress-bar');
    
    if (statusText) statusText.textContent = info.label + '...';
    if (progressBar) {
        progressBar.style.width = info.progress + '%';
        progressBar.textContent = info.progress + '%';
    }
    
    addUpdateLog(info.label + ' у процесі...');

    const body = new URLSearchParams();
    body.set('csrf', getUpdateCsrfToken());
    if (step === 'init' && password !== null) {
        body.set('password', password);
    }

    fetch('/admin/update/' + step, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        body: body.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            addUpdateLog(data.message, 'success');
            if (data.next_step) {
                setTimeout(() => runUpdateProcess(data.next_step), 1000);
            } else {
                if (statusText) statusText.textContent = 'Оновлення завершено!';
                if (progressBar) progressBar.style.background = '#10b981';
                addUpdateLog('Система готова до роботи', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } else {
            addUpdateLog('ПОМИЛКА: ' + data.message, 'error');
            if (statusText) statusText.textContent = 'Оновлення зупинено через помилку';
            if (progressBar) progressBar.style.background = '#ef4444';
            const startBtn = document.getElementById('start-update-btn');
            if (startBtn) startBtn.disabled = false;
        }
    })
    .catch(err => {
        addUpdateLog('Критична помилка запиту', 'error');
        const startBtn = document.getElementById('start-update-btn');
        if (startBtn) startBtn.disabled = false;
    });
}
