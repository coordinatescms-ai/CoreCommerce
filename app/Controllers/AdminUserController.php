<?php

namespace App\Controllers;

use App\Core\Http\Csrf;
use App\Core\Mail\MailService;
use App\Core\View\View;
use App\Models\CrmUserService;
use App\Models\User;

class AdminUserController
{
    private const CRM_CART_REFRESH_SECONDS = 15;

    private function validateCsrfOrAbort()
    {
        Csrf::abortIfInvalid();
    }

    private function checkAdmin()
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requireReason(string $reason): void
    {
        if (mb_strlen(trim($reason)) < 5) {
            $_SESSION['error'] = 'Причина зміни обовʼязкова (мінімум 5 символів).';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/users'));
            exit;
        }
    }

    public function index()
    {
        $this->checkAdmin();

        $users = User::getAllForAdmin();

        View::render('admin/users/index', [
            'users' => $users,
        ], 'admin');
    }

    public function edit($id)
    {
        $this->checkAdmin();

        $userId = (int) $id;
        $user = User::findById($userId);

        if (!$user) {
            $_SESSION['error'] = 'Користувача не знайдено.';
            header('Location: /admin/users');
            exit;
        }

        $roles = User::getRoles();
        $crmData = $this->buildCrmData($user);

        View::render('admin/users/edit', [
            'user' => $user,
            'roles' => $roles,
            'crmData' => $crmData,
            'cartRefreshSeconds' => self::CRM_CART_REFRESH_SECONDS,
        ], 'admin');
    }

    private function buildCrmData(array $user): array
    {
        CrmUserService::ensureSchema();

        $userId = (int) ($user['id'] ?? 0);
        $fullName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));

        return [
            'profile' => [
                'full_name' => $fullName !== '' ? $fullName : __('crm_unknown_name'),
                'registered_at' => (string) ($user['created_at'] ?? ''),
            ],
            'locations' => CrmUserService::getLocations($userId),
            'group' => $this->resolveUserGroupSlug((int) ($user['role_id'] ?? 0)),
            'security' => [
                'is_blocked' => ((int) ($user['is_active'] ?? 1)) === 0,
            ],
            'stats' => CrmUserService::getUserStats($userId),
            'orders' => CrmUserService::getOrders($userId, 20),
            'live_cart' => CrmUserService::getLiveCart($userId),
            'wishlist' => CrmUserService::getWishlist($userId),
            'activity_log' => CrmUserService::getActivity($userId, 30),
            'bonus' => [
                'balance' => CrmUserService::getBonusBalance($userId),
            ],
            'subscriptions' => [
                'marketing_email' => CrmUserService::getMarketingEmailSubscription($userId),
            ],
        ];
    }

    private function resolveUserGroupSlug(int $roleId): string
    {
        $roleMap = [
            2 => 'vip',
            4 => 'wholesale',
        ];

        return $roleMap[$roleId] ?? 'regular';
    }

    public function update($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $userId = (int) $id;
        $user = User::findById($userId);

        if (!$user) {
            $_SESSION['error'] = 'Користувача не знайдено.';
            header('Location: /admin/users');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $password = (string) ($_POST['password'] ?? '');
        $groupReason = trim((string) ($_POST['group_reason'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Введіть коректний email.';
            header('Location: /admin/users/edit/' . $userId);
            exit;
        }

        if ($roleId <= 0 || !User::roleExists($roleId)) {
            $_SESSION['error'] = 'Оберіть коректну роль.';
            header('Location: /admin/users/edit/' . $userId);
            exit;
        }

        $existingUser = User::findByEmail($email);
        if ($existingUser && (int) $existingUser['id'] !== $userId) {
            $_SESSION['error'] = 'Цей email вже використовується іншим користувачем.';
            header('Location: /admin/users/edit/' . $userId);
            exit;
        }

        if ($roleId !== (int) ($user['role_id'] ?? 0) && mb_strlen($groupReason) < 5) {
            $_SESSION['error'] = 'Для зміни групи користувача вкажіть причину (мінімум 5 символів).';
            header('Location: /admin/users/edit/' . $userId);
            exit;
        }

        $updateData = [
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'role_id' => $roleId,
        ];

        User::update($userId, $updateData);

        if ($roleId !== (int) ($user['role_id'] ?? 0)) {
            CrmUserService::recordAudit(
                $userId,
                (int) ($_SESSION['user']['id'] ?? 0),
                'group_change',
                $groupReason,
                (string) ($user['role_id'] ?? ''),
                (string) $roleId
            );
        }

        if ($password !== '') {
            if (mb_strlen($password) < 8) {
                $_SESSION['error'] = 'Пароль повинен містити щонайменше 8 символів.';
                header('Location: /admin/users/edit/' . $userId);
                exit;
            }
            User::updatePassword($userId, $password);
        }

        $_SESSION['success'] = 'Дані користувача оновлено.';
        header('Location: /admin/users/edit/' . $userId);
        exit;
    }

    public function updateBonus($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $userId = (int) $id;
        $delta = (int) ($_POST['delta'] ?? 0);
        $reason = trim((string) ($_POST['reason'] ?? ''));

        if ($delta === 0 || mb_strlen($reason) < 5) {
            $this->jsonResponse(['success' => false, 'message' => 'Вкажіть коректну зміну бонусів та причину (мінімум 5 символів).'], 422);
        }

        $newBalance = CrmUserService::adjustBonus($userId, (int) ($_SESSION['user']['id'] ?? 0), $delta, $reason);

        $this->jsonResponse([
            'success' => true,
            'balance' => $newBalance,
        ]);
    }

    public function updateBlockStatus($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $userId = (int) $id;
        $isBlocked = (int) ($_POST['is_blocked'] ?? 0) === 1;
        $reason = trim((string) ($_POST['reason'] ?? ''));

        if (mb_strlen($reason) < 5) {
            $this->jsonResponse(['success' => false, 'message' => 'Причина для бану/розбану обовʼязкова (мінімум 5 символів).'], 422);
        }

        $targetUser = User::findById($userId);
        if (!$targetUser) {
            $this->jsonResponse(['success' => false, 'message' => 'Користувача не знайдено.'], 404);
        }

        User::setActive($userId, !$isBlocked);
        CrmUserService::recordAudit(
            $userId,
            (int) ($_SESSION['user']['id'] ?? 0),
            $isBlocked ? 'ban' : 'unban',
            $reason,
            (string) ((int) ($targetUser['is_active'] ?? 1)),
            $isBlocked ? '0' : '1'
        );

        CrmUserService::recordActivity(
            $userId,
            $isBlocked ? 'ban' : 'unban',
            $isBlocked ? 'Користувача заблоковано адміністратором' : 'Користувача розблоковано адміністратором'
        );

        $this->jsonResponse(['success' => true]);
    }

    public function updateSubscription($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $userId = (int) $id;
        $marketingEmail = (int) ($_POST['marketing_email'] ?? 0) === 1;

        CrmUserService::setMarketingEmailSubscription($userId, $marketingEmail);

        CrmUserService::recordActivity(
            $userId,
            'subscription_update',
            $marketingEmail ? 'Увімкнено email-розсилку' : 'Вимкнено email-розсилку'
        );

        $this->jsonResponse(['success' => true]);
    }

    public function sendEmail($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $userId = (int) $id;
        $user = User::findById($userId);

        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Користувача не знайдено.'], 404);
        }

        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($subject === '' || $message === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Тема та текст листа є обовʼязковими.'], 422);
        }

        $mailService = new MailService();
        $sent = $mailService->send((string) $user['email'], $subject, nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')));

        if (!$sent) {
            $this->jsonResponse(['success' => false, 'message' => 'Не вдалося відправити лист. Перевірте SMTP налаштування.'], 500);
        }

        CrmUserService::recordActivity($userId, 'email_sent', 'Адміністратор надіслав email: ' . $subject);

        $this->jsonResponse(['success' => true]);
    }

    public function createOrder($id)
    {
        $this->checkAdmin();

        $userId = (int) $id;
        $user = User::findById($userId);

        if (!$user) {
            $_SESSION['error'] = 'Користувача не знайдено.';
            header('Location: /admin/users');
            exit;
        }

        $shippingMethods = array_values(array_filter(
            \App\Models\Setting::getShopMethods('shipping'),
            static fn($m) => (int)($m['is_active'] ?? 0) === 1
        ));
        $paymentMethods = array_values(array_filter(
            \App\Models\Setting::getShopMethods('payment'),
            static fn($m) => (int)($m['is_active'] ?? 0) === 1
        ));

        View::render('admin/users/create_order', [
            'user'            => $user,
            'shippingMethods' => $shippingMethods,
            'paymentMethods'  => $paymentMethods,
            'csrf'            => \App\Core\Http\Csrf::token(),
            'allowedStatuses' => [
                'new','confirmed','processing','shipped',
                'delivered','completed','cancelled','returned',
            ],
        ], 'admin');
    }

    public function storeOrder($id)
    {
        $this->checkAdmin();

        $userId = (int) $id;

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Метод не підтримується'], 405);
        }

        $rawInput = file_get_contents('php://input') ?: '';
        $payload  = json_decode($rawInput, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        // Перевірка CSRF
        $token = trim((string)($payload['csrf'] ?? ''));
        if (!hash_equals((string)($_SESSION['csrf'] ?? ''), $token)) {
            $this->jsonResponse(['success' => false, 'message' => 'CSRF токен недійсний'], 419);
        }

        $user = User::findById($userId);
        if (!$user) {
            $this->jsonResponse(['success' => false, 'message' => 'Користувача не знайдено'], 404);
        }

        // Делегуємо нормалізацію та збереження AdminOrderController
        $orderController = new \App\Controllers\AdminOrderController();

        // Підставляємо user_id у payload — saveOrder його ігнорував,
        // тому зберігаємо через власний INSERT нижче
        try {
            $allowedStatuses = [
                'new','confirmed','processing','shipped',
                'delivered','completed','cancelled','returned',
            ];

            $customerName  = trim((string)($payload['customer_name'] ?? ''));
            $customerPhone = trim((string)($payload['customer_phone'] ?? ''));
            $customerEmail = trim((string)($payload['customer_email'] ?? ''));
            $deliveryMethod = trim((string)($payload['delivery_method'] ?? ''));
            $deliveryCity   = trim((string)($payload['delivery_city'] ?? ''));
            $deliveryWarehouse = trim((string)($payload['delivery_warehouse'] ?? ''));
            $deliveryAddress = trim((string)($payload['delivery_address'] ?? ''));
            $paymentMethod  = trim((string)($payload['payment_method'] ?? ''));
            $comment        = trim((string)($payload['comment'] ?? ''));
            $status         = trim((string)($payload['status'] ?? 'new'));

            if ($customerName === '' || mb_strlen($customerName) < 2) {
                throw new \InvalidArgumentException('Вкажіть коректне імʼя клієнта');
            }
            $phoneMask = normalize_phone_mask((string)\App\Models\Setting::get('phone_mask', '+38 (###) ###-##-##'));
            if (!is_phone_matching_mask($customerPhone, $phoneMask)) {
                throw new \InvalidArgumentException('Вкажіть коректний номер телефону');
            }
            if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL) === false) {
                throw new \InvalidArgumentException('Невірний формат email');
            }
            if (!in_array($status, $allowedStatuses, true)) {
                throw new \InvalidArgumentException('Невідомий статус замовлення');
            }

            $itemsPayload = $payload['items'] ?? [];
            if (!is_array($itemsPayload) || count($itemsPayload) === 0) {
                throw new \InvalidArgumentException('Додайте хоча б один товар');
            }

            $normalizedItems = [];
            $total = 0.0;
            foreach ($itemsPayload as $i => $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $qty       = (int)($item['qty'] ?? 0);
                if ($productId <= 0 || $qty <= 0) {
                    throw new \InvalidArgumentException('Некоректні дані товару у рядку ' . ($i + 1));
                }
                $product = \App\Core\Database\DB::query(
                    'SELECT id, price FROM products WHERE id = ?', [$productId]
                )->fetch(\PDO::FETCH_ASSOC);
                if (!$product) {
                    throw new \InvalidArgumentException('Товар ID ' . $productId . ' не знайдено');
                }
                $price = isset($item['price']) && $item['price'] !== ''
                    ? (float)$item['price']
                    : (float)($product['price'] ?? 0);
                if ($price < 0) {
                    throw new \InvalidArgumentException('Ціна не може бути відʼємною');
                }
                $normalizedItems[] = [
                    'product_id' => $productId,
                    'qty'        => $qty,
                    'price'      => round($price, 2),
                ];
                $total += $qty * $price;
            }

            DB::beginTransaction();

            \App\Core\Database\DB::query(
                'INSERT INTO orders
                    (user_id, total, customer_name, customer_phone, customer_email,
                     delivery_method, delivery_city, delivery_warehouse, delivery_address,
                     payment_method, comment, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $userId,
                    round($total, 2),
                    $customerName,
                    $customerPhone,
                    $customerEmail,
                    $deliveryMethod,
                    $deliveryCity,
                    $deliveryWarehouse,
                    $deliveryAddress,
                    $paymentMethod,
                    $comment,
                    $status,
                ]
            );
            $orderId = (int)DB::lastInsertId();

            foreach ($normalizedItems as $item) {
                \App\Core\Database\DB::query(
                    'INSERT INTO order_items (order_id, product_id, qty, price, selected_options)
                     VALUES (?, ?, ?, ?, ?)',
                    [$orderId, $item['product_id'], $item['qty'], $item['price'], null]
                );
            }

            DB::commit();

            \App\Models\CrmUserService::recordActivity(
                $userId,
                'order_created',
                'Адміністратор створив замовлення #' . $orderId
            );

            $this->jsonResponse([
                'success'  => true,
                'message'  => 'Замовлення #' . $orderId . ' створено',
                'order_id' => $orderId,
                'redirect' => '/admin/orders/details/' . $orderId,
            ]);

        } catch (\InvalidArgumentException $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
            }
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
            }
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function liveCart($id)
    {
        $this->checkAdmin();

        $userId = (int) $id;
        $items = CrmUserService::getLiveCart($userId);

        $this->jsonResponse([
            'success' => true,
            'items' => $items,
        ]);
    }

    public function delete($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $userId = (int) $id;

        if (!empty($_SESSION['user']['id']) && (int) $_SESSION['user']['id'] === $userId) {
            $_SESSION['error'] = 'Неможливо видалити власний обліковий запис.';
            header('Location: /admin/users');
            exit;
        }

        if (User::delete($userId)) {
            $_SESSION['success'] = 'Користувача видалено.';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні користувача.';
        }

        header('Location: /admin/users');
        exit;
    }
}