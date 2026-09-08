# HANDOFF: Розробка плагінів для CoreCommerce

**Контекст:** v1.0.0 CoreCommerce (чистий PHP/MySQL, без фреймворків) готовий до релізу.
Наступна сесія — новий чат, свіжий архів проєкту зі змінами. Задача: писати нові
платні плагіни (міжнародні перевезення, платіжні шлюзи тощо) для цієї версії ядра.

**Перше, що робити в новому чаті:** розпакувати наданий архів. `HANDOFF_CONTEXT.md`
(файл із попередньої сесії, де фіксувався загальний стан проєкту) — видалений,
більше не існує. Цей файл — єдине джерело контексту про архітектуру плагінів;
усе решта про поточний стан проєкту (які фікси вже застосовані, що ще недороблено)
треба звірити напряму з кодом у наданому архіві, а не з пам'яті.

---

## 1. Загальна архітектура плагінів

- **Формат:** кожен плагін — папка в `plugins/{PluginName}/` з обов'язковими:
  - `info.json` — метадані (`slug`, `name`, `description`, `version`, `author`, `requires_php`, `requires_core`, `requires`)
  - `plugin.php` — повертає **анонімний клас**, що реалізує `App\Core\Plugin\PluginInterface`:
    ```php
    <?php
    return new class implements PluginInterface {
        public function getName(): string { return 'MyPlugin'; }
        public function getVersion(): string { return '1.0.0'; }
        public function register(PluginManager $pluginManager): void { /* хуки, реєстрація гейтвея тощо */ }
        public function getSettingsSchema(): array { return []; } // або схема полів
    };
    ```
  - `lang/ua.json`, `lang/en.json` — **обов'язково**, дивись розділ 4
- Ядро підключає активні плагіни через `PluginManager::load()` → `boot()`, стан
  активності зберігається в таблиці `plugins` (`slug`, `is_active`) і кешується в
  `storage/cache/active_plugins.json`.
- Активація/деактивація — сторінка `/admin/plugins`, ендпоінт
  `POST /admin/plugins/toggle` (`slug`, `action=activate|deactivate`, CSRF).
- Завантаження нового плагіна архівом (`.zip`) — `POST /admin/plugins/upload`,
  вже реалізовано, валідує вміст архіву, розпаковує в `plugins/`.

### Два способи UI налаштувань плагіна
1. **Генерик-UI через `getSettingsSchema()`** (єдиний плагін, що це використовує —
   `TestPlugin`): повертає масив `['key' => ['label','type','default','required','hint']]`,
   ядро саме рендерить форму на `/admin/plugins/settings/{slug}`. Прості
   ключ-значення налаштування (API-ключі, текстові поля, чекбокси).
2. **Власна admin-сторінка** (`PromoCodes`, `SalePrice`, `CallbackWidget`) — плагін
   підключає свій `admin.php` через хук і рендерить довільний HTML. Використовуй,
   коли потрібна складніша логіка (списки, таблиці, JS-взаємодія).

Для **платіжних і shipping-плагінів** налаштування зазвичай ідуть через
`shop_methods.settings` (JSON-колонка), а не через жоден з двох способів вище —
дивись розділи 2 і 3.

---

## 2. Платіжні плагіни — є формальний контракт

`app/Core/Payment/PaymentGatewayInterface.php`:
```php
interface PaymentGatewayInterface
{
    public function getName(): string;   // 'liqpay', 'stripe', latin+digits, = webhook URL segment
    public function getLabel(): string;  // людська назва
    public function initiate(int $orderId, float $amount, array $meta = []): PaymentResult;
    public function handleWebhook(array $postData, string $rawBody, array $headers): WebhookResult;
    public function getSettingsSchema(): array; // зазвичай [] — налаштування через shop_methods
}
```

**`PaymentResult`** (що повертає `initiate()`):
- `PaymentResult::redirect($url)` — редірект на зовнішню сторінку оплати
- `PaymentResult::render($html)` — вставити HTML-форму/кнопку прямо на сторінці (напр. автосабміт форма з підписом)
- `PaymentResult::thankYou($message)` — одразу сторінка подяки (оплата при отриманні)
- `PaymentResult::error($message)` — показати помилку (checkout.js **вже виправлено** обробляти цей кейс окремо, показує `showStatus('error', ...)`)

**`WebhookResult`** (що повертає `handleWebhook()`):
- `::paid($orderId, $message)` → статус замовлення → `completed`
- `::failed($orderId, $message)` → статус → `cancelled`
- `::pending($orderId, $message)` → статус **не змінюється** (проміжні стани типу `wait_secure`)
- `::invalid($message)` → підпис не пройшов верифікацію, `400`

**Реєстрація:** у `register()` викликати `PaymentManager::register($this)` (передавши
анонімний внутрішній клас, що реалізує `PaymentGatewayInterface` — дивись
`LiqPayGateway`/`StripeGateway` як зразок; там `register()` створює вкладений
анонімний клас саме для гейтвея, а зовнішній клас плагіна відповідає лише за
`getSettingsSchema()`/переклади).

**`PaymentManager::findForOrder($paymentMethodCode)`** шукає гейтвей або напряму
по імені, або через `shop_methods.settings.gateway_name`.

**Ключі гейтвея (API keys) зберігаються в `shop_methods.settings`** (JSON),
редагуються через `/admin/settings` вкладку "Оплата" — **не** через
`getSettingsSchema()`. Подивись `resources/views/admin/settings/tabs/payment.php`:
там є **хардкоджені по `code === 'liqpay'`** блоки полів для конкретних гейтвеїв.
**Якщо додаєш новий платіжний гейтвей — треба буде додати туди ж аналогічний
блок полів** (`code === 'newgateway'`), інакше адмін не зможе ввести ключі API.
Це наразі не абстраговано — technical debt ядра, вартий уваги, якщо плагінів
стане багато.

### Відомий, ще не виправлений архітектурний борг (навмисно відкладено)
**Вартість доставки НІКОЛИ не додається до `$total` замовлення** —
`OrderController::placeOrder()` рахує `$total` лише як суму `price × quantity`.
Навіть якщо `shop_methods.settings.cost > 0` для платної доставки, клієнт
платить лише за товар. Василь **свідомо вирішив не чіпати це в v1** (велика
зміна ядра — потрібна нова колонка в `orders`, зміни в `OrderController`,
`checkout` UI). Замість цього на `/checkout` просто виводиться текст
"Додатково за доставку від X грн" біля методу з `cost > 0` (див.
`resources/views/checkout/index.php`). **Якщо новий платіжний/shipping плагін
покладається на те, що вартість доставки враховується в сумі до оплати —
це не так, май на увазі.**

---

## 3. Shipping-плагіни — НЕМАЄ формального інтерфейсу

На відміну від платежів, у shipping **немає** `ShippingGatewayInterface`. Єдиний
приклад — `UkrposhtaShipping`. Патерн:

1. У `register()`:
   - Створити свій рядок у `shop_methods` (`type='shipping'`) при першій активації, якщо його ще нема:
     ```php
     DB::query("SELECT id FROM shop_methods WHERE type='shipping' AND code=? LIMIT 1", [self::CODE]);
     // якщо немає — INSERT з дефолтними settings (JSON)
     ```
   - Підписатись на хук `admin.shipping_method_settings` (`addAction`, 2 аргументи:
     `$method`, `$settings`) — рендерить свій блок полів у адмінці "Доставка" для
     свого методу (перевіряй `$method['code'] === self::CODE`).
   - Підписатись на `checkout.delivery_fields` (`addAction`, 0 аргументів) —
     рендерить свої додаткові поля на чекауті (напр. вибір відділення).
   - Опційно: `logistics.carrier_status` (`addFilter`, 3 аргументи: `$status,
     $order, $method`) — для синхронізації статусу ТТН.
   - Опційно: `logistics.lookup` (`addFilter`, 4 аргументи) — для автокомплітів
     адрес/відділень через AJAX (`LogisticsLookupController`).
2. `getSettingsSchema()` повертає `[]` — налаштування рендеряться через хук
   `admin.shipping_method_settings`, не через генерик-схему.

**Вартість доставки:** `shop_methods.settings.cost` (рядок/число). Читається
у `/cart` (чи всі активні методи безкоштовні → показати "Безкоштовно" чи
нейтральний текст) і на `/checkout` (текст "Додатково за доставку від X").
**Не бере участі в розрахунку total замовлення** (див. розділ 2, борг).

---

## 4. Локалізація плагінів — суворе правило, без хардкоду

**Кожен плагін має `lang/ua.json` + `lang/en.json`** (плаский `{"key": "значення"}`),
і приватний метод `t()`/`translate()` всередині класу плагіна:

```php
private function t(string $key, string $default = ''): string
{
    static $translations = null;
    if ($translations === null) {
        $lang = function_exists('get_current_language') ? get_current_language() : 'ua';
        $file = __DIR__ . '/lang/' . ($lang === 'en' ? 'en' : 'ua') . '.json';
        $translations = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    }
    return $translations[$key] ?? $default;
}
```

Якщо `register()` створює **вкладений анонімний клас** (напр. для
`PaymentGatewayInterface`), метод `t()` потрібен **в обох класах** (зовнішньому і
вкладеному) — вони не діляться приватними методами. Дивись
`plugins/LiqPayGateway/plugin.php` як канонічний зразок (там і зовнішній, і
вкладений клас мають власний ідентичний `t()`).

**Що треба перекладати:** усе, що бачить клієнт або адмін — `getLabel()`,
повідомлення `PaymentResult::error()`, тексти в `getSettingsSchema()`
(`label`/`hint`/`default`), будь-який echo/print у власних admin-сторінках,
тексти на чекауті.

**Що НЕ треба перекладати:** записи в лог-файли (`storage/logs/*.log`) — це
для розробника/адміна, читається grep'ом, не для кінцевого користувача. Дивись
`WebhookResult::$message` — у коментарі класу прямо зазначено "опис для логу".

---

## 5. Повний список хуків ядра (перевірено грепом по всьому коду)

| Хук | Тип | Аргументи | Де викликається |
|---|---|---|---|
| `theme.head` | action | — | усі 3 теми, в `<head>` |
| `theme.footer` | action | — | усі 3 теми, перед `</footer>` |
| `checkout.delivery_fields` | action | `$deliveryMethods` | `checkout/index.php` |
| `checkout.summary.before_total` | action | `$total` | `checkout/index.php` |
| `cart.summary.before_total` | action | — | `cart/index.php` |
| `cart.add_item` | action | `$id, $quantity, $selectedOptionIds` | `CartController::add()` |
| `order.placed` | action | `['order_id','user_id','total']` | `CheckoutController` |
| `order.created` | action | `$orderId, $paymentMethodCode, $total` | `OrderController` |
| `order.status_changed` | action | `$orderId, $status, ''` | `PaymentWebhookController` |
| `product.updated` | action | `$id, $changedFields` | `AdminProductController` |
| `product.summary.after` | action | `$product` | `products/show.php` |
| `profile.content.before` | action | `$user` | `auth/profile.php` |
| `auth.success` | action | `$user` | `AuthController` |
| `admin.shipping_method_settings` | action | `$method, $settings` | `AdminController` (рендер налаштувань доставки) |
| `product.price` | **filter** | `$price, $product` | `ProductController`, `SearchController` |
| `product.description` | **filter** | `$description, $productId` | `ProductController` |
| `logistics.carrier_status` | **filter** | `$status, $order, $method` | `AdminOrderController` |
| `logistics.lookup` | **filter** | `$result, $carrier, $action, $params` | `LogisticsLookupController` |

`addAction($hook, $callback, $priority=10, $acceptedArgs=99)` /
`doAction($hook, ...$args)` — виконує всі callback'и, нічого не повертає.
`addFilter(...)` / `applyFilters($hook, $value, ...$args)` — кожен callback
отримує поточне `$value` і повертає нове (або `null`/незмінене, якщо хук не
для нього — дивись патерн `if ($method['code'] !== self::CODE) { return
is_array($result) ? $result : null; }` у `UkrposhtaShipping`).

**Якщо новому плагіну потрібен хук, якого немає в цьому списку** — доведеться
додати `do_action()`/`apply_filters()` у відповідне місце ядра (контролер або
view). Це нормально й вже траплялось (`checkout.delivery_fields` явно існує
заради `UkrposhtaShipping`).

---

## 6. Тестування — обов'язковий sandbox-процес

Встановлено й перевірено робочий флоу для живого HTTP-тестування (не
покладайся на статичний рев'ю коду для платіжних/shipping плагінів —
обов'язково ганяй реальні запити):

1. **Sandbox:** MariaDB + PHP 8.3 built-in server у контейнері.
   `apt-get install -y php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl mariadb-server`
2. **Критичне обмеження sandbox:** фонові процеси (`mysqld_safe &`, `php -S ... &`)
   **не виживають між окремими викликами bash-інструменту**. Кожен тест — це
   ОДИН атомарний виклик: рестарт MariaDB → рестарт PHP-сервера → curl-сценарій,
   усе в одному `bash_tool` виклику. Патерн:
   ```bash
   rm -f /run/mysqld/mysqld.sock /run/mysqld/mysqld.pid
   mysqld_safe --datadir=/var/lib/mysql > /tmp/mariadb.log 2>&1 &
   for i in $(seq 1 20); do mysqladmin ping 2>/dev/null && break; sleep 1; done
   php -S 127.0.0.1:8091 -t public storage/testing/router.php > /tmp/php-server.log 2>&1 &
   sleep 2
   # ... curl-тести тут ...
   ```
3. **Немає composer/vendor** — мережа sandbox блокує Packagist. Потрібен
   рукописний `vendor/autoload.php` (PSR-4 для `App\`, простий). PHPMailer не
   потрібен для тестування платежів/доставки — не витрачай час на нього, якщо
   плагін не відправляє email.
4. **Реалістичні HTTP-заголовки — критично важливо.** Найбільша власна помилка
   цієї сесії: тестування з `-H "Accept-Language:"` (порожній) замість
   реалістичного `-H "Accept-Language: uk-UA,uk;q=0.9,en-US;q=0.8"` **приховало
   реальний баг** (мова браузера завжди перебивала адмінське налаштування).
   **Завжди тестуй з реалістичними заголовками**, не тільки з "чистими".
5. **Логін адміна:** якщо пароль невідомий — можна скинути bcrypt-хеш напряму
   в БД для sandbox-тестування (`php -r "echo password_hash('Test1234!',
   PASSWORD_BCRYPT);"` → `UPDATE users SET password=... WHERE id=...`).
6. **CSRF:** токен один на сесію, лежить у кількох формах на сторінці —
   перший знайдений `name="csrf" value="..."` підходить для будь-якої форми в
   тій самій сесії.
7. **`/admin/settings?tab=X` НЕ повертає вміст вкладки** — це лише shell-сторінка,
   що робить AJAX на `/admin/settings/tab/X` (потрібен заголовок
   `X-Requested-With: XMLHttpRequest`, хоча й без нього зазвичай спрацьовує).
8. **Завжди повертай тестові дані до початкового стану** в кінці тестування
   (активна тема, мова, ключі гейтвеїв, is_active плагінів) — БД спільна між
   тестами в межах сесії.
9. Для платіжних плагінів: генеруй підпис вебхука вручну (Python `hashlib`),
   тестуй `success`/`failure`/проміжний статус/невірний підпис/відсутні ключі
   — усі 5 сценаріїв, не тільки щасливий шлях.

---

## 7. Ключові файли для орієнтації в новому чаті

```
app/Core/Payment/PaymentGatewayInterface.php   — контракт платіжного гейтвея
app/Core/Payment/PaymentResult.php             — що повертає initiate()
app/Core/Payment/WebhookResult.php             — що повертає handleWebhook()
app/Core/Payment/PaymentManager.php            — реєстр гейтвеїв, findForOrder()
app/Core/Plugin/PluginInterface.php            — базовий контракт плагіна
app/Core/Plugin/PluginManager.php              — активація, хуки, upload
app/Models/Setting.php                         — getShopMethods(), updateShopMethod()
app/Controllers/OrderController.php            — placeOrder(), виклик gateway->initiate()
app/Controllers/PaymentWebhookController.php   — POST /payment/webhook/{gateway}
app/Controllers/LogisticsLookupController.php  — AJAX-хук logistics.lookup
resources/views/admin/settings/tabs/payment.php — хардкод-блоки полів per-gateway (!)
resources/views/checkout/index.php             — рендер методів доставки/оплати
public/js/checkout.js                          — обробка PaymentResult на фронті
plugins/LiqPayGateway/plugin.php               — еталон платіжного плагіна + lang
plugins/UkrposhtaShipping/plugin.php           — еталон shipping-плагіна
plugins/StripeGateway/plugin.php               — другий приклад платіжного плагіна
```

---

## 8. Що вже зроблено в базовому релізі v1.0.0 (для контексту, не для повтору)

- 6 платних плагінів (`PromoCodes`, `SEOAnalytics`, `StripeGateway`, `SalePrice`,
  `UkrposhtaShipping`, `CallbackWidget`) винесені з базової збірки Василем вручну.
  База містить лише `LiqPayGateway` + `TestPlugin`.
- `LiqPayGateway` і `TestPlugin` — повністю протестовані живими HTTP-запитами,
  локалізовані (lang/ua.json, en.json).
- Виправлено: тема оформлення в адмінці, мова інтерфейсу за замовчуванням
  (пріоритет: сесія → адмінський дефолт, без автовизначення браузера — Василь
  сам довиправив фінальну версію `LocalizationManager.php`), адреса магазину в
  футері (`contact_address` замість хардкоду), текст доставки на `/cart` і
  `/checkout` (замість вічного "Безкоштовно"), бонусна система в
  `/admin/users/edit/` прихована з UI (backend лишився, не підключений нікуди
  далі — вважай непридатною для реального використання, поки хтось не додасть
  списання бонусів на чекауті).
- `install.sql` — чистий дистрибутивний дамп (структура + мінімальні дані
  англійською, users порожня — перша реєстрація на `/register` = адмін).
- `config/database.php` — автокопіювання `.env.example` → `.env`, якщо `.env`
  відсутній; перевірка на placeholder-значення перед спробою підключення.
- `INSTALL.md` — повна інструкція встановлення на хостинг.

## 9. Не чіпати без потреби / відомі technical debt

- **Два співіснуючі механізми міграцій:** `migrations/*.sql` (реальний, читає
  `MigrationRunner`) і окрема `database/migrations/*.php` (сирітський
  одноразовий скрипт, НЕ підхоплюється системою). Якщо новий плагін потребує
  міграцію БД — клади `.sql` у `migrations/`, не в `database/migrations/`.
- **`resources/views/admin/settings/tabs/payment.php`** має хардкод
  `if ($code === 'liqpay')`-блоки — новий платіжний гейтвей вимагає ручного
  додавання аналогічного блоку туди ж (ядро не абстрагує це автоматично).
- Вартість доставки не входить у total замовлення (розділ 2) — свідомо
  відкладено, не намагайся "тихцем" полагодити в рамках плагіна.
