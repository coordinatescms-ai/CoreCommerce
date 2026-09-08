(() => {
    const form = document.getElementById('checkout-form');
    if (!form) {
        return;
    }

    const t = window.CHECKOUT_TRANSLATIONS || {};

    const statusBar = document.getElementById('checkout-status');
    const submitButton = document.getElementById('checkout-submit');
    const deliveryRadios = form.querySelectorAll('input[name="delivery_id"]');
    const npFields = document.getElementById('delivery-np-fields');
    const courierFields = document.getElementById('delivery-courier-fields');
    const pickupFields = document.getElementById('delivery-pickup-fields');
    const pickupAddressText = document.getElementById('pickup-address-text');
    const cityInput = document.getElementById('delivery_city');
    const cityRefInput = document.getElementById('delivery_city_ref');
    const cityList = document.getElementById('np-city-list');
    const warehouseSelect = document.getElementById('delivery_warehouse');

    const cityStore = [];

    // ── Маска телефону (з БД: settings.phone_mask, напр. "+38 (###) ###-##-##") ──
    // Символ "#" у масці — місце під цифру. Користувач друкує тільки цифри,
    // усі інші символи (дужки, дефіси, код країни) підставляються самі,
    // синхронно з тим що на сервері OrderController звіряє через ту саму маску
    // (is_phone_matching_mask()) — тому формати мають лишатись ідентичними.
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        const rawMask = phoneInput.dataset.phoneMask || '+38 (###) ###-##-##';
        const maxDigits = (rawMask.match(/#/g) || []).length;

        const formatByMask = (digits, mask) => {
            let result = '';
            let di = 0;
            for (let i = 0; i < mask.length && di < digits.length; i++) {
                if (mask[i] === '#') {
                    result += digits[di];
                    di++;
                } else {
                    result += mask[i];
                }
            }
            return result;
        };

        // КРИТИЧНО: маска сама містить "фіксовані" цифри (наприклад код країни
        // "38" в "+38 (###)..."). Якщо на кожному input-евенті брати ВСІ цифри
        // з поточного (вже відформатованого) значення поля через
        // replace(/\D/g,''), ці фіксовані цифри з префіксу знову потраплять
        // у видобуті "цифри користувача" — і кожна наступна цифра буде
        // зсуватись/затиратись схожими цифрами з маски. Тому цифри, введені
        // користувачем, зберігаємо ОКРЕМО в змінній (userDigits), а
        // відформатоване значення поля — лише похідне відображення.
        let userDigits = '';

        if (phoneInput.value) {
            userDigits = phoneInput.value.replace(/\D/g, '').slice(0, maxDigits);
        }

        const render = () => {
            phoneInput.value = formatByMask(userDigits, rawMask);
        };

        phoneInput.addEventListener('input', (event) => {
            const inputType = event.inputType || '';

            if (inputType === 'deleteContentBackward' || inputType === 'deleteContentForward') {
                userDigits = userDigits.slice(0, -1);
            } else {
                const previousFormatted = formatByMask(userDigits, rawMask);
                const currentValue = phoneInput.value;

                if (currentValue.length > previousFormatted.length) {
                    const added = currentValue.slice(previousFormatted.length).replace(/\D/g, '');
                    userDigits = (userDigits + added).slice(0, maxDigits);
                } else {
                    userDigits = currentValue.replace(/\D/g, '').slice(0, maxDigits);
                }
            }

            render();
        });

        phoneInput.addEventListener('focus', () => {
            if (phoneInput.value === '' && userDigits === '') {
                render();
            }
        });

        if (userDigits) {
            render();
        }
    }

    const showStatus = (type, message) => {
        statusBar.hidden = false;
        statusBar.classList.remove('success', 'error');
        statusBar.classList.add(type);
        statusBar.textContent = message;
    };

    const setFieldError = (name, message) => {
        const field = form.querySelector(`[name="${name}"]`);
        const error = form.querySelector(`[data-error-for="${name}"]`);

        if (field) {
            field.classList.toggle('invalid', Boolean(message));
        }

        if (error) {
            error.textContent = message || '';
        }
    };

    const clearErrors = () => {
        form.querySelectorAll('.field-error').forEach((el) => {
            el.textContent = '';
        });
        form.querySelectorAll('.invalid').forEach((el) => {
            el.classList.remove('invalid');
        });
    };

    const validateField = (field) => {
        const value = (field.value || '').trim();

        if (field.name === 'full_name') {
            setFieldError(field.name, value.length >= 5 ? '' : t.specify_pib || 'Вкажіть ПІБ (мінімум 5 символів).');
            return;
        }

        if (field.name === 'phone') {
            // Звіряємо СТРОГО за тією ж маскою, що на сервері (OrderController::is_phone_matching_mask),
            // інакше форматований рядок "+38 (067) 123-45-67" ніколи не пройде перевірку.
            const mask = field.dataset.phoneMask || '+38 (###) ###-##-##';
            const PLACEHOLDER = '\u0000';
            const withPlaceholder = mask.split('#').join(PLACEHOLDER);
            const escaped = withPlaceholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const maskRegexSource = escaped.split(PLACEHOLDER).join('\\d');
            const ok = new RegExp('^' + maskRegexSource + '$').test(value);
            setFieldError(field.name, ok ? '' : t.specify_phone || 'Вкажіть коректний номер телефону.');
            return;
        }

        if (field.name === 'email') {
            const ok = /^\S+@\S+\.\S+$/.test(value);
            setFieldError(field.name, ok ? '' : t.specify_email || 'Вкажіть коректний Email.');
            return;
        }

        if (field.name === 'delivery_id') {
            setFieldError(field.name, value ? '' : t.specify_delivery || 'Оберіть спосіб доставки.');
            return;
        }

        if (field.name === 'payment_id') {
            setFieldError(field.name, value ? '' : t.specify_payment || 'Оберіть спосіб оплати.');
            return;
        }

        if (field.name === 'delivery_city') {
            const code = form.querySelector('input[name="delivery_id"]:checked')?.dataset.code;
            const isNp = code === 'nova_poshta';
            setFieldError(field.name, !isNp || value ? '' : t.specify_city || 'Оберіть місто Нової Пошти.');
            return;
        }

        if (field.name === 'delivery_warehouse') {
            const code = form.querySelector('input[name="delivery_id"]:checked')?.dataset.code;
            const isNp = code === 'nova_poshta';
            setFieldError(field.name, !isNp || value ? '' : t.specify_warehouse || 'Оберіть відділення Нової Пошти.');
            return;
        }

        if (field.name === 'delivery_address') {
            const code = form.querySelector('input[name="delivery_id"]:checked')?.dataset.code;
            const isCourier = code === 'courier';
            setFieldError(field.name, !isCourier || value ? '' : t.specify_address || 'Вкажіть адресу для курʼєра.');
        }
    };

    const toggleDeliveryFields = () => {
        const checked = form.querySelector('input[name="delivery_id"]:checked');
        const code = checked?.dataset.code;
        const isNp = code === 'nova_poshta';
        const isPickup = code === 'self_pickup';
        const isCourier = code === 'courier';

        npFields.hidden = !isNp;
        courierFields.hidden = !isCourier;
        pickupFields.hidden = !isPickup;

        if (isPickup && pickupAddressText) {
            pickupAddressText.textContent = checked?.dataset.pickupAddress || '';
        }

        validateField(cityInput);
        validateField(warehouseSelect);
        validateField(form.querySelector('[name="delivery_address"]'));
    };

    const fetchCities = async (query) => {
        const response = await fetch('/np_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'cities', query })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || t.loading_cities || 'Не вдалося завантажити міста');
        }

        return data.data || [];
    };

    const fetchWarehouses = async (cityRef) => {
        const response = await fetch('/np_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'warehouses', cityRef })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || t.loading_warehouses || 'Не вдалося завантажити відділення');
        }

        return data.data || [];
    };

    // Завантаження відділень для вибраного міста
    const loadWarehouses = async () => {
        const selected = cityStore.find((city) => city.Description === cityInput.value.trim());
        if (!selected) {
            return; // місто ще не вибране зі списку — чекаємо
        }

        cityRefInput.value = selected.Ref;
        warehouseSelect.innerHTML = '<option value="">' + (t.loading || 'Завантаження...') + '</option>';

        try {
            const warehouses = await fetchWarehouses(selected.Ref);
            warehouseSelect.innerHTML = '<option value="">' + (t.select_warehouse || 'Оберіть відділення') + '</option>';
            warehouses.forEach((wh) => {
                const option = document.createElement('option');
                option.value = wh.Description;
                option.textContent = wh.Description;
                warehouseSelect.appendChild(option);
            });
        } catch (error) {
            warehouseSelect.innerHTML = '<option value="">' + (t.load_failed || 'Не вдалося завантажити') + '</option>';
            showStatus('error', error.message);
        }
    };

    let cityDebounce;
    cityInput.addEventListener('input', () => {
        cityRefInput.value = '';
        warehouseSelect.innerHTML = '<option value="">' + (t.select_city_first || 'Оберіть місто спочатку') + '</option>';

        const value = cityInput.value.trim();
        if (value.length < 3) {
            cityList.innerHTML = '';
            return;
        }

        clearTimeout(cityDebounce);
        cityDebounce = setTimeout(async () => {
            try {
                const cities = await fetchCities(value);
                cityStore.length = 0;
                cityStore.push(...cities);

                cityList.innerHTML = cities
                    .map((city) => `<option value="${city.Description}"></option>`)
                    .join('');

                // Chrome при виборі з datalist генерує 'input', не 'change'
                // Тому одразу після заповнення списку перевіряємо чи значення вже збігається
                await loadWarehouses();
            } catch (error) {
                showStatus('error', error.message);
            }
        }, 350);
    });

    // 'change' спрацьовує при blur після вибору — страховка для Firefox та Edge
    cityInput.addEventListener('change', loadWarehouses);

    deliveryRadios.forEach((radio) => {
        radio.addEventListener('change', toggleDeliveryFields);
    });

    form.querySelectorAll('input,select,textarea').forEach((field) => {
        field.addEventListener('input', () => validateField(field));
        field.addEventListener('blur', () => validateField(field));
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();

        form.querySelectorAll('input,select,textarea').forEach((field) => validateField(field));
        if (form.querySelector('.invalid')) {
            showStatus('error', t.fix_errors || 'Виправте помилки у формі.');
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = t.sending || 'Відправлення...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                if (result.errors) {
                    Object.entries(result.errors).forEach(([name, message]) => {
                        setFieldError(name, message);
                    });
                }

                showStatus('error', result.message || t.order_error || 'Помилка оформлення замовлення.');
                return;
            }

            // Обробляємо відповідь платіжного шлюзу
            const action = result.payment_action || 'thank_you';

            if (action === 'redirect' && result.payment_url) {
                // Перенаправляємо на зовнішню платіжну сторінку
                window.location.href = result.payment_url;
                return;
            }

            if (action === 'render' && result.payment_html) {
                // Плагін повернув HTML з <form> — вставляємо ПОРУЧ з checkout-формою,
                // бо вкладені <form> теги браузер видаляє з DOM → liqpay-form.submit() = null
                const wrapper = document.createElement('div');
                wrapper.innerHTML = result.payment_html;

                // Замінюємо форму на wrapper (не всередину)
                form.replaceWith(wrapper);

                // Виконуємо <script> теги вручну (innerHTML не запускає їх)
                wrapper.querySelectorAll('script').forEach((old) => {
                    const s = document.createElement('script');
                    s.textContent = old.textContent;
                    old.parentNode.replaceChild(s, old);
                });
                return;
            }

            if (action === 'error') {
                // Платіжний шлюз не зміг ініціювати оплату (напр. не налаштовані
                // ключі API). Замовлення в БД вже створено (статус "pending"),
                // але гроші НЕ рухались — клієнту показуємо це як помилку,
                // а не як успіх, інакше він піде з сайту думаючи що оплатив.
                // (submitButton повертається в normal стан у finally нижче.)
                const errMsg = result.payment_message || t.payment_error
                    || 'Не вдалося ініціювати оплату. Зверніться до підтримки, вказавши номер замовлення.';
                showStatus('error', `${errMsg} Номер замовлення: #${result.order_id}`);
                return;
            }

            // thank_you або fallback — відкриваємо окрему сторінку успішного замовлення.
            // replace() не залишає checkout-форму в історії переходів браузера.
            if (result.order_id) {
                window.location.replace('/order-success/' + encodeURIComponent(String(result.order_id)));
                return;
            }

            // Запасний варіант для нестандартної відповіді без номера замовлення.
            const msg = result.payment_message || result.message || t.order_success || 'Замовлення успішно оформлено.';
            showStatus('success', msg);
        } catch (error) {
            showStatus('error', error.message || t.connection_error || 'Помилка зʼєднання із сервером.');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = t.submit_button || 'Підтвердити замовлення';
        }
    });

    toggleDeliveryFields();
})();
