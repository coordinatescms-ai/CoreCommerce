/**
 * Універсальна маска вводу телефону.
 *
 * Застосовується до будь-якого <input data-phone-mask="+38 (###) ###-##-##">
 * на сторінці. Символ "#" у масці — місце під цифру; користувач друкує
 * тільки цифри, решта символів (дужки, дефіси, код країни) підставляється
 * автоматично.
 *
 * ВАЖЛИВО: формат маски має бути ідентичним тому, що сервер звіряє через
 * is_phone_matching_mask() (app/helpers.php) — обидва боки читають те саме
 * значення settings.phone_mask, тому формати завжди узгоджені.
 *
 * Підключення: <script src="/js/phone_mask.js"></script> — ініціалізується
 * автоматично для всіх відповідних полів на сторінці при завантаженні DOM.
 */
(() => {
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

    const attachPhoneMask = (input) => {
        const rawMask = input.dataset.phoneMask;
        if (!rawMask) {
            return;
        }

        const maxDigits = (rawMask.match(/#/g) || []).length;

        // КРИТИЧНО: маска сама містить "фіксовані" цифри (наприклад код країни
        // "38" в "+38 (###)..."). Якщо на кожному input-евенті брати ВСІ цифри
        // з поточного (вже відформатованого) значення поля через
        // replace(/\D/g,''), ці фіксовані цифри з префіксу знову потраплять
        // у видобуті "цифри користувача" — і кожна наступна цифра буде
        // зсуватись/затиратись схожими цифрами з маски. Тому цифри, введені
        // користувачем, зберігаємо ОКРЕМО в змінній (userDigits), а
        // відформатоване значення поля — лише похідне відображення.
        let userDigits = '';

        if (input.value) {
            userDigits = input.value.replace(/\D/g, '').slice(0, maxDigits);
        }

        const render = () => {
            input.value = formatByMask(userDigits, rawMask);
        };

        input.addEventListener('input', (event) => {
            const inputType = event.inputType || '';

            if (inputType === 'deleteContentBackward' || inputType === 'deleteContentForward') {
                userDigits = userDigits.slice(0, -1);
            } else {
                const previousFormatted = formatByMask(userDigits, rawMask);
                const currentValue = input.value;

                if (currentValue.length > previousFormatted.length) {
                    const added = currentValue.slice(previousFormatted.length).replace(/\D/g, '');
                    userDigits = (userDigits + added).slice(0, maxDigits);
                } else {
                    // Фолбек для рідкісних випадків (вставка з буфера обміну,
                    // редагування в середині рядка).
                    userDigits = currentValue.replace(/\D/g, '').slice(0, maxDigits);
                }
            }

            render();
        });

        input.addEventListener('focus', () => {
            if (input.value === '' && userDigits === '') {
                render();
            }
        });

        if (userDigits) {
            render();
        }
    };

    const init = () => {
        document.querySelectorAll('input[data-phone-mask]').forEach(attachPhoneMask);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
