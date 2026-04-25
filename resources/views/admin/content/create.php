<div class="admin-container">
    <h2>Додати нову сторінку</h2>
    
    <form action="/admin/content/store" method="POST">
        <div class="form-group">
            <label for="title">Заголовок сторінки:</label>
            <input type="text" name="title" id="title" class="form-control" required placeholder="Наприклад: Про нас">
        </div>

        <div class="form-group">
            <label for="slug">URL-адреса (slug):</label>
            <input type="text" name="slug" id="slug" class="form-control" required placeholder="pro-nas">
            <small>Це буде відображатися в браузері: mysite.test/<strong>pro-nas</strong></small>
        </div>

        <div class="form-group">
            <label for="content">Контент сторінки:</label>
            <textarea name="content" id="content" rows="10" class="form-control" required></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-save">Зберегти сторінку</button>
            <a href="/admin/content" class="btn-cancel">Скасувати</a>
        </div>
    </form>
</div>

<!-- Скрипт для автоматичної генерації slug -->
<script>
function transliterate(text) {
    const cyrillic = {
        'а':'a', 'б':'b', 'в':'v', 'г':'g', 'д':'d', 'е':'e', 'ё':'e', 'ж':'zh', 
        'з':'z', 'и':'i', 'й':'y', 'к':'k', 'л':'l', 'м':'m', 'н':'n', 'о':'o', 
        'п':'p', 'р':'r', 'с':'s', 'т':'t', 'у':'u', 'ф':'f', 'х':'h', 'ц':'ts', 
        'ч':'ch', 'ш':'sh', 'щ':'shch', 'ъ':'', 'ы':'y', 'ь':'', 'э':'e', 'ю':'yu', 'я':'ya',
        'і':'i', 'ї':'yi', 'є':'ye', 'ґ':'g'
    };
    
    return text.split('').map(char => {
        let lowerChar = char.toLowerCase();
        return cyrillic[lowerChar] !== undefined ? cyrillic[lowerChar] : lowerChar;
    }).join('');
}

document.getElementById('title').addEventListener('input', function() {
    let text = this.value;
    
    // 1. Транслітеруємо кирилицю в латиницю
    let slug = transliterate(text);
    
    // 2. Очищуємо від зайвих символів
    slug = slug.toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')     // видаляємо все крім букв, цифр, пробілів і дефісів
        .replace(/[\s_-]+/g, '-')     // замінюємо пробіли та підкреслення на дефіси
        .replace(/^-+|-+$/g, '');     // видаляємо дефіси на початку та в кінці
    
    document.getElementById('slug').value = slug;
});
</script>
