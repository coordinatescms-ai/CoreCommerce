(() => {
    const mask = String(window.APP_PHONE_MASK || '+38 (###) ###-##-##').trim();
    const slots = [];
    const DIGIT_RE = /\d/;

    for (let i = 0; i < mask.length; i += 1) {
        if (mask[i] === '#') slots.push(i);
    }

    const slotCount = slots.length;

    const escapeRegex = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const extractDigits = (value) => (String(value || '').match(/\d/g) || []).join('').slice(0, slotCount);

    const formatDigits = (digits) => {
        const normalized = extractDigits(digits);
        if (!normalized) return '';

        const chars = mask.split('');
        for (let i = 0; i < slotCount; i += 1) {
            chars[slots[i]] = i < normalized.length ? normalized[i] : '';
        }

        let formatted = chars.join('');
        const lastDigitIndex = normalized.length - 1;
        if (lastDigitIndex >= 0) {
            formatted = formatted.slice(0, slots[lastDigitIndex] + 1);
        }

        return formatted;
    };

    const regex = new RegExp('^' + escapeRegex(mask).replace(/#/g, '\\d') + '$');

    window.PhoneMask = window.PhoneMask || {};
    window.PhoneMask.mask = mask;
    window.PhoneMask.isComplete = (value) => regex.test((value || '').trim());

    const digitIndexFromCaret = (value, caret) => {
        const stop = Math.max(0, Math.min(caret ?? 0, value.length));
        let count = 0;

        for (let i = 0; i < stop; i += 1) {
            if (DIGIT_RE.test(value[i])) count += 1;
        }

        return count;
    };

    const caretFromDigitIndex = (formatted, digitIndex) => {
        if (digitIndex <= 0) return 0;

        let seen = 0;
        for (let i = 0; i < formatted.length; i += 1) {
            if (!DIGIT_RE.test(formatted[i])) continue;
            seen += 1;
            if (seen === digitIndex) return i + 1;
        }

        return formatted.length;
    };

    const clamp = (n, min, max) => Math.max(min, Math.min(n, max));

    const transformDigits = ({ digits, startDigit, endDigit, action, payloadDigits }) => {
        const safeStart = clamp(startDigit, 0, digits.length);
        const safeEnd = clamp(endDigit, 0, digits.length);
        const from = Math.min(safeStart, safeEnd);
        const to = Math.max(safeStart, safeEnd);

        const left = digits.slice(0, from);
        const right = digits.slice(to);

        if (action === 'insert' || action === 'paste') {
            const payload = extractDigits(payloadDigits);
            const nextDigits = (left + payload + right).slice(0, slotCount);
            const nextCaretDigit = clamp(left.length + payload.length, 0, nextDigits.length);
            return { nextDigits, nextCaretDigit };
        }

        if (from !== to) {
            const nextDigits = left + right;
            return { nextDigits, nextCaretDigit: left.length };
        }

        if (action === 'deleteBackward') {
            if (from === 0) return { nextDigits: digits, nextCaretDigit: 0 };
            const nextDigits = digits.slice(0, from - 1) + digits.slice(from);
            return { nextDigits, nextCaretDigit: from - 1 };
        }

        if (action === 'deleteForward') {
            if (from >= digits.length) return { nextDigits: digits, nextCaretDigit: digits.length };
            const nextDigits = digits.slice(0, from) + digits.slice(from + 1);
            return { nextDigits, nextCaretDigit: from };
        }

        return { nextDigits: digits, nextCaretDigit: from };
    };

    const applyDigits = (input, digits, caretDigit) => {
        const normalizedDigits = extractDigits(digits);
        const formatted = formatDigits(normalizedDigits);
        const nextCaret = caretFromDigitIndex(formatted, clamp(caretDigit, 0, normalizedDigits.length));

        const prevValue = input.value;
        const prevStart = input.selectionStart ?? 0;
        const prevEnd = input.selectionEnd ?? 0;

        const valueChanged = prevValue !== formatted;
        const caretChanged = prevStart !== nextCaret || prevEnd !== nextCaret;

        if (!valueChanged && !caretChanged) return false;

        if (valueChanged) input.value = formatted;
        if (caretChanged && document.activeElement === input) {
            input.setSelectionRange(nextCaret, nextCaret);
        }

        return true;
    };

    const snapshotSelection = (input) => {
        const value = input.value;
        const start = input.selectionStart ?? 0;
        const end = input.selectionEnd ?? start;

        return {
            startDigit: digitIndexFromCaret(value, start),
            endDigit: digitIndexFromCaret(value, end),
        };
    };

    const bind = (input) => {
        if (!(input instanceof HTMLInputElement) || input.dataset.phoneMaskBound === '1') return;

        input.dataset.phoneMaskBound = '1';
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'tel');
        input.placeholder = mask;
        input.maxLength = mask.length;

        let ignoreInputEvent = false;
        let rememberedSelection = snapshotSelection(input);

        const remember = () => {
            rememberedSelection = snapshotSelection(input);
        };

        const commit = (action, payloadDigits = '') => {
            const currentDigits = extractDigits(input.value);
            const sel = rememberedSelection;
            const { nextDigits, nextCaretDigit } = transformDigits({
                digits: currentDigits,
                startDigit: sel.startDigit,
                endDigit: sel.endDigit,
                action,
                payloadDigits,
            });

            ignoreInputEvent = true;
            applyDigits(input, nextDigits, nextCaretDigit);
            ignoreInputEvent = false;
            remember();
        };

        const normalizeFallback = () => {
            if (ignoreInputEvent) return;
            const raw = input.value;
            const caretDigit = digitIndexFromCaret(raw, input.selectionStart ?? raw.length);
            ignoreInputEvent = true;
            applyDigits(input, raw, caretDigit);
            ignoreInputEvent = false;
            remember();
        };

        ['focus', 'click', 'select', 'keyup'].forEach((eventName) => {
            input.addEventListener(eventName, remember);
        });

        input.addEventListener('beforeinput', (event) => {
            rememberedSelection = snapshotSelection(input);
            const type = event.inputType || '';

            if (type === 'insertText' || type === 'insertCompositionText') {
                const digits = extractDigits(event.data || '');
                if (!digits) {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();
                commit('insert', digits);
                return;
            }

            if (type === 'insertFromPaste') {
                const digits = extractDigits(event.data || '');
                event.preventDefault();
                commit('paste', digits);
                return;
            }

            if (type === 'deleteContentBackward') {
                event.preventDefault();
                commit('deleteBackward');
                return;
            }

            if (type === 'deleteContentForward') {
                event.preventDefault();
                commit('deleteForward');
            }
        });

        input.addEventListener('keydown', (event) => {
            rememberedSelection = snapshotSelection(input);

            if (event.key === 'Backspace') {
                event.preventDefault();
                commit('deleteBackward');
                return;
            }

            if (event.key === 'Delete') {
                event.preventDefault();
                commit('deleteForward');
            }
        });

        input.addEventListener('paste', (event) => {
            rememberedSelection = snapshotSelection(input);
            const text = (event.clipboardData || window.clipboardData)?.getData('text') || '';
            event.preventDefault();
            commit('paste', text);
        });

        input.addEventListener('input', normalizeFallback);

        normalizeFallback();
    };

    const init = (root = document) => {
        if (root instanceof HTMLInputElement && root.matches('input[data-phone-mask]')) {
            bind(root);
            return;
        }

        if (!(root instanceof Document || root instanceof Element)) return;
        root.querySelectorAll('input[data-phone-mask]').forEach(bind);
    };

    window.PhoneMask.init = init;
    init();

    document.addEventListener('focusin', (event) => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.matches('input[data-phone-mask]')) {
            bind(target);
        }
    });
})();
