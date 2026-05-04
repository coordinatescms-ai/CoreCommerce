<form action="/admin/settings/save" method="POST">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(\App\Core\Security\Csrf::token()); ?>">
    <input type="hidden" name="current_tab" value="reviews">
    <div class="settings-card">
        <h3>Налаштування відгуків</h3>
        <p>Дворівнева структура увімкнена: відгук + відповіді без подальшого заглиблення.</p>
        <label><input type="checkbox" checked disabled> Публікувати відгуки одразу (без модерації)</label><br>
        <label><input type="checkbox" checked disabled> Дозволити тільки зареєстрованим користувачам</label>
    </div>
</form>
