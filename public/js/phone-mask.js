(() => {
    const mask = (window.APP_PHONE_MASK || '+38 (###) ###-##-##').trim();
    const slots = [...mask].filter((ch) => ch === '#').length;

    const escapeRegex = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const DIGIT_RE = /\d/;

    const digitsFrom = (value) => (String(value || '').match(/\d/g) || []).join('').slice(0, slots);

    const format = (digits) => {
        let out = '';
        let i = 0;

        for (const ch of mask) {
            if (ch === '#') {
                if (i >= digits.length) break;
                out += digits[i++];
                continue;
            }

            if (digits.length > 0 && i > 0) {
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

    const countDigitsBeforeCaret = (value, caret) => {
        const limit = Math.max(0, Math.min(caret ?? 0, value.length));
        let count = 0;
        for (let i = 0; i < limit; i += 1) {
            if (DIGIT_RE.test(value[i])) count += 1;
        }
        return count;
    };

    const caretForDigitsIndex = (formatted, digitsIndex) => {
        if (digitsIndex <= 0) return 0;

        let seen = 0;
        for (let i = 0; i < formatted.length; i += 1) {
            if (DIGIT_RE.test(formatted[i])) {
                seen += 1;
                if (seen === digitsIndex) return i + 1;
            }
        }

        return formatted.length;
    };

    const applyFormattedValue = (input, preferredDigitsIndex) => {
        const raw = input.value;
        const digits = digitsFrom(raw);
        const formatted = format(digits);

        const safeIndex = Math.max(0, Math.min(preferredDigitsIndex, digits.length));
        const nextCaret = caretForDigitsIndex(formatted, safeIndex);

        if (raw !== formatted) {
            input.value = formatted;
        }

        const currentStart = input.selectionStart ?? 0;
        const currentEnd = input.selectionEnd ?? 0;
        if (currentStart !== nextCaret || currentEnd !== nextCaret) {
            input.setSelectionRange(nextCaret, nextCaret);
        }
    };

    const bindPhoneMask = (input) => {
        if (!(input instanceof HTMLInputElement) || input.dataset.phoneMaskBound === '1') return;
        input.dataset.phoneMaskBound = '1';

        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'tel');
        input.placeholder = mask;
        input.maxLength = mask.length;

        const rememberSelection = () => {
            input._phoneMaskSelection = {
                start: input.selectionStart ?? 0,
                end: input.selectionEnd ?? (input.selectionStart ?? 0),
            };
        };

        const normalizeOnInput = () => {
            const raw = input.value;
            const fallbackCaret = input.selectionStart ?? raw.length;
            const digitsIndex = countDigitsBeforeCaret(raw, fallbackCaret);
            applyFormattedValue(input, digitsIndex);
        };

        input.addEventListener('keydown', rememberSelection);
        input.addEventListener('click', rememberSelection);
        input.addEventListener('select', rememberSelection);

        input.addEventListener('beforeinput', (event) => {
            const type = event.inputType || '';
            if (!type.startsWith('delete')) {
                rememberSelection();
                return;
            }

            const value = input.value;
            const selection = input._phoneMaskSelection || {
                start: input.selectionStart ?? 0,
                end: input.selectionEnd ?? 0,
            };
            let start = selection.start;
            let end = selection.end;

            if (start === end) {
                if (type === 'deleteContentBackward' && start > 0) {
                    start -= 1;
                }
                if (type === 'deleteContentForward' && end < value.length) {
                    end += 1;
                }
            }

            const leftDigits = digitsFrom(value.slice(0, start));
            const removedDigits = digitsFrom(value.slice(start, end));
            if (removedDigits.length === 0) return;

            event.preventDefault();
            const rightDigits = digitsFrom(value.slice(end));
            const merged = (leftDigits + rightDigits).slice(0, slots);
            input.value = format(merged);
            const caret = caretForDigitsIndex(input.value, leftDigits.length);
            input.setSelectionRange(caret, caret);
        });

        input.addEventListener('input', normalizeOnInput);

        input.addEventListener('paste', (event) => {
            event.preventDefault();
            const text = (event.clipboardData || window.clipboardData)?.getData('text') || '';
            const insertedDigits = digitsFrom(text);

            const value = input.value;
            const start = input.selectionStart ?? 0;
            const end = input.selectionEnd ?? start;

            const leftDigits = digitsFrom(value.slice(0, start));
            const rightDigits = digitsFrom(value.slice(end));
            const merged = (leftDigits + insertedDigits + rightDigits).slice(0, slots);

            input.value = format(merged);
            const caret = caretForDigitsIndex(input.value, leftDigits.length + insertedDigits.length);
            input.setSelectionRange(caret, caret);
        });

        normalizeOnInput();
    };

    const bindAll = (root = document) => {
        if (!(root instanceof Element || root instanceof Document)) return;
        root.querySelectorAll('input[data-phone-mask]').forEach(bindPhoneMask);
        if (root instanceof HTMLInputElement && root.matches('input[data-phone-mask]')) {
            bindPhoneMask(root);
        }
    };

    window.PhoneMask.init = bindAll;

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
                if (node instanceof HTMLElement) bindAll(node);
            });
        });
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });
})();
