# Плагін StripeGateway — інструкція для адміна магазину

## Встановлення (3 кроки)

1. Скопіюйте теку `plugins/StripeGateway/` у вашу `plugins/`.
2. Скопіюйте `resources/views/admin/settings/tabs/payment.php` поверх
   існуючого файлу (одна технічна правка ядра, без якої плагін не показав
   би свої поля — див. розділ нижче).
3. Адмінка → **Плагіни** → «Stripe — оплата карткою» → **Активувати**.

Все. Метод оплати «Оплата карткою (Stripe)» вже сам з'явився в
**Налаштування → Оплата** — плагін створює його автоматично при активації,
руками нічого добавляти не треба.

## Введення ключів

1. Адмінка → **Налаштування → Оплата** → знайдіть картку
   «Оплата карткою (Stripe)».
2. Заповніть три поля:
   - **Publishable Key** (`pk_live_...` / `pk_test_...`)
   - **Secret Key** (`sk_live_...` / `sk_test_...`)
   - **Webhook Secret** (`whsec_...`, беремо з Stripe Dashboard, крок нижче)
3. Увімкніть перемикач **«Увімкнено»**.
4. **«Зберегти всі зміни»**.

Готово — метод оплати працює на чекауті.

## Webhook у Stripe Dashboard (один раз)

1. Stripe Dashboard → **Developers → Webhooks → Add endpoint**.
2. URL показаний прямо під полями ключів в адмінці:
   `https://ваш-сайт/payment/webhook/stripe`
3. Підпишіться на події:
   `checkout.session.completed`, `checkout.session.async_payment_succeeded`,
   `checkout.session.async_payment_failed`, `checkout.session.expired`.
4. Скопіюйте **Signing secret** (`whsec_...`) → вставте в поле
   **Webhook Secret** в адмінці → збережіть.

## Перевірка

Тестова картка Stripe: `4242 4242 4242 4242`, будь-яка майбутня дата, будь-який CVC.

---

## Технічні деталі (для розробника, не для щоденної роботи)

<details>
<summary>Розгорнути</summary>

- Плагін повністю ізольований: єдина точка контакту з ядром —
  `App\Core\Payment\PaymentGatewayInterface`, реєстрація через
  `PaymentManager::register()` (як `LiqPayGateway`).
- При кожному запиті, поки плагін активний, `register()` перевіряє чи є
  в `shop_methods` рядок з `code='stripe'` — якщо нема, створює його сам
  (`is_active=0` за замовчуванням, доки ви самі не введете ключі й не
  увімкнете). `code` має `UNIQUE KEY` в БД, тож дублікат неможливий навіть
  за гонки запитів.
- Правка ядра (1 рядок): `do_action('admin.payment_method_settings', ...)`
  у `resources/views/admin/settings/tabs/payment.php` — той самий підхід,
  що вже є для доставки (`admin.shipping_method_settings`). Це дозволяє
  плагіну самому малювати свої 3 поля, без хардкоду в core view.
- Ключі зберігаються в `shop_methods.settings` (JSON), як у LiqPay.
- Оплата: Stripe Checkout Session через прямий `cURL` (без SDK).
- Вебхук: підпис `Stripe-Signature` перевіряється вручну
  (`hash_hmac('sha256', ...)`, захист від replay-атак — 5 хв допуск).
- Усі помилки (cURL, Stripe API, невалідний JSON) — `try/catch`, лог у
  `storage/logs/payment.log` з префіксом `[STRIPE]`, покупцю — загальне
  повідомлення без внутрішніх деталей.
- Тексти, які бачить покупець/адмін, — через `lang/ua.json` / `lang/en.json`.
  Діагностичні рядки в `payment.log` — хардкод (як і в LiqPay), бо це для
  розробника, а не частина мови інтерфейсу сайту.

</details>
