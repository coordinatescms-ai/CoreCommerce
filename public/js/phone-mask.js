(() => {
    const mask = String(window.APP_PHONE_MASK || document.querySelector('input[data-phone-mask]')?.dataset.phoneMask || '+38 (###) ###-##-##').trim();
    const slots = [];
    const DIGIT_RE = /\d/;

    // Identify mask fixed digits to exclude them from user input extraction
    const maskFixedDigits = [];
    for (let i = 0; i < mask.length; i++) {
        if (DIGIT_RE.test(mask[i]) && mask[i] !== '#') {
            maskFixedDigits.push({ index: i, digit: mask[i] });
        }
    }

    for (let i = 0; i < mask.length; i += 1) {
        if (mask[i] === '#') slots.push(i);
    }

    const slotCount = slots.length;

    const escapeRegex = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    
    /**
     * Extracts ONLY user-entered digits by ignoring fixed digits that are part of the mask prefix/structure.
     */
    const extractDigits = (value) => {
        let str = String(value || '');
        
        // If the value starts with the mask's fixed prefix, strip it to avoid double-counting
        // Example: if mask is "+38 (###)" and value is "+38 (099)", we want only "099"
        let prefix = '';
        for (let i = 0; i < mask.length; i++) {
            if (mask[i] === '#') break;
            prefix += mask[i];
        }
        
        if (prefix && str.startsWith(prefix)) {
            str = str.slice(prefix.length);
        }

        return (str.match(/\d/g) || []).join('').slice(0, slotCount);
    };

    const formatDigits = (digits) => {
        const normalized = digits; // already extracted
        if (!normalized && normalized !== '') return '';

        const chars = mask.split('');
        for (let i = 0; i < slotCount; i += 1) {
            chars[slots[i]] = i < normalized.length ? normalized[i] : '';
        }

        let formatted = chars.join('');
        const lastDigitIndex = normalized.length - 1;
        
        // If no digits, show at least the prefix
        if (lastDigitIndex < 0) {
            let firstSlot = slots[0];
            return mask.slice(0, firstSlot);
        }
        
        formatted = formatted.slice(0, slots[lastDigitIndex] + 1);
        return formatted;
    };

    const regex = new RegExp('^' + escapeRegex(mask).replace(/#/g, '\\d') + '$');

    window.PhoneMask = window.PhoneMask || {};
    window.PhoneMask.mask = mask;
    window.PhoneMask.isComplete = (value) => regex.test((value || '').trim());

    const digitIndexFromCaret = (value, caret) => {
        const stop = Math.max(0, Math.min(caret ?? 0, value.length));
        let count = 0;
        
        // We need to know how many USER digits are before the caret
        // First, find the prefix length
        let prefixLen = 0;
        for (let i = 0; i < mask.length; i++) {
            if (mask[i] === '#') break;
            prefixLen++;
        }
        
        const effectiveStop = Math.max(0, stop - prefixLen);
        const sub = value.slice(prefixLen, stop);
        return (sub.match(/\d/g) || []).length;
    };

    const caretFromDigitIndex = (formatted, digitIndex) => {
        if (digitIndex <= 0) {
            // Return position after prefix
            for (let i = 0; i < mask.length; i++) {
                if (mask[i] === '#') return i;
            }
            return 0;
        }

        let seen = 0;
        // Find prefix end
        let startSearch = 0;
        for (let i = 0; i < mask.length; i++) {
            if (mask[i] === '#') {
                startSearch = i;
                break;
            }
        }

        for (let i = startSearch; i < formatted.length; i += 1) {
            if (!DIGIT_RE.test(formatted[i])) continue;
            seen += 1;
            if (seen === digitIndex) return i + 1;
        }

        return formatted.length;
    };

    const clamp = (n, min, max) => Math.max(min, Math.min(n, max));

    const transformDigits = ({ digits, startDigit, endDigit, action, payloadDigits }) => {
        const from = Math.min(startDigit, endDigit);
        const to = Math.max(startDigit, endDigit);

        const left = digits.slice(0, from);
        const right = digits.slice(to);

        if (action === 'insert' || action === 'paste') {
            const payload = extractDigits(payloadDigits);
            const nextDigits = (left + payload + right).slice(0, slotCount);
            const nextCaretDigit = left.length + payload.length;
            return { nextDigits, nextCaretDigit };
        }

        if (from !== to) {
            const nextDigits = left + right;
            return { nextDigits, nextCaretDigit: from };
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
        const formatted = formatDigits(digits);
        const nextCaret = caretFromDigitIndex(formatted, caretDigit);

        if (input.value !== formatted) {
            input.value = formatted;
        }
        
        if (document.activeElement === input) {
            input.setSelectionRange(nextCaret, nextCaret);
        }
    };

    const snapshotSelection = (input) => {
        return {
            startDigit: digitIndexFromCaret(input.value, input.selectionStart),
            endDigit: digitIndexFromCaret(input.value, input.selectionEnd),
        };
    };

    const bind = (input) => {
        if (!(input instanceof HTMLInputElement) || input.dataset.phoneMaskBound === '1') return;

        input.dataset.phoneMaskBound = '1';
        input.setAttribute('inputmode', 'numeric');
        
        const initialDigits = extractDigits(input.value);
        applyDigits(input, initialDigits, initialDigits.length);

        input.addEventListener('keydown', (e) => {
            const sel = snapshotSelection(input);
            const digits = extractDigits(input.value);

            if (e.key === 'Backspace') {
                e.preventDefault();
                const { nextDigits, nextCaretDigit } = transformDigits({
                    digits, ...sel, action: 'deleteBackward'
                });
                applyDigits(input, nextDigits, nextCaretDigit);
            } else if (e.key === 'Delete') {
                e.preventDefault();
                const { nextDigits, nextCaretDigit } = transformDigits({
                    digits, ...sel, action: 'deleteForward'
                });
                applyDigits(input, nextDigits, nextCaretDigit);
            } else if (e.key.length === 1 && DIGIT_RE.test(e.key) && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                const { nextDigits, nextCaretDigit } = transformDigits({
                    digits, ...sel, action: 'insert', payloadDigits: e.key
                });
                applyDigits(input, nextDigits, nextCaretDigit);
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = e.clipboardData.getData('text');
            const sel = snapshotSelection(input);
            const digits = extractDigits(input.value);
            const { nextDigits, nextCaretDigit } = transformDigits({
                digits, ...sel, action: 'paste', payloadDigits: text
            });
            applyDigits(input, nextDigits, nextCaretDigit);
        });
    };

    const init = () => {
        document.querySelectorAll('input[data-phone-mask]').forEach(bind);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    window.PhoneMask.bind = bind;
})();
