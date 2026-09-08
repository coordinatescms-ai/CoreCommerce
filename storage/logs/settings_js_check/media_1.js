
(function () {
    const qualityInput = document.getElementById('media_quality');
    const qualityValue = document.getElementById('quality-value');
    if (!qualityInput || !qualityValue) {
        return;
    }

    qualityInput.addEventListener('input', function () {
        qualityValue.textContent = qualityInput.value;
    });
})();
