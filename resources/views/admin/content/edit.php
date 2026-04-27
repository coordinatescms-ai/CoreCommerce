<div class="admin-container">
    <h2>Редагувати сторінку</h2>
    
    <form action="/admin/content/update/<?= $page['id'] ?>" method="POST" id="content-form">
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Заголовок сторінки:</label>
            <input type="text" name="title" id="title" class="form-control" style="width: 100%; padding: 8px;" 
                   value="<?= htmlspecialchars($page['title']) ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label>URL-адреса (slug):</label>
            <input type="text" name="slug" id="slug" class="form-control" style="width: 100%; padding: 8px;" 
                   value="<?= htmlspecialchars($page['slug']) ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label>Контент:</label>
            <div class="editor-toolbar" style="background: #eee; border: 1px solid #ccc; border-bottom: none; padding: 8px;">
                <button type="button" class="ed-btn" onclick="runCmd('bold')"><b>B</b></button>
                <button type="button" class="ed-btn" onclick="runCmd('italic')"><i>I</i></button>
                <button type="button" class="ed-btn" onclick="runCmd('insertUnorderedList')">• Список</button>
                <button type="button" class="ed-btn" onclick="runCmd('formatBlock', 'h2')">H2</button>
                <button type="button" class="ed-btn" onclick="runCmd('formatBlock', 'p')">P</button>
                <button type="button" class="ed-btn" onclick="runCmd('createLink', prompt('URL:'))">🔗</button>
                <!-- Кнопка викликає клік по прихованому інпуту -->
                <button type="button" class="ed-btn" onclick="document.getElementById('image-upload').click()" title="Завантажити фото">🖼️ Фото</button>
                <!-- Важливо: type="file", а не checkbox! -->
                <input type="file" id="image-upload" style="display:none" accept="image/*" onchange="uploadEditorImage(this)">         
            </div>

            <!-- Візуальний редактор з існуючим контентом -->
            <div id="visual-editor" contenteditable="true" 
                 style="border: 1px solid #ccc; min-height: 300px; padding: 15px; background: #fff; outline: none; overflow-y: auto;">
                <?= $page['content'] ?>
            </div>

            <input type="hidden" name="content" id="real-content">
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #fdfdfd; border: 1px solid #ddd; border-radius: 5px;">
            <h4 style="margin-top: 0;">SEO налаштування</h4>
            <div class="form-group" style="margin-bottom: 10px;">
            <label>Meta Title (заголовок для Google):</label>
            <input type="text" name="meta_title" value="<?= htmlspecialchars($page['meta_title'] ?? '') ?>" class="form-control" style="width: 100%; padding: 8px;">
            </div>
            <div class="form-group">
                <label>Meta Description (опис для пошуку):</label>
                <textarea name="meta_description" class="form-control" rows="3" style="width: 100%; padding: 8px;"><?= htmlspecialchars($page['meta_description'] ?? '') ?></textarea>
            </div>
        </div>        

        <div class="form-group" style="margin-bottom: 15px;">
            <label>Порядок сортування:</label>
            <input type="number" name="sort_order" value="<?= $page['sort_order'] ?? 0 ?>" class="form-control" style="width: 100px; padding: 8px;">
            <small>Чим менше число, тим лівіше буде сторінка у футері.</small>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= $page['is_active'] ? 'checked' : '' ?>> Опубліковано
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-save" style="background: #3498db; color: white; padding: 10px 20px; border: none; cursor: pointer;">Оновити дані</button>
            <a href="/admin/content" style="margin-left: 10px;">Скасувати</a>
        </div>
    </form>
</div>

<style>
    .ed-btn { padding: 5px 12px; cursor: pointer; border: 1px solid #bbb; background: #fff; margin-right: 3px; border-radius: 3px; }
    .ed-btn:hover { background: #ddd; }
    #visual-editor ul { list-style-type: disc !important; padding-left: 40px !important; margin: 1em 0 !important; }
    #visual-editor li { display: list-item !important; }
</style>

<script>
function uploadEditorImage(input) {
    if (!input.files || !input.files[0]) return;

    let formData = new FormData();
    formData.append('image', input.files[0]); // Беремо перший файл з масиву

    fetch('/admin/content/upload', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.url) {
            // Повертаємо фокус у редактор
            const editor = document.getElementById('visual-editor');
            editor.focus();
            
            // Вставляємо картинку
            document.execCommand('insertImage', false, data.url);
            
            // Додаємо картинці адаптивність
            setTimeout(() => {
                const imgs = editor.getElementsByTagName('img');
                const lastImg = imgs[imgs.length - 1];
                if (lastImg) {
                    lastImg.style.maxWidth = '100%';
                    lastImg.style.height = 'auto';
                    lastImg.style.display = 'block';
                    lastImg.style.margin = '10px 0';
                }
            }, 100);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Помилка завантаження зображення');
    });
    
    input.value = ''; // Очищуємо інпут, щоб можна було вибрати те саме фото ще раз
}

function runCmd(cmd, val = null) {
    document.getElementById('visual-editor').focus();
    document.execCommand(cmd, false, val);
}

document.getElementById('content-form').onsubmit = function() {
    document.getElementById('real-content').value = document.getElementById('visual-editor').innerHTML;
    return true;
};
</script>

