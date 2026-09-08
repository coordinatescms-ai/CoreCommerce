
(function () {
    const tabsRoot = document.getElementById('settings-tabs');
    const tabContent = document.getElementById('settings-tab-content');

    function setActiveTab(tab) {
        tabsRoot.querySelectorAll('.settings-tab-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.tab === tab);
            if (btn.dataset.tab === tab) {
                btn.classList.add('btn-primary');
                btn.classList.remove('btn-outline');
            } else {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
            }
        });
    }

    function loadTab(tab) {
        setActiveTab(tab);
        tabContent.innerHTML = '<div class="card"><div class="card-body">x</div></div>';

        fetch('/admin/settings/tab/' + encodeURIComponent(tab), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(x);
                }
                return response.text();
            })
            .then(function (html) {
                tabContent.innerHTML = html;
                // innerHTML не виконує <script> — запускаємо вручну
                tabContent.querySelectorAll('script').forEach(function (oldScript) {
                    const newScript = document.createElement('script');
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            })
            .catch(function () {
                tabContent.innerHTML = '<div class="card"><div class="card-body" style="color:#ef4444;">x</div></div>';
            });
    }

        tabsRoot.addEventListener('click', function (event) {
            const button = event.target.closest('.settings-tab-btn');
            if (!button) {
                return;
            }

            loadTab(button.dataset.tab || 'general');
        });

        // Знаходимо рядок ?tab=... у посиланні
        const urlParams = new URLSearchParams(window.location.search);
        const tabToLoad = urlParams.get('tab') || 'general';

        // Замість loadTab('general') викликаємо:
        loadTab(tabToLoad);
        })();
