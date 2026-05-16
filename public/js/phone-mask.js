(() => {
    const mask = (window.APP_PHONE_MASK || '+38 (###) ###-##-##').trim();
    const slots = [...mask].filter((ch) => ch === '#').length;

    const format = (digits) => {
        let i = 0;
        let out = '';
        for (const ch of mask) {
            if (ch === '#') {
                if (i < digits.length) out += digits[i++];
                else break;
            } else {
                out += ch;
            }
        }
        return out;
    };

    const regex = new RegExp('^' + mask.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/#/g, '\\d') + '$');

    window.PhoneMask = {
        mask,
        isComplete: (value) => regex.test((value || '').trim()),
    };

    document.querySelectorAll('input[data-phone-mask]').forEach((input) => {
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'tel');
        input.placeholder = mask;
        input.maxLength = mask.length;

        const apply = () => {
            const digits = (input.value.match(/\d/g) || []).join('').slice(0, slots);
            input.value = format(digits);
        };

        input.addEventListener('input', apply);
        input.addEventListener('paste', () => setTimeout(apply, 0));
        apply();
    });
})();
