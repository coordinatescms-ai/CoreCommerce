
(function () {
    const csrf    = x;
    const STEPS   = ['init','backup','download','extract','database','finish'];
    const LABELS  = {
        init:     x,
        backup:   x,
        download: x,
        extract:  x,
        database: x,
        finish:   x,
    };

    // ── Перевірка оновлення ───────────────────────────────────────────
    const btnCheck   = document.getElementById('btn-check-update');
    const checkResult= document.getElementById('update-check-result');
    const runCard    = document.getElementById('update-run-card');

    if (btnCheck) {
        btnCheck.addEventListener('click', async () => {
            btnCheck.disabled = true;
            btnCheck.innerHTML = '<span class="spin"></span> x';
            checkResult.style.color = '#64748b';
            checkResult.textContent = x;

            try {
                const res  = await fetch('/admin/update/check');
                const data = await res.json();

                if (!data.update_available) {
                    checkResult.textContent = '✅ ' + data.message;
                    checkResult.style.color = '#10b981';
                } else {
                    checkResult.innerHTML = `🆕 <strong>${data.message}</strong>`;
                    checkResult.style.color = '#3b82f6';
                    document.getElementById('update-changelog').textContent = data.changelog || '';
                    runCard.style.display = 'block';
                    runCard.scrollIntoView({ behavior: 'smooth' });
                }
            } catch (e) {
                checkResult.textContent = x;
                checkResult.style.color = '#ef4444';
            }

            btnCheck.disabled = false;
            btnCheck.innerHTML = '<i class="fas fa-sync-alt"></i> x';
        });
    }

    // ── Запуск оновлення ──────────────────────────────────────────────
    const btnStart  = document.getElementById('btn-start-update');
    const stepsEl   = document.getElementById('update-steps-list');
    const progressBar = document.getElementById('update-progress-bar');
    const progressEl  = document.getElementById('update-progress');
    const finalMsg    = document.getElementById('update-final-message');
    const actionBtns  = document.getElementById('update-action-btns');

    if (btnStart) {
        btnStart.addEventListener('click', () => runUpdate());
    }

    async function runUpdate() {
        const password = document.getElementById('update-password')?.value ?? '';
        if (!password) {
            alert(x);
            return;
        }

        btnStart && (btnStart.disabled = true);
        progressEl.style.display = 'block';
        stepsEl.innerHTML = '';
        finalMsg.style.display = 'none';

        let stepIndex  = 0;
        let nextStep   = 'init';
        const total    = STEPS.length;

        while (nextStep) {
            const stepEl = document.createElement('div');
            stepEl.style.cssText = 'display:flex;align-items:center;gap:.5rem;';
            stepEl.innerHTML = `<span class="spin" style="flex-shrink:0;"></span> <span>${LABELS[nextStep] ?? nextStep}…</span>`;
            stepsEl.appendChild(stepEl);

            try {
                const body = new URLSearchParams({ csrf });
                if (nextStep === 'init') body.append('password', password);

                const res  = await fetch('/admin/update/' + nextStep, { method: 'POST', body });
                const data = await res.json();

                stepEl.innerHTML = `<i class="fas ${data.success ? 'fa-check-circle' : 'fa-times-circle'}"
                    style="color:${data.success ? '#10b981' : '#ef4444'};flex-shrink:0;"></i>
                    <span>${data.message}</span>`;

                stepIndex++;
                progressBar.style.width = Math.round(stepIndex / total * 100) + '%';

                if (!data.success) {
                    showFinalMessage(data.message, false);
                    return;
                }

                nextStep = data.next_step || null;

            } catch (err) {
                stepEl.innerHTML = `<i class="fas fa-times-circle" style="color:#ef4444;flex-shrink:0;"></i> <span>x${err.message}</span>`;
                showFinalMessage(x + ": " + err.message, false);
                return;
            }
        }

        // Успіх
        progressBar.style.width = '100%';
        progressBar.style.background = '#10b981';
        showFinalMessage(x, true);
    }

    function showFinalMessage(text, success) {
        finalMsg.style.display = 'block';
        finalMsg.style.cssText = `display:block;padding:.75rem 1rem;border-radius:8px;margin-top:.75rem;
            background:${success ? '#f0fdf4' : '#fef2f2'};
            color:${success ? '#166534' : '#991b1b'};
            border:1px solid ${success ? '#bbf7d0' : '#fecaca'};
            font-weight:500;`;
        finalMsg.textContent = text;
        if (success) {
            actionBtns.innerHTML = '<a href="/admin" class="btn btn-primary"><i class="fas fa-home"></i> x</a>';
        }
    }
})();
