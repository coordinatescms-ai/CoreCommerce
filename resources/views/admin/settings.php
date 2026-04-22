<div class="page-header" style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
    <h1 class="page-title">Налаштування магазину</h1>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body" style="padding: 0.75rem 1rem;">
        <div id="settings-tabs" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <button type="button" class="btn btn-outline settings-tab-btn active" data-tab="general">Загальні</button>
            <button type="button" class="btn btn-outline settings-tab-btn" data-tab="media">Мультимедіа</button>
            <button type="button" class="btn btn-outline settings-tab-btn" data-tab="shipping">Доставка</button>
            <button type="button" class="btn btn-outline settings-tab-btn" data-tab="payment">Оплата</button>
        </div>
    </div>
</div>

<div id="settings-tab-content"></div>

<script>
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
        tabContent.innerHTML = '<div class="card"><div class="card-body">Завантаження...</div></div>';

        fetch('/admin/settings/tab/' + encodeURIComponent(tab), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Не вдалося завантажити вкладку.');
                }
                return response.text();
            })
            .then(function (html) {
                tabContent.innerHTML = html;
            })
            .catch(function () {
                tabContent.innerHTML = '<div class="card"><div class="card-body" style="color:#ef4444;">Помилка завантаження вкладки.</div></div>';
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
</script>
