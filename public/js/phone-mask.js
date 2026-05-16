(() => {
    const mask = (window.APP_PHONE_MASK || '+38 (###) ###-##-##').trim();
    const slots = [...mask].filter((ch) => ch === '#').length;

    const escapeRegex = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    const format = (digits) => {
        let i = 0;
        let out = '';

        for (const ch of mask) {
            if (ch === '#') {
                if (i >= digits.length) break;
                out += digits[i++];
            } else if (i > 0 || digits.length > 0) {
                out += ch;
            }
        }

        return out;
    };

    const regex = new RegExp('^' + escapeRegex(mask).replace(/#/g, '\\d') + '$');

    window.PhoneMask = {
        mask,
        isComplete: (value) => regex.test((value || '').trim()),
    };

    const digitsFrom = (value) => (value.match(/\d/g) || []).join('').slice(0, slots);

    const countDigitsBeforeCaret = (value, caret) => {
        let count = 0;
        for (let i = 0; i < Math.min(caret, value.length); i += 1) {
            if (/\d/.test(value[i])) count += 1;
        }
        return count;
    };

    const caretIndexForDigits = (value, digitsCount) => {
        if (digitsCount <= 0) return 0;

        let seen = 0;
        for (let i = 0; i < value.length; i += 1) {
            if (/\d/.test(value[i])) {
                seen += 1;
                if (seen >= digitsCount) return i + 1;
            }
        }

        return value.length;
    };

    const applyWithCaret = (input, preferredDigitPosition) => {
        const digits = digitsFrom(input.value);
        const nextValue = format(digits);
        input.value = nextValue;

        const safeDigitsPos = Math.max(0, Math.min(preferredDigitPosition, digits.length));
        const caret = caretIndexForDigits(nextValue, safeDigitsPos);
        input.setSelectionRange(caret, caret);
    };

    const bindPhoneMask = (input) => {
        if (!input || input.dataset.phoneMaskBound === '1') return;
        input.dataset.phoneMaskBound = '1';

        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'tel');
        input.placeholder = mask;
        input.maxLength = mask.length;

        input.addEventListener('input', () => {
            const rawValue = input.value;
            const caret = input.selectionStart ?? rawValue.length;
            const digitPos = countDigitsBeforeCaret(rawValue, caret);
            applyWithCaret(input, digitPos);
        });

        input.addEventListener('paste', () => {
            requestAnimationFrame(() => {
                const rawValue = input.value;
                const caret = input.selectionStart ?? rawValue.length;
                const digitPos = countDigitsBeforeCaret(rawValue, caret);
                applyWithCaret(input, digitPos);
            });
        });

        applyWithCaret(input, digitsFrom(input.value).length);
    };

    const bindAll = (root = document) => {
        root.querySelectorAll('input[data-phone-mask]').forEach(bindPhoneMask);
    };

    bindAll();

    document.addEventListener('focusin', (event) => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.matches('input[data-phone-mask]')) {
            bindPhoneMask(target);
        }
    });

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) return;
                if (node.matches('input[data-phone-mask]')) {
                    bindPhoneMask(node);
                }
                bindAll(node);
            });
        });
    });

    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
    });
})();
