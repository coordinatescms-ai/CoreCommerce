(() => {
    const form = document.getElementById('checkout-form');
    if (!form) {
        return;
    }

    const statusBar = document.getElementById('checkout-status');
    const submitButton = document.getElementById('checkout-submit');
    const deliveryRadios = form.querySelectorAll('input[name="delivery_method"]');
    const npFields = document.getElementById('delivery-np-fields');
    const courierFields = document.getElementById('delivery-courier-fields');
    const cityInput = document.getElementById('delivery_city');
    const cityRefInput = document.getElementById('delivery_city_ref');
    const cityList = document.getElementById('np-city-list');
    const warehouseSelect = document.getElementById('delivery_warehouse');

    const cityStore = [];

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
            setFieldError(field.name, value.length >= 5 ? '' : 'Вкажіть ПІБ (мінімум 5 символів).');
            return;
        }

        if (field.name === 'phone') {
            const ok = /^[\d\+\(\)\-\s]{10,20}$/.test(value);
            setFieldError(field.name, ok ? '' : 'Вкажіть коректний номер телефону.');
            return;
        }

        if (field.name === 'email') {
            const ok = /^\S+@\S+\.\S+$/.test(value);
            setFieldError(field.name, ok ? '' : 'Вкажіть коректний Email.');
            return;
        }

        if (field.name === 'delivery_city') {
            const isNp = form.querySelector('input[name="delivery_method"]:checked')?.value === 'nova_poshta';
            setFieldError(field.name, !isNp || value ? '' : 'Оберіть місто Нової Пошти.');
            return;
        }

        if (field.name === 'delivery_warehouse') {
            const isNp = form.querySelector('input[name="delivery_method"]:checked')?.value === 'nova_poshta';
            setFieldError(field.name, !isNp || value ? '' : 'Оберіть відділення Нової Пошти.');
            return;
        }

        if (field.name === 'delivery_address') {
            const isCourier = form.querySelector('input[name="delivery_method"]:checked')?.value === 'courier';
            setFieldError(field.name, !isCourier || value ? '' : 'Вкажіть адресу для курʼєра.');
        }
    };

    const toggleDeliveryFields = () => {
        const method = form.querySelector('input[name="delivery_method"]:checked')?.value;
        const isNp = method === 'nova_poshta';

        npFields.hidden = !isNp;
        courierFields.hidden = isNp;

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
            throw new Error(data.message || 'Не вдалося завантажити міста');
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
            throw new Error(data.message || 'Не вдалося завантажити відділення');
        }

        return data.data || [];
    };

    let cityDebounce;
    cityInput.addEventListener('input', () => {
        cityRefInput.value = '';
        warehouseSelect.innerHTML = '<option value="">Оберіть місто спочатку</option>';

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
                    .map((city) => `<option value="${city.Present}"></option>`)
                    .join('');
            } catch (error) {
                showStatus('error', error.message);
            }
        }, 350);
    });

    cityInput.addEventListener('change', async () => {
        const selected = cityStore.find((city) => city.Present === cityInput.value.trim());
        if (!selected) {
            cityRefInput.value = '';
            return;
        }

        cityRefInput.value = selected.Ref;
        warehouseSelect.innerHTML = '<option value="">Завантаження...</option>';

        try {
            const warehouses = await fetchWarehouses(selected.Ref);
            warehouseSelect.innerHTML = '<option value="">Оберіть відділення</option>';

            warehouses.forEach((wh) => {
                const option = document.createElement('option');
                option.value = wh.Description;
                option.textContent = wh.Description;
                warehouseSelect.appendChild(option);
            });
        } catch (error) {
            warehouseSelect.innerHTML = '<option value="">Не вдалося завантажити</option>';
            showStatus('error', error.message);
        }
    });

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
            showStatus('error', 'Виправте помилки у формі.');
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Відправлення...';

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

                showStatus('error', result.message || 'Помилка оформлення замовлення.');
                return;
            }

            showStatus('success', `${result.message} Номер замовлення: #${result.order_id}`);
            form.reset();
            toggleDeliveryFields();
        } catch (error) {
            showStatus('error', error.message || 'Помилка зʼєднання із сервером.');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Підтвердити замовлення';
        }
    });

    toggleDeliveryFields();
})();
