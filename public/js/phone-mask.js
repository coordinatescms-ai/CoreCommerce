(() => {
    const mask = (window.APP_PHONE_MASK || '+38 (###) ###-##-##').trim();
    const slotCount = [...mask].filter((ch) => ch === '#').length;
    const DIGIT_RE = /\d/;

    const escapeRegex = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const digitsFrom = (value) => (String(value || '').match(/\d/g) || []).join('').slice(0, slotCount);

    const format = (digits) => {
        const normalized = digitsFrom(digits);
        if (!normalized) return '';

        let out = '';
        let i = 0;

        for (const ch of mask) {
            if (ch === '#') {
                if (i >= normalized.length) break;
                out += normalized[i++];
                continue;
            }

            if (i > 0) out += ch;
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

    const caretForDigitIndex = (formatted, digitIndex) => {
        if (digitIndex <= 0) return 0;

        let seen = 0;
        for (let i = 0; i < formatted.length; i += 1) {
            if (!DIGIT_RE.test(formatted[i])) continue;
            seen += 1;
            if (seen === digitIndex) return i + 1;
        }

        return formatted.length;
    };

    const clampDigitIndex = (index, digitsLength) => Math.max(0, Math.min(index, digitsLength));

    const transformValue = ({
        digits,
        startDigit,
        endDigit,
        type,
        insertedDigits,
    }) => {
        const safeStart = clampDigitIndex(startDigit, digits.length);
        const safeEnd = clampDigitIndex(endDigit, digits.length);
        const selectionStart = Math.min(safeStart, safeEnd);
        const selectionEnd = Math.max(safeStart, safeEnd);

        const left = digits.slice(0, selectionStart);
        const selected = digits.slice(selectionStart, selectionEnd);
        const right = digits.slice(selectionEnd);

        if (type === 'insert' || type === 'paste') {
            const payload = digitsFrom(insertedDigits || '');
            const nextDigits = (left + payload + right).slice(0, slotCount);
            const nextCaretDigit = clampDigitIndex(left.length + payload.length, nextDigits.length);
            return { digits: nextDigits, caretDigit: nextCaretDigit };
        }

        if (selected.length > 0) {
            const nextDigits = left + right;
            return { digits: nextDigits, caretDigit: left.length };
        }

        if (type === 'deleteBackward') {
            if (selectionStart <= 0) return { digits, caretDigit: 0 };
            const nextDigits = digits.slice(0, selectionStart - 1) + digits.slice(selectionStart);
            return { digits: nextDigits, caretDigit: selectionStart - 1 };
        }

        if (type === 'deleteForward') {
            if (selectionStart >= digits.length) return { digits, caretDigit: digits.length };
            const nextDigits = digits.slice(0, selectionStart) + digits.slice(selectionStart + 1);
            return { digits: nextDigits, caretDigit: selectionStart };
        }

        return { digits, caretDigit: selectionStart };
    };

    const applyState = (input, nextDigits, nextCaretDigit) => {
        const normalizedDigits = digitsFrom(nextDigits);
        const formatted = format(normalizedDigits);
        const caret = caretForDigitIndex(formatted, clampDigitIndex(nextCaretDigit, normalizedDigits.length));

        const prevValue = input.value;
        const prevStart = input.selectionStart ?? 0;
        const prevEnd = input.selectionEnd ?? 0;

        const valueChanged = prevValue !== formatted;
        const caretChanged = prevStart !== caret || prevEnd !== caret;

        if (!valueChanged && !caretChanged) return;

        if (valueChanged) input.value = formatted;
        if (caretChanged) input.setSelectionRange(caret, caret);
    };

    const captureSelection = (input) => {
        const value = input.value;
        const startRaw = input.selectionStart ?? 0;
        const endRaw = input.selectionEnd ?? startRaw;

        return {
            startRaw,
            endRaw,
            startDigit: countDigitsBeforeCaret(value, startRaw),
            endDigit: countDigitsBeforeCaret(value, endRaw),
        };
    };

    const bindPhoneMask = (input) => {
        if (!(input instanceof HTMLInputElement) || input.dataset.phoneMaskBound === '1') return;
        input.dataset.phoneMaskBound = '1';

        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('autocomplete', 'tel');
        input.placeholder = mask;
        input.maxLength = mask.length;

        let lastSelection = captureSelection(input);

        const rememberSelection = () => {
            lastSelection = captureSelection(input);
        };

        const normalizeInputFallback = () => {
            const raw = input.value;
            const fallbackDigit = countDigitsBeforeCaret(raw, input.selectionStart ?? raw.length);
            applyState(input, digitsFrom(raw), fallbackDigit);
            rememberSelection();
        };

        ['focus', 'click', 'select', 'keyup'].forEach((eventName) => {
            input.addEventListener(eventName, rememberSelection);
        });

        input.addEventListener('beforeinput', (event) => {
            const type = event.inputType || '';
            const currentDigits = digitsFrom(input.value);
            const snapshot = lastSelection = captureSelection(input);

            if (type === 'insertText' || type === 'insertCompositionText') {
                if (!event.data) return;
                const onlyDigits = digitsFrom(event.data);
                if (!onlyDigits) {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();
                const next = transformValue({
                    digits: currentDigits,
                    startDigit: snapshot.startDigit,
                    endDigit: snapshot.endDigit,
                    type: 'insert',
                    insertedDigits: onlyDigits,
                });
                applyState(input, next.digits, next.caretDigit);
                rememberSelection();
                return;
            }

            if (type === 'deleteContentBackward' || type === 'deleteContentForward') {
                event.preventDefault();
                const next = transformValue({
                    digits: currentDigits,
                    startDigit: snapshot.startDigit,
                    endDigit: snapshot.endDigit,
                    type: type === 'deleteContentBackward' ? 'deleteBackward' : 'deleteForward',
                });
                applyState(input, next.digits, next.caretDigit);
                rememberSelection();
            }
        });

        input.addEventListener('paste', (event) => {
            const text = (event.clipboardData || window.clipboardData)?.getData('text') || '';
            const inserted = digitsFrom(text);
            event.preventDefault();

            const currentDigits = digitsFrom(input.value);
            const snapshot = captureSelection(input);
            const next = transformValue({
                digits: currentDigits,
                startDigit: snapshot.startDigit,
                endDigit: snapshot.endDigit,
                type: 'paste',
                insertedDigits: inserted,
            });

            applyState(input, next.digits, next.caretDigit);
            rememberSelection();
        });

        input.addEventListener('input', normalizeInputFallback);

        normalizeInputFallback();
    };

    const init = (root = document) => {
        if (root instanceof HTMLInputElement && root.matches('input[data-phone-mask]')) {
            bindPhoneMask(root);
            return;
        }

        if (!(root instanceof Element || root instanceof Document)) return;
        root.querySelectorAll('input[data-phone-mask]').forEach(bindPhoneMask);
    };

    window.PhoneMask.init = init;

    init();

    document.addEventListener('focusin', (event) => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.matches('input[data-phone-mask]')) bindPhoneMask(target);
    });
})();
