# CoreCommerce — Інструкція з розробки плагінів

> **Версія документа:** 1.0  
> **Мінімальна версія PHP:** 8.1  
> **Мінімальна версія рушія:** 1.0

---

## Зміст

1. [Архітектура системи плагінів](#1-архітектура-системи-плагінів)
2. [Структура плагіна](#2-структура-плагіна)
3. [Файл info.json](#3-файл-infojson)
4. [Файл plugin.php — головний клас](#4-файл-pluginphp--головний-клас)
5. [Система хуків — Actions та Filters](#5-система-хуків--actions-та-filters)
6. [Довідник хуків](#6-довідник-хуків)
7. [Робота з базою даних (PluginDB)](#7-робота-з-базою-даних-plugindb)
8. [Налаштування плагіна (getSettingsSchema)](#8-налаштування-плагіна-getsettingsschema)
9. [Встановлення та активація](#9-встановлення-та-активація)
10. [Повний приклад — плагін "Знижка на день народження"](#10-повний-приклад--плагін-знижка-на-день-народження)
11. [Безпека та обмеження](#11-безпека-та-обмеження)
12. [Чеклист перед публікацією](#12-чеклист-перед-публікацією)

---

## 1. Архітектура системи плагінів

CoreCommerce використовує **систему хуків** (аналог WordPress Actions/Filters) та **пісочницю БД** (PluginDB).

```
plugins/
└── YourPlugin/
    ├── info.json      ← метадані (назва, версія, автор, залежності)
    └── plugin.php     ← головний клас (анонімний клас або named class)
```

**Як рушій завантажує плагін:**

```
index.php
  └── PluginManager::load()
        └── boot() — читає active_plugins.json
              └── loadPlugin('YourPlugin')
                    └── require plugins/YourPlugin/plugin.php
                          └── $plugin->register($pluginManager)
                                └── addAction() / addFilter()
```

Плагіни завантажуються **до роутингу** — тому можуть перехоплювати будь-який запит.

---

## 2. Структура плагіна

### Мінімальна структура

```
plugins/
└── MyPlugin/
    ├── info.json
    └── plugin.php
```

### Розширена структура

```
plugins/
└── MyPlugin/
    ├── info.json
    ├── plugin.php
    ├── README.md
    ├── assets/
    │   ├── my-plugin.css
    │   └── my-plugin.js
    └── migrations/
        └── install.sql      ← SQL для створення власних таблиць
```

> ⚠️ Усі файли з розширеннями: `php`, `js`, `css`, `json`, `sql`, `png`, `jpg`, `webp`, `gif`, `ico`, `txt`, `md` — дозволені до завантаження через адмінку у форматі `.zip`.

---

## 3. Файл info.json

```json
{
    "slug": "MyPlugin",
    "name": "Мій плагін",
    "description": "Короткий опис що робить плагін (до 200 символів).",
    "version": "1.0.0",
    "author": "Ваше Ім'я або Компанія",
    "author_url": "https://example.com",
    "requires_php": "8.1",
    "requires_core": "1.0",
    "requires": {
        "AnotherPlugin": ">=1.2.0"
    }
}
```

### Опис полів

| Поле | Обов'язково | Опис |
|---|---|---|
| `slug` | ✅ | Унікальний ідентифікатор. Лише латиниця, цифри, дефіс. Збігається з назвою папки |
| `name` | ✅ | Назва для відображення в адмінці |
| `description` | ✅ | Короткий опис |
| `version` | ✅ | Семантична версія: `MAJOR.MINOR.PATCH` |
| `author` | ✅ | Ім'я автора |
| `requires_php` | ✅ | Мінімальна версія PHP |
| `requires_core` | ✅ | Мінімальна версія CoreCommerce |
| `requires` | — | Залежності від інших плагінів: `{"Slug": ">=1.0.0"}` |

> 💡 `slug` **повинен точно збігатися** з назвою папки. Відмінність регістру призводить до помилки завантаження.

---

## 4. Файл plugin.php — головний клас

Файл **повинен повертати об'єкт** що реалізує `PluginInterface`. Рекомендований спосіб — анонімний клас:

```php
<?php

use App\Core\Plugin\PluginInterface;
use App\Core\Plugin\PluginManager;

return new class implements PluginInterface {

    public function getName(): string
    {
        return 'MyPlugin'; // Збігається зі slug
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Реєстрація хуків. Викликається один раз при завантаженні плагіна.
     * НЕ виконуй важких операцій тут — лише реєструй обробники.
     */
    public function register(PluginManager $pluginManager): void
    {
        // Підписуємось на хуки
        $pluginManager->addAction('order.created', [$this, 'onOrderCreated']);
        $pluginManager->addFilter('product.price', [$this, 'applyDiscount'], 20);
    }

    public function onOrderCreated(array $order): void
    {
        // Обробка події
    }

    public function applyDiscount(float $price, array $product): float
    {
        return $price * 0.9; // -10%
    }

    /**
     * Схема налаштувань (відображається в адмінці).
     * Поверни [] якщо налаштувань немає.
     */
    public function getSettingsSchema(): array
    {
        return [];
    }
};
```

### Інтерфейс PluginInterface

```php
interface PluginInterface
{
    public function getName(): string;
    public function getVersion(): string;
    public function register(PluginManager $pluginManager): void;
    public function getSettingsSchema(): array;
}
```

Усі чотири методи **обов'язкові**.

---

## 5. Система хуків — Actions та Filters

### Actions (події)

Дія — це повідомлення про те що щось відбулось. Плагін може виконати будь-який код у відповідь.

```php
// Підписка
$pluginManager->addAction(
    'order.created',    // назва хука
    $callback,          // callable
    10,                 // пріоритет (менше = раніше)
    99                  // макс. кількість аргументів
);

// Виклик у рушії (для довідки — не потрібно викликати самостійно)
do_action('order.created', $order);
```

### Filters (фільтри)

Фільтр дозволяє **змінити значення** що передається через нього. Обов'язково повертай значення того самого типу.

```php
// Підписка
$pluginManager->addFilter(
    'product.price',    // назва хука
    $callback,          // callable — ПОВИНЕН повертати значення
    10,                 // пріоритет
    99                  // макс. кількість аргументів
);

// Виклик у рушії
$price = apply_filters('product.price', $price, $product);
```

> ⚠️ **Правило фільтра:** завжди повертай значення з обробника фільтра. Якщо забудеш — рушій отримає `null` замість ціни/тексту.

### Пріоритет

Нижче число = виконується раніше. За замовчуванням `10`.

```php
// Виконається першим
$pluginManager->addFilter('product.price', $callback1, 5);

// Виконається другим
$pluginManager->addFilter('product.price', $callback2, 10);

// Виконається останнім
$pluginManager->addFilter('product.price', $callback3, 20);
```

---

## 6. Довідник хуків

### Actions (do_action)

| Хук | Аргументи | Де викликається | Опис |
|---|---|---|---|
| `order.created` | `array $order` | Після створення замовлення | Надсилання email, CRM, нарахування бонусів |
| `order.placed` | `array $order` | Після підтвердження оплати | Запуск логістики, склад |
| `order.status_changed` | `array $order, string $oldStatus, string $newStatus` | При зміні статусу замовлення | Сповіщення клієнта |
| `product.updated` | `int $productId, array $data` | Після оновлення товару | Синхронізація з маркетплейсами |
| `cart.add_item` | `int $productId, int $quantity` | При додаванні товару в кошик | Аналітика, обмеження |
| `auth.success` | `array $user` | Після успішного входу | Логування, 2FA |
| `theme.head` | — | Всередині `<head>` на кожній сторінці | Підключення CSS/мета-тегів |
| `theme.footer` | — | Перед `</body>` на кожній сторінці | Підключення JS, чат-виджети |
| `product.summary.after` | `array $product` | Після блоку з ціною на сторінці товару | Кнопки, банери, відмітки |

### Filters (apply_filters)

| Хук | Початкове значення | Додаткові аргументи | Опис |
|---|---|---|---|
| `product.price` | `float $price` | `array $product` | Зміна ціни (знижки, надбавки) |
| `product.description` | `string $html` | `array $product` | Зміна HTML-опису товару |
| `cart.item.price` | `float $price` | `array $item` | Ціна позиції в кошику |
| `page.title` | `string $title` | — | SEO-заголовок сторінки |

### Додавання власних хуків у плагін

Якщо твій плагін надає функціональність іншим плагінам — додай свої хуки:

```php
// У своєму плагіні — викликаємо хук для інших
do_action('myplugin.before_send', $data);
$result = apply_filters('myplugin.message_text', $message, $user);
```

---

## 7. Робота з базою даних (PluginDB)

`PluginDB` — пісочниця яка захищає таблиці рушія від випадкового або навмисного пошкодження.

```php
public function register(PluginManager $pluginManager): void
{
    $db = $pluginManager->getPluginDB('MyPlugin'); // отримати пісочницю
}
```

### Що можна і що не можна

| Операція | Власні таблиці (`plugin_myplugin_*`) | Таблиці ядра (`products`, `orders`…) |
|---|---|---|
| SELECT | ✅ | ✅ |
| INSERT | ✅ | ❌ → `PluginSecurityException` |
| UPDATE | ✅ | ❌ → `PluginSecurityException` |
| DELETE | ✅ | ❌ → `PluginSecurityException` |
| CREATE TABLE | ✅ | ❌ |

### SELECT — читання даних

```php
// Читати власні дані
$rows = $db->select(
    "SELECT * FROM {$db->table('logs')} WHERE created_at > ?",
    [date('Y-m-d', strtotime('-7 days'))]
);

// Читати товари з ядра
$products = $db->select(
    "SELECT id, name, price FROM products WHERE is_visible = 1 LIMIT 10"
);
```

### WRITE — запис у власні таблиці

```php
$db->write(
    "INSERT INTO {$db->table('logs')} (user_id, action, created_at)
     VALUES (?, ?, NOW())",
    [$userId, $action]
);
```

### Транзакції

```php
$db->beginTransaction();
try {
    $db->write("INSERT INTO {$db->table('orders')} ...", [...]);
    $db->write("UPDATE {$db->table('balances')} ...", [...]);
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    throw $e;
}
```

### Отримання та збереження налаштувань через PluginDB

```php
// Читати налаштування (зберігаються в plugin_settings)
$apiKey  = $db->getSetting('api_key', '');
$sandbox = $db->getSetting('sandbox', '0');

// Зберегти програмно (зазвичай адмінка робить це автоматично)
$db->setSetting('last_sync', date('Y-m-d H:i:s'));
```

### Отримання ID останнього запису

```php
$db->write("INSERT INTO {$db->table('items')} (name) VALUES (?)", ['test']);
$id = $db->lastInsertId();
```

### helper метод table()

`$db->table('logs')` повертає `plugin_myplugin_logs` — повну назву з префіксом.

```php
// Замість
$table = 'plugin_myplugin_logs';

// Використовуй
$table = $db->table('logs');
```

---

## 8. Налаштування плагіна (getSettingsSchema)

Поверни масив з описом полів — адмінка автоматично побудує форму.

```php
public function getSettingsSchema(): array
{
    return [
        // Ключ = назва налаштування в plugin_settings
        'api_key' => [
            'label'    => 'API ключ',
            'type'     => 'password',        // text | password | textarea | select | checkbox
            'default'  => '',
            'required' => true,
            'hint'     => 'Знайдіть у кабінеті на example.com → API',
        ],

        'mode' => [
            'label'   => 'Режим роботи',
            'type'    => 'select',
            'default' => 'sandbox',
            'options' => [                   // тільки для type=select
                'sandbox'    => 'Тестовий (Sandbox)',
                'production' => 'Бойовий',
            ],
            'required' => true,
        ],

        'send_notifications' => [
            'label'   => 'Надсилати email-сповіщення',
            'type'    => 'checkbox',
            'default' => '1',
            'hint'    => 'Листи будуть надсилатись через SMTP з налаштувань магазину.',
        ],

        'message_template' => [
            'label'   => 'Шаблон повідомлення',
            'type'    => 'textarea',
            'default' => 'Ваше замовлення #{order_id} оброблено.',
            'hint'    => 'Доступні маски: {order_id}, {customer_name}, {total}',
        ],
    ];
}
```

### Типи полів

| `type` | HTML | Додатково |
|---|---|---|
| `text` | `<input type="text">` | — |
| `password` | `<input type="password">` | Значення маскується в UI |
| `textarea` | `<textarea>` | — |
| `select` | `<select>` | Потребує `options: {value: label}` |
| `checkbox` | `<input type="checkbox">` | Зберігається як `'0'` або `'1'` |

### Читання налаштувань у коді плагіна

```php
public function register(PluginManager $pluginManager): void
{
    $db = $pluginManager->getPluginDB('MyPlugin');

    $pluginManager->addAction('order.created', function (array $order) use ($db) {
        $apiKey = $db->getSetting('api_key', '');
        $mode   = $db->getSetting('mode', 'sandbox');

        if (empty($apiKey)) {
            return; // плагін не налаштований
        }

        // ... логіка
    });
}
```

---

## 9. Встановлення та активація

### Через адмінку

1. Стисни папку плагіна у `.zip` (папка → `MyPlugin.zip`, всередині: `MyPlugin/info.json`, `MyPlugin/plugin.php`)
2. Адмінка → **Плагіни** → кнопка "Завантажити плагін (.zip)"
3. Після завантаження — натисни **Увімкнути**
4. Якщо є налаштування — перейди до іконки ⚙️ і заповни форму

### Вручну (через FTP/SSH)

```bash
# Розпакуй в папку plugins/
unzip MyPlugin.zip -d /var/www/mysite/plugins/

# Перевір права
chmod -R 755 /var/www/mysite/plugins/MyPlugin/
```

Потім увімкни через адмінку → Плагіни.

### Міграція БД при активації

CoreCommerce не має автоматичного запуску SQL при активації. Якщо плагін потребує власних таблиць — виконай CREATE TABLE в `register()` через `PluginDB::write()`:

```php
public function register(PluginManager $pluginManager): void
{
    $db = $pluginManager->getPluginDB('MyPlugin');

    // Таблиця створюється лише якщо не існує
    $db->write("
        CREATE TABLE IF NOT EXISTS {$db->table('logs')} (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id   INT UNSIGNED NOT NULL,
            message    TEXT         NOT NULL,
            created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
```

---

## 10. Повний приклад — плагін "Знижка на день народження"

Плагін дає знижку 15% покупцям у їхній день народження.

### info.json

```json
{
    "slug": "BirthdayDiscount",
    "name": "Знижка на день народження",
    "description": "Надає знижку 15% усім покупцям у день їхнього народження.",
    "version": "1.0.0",
    "author": "Розробник",
    "requires_php": "8.1",
    "requires_core": "1.0",
    "requires": {}
}
```

### plugin.php

```php
<?php

use App\Core\Plugin\PluginInterface;
use App\Core\Plugin\PluginManager;

return new class implements PluginInterface {

    public function getName(): string    { return 'BirthdayDiscount'; }
    public function getVersion(): string { return '1.0.0'; }

    public function register(PluginManager $pluginManager): void
    {
        $db = $pluginManager->getPluginDB('BirthdayDiscount');

        // 1. Створюємо таблицю для логування (один раз)
        $db->write("
            CREATE TABLE IF NOT EXISTS {$db->table('log')} (
                id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id    INT UNSIGNED NOT NULL,
                order_id   INT UNSIGNED NOT NULL,
                discount   DECIMAL(10,2) NOT NULL,
                applied_at DATE NOT NULL,
                UNIQUE KEY uq_user_date (user_id, applied_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 2. Фільтр ціни товару в кошику
        $pluginManager->addFilter(
            'cart.item.price',
            function (float $price, array $item) use ($db, $pluginManager): float {
                return $this->applyBirthdayDiscount($price, $db, $pluginManager);
            }
        );

        // 3. Логуємо використання знижки при оформленні замовлення
        $pluginManager->addAction(
            'order.created',
            function (array $order) use ($db): void {
                if (!$this->isBirthday()) {
                    return;
                }

                $userId = $_SESSION['user']['id'] ?? 0;
                if ($userId === 0) {
                    return;
                }

                // ON DUPLICATE KEY — захист від подвійного запису
                $db->write(
                    "INSERT INTO {$db->table('log')} (user_id, order_id, discount, applied_at)
                     VALUES (?, ?, ?, CURDATE())
                     ON DUPLICATE KEY UPDATE order_id = VALUES(order_id)",
                    [$userId, $order['id'], $order['total'] * 0.15]
                );
            }
        );

        // 4. Виводимо банер на сторінках (через action theme.head)
        $pluginManager->addAction('theme.head', function () use ($db): void {
            if (!$this->isBirthday()) {
                return;
            }

            $discountPct = (int) $db->getSetting('discount_percent', '15');
            echo '<style>.birthday-banner{background:#fef08a;padding:.75rem 1rem;text-align:center;font-weight:600;}</style>';
            echo '<script>document.addEventListener("DOMContentLoaded",function(){'
               . 'var b=document.createElement("div");b.className="birthday-banner";'
               . 'b.textContent="🎂 З днем народження! Знижка ' . $discountPct . '% на всі товари сьогодні!";'
               . 'document.body.prepend(b);});</script>';
        });
    }

    // ── Приватні методи ───────────────────────────────────────────────────────

    private function applyBirthdayDiscount(float $price, $db, $pluginManager): float
    {
        if (!$this->isBirthday()) {
            return $price;
        }

        if (empty($_SESSION['user']['id'])) {
            return $price; // лише для авторизованих
        }

        $discountPct = (float) $db->getSetting('discount_percent', '15');
        return round($price * (1 - $discountPct / 100), 2);
    }

    private function isBirthday(): bool
    {
        $birthday = $_SESSION['user']['birthday'] ?? '';
        if (empty($birthday)) {
            return false;
        }

        // Порівнюємо лише місяць і день (рік не важливий)
        return date('m-d', strtotime($birthday)) === date('m-d');
    }

    // ── Settings Schema ───────────────────────────────────────────────────────

    public function getSettingsSchema(): array
    {
        return [
            'discount_percent' => [
                'label'    => 'Розмір знижки (%)',
                'type'     => 'select',
                'default'  => '15',
                'options'  => [
                    '5'  => '5%',
                    '10' => '10%',
                    '15' => '15% (рекомендовано)',
                    '20' => '20%',
                ],
                'required' => true,
                'hint'     => 'Знижка застосовується лише в день народження покупця.',
            ],
        ];
    }
};
```

---

## 11. Безпека та обмеження

### Що заборонено

| ❌ Заборонено | Альтернатива |
|---|---|
| Прямий доступ до `DB::$pdo` | Використовуй `PluginDB` |
| `eval()`, `exec()`, `system()`, `shell_exec()` | — |
| Запис у таблиці ядра (`products`, `users`…) | Тільки `SELECT` через `PluginDB::select()` |
| Читання/запис за межами директорії плагіна | Використовуй `storage/` через абсолютні шляхи |
| Підключення зовнішніх бібліотек через Composer | Включай залежності вручну у папку плагіна |

### SQL-ін'єкції

Завжди використовуй prepared statements:

```php
// ✅ Правильно
$db->select("SELECT * FROM products WHERE id = ?", [$id]);

// ❌ Небезпечно
$db->select("SELECT * FROM products WHERE id = $id");
```

### XSS

Завжди екранувати виведення:

```php
// ✅ Правильно
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ❌ Небезпечно
echo $userInput;
```

### Валідація вхідних даних

```php
$pluginManager->addAction('order.created', function (array $order): void {
    $orderId = (int) ($order['id'] ?? 0);
    if ($orderId <= 0) {
        return;
    }
    // ...
});
```

### CSRF у власних формах

Якщо плагін виводить форму (через `theme.footer` або `product.summary.after`), **додай CSRF-токен**:

```php
$pluginManager->addAction('product.summary.after', function (array $product): void {
    $csrf = htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8');
    echo '<form method="POST" action="/plugin/myplugin/action">';
    echo '  <input type="hidden" name="csrf" value="' . $csrf . '">';
    echo '  <button type="submit">Дія</button>';
    echo '</form>';
});
```

---

## 12. Чеклист перед публікацією

```
Структура
 [ ] Папка плагіна має правильну назву (збігається з slug в info.json)
 [ ] Файли info.json і plugin.php присутні
 [ ] plugin.php повертає об'єкт що реалізує PluginInterface
 [ ] Всі 4 методи інтерфейсу реалізовані

Метадані (info.json)
 [ ] slug унікальний і не збігається з існуючими плагінами
 [ ] version у форматі MAJOR.MINOR.PATCH
 [ ] requires_php та requires_core вказані

Хуки
 [ ] Всі filter-обробники повертають значення правильного типу
 [ ] Пріоритети хуків обґрунтовані
 [ ] Важкі операції (HTTP-запити, файловий I/O) не блокують рендер сторінки

База даних
 [ ] Використовується тільки PluginDB (не DB напряму)
 [ ] Назви таблиць через $db->table() (з префіксом)
 [ ] Параметри у запитах передаються через prepared statements
 [ ] CREATE TABLE IF NOT EXISTS (захист від повторного запуску)

Безпека
 [ ] Весь виведений HTML екранований через htmlspecialchars()
 [ ] Вхідні дані з $_POST/$_GET валідуються та приводяться до типу
 [ ] Форми містять CSRF-токен

Налаштування
 [ ] getSettingsSchema() повертає коректний масив
 [ ] Код не падає якщо налаштування порожні (є default значення)

Тестування
 [ ] Плагін встановлюється через адмінку (zip-архів)
 [ ] Плагін вмикається і вимикається без помилок
 [ ] Налаштування зберігаються і читаються коректно
 [ ] Сторінки сайту відкриваються без помилок при увімкненому плагіні
```

---

## Довідка: глобальні helper-функції

Ці функції доступні в будь-якому місці плагіна (визначені в `app/helpers.php`):

```php
// Переклад UI
__('key_name')                          // рядок з lang/ua.php або lang/en.php

// Налаштування магазину
get_setting('site_name', 'default')     // читати з таблиці settings
format_price(1999.00)                   // → "1 999,00 ₴"
get_current_language()                  // → "ua" або "en"

// Хуки (оголошені через PluginManager)
do_action('hook.name', $arg1, $arg2)
apply_filters('hook.name', $value, $arg1)

// Плагін-хуки (реєстрація)
add_action('hook.name', $callback, $priority, $args)
add_filter('hook.name', $callback, $priority, $args)
```

---

*Документ актуальний для CoreCommerce v1.0. З питань — відкривай issue або звертайся до автора рушія.*
