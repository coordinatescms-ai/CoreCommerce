# Приклади використання системи управління темами

## Таблиця змісту
1. [Використання ThemeManager в коді](#використання-thememanager-в-коді)
2. [Додавання нової теми](#додавання-нової-теми)
3. [Активація теми програмно](#активація-теми-програмно)
4. [Отримання інформації про тему](#отримання-інформації-про-тему)
5. [Використання в шаблонах](#використання-в-шаблонах)
6. [API запити](#api-запити)

---

## Використання ThemeManager в коді

### Отримання активної теми

```php
<?php
use App\Core\Theme\ThemeManager;

// Отримати ID активної теми
$active_theme = ThemeManager::getActiveTheme();
echo $active_theme; // Виведе: "modern" або "default"

// Отримати список всіх доступних тем
$themes = ThemeManager::getAvailableThemes();
foreach ($themes as $theme) {
    echo $theme['id'] . ' - ' . $theme['name'] . "\n";
}
```

### Встановлення активної теми

```php
<?php
use App\Core\Theme\ThemeManager;

// Встановити активну тему
if (ThemeManager::setActiveTheme('modern')) {
    echo "Тема успішно активована!";
} else {
    echo "Тема не знайдена!";
}
```

### Отримання інформації про тему

```php
<?php
use App\Core\Theme\ThemeManager;

// Отримати інформацію про конкретну тему
$theme_info = ThemeManager::getThemeInfo('modern');
echo $theme_info['name'];        // "Modern Theme"
echo $theme_info['description']; // Опис теми
echo $theme_info['version'];     // "1.0.0"
echo $theme_info['author'];      // "CoreCommerce Team"

// Отримати конфіг теми
$config = ThemeManager::getThemeConfig('modern');
print_r($config['colors']);  // Масив кольорів
print_r($config['fonts']);   // Масив шрифтів

// Отримати кольори теми
$colors = ThemeManager::getThemeColors('modern');
echo $colors['primary'];     // "#2563eb"

// Отримати шрифти теми
$fonts = ThemeManager::getThemeFonts('modern');
echo $fonts['primary'];      // "Poppins"
```

### Отримання шляхів до файлів теми

```php
<?php
use App\Core\Theme\ThemeManager;

// Отримати шлях до макета теми
$layout_path = ThemeManager::getLayoutPath('modern');
// /home/ubuntu/corecommerce/CoreCommerce/resources/themes/modern/layout.php

// Отримати шлях до CSS файлу
$style_path = ThemeManager::getStylePath('modern');
// /resources/themes/modern/style.css

// Отримати шлях до директорії теми
$theme_path = ThemeManager::getThemePath('modern');
// /home/ubuntu/corecommerce/CoreCommerce/resources/themes/modern

// Перевірити, чи існує тема
if (ThemeManager::themeExists('modern')) {
    echo "Тема існує!";
}
```

---

## Додавання нової теми

### Крок 1: Створити структуру папок

```bash
mkdir -p resources/themes/my_theme
```

### Крок 2: Створити theme.json

```json
{
  "id": "my_theme",
  "name": "My Custom Theme",
  "description": "A beautiful custom theme for CoreCommerce",
  "version": "1.0.0",
  "author": "Your Company",
  "colors": {
    "primary": "#FF6B6B",
    "secondary": "#4ECDC4",
    "success": "#45B7D1",
    "danger": "#F7B731",
    "warning": "#5F27CD",
    "info": "#00D2D3",
    "light": "#F8F9FA",
    "dark": "#2C3E50"
  },
  "fonts": {
    "primary": "Roboto",
    "secondary": "Open Sans",
    "monospace": "Courier New"
  }
}
```

### Крок 3: Створити layout.php

```php
<!DOCTYPE html>
<html lang="<?php echo get_current_language() ?? 'ua'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageSeo['meta_title'] ?? 'My Store') ?></title>
    <link rel="stylesheet" href="<?= \App\Core\View\View::getThemeStyle() ?>">
    <style>
        /* Ваші стилі тут */
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <header>
        <!-- Ваш заголовок -->
    </header>
    
    <main>
        <?= $content ?? '' ?>
    </main>
    
    <footer>
        <!-- Ваш футер -->
    </footer>
</body>
</html>
```

### Крок 4: Створити style.css (опціонально)

```css
:root {
    --primary: #FF6B6B;
    --secondary: #4ECDC4;
    --light: #F8F9FA;
    --dark: #2C3E50;
}

body {
    font-family: 'Roboto', sans-serif;
    color: var(--dark);
    background-color: var(--light);
}

/* Ваші стилі */
```

### Крок 5: Тема готова!

Нова тема автоматично з'явиться в списку доступних тем на сторінці `/admin/themes`.

---

## Активація теми програмно

### У контролері

```php
<?php
namespace App\Controllers;

use App\Core\Theme\ThemeManager;

class MyController
{
    public function activateTheme()
    {
        // Активувати тему
        if (ThemeManager::setActiveTheme('my_theme')) {
            $_SESSION['success'] = 'Тема успішно активована!';
        } else {
            $_SESSION['error'] = 'Помилка активації теми!';
        }
        
        header('Location: /');
        exit;
    }
}
```

### У middleware

```php
<?php
namespace App\Middleware;

use App\Core\Theme\ThemeManager;

class ThemeMiddleware
{
    public static function handle()
    {
        // Встановити тему на основі користувача
        if (!empty($_SESSION['user']['preferred_theme'])) {
            ThemeManager::setActiveTheme($_SESSION['user']['preferred_theme']);
        }
    }
}
```

---

## Отримання інформації про тему

### Список всіх тем з деталями

```php
<?php
use App\Core\Theme\ThemeManager;

$themes = ThemeManager::getAvailableThemes();

foreach ($themes as $theme) {
    echo "ID: " . $theme['id'] . "\n";
    echo "Назва: " . $theme['name'] . "\n";
    echo "Опис: " . $theme['description'] . "\n";
    echo "Версія: " . $theme['version'] . "\n";
    echo "Автор: " . $theme['author'] . "\n";
    echo "Шлях: " . $theme['path'] . "\n";
    echo "---\n";
}
```

### Отримання активної теми з конфігом

```php
<?php
use App\Core\Theme\ThemeManager;

$active_theme_id = ThemeManager::getActiveTheme();
$theme_info = ThemeManager::getThemeInfo($active_theme_id);
$theme_config = ThemeManager::getThemeConfig($active_theme_id);

echo "Активна тема: " . $theme_info['name'] . "\n";
echo "Основний колір: " . $theme_config['colors']['primary'] . "\n";
echo "Основний шрифт: " . $theme_config['fonts']['primary'] . "\n";
```

---

## Використання в шаблонах

### Отримання інформації про активну тему

```php
<!-- resources/views/my_view.php -->

<?php
use App\Core\Theme\ThemeManager;

$active_theme = ThemeManager::getActiveTheme();
$theme_info = ThemeManager::getThemeInfo($active_theme);
$colors = ThemeManager::getThemeColors($active_theme);
?>

<div style="background-color: <?= $colors['primary'] ?>">
    <h1><?= $theme_info['name'] ?></h1>
    <p><?= $theme_info['description'] ?></p>
</div>
```

### Вивід списку тем для вибору

```php
<!-- resources/views/theme_selector.php -->

<?php
use App\Core\Theme\ThemeManager;

$themes = ThemeManager::getAvailableThemes();
$active_theme = ThemeManager::getActiveTheme();
?>

<div class="theme-selector">
    <?php foreach ($themes as $theme): ?>
        <div class="theme-card <?= $theme['id'] === $active_theme ? 'active' : '' ?>">
            <h3><?= htmlspecialchars($theme['name']) ?></h3>
            <p><?= htmlspecialchars($theme['description']) ?></p>
            
            <?php if ($theme['id'] !== $active_theme): ?>
                <a href="/theme/switch/<?= htmlspecialchars($theme['id']) ?>" class="btn">
                    Активувати
                </a>
            <?php else: ?>
                <span class="badge">Активна</span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
```

---

## API запити

### Отримати список тем (JSON)

```bash
GET /themes
```

**Відповідь:**
```json
{
  "themes": [
    {
      "id": "default",
      "name": "Default Theme",
      "description": "Default theme for CoreCommerce",
      "version": "1.0.0",
      "author": "CoreCommerce Team",
      "path": "/home/ubuntu/corecommerce/CoreCommerce/resources/themes/default"
    },
    {
      "id": "modern",
      "name": "Modern Theme",
      "description": "Modern and responsive theme",
      "version": "1.0.0",
      "author": "CoreCommerce Team",
      "path": "/home/ubuntu/corecommerce/CoreCommerce/resources/themes/modern"
    }
  ],
  "active_theme": "modern"
}
```

### Змінити активну тему (API)

```bash
GET /theme/switch/default
```

**Відповідь:**
```json
{
  "success": true,
  "theme": "default"
}
```

### Отримати інформацію про активну тему

```bash
GET /theme/info
```

**Відповідь:**
```json
{
  "id": "modern",
  "name": "Modern Theme",
  "description": "Modern and responsive theme",
  "version": "1.0.0",
  "author": "CoreCommerce Team",
  "path": "/home/ubuntu/corecommerce/CoreCommerce/resources/themes/modern"
}
```

### Активувати тему з адмінки

```bash
GET /admin/theme/switch/default
```

**Результат:** Перенаправлення на `/admin/themes` з сповіщенням про успіх.

---

## Поширені помилки та рішення

### Помилка: "Theme not found"

**Причина:** Тема не існує в директорії `resources/themes/`

**Рішення:** 
1. Перевірте, що папка теми існує
2. Перевірте, що файл `theme.json` присутній
3. Перевірте, що ID в `theme.json` збігається з назвою папки

### Помилка: "Тема не змінюється"

**Причина:** Кеш браузера або сесія не оновлена

**Рішення:**
1. Очистіть кеш браузера (Ctrl+Shift+Delete)
2. Перезавантажте сторінку (Ctrl+F5)
3. Перевірте, що cookie не заблоковані

### Помилка: "Стилі не завантажуються"

**Причина:** Неправильний шлях до CSS файлу

**Рішення:**
1. Перевірте, що файл `style.css` існує в папці теми
2. Перевірте, що шлях в `layout.php` правильний
3. Використовуйте `ThemeManager::getStylePath()` для отримання правильного шляху

---

## Кращі практики

1. **Завжди перевіряйте наявність теми** перед активацією:
   ```php
   if (ThemeManager::themeExists('my_theme')) {
       ThemeManager::setActiveTheme('my_theme');
   }
   ```

2. **Кешуйте інформацію про тему** для покращення продуктивності:
   ```php
   $theme_config = ThemeManager::getThemeConfig();
   // Повторне звернення повернеться з кешу
   ```

3. **Використовуйте локалізацію** в назвах та описах тем:
   ```json
   {
     "name": "Modern Theme",
     "description": "A modern and responsive theme"
   }
   ```

4. **Тестуйте теми** на різних пристроях та браузерах

5. **Документуйте** особливості вашої теми

---

## Контакти та підтримка

Для питань та пропозицій, звертайтесь до команди розробки.
