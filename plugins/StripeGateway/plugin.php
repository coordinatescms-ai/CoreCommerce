<?php

use App\Core\Plugin\PluginInterface;
use App\Core\Plugin\PluginManager;
use App\Core\Payment\PaymentManager;
use App\Core\Payment\PaymentGatewayInterface;
use App\Core\Payment\PaymentResult;
use App\Core\Payment\WebhookResult;
use App\Core\Database\DB;

/**
 * Платіжний плагін Stripe (Stripe Checkout).
 *
 * Повністю ізольований: єдина точка контакту з ядром —
 * App\Core\Payment\PaymentGatewayInterface (initiate/handleWebhook),
 * реєстрація через PaymentManager::register(), як і LiqPayGateway.
 *
 * Ключі читаються з shop_methods.settings де code = 'stripe'
 * (той самий підхід, що й у LiqPayGateway — конфігурація в адмінці,
 * а не через PluginInterface::getSettingsSchema()).
 *
 * Поля в адмінці рендеряться через хук `admin.payment_method_settings`
 * (той самий патерн, що й `admin.shipping_method_settings` у
 * UkrposhtaShipping) — плагін сам малює свої поля, ядро про Stripe
 * нічого не знає.
 *
 * Немає жодної залежності від stripe-php SDK — усі виклики API
 * робляться напряму через cURL (як і LiqPay без SDK), тож плагін
 * ставиться drop-in, без composer install.
 *
 * Webhook URL для Stripe Dashboard → Developers → Webhooks:
 *   https://your-site.com/payment/webhook/stripe
 *   Події: checkout.session.completed, checkout.session.async_payment_succeeded,
 *          checkout.session.async_payment_failed, checkout.session.expired
 */
return new class implements PluginInterface {

    public function getName(): string    { return 'StripeGateway'; }
    public function getVersion(): string { return '1.0.0'; }

    public function register(PluginManager $pluginManager): void
    {
        // Автоматично створюємо метод оплати "stripe" в shop_methods,
        // якщо його ще нема — адміну більше НЕ треба вручну тиснути
        // "Додати спосіб оплати" і вписувати gateway_name. Просто:
        // завантажив плагін → активував → зайшов у Оплата → ввів ключі → готово.
        $this->ensureShopMethodExists();

        // Реєструємо шлюз у PaymentManager ядра
        PaymentManager::register(new class implements PaymentGatewayInterface {

            /** Допустиме розходження часу вебхука (захист від replay-атак), сек. */
            private const WEBHOOK_TOLERANCE_SECONDS = 300;

            public function getName(): string  { return 'stripe'; }
            public function getLabel(): string { return $this->t('label_stripe', 'Stripe — оплата карткою'); }

            // ── Ініціація платежу ────────────────────────────────────────────

            public function initiate(int $orderId, float $amount, array $meta = []): PaymentResult
            {
                [, $secretKey] = $this->loadKeys();

                if ($secretKey === '') {
                    return PaymentResult::error(
                        $this->t('error_not_configured', 'Stripe не налаштовано: відсутній Secret Key. Заповніть його в Адмінка → Налаштування → Оплата.')
                    );
                }

                if ($amount <= 0) {
                    return PaymentResult::error($this->t('error_invalid_amount', 'Некоректна сума замовлення для оплати Stripe.'));
                }

                try {
                    $checkoutUrl = $this->createCheckoutSession($orderId, $amount, $meta, $secretKey);
                } catch (\Throwable $e) {
                    // Ловимо ВСЕ: помилки cURL, помилки Stripe API, некоректний JSON тощо.
                    // Клієнту показуємо загальне повідомлення, деталі — лише в лог.
                    $this->log('initiate() EXCEPTION: order_id=' . $orderId . ' — ' . $e->getMessage());
                    return PaymentResult::error(
                        $this->t('error_session_failed', 'Не вдалося створити сесію оплати Stripe. Спробуйте ще раз або оберіть інший спосіб оплати.')
                    );
                }

                return PaymentResult::redirect($checkoutUrl, ['order_id' => $orderId]);
            }

            /**
             * Створити Stripe Checkout Session через прямий виклик API (без SDK).
             *
             * @throws \RuntimeException якщо cURL або Stripe API повернули помилку
             */
            private function createCheckoutSession(int $orderId, float $amount, array $meta, string $secretKey): string
            {
                $siteUrl  = rtrim((string) \App\Models\Setting::get('site_url', ''), '/');
                $currency = strtolower($this->currencyCode());

                $params = [
                    'mode'                 => 'payment',
                    'client_reference_id'  => (string) $orderId,
                    'success_url'          => $siteUrl . '/thank-you?order_id=' . $orderId . '&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'           => $siteUrl . '/checkout',
                    'line_items'           => [[
                        'quantity'   => 1,
                        'price_data' => [
                            'currency'     => $currency,
                            // Stripe очікує суму в мінімальних одиницях валюти (копійки/центи)
                            'unit_amount'  => (int) round($amount * 100),
                            'product_data' => [
                                'name' => (string) ($meta['description'] ?? ($this->t('order_fallback_name', 'Замовлення #') . $orderId)),
                            ],
                        ],
                    ]],
                ];

                $email = trim((string) ($meta['email'] ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $params['customer_email'] = $email;
                }

                $response = $this->callStripeApi('https://api.stripe.com/v1/checkout/sessions', $params, $secretKey);

                $checkoutUrl = (string) ($response['url'] ?? '');
                if ($checkoutUrl === '') {
                    throw new \RuntimeException('Stripe не повернув URL сесії оплати (порожнє поле "url").');
                }

                $this->log(sprintf(
                    'Checkout Session створено: order_id=%d session_id=%s',
                    $orderId,
                    (string) ($response['id'] ?? '?')
                ));

                return $checkoutUrl;
            }

            /**
             * Низькорівневий виклик Stripe REST API через cURL.
             * Кидає \RuntimeException при будь-якій помилці транспорту або API.
             *
             * @throws \RuntimeException
             */
            private function callStripeApi(string $url, array $params, string $secretKey): array
            {
                $ch = curl_init($url);
                if ($ch === false) {
                    throw new \RuntimeException('Не вдалося ініціалізувати cURL.');
                }

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    // Stripe API приймає стандартний application/x-www-form-urlencoded
                    // з "вкладеними" ключами на кшталт line_items[0][price_data][currency] —
                    // саме так їх і генерує http_build_query().
                    CURLOPT_POSTFIELDS     => http_build_query($params),
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: Bearer ' . $secretKey,
                        'Content-Type: application/x-www-form-urlencoded',
                    ],
                    CURLOPT_TIMEOUT        => 20,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);

                $response = curl_exec($ch);

                if ($response === false) {
                    $curlError = curl_error($ch);
                    $curlErrno = curl_errno($ch);
                    curl_close($ch);
                    throw new \RuntimeException("cURL error #{$curlErrno}: {$curlError}");
                }

                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $decoded = json_decode((string) $response, true);

                if ($httpCode >= 400) {
                    $apiMessage = is_array($decoded) ? (string) ($decoded['error']['message'] ?? '') : '';
                    throw new \RuntimeException(
                        $apiMessage !== '' ? "Stripe API: {$apiMessage}" : "Stripe API повернув HTTP {$httpCode}"
                    );
                }

                if (!is_array($decoded)) {
                    throw new \RuntimeException('Stripe API повернув некоректний JSON.');
                }

                return $decoded;
            }

            // ── Вебхук від Stripe ────────────────────────────────────────────

            public function handleWebhook(array $postData, string $rawBody, array $headers): WebhookResult
            {
                try {
                    [, , $webhookSecret] = $this->loadKeys();

                    if ($webhookSecret === '') {
                        return WebhookResult::invalid('Stripe Webhook Secret не налаштовано в адмінці.');
                    }

                    // Заголовки в ядрі нормалізуються у ВЕРХНІЙ регістр з "-" замість "_"
                    // (App\Controllers\PaymentWebhookController::extractHeaders()).
                    $signatureHeader = $headers['STRIPE-SIGNATURE'] ?? '';
                    if ($signatureHeader === '') {
                        return WebhookResult::invalid('Відсутній заголовок Stripe-Signature.');
                    }

                    if (!$this->verifySignature($rawBody, $signatureHeader, $webhookSecret)) {
                        $this->log('Webhook: підпис Stripe-Signature не пройшов перевірку.');
                        return WebhookResult::invalid('Invalid Stripe signature.');
                    }

                    $event = json_decode($rawBody, true);
                    if (!is_array($event)) {
                        return WebhookResult::invalid('Не вдалося розібрати JSON тіла вебхука.');
                    }

                    $type    = (string) ($event['type'] ?? '');
                    $object  = (array) ($event['data']['object'] ?? []);
                    $orderId = (int) ($object['client_reference_id'] ?? 0);

                    $this->log(sprintf('Webhook OK: type=%s order_id=%d', $type, $orderId));

                    if ($orderId <= 0) {
                        // Подія від Stripe, яка не стосується наших замовлень
                        // (напр. тестовий пінг) — відповідаємо 200, щоб Stripe не ретраїв.
                        return WebhookResult::pending(0, "Webhook без client_reference_id (type={$type}), ігноруємо");
                    }

                    if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
                        $paymentStatus = (string) ($object['payment_status'] ?? '');

                        if ($type === 'checkout.session.async_payment_succeeded' || $paymentStatus === 'paid') {
                            return WebhookResult::paid($orderId, "Stripe: оплата підтверджена ({$type})");
                        }

                        // Сесія завершена, але оплата ще обробляється (напр. банківський переказ) —
                        // остаточне підтвердження прийде окремою подією async_payment_succeeded.
                        return WebhookResult::pending(
                            $orderId,
                            "Stripe: сесія завершена, оплата в обробці (payment_status={$paymentStatus})"
                        );
                    }

                    if (in_array($type, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
                        return WebhookResult::failed($orderId, "Stripe: оплата не відбулась ({$type})");
                    }

                    // Інші типи подій нас не цікавлять — приймаємо, статус не змінюємо.
                    return WebhookResult::pending($orderId, "Stripe: подія {$type} отримана, без дій");

                } catch (\Throwable $e) {
                    $this->log('Webhook EXCEPTION: ' . $e->getMessage());
                    return WebhookResult::invalid('Internal error while processing Stripe webhook.', 500);
                }
            }

            /**
             * Перевірка підпису Stripe-Signature без SDK.
             * Формат заголовка: "t=1614556800,v1=abcdef...,v1=..." (може бути кілька v1 при ротації секрету).
             */
            private function verifySignature(string $payload, string $signatureHeader, string $secret): bool
            {
                $parts = [];
                foreach (explode(',', $signatureHeader) as $pair) {
                    $kv = explode('=', trim($pair), 2);
                    if (count($kv) === 2) {
                        $parts[$kv[0]][] = $kv[1];
                    }
                }

                $timestamp  = $parts['t'][0] ?? null;
                $signatures = $parts['v1'] ?? [];

                if ($timestamp === null || !ctype_digit($timestamp) || empty($signatures)) {
                    return false;
                }

                // Захист від replay-атак: підпис дійсний лише протягом WEBHOOK_TOLERANCE_SECONDS
                if (abs(time() - (int) $timestamp) > self::WEBHOOK_TOLERANCE_SECONDS) {
                    return false;
                }

                $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

                foreach ($signatures as $signature) {
                    if (hash_equals($expected, $signature)) {
                        return true;
                    }
                }

                return false;
            }

            public function getSettingsSchema(): array { return []; }

            // ── Private helpers ───────────────────────────────────────────────

            /** @return array{0:string,1:string,2:string} [publishable_key, secret_key, webhook_secret] */
            private function loadKeys(): array
            {
                // Шукаємо серед УСІХ методів оплати, а не WHERE code = 'stripe',
                // бо метод, доданий кнопкою «Додати спосіб оплати», завжди має
                // code = custom_<timestamp> — прив'язка до нашого шлюзу
                // відбувається через settings.gateway_name (див. коментар біля
                // хука admin.payment_method_settings вище).
                $rows = DB::query(
                    "SELECT code, settings FROM shop_methods WHERE type = 'payment'"
                )->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($rows as $row) {
                    $settings    = json_decode((string) ($row['settings'] ?? '{}'), true) ?: [];
                    $gatewayName = (string) ($settings['gateway_name'] ?? $row['code']);

                    if ((string) $row['code'] === 'stripe' || $gatewayName === 'stripe') {
                        return [
                            trim((string) ($settings['publishable_key'] ?? '')),
                            trim((string) ($settings['secret_key'] ?? '')),
                            trim((string) ($settings['webhook_secret'] ?? '')),
                        ];
                    }
                }

                return ['', '', ''];
            }

            private function currencyCode(): string
            {
                try {
                    $code = DB::query('SELECT code FROM currencies WHERE is_active = 1 LIMIT 1')->fetchColumn();
                } catch (\Throwable $e) {
                    $code = false;
                }
                return $code !== false && $code !== null ? (string) $code : 'UAH';
            }

            private function log(string $message): void
            {
                $logFile = dirname(__DIR__, 2) . '/storage/logs/payment.log';
                $dir     = dirname($logFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents(
                    $logFile,
                    sprintf("[%s] [STRIPE] %s\n", date('Y-m-d H:i:s'), $message),
                    FILE_APPEND | LOCK_EX
                );
            }

            /**
             * Переклад customer-facing текстів (getLabel(), помилки initiate()).
             * Внутрішні діагностичні записи в payment.log НЕ перекладаються —
             * той самий підхід, що й у LiqPayGateway (лог — для розробника/адміна,
             * читається grep-ом, а не для кінцевого користувача).
             */
            private function t(string $key, string $default = ''): string
            {
                static $translations = null;

                if ($translations === null) {
                    $lang = $this->getCurrentLanguage();
                    $file = __DIR__ . '/lang/' . $lang . '.json';
                    $translations = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];

                    // Fallback до української якщо файл не знайдено
                    if (empty($translations)) {
                        $fallbackFile = __DIR__ . '/lang/ua.json';
                        $translations = is_file($fallbackFile) ? (json_decode((string) file_get_contents($fallbackFile), true) ?: []) : [];
                    }
                }

                return $translations[$key] ?? $default;
            }

            /**
             * Отримати поточну мову з підтримкою всіх налаштованих мов
             */
            private function getCurrentLanguage(): string
            {
                // Пробуємо отримати через LocalizationManager
                if (function_exists('get_current_language')) {
                    return get_current_language();
                }

                // Fallback через сесію
                $lang = $_SESSION['lang'] ?? 'ua';

                // Перевіряємо, чи існує файл перекладу для цієї мови
                $supportedLangs = $this->getSupportedLanguages();
                if (!in_array($lang, $supportedLangs)) {
                    $lang = 'ua'; // дефолтна мова
                }

                return $lang;
            }

            /**
             * Отримати список підтримуваних мов плагіна
             */
            private function getSupportedLanguages(): array
            {
                $langDir = __DIR__ . '/lang';
                $languages = [];

                if (is_dir($langDir)) {
                    foreach (glob($langDir . '/*.json') as $file) {
                        $langCode = basename($file, '.json');
                        $languages[] = $langCode;
                    }
                }

                return $languages ?: ['ua']; // fallback якщо папка порожня
            }
        });

        // Рендер власних полів в адмінці (Налаштування → Оплата), без жодних
        // правок ядра для конкретно Stripe — той самий підхід, що й
        // admin.shipping_method_settings у плагіні UkrposhtaShipping.
        $pluginManager->addAction('admin.payment_method_settings', function (array $method, array $settings): void {
            $code        = (string) ($method['code'] ?? '');
            $gatewayName = (string) ($settings['gateway_name'] ?? $code);

            // Новий метод, доданий кнопкою «Додати спосіб оплати», завжди має
            // code = custom_<timestamp> (поле code ніде не редагується в UI) —
            // тож перевіряємо ще й settings.gateway_name, яке адмін вписує сам
            // у полі «Прив'язка до платіжного плагіна» (той самий принцип,
            // що й у PaymentManager::findForOrder()).
            if ($code === 'stripe' || $gatewayName === 'stripe') {
                $this->renderAdminFields((int) $method['id'], $settings);
            }
        }, 10, 2);
    }

    public function getSettingsSchema(): array
    {
        return []; // Ключі налаштовуються через вкладку "Оплата" в адмінці (shop_methods.settings)
    }

    // ── Авто-створення методу оплати (щоб не потрібно було нічого руками) ──

    /**
     * Гарантує, що в shop_methods є рядок з code='stripe'.
     *
     * register() викликається на КОЖЕН запит (поки плагін активний),
     * тож тут — дешева перевірка "вже існує?" і лише за потреби один INSERT.
     * `code` має UNIQUE KEY в БД, тож навіть за гіпотетичної гонки двох
     * паралельних запитів дублікат не створиться (упіймаємо виняток і
     * тихо проігноруємо — рядок все одно вже буде).
     */
    private function ensureShopMethodExists(): void
    {
        try {
            $exists = DB::query("SELECT id FROM shop_methods WHERE code = 'stripe' LIMIT 1")
                ->fetch(\PDO::FETCH_ASSOC);

            if ($exists) {
                return;
            }

            DB::query(
                "INSERT INTO shop_methods (type, code, name, description, is_active, is_test_mode, settings, sort_order)
                 VALUES ('payment', 'stripe', ?, ?, 0, 1, '{}', 20)",
                [
                    $this->t('default_method_name', 'Оплата карткою (Stripe)'),
                    $this->t('default_method_description', 'Visa / Mastercard через Stripe Checkout'),
                ]
            );
        } catch (\Throwable $e) {
            // Таблиця тимчасово недоступна або гонка запитів — не критично,
            // спробуємо ще раз на наступному запиті. Плагін не повинен
            // ламати сайт через це.
        }
    }

    // ── Рендер полів "Publishable Key / Secret Key / Webhook Secret" ────────

    private function renderAdminFields(int $methodId, array $extra): void
    {
        $siteUrl = rtrim((string) \App\Models\Setting::get('site_url', ''), '/');
        $t = fn(string $key, string $default) => $this->t($key, $default);
        ?>
        <hr>
        <p class="text-muted small mb-2">
            <i class="fas fa-puzzle-piece"></i>
            <?php echo htmlspecialchars(
                sprintf($t('hint_plugin_note', 'Ці поля читає плагін %s.'), 'StripeGateway'),
                ENT_QUOTES, 'UTF-8'
            ); ?>
            <a href="/admin/plugins"><?php echo htmlspecialchars($t('hint_enable_link', 'Увімкніть плагін у розділі Керування плагінами'), ENT_QUOTES, 'UTF-8'); ?></a>
        </p>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Publishable Key</label>
                <input type="text"
                       name="methods[<?php echo $methodId; ?>][settings][publishable_key]"
                       value="<?php echo htmlspecialchars((string) ($extra['publishable_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                       class="form-control" placeholder="pk_live_... / pk_test_...">
                <small class="text-muted"><?php echo htmlspecialchars($t('hint_publishable_key', 'Публічний ключ, безпечно показувати на фронтенді.'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Secret Key</label>
                <input type="password"
                       name="methods[<?php echo $methodId; ?>][settings][secret_key]"
                       value="<?php echo htmlspecialchars((string) ($extra['secret_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                       class="form-control" placeholder="sk_live_... / sk_test_...">
                <small class="text-muted"><?php echo htmlspecialchars($t('hint_secret_key', 'Секретний ключ — нікому не показуйте, не логуйте.'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Webhook Secret</label>
                <input type="password"
                       name="methods[<?php echo $methodId; ?>][settings][webhook_secret]"
                       value="<?php echo htmlspecialchars((string) ($extra['webhook_secret'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                       class="form-control" placeholder="whsec_...">
                <small class="text-muted"><?php echo htmlspecialchars($t('hint_webhook_secret', 'З розділу Webhooks у Stripe Dashboard (signing secret).'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
        </div>
        <div class="alert alert-info mt-3 mb-0" style="font-size:0.875rem;">
            <i class="fas fa-info-circle"></i>
            <strong>Webhook URL</strong> — <?php echo htmlspecialchars($t('hint_webhook_url', 'вкажіть цей URL у Stripe Dashboard → Developers → Webhooks (потрібні події нижче):'), ENT_QUOTES, 'UTF-8'); ?>
            <code>checkout.session.completed</code>,
            <code>checkout.session.async_payment_succeeded</code>,
            <code>checkout.session.async_payment_failed</code>,
            <code>checkout.session.expired</code>:<br>
            <code><?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/payment/webhook/stripe</code>
        </div>
        <?php
    }

    /**
     * Переклад для адмін-полів (той самий підхід, що й t() у внутрішньому
     * класі шлюзу вище — окрема копія, бо це інша анонімна PHP-сутність;
     * обидві читають ті самі lang/ua.json / lang/en.json).
     */
    private function t(string $key, string $default = ''): string
    {
        static $translations = null;

        if ($translations === null) {
            $lang = $this->getCurrentLanguage();
            $file = __DIR__ . '/lang/' . $lang . '.json';
            $translations = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];

            // Fallback до української якщо файл не знайдено
            if (empty($translations)) {
                $fallbackFile = __DIR__ . '/lang/ua.json';
                $translations = is_file($fallbackFile) ? (json_decode((string) file_get_contents($fallbackFile), true) ?: []) : [];
            }
        }

        return $translations[$key] ?? $default;
    }

    /**
     * Отримати поточну мову з підтримкою всіх налаштованих мов
     */
    private function getCurrentLanguage(): string
    {
        // Пробуємо отримати через LocalizationManager
        if (function_exists('get_current_language')) {
            return get_current_language();
        }

        // Fallback через сесію
        $lang = $_SESSION['lang'] ?? 'ua';

        // Перевіряємо, чи існує файл перекладу для цієї мови
        $supportedLangs = $this->getSupportedLanguages();
        if (!in_array($lang, $supportedLangs)) {
            $lang = 'ua'; // дефолтна мова
        }

        return $lang;
    }

    /**
     * Отримати список підтримуваних мов плагіна
     */
    private function getSupportedLanguages(): array
    {
        $langDir = __DIR__ . '/lang';
        $languages = [];

        if (is_dir($langDir)) {
            foreach (glob($langDir . '/*.json') as $file) {
                $langCode = basename($file, '.json');
                $languages[] = $langCode;
            }
        }

        return $languages ?: ['ua']; // fallback якщо папка порожня
    }
};
