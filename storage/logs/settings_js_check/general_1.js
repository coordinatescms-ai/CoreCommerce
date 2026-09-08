
function toggleRateSource() {
    const isApi = document.getElementById('src_api').checked;
    document.getElementById('manual_rate_group').style.display = isApi ? 'none' : '';
    document.getElementById('api_rate_group').style.display    = isApi ? '' : 'none';
    document.getElementById('manual_rate').required = !isApi;
}
toggleRateSource();
