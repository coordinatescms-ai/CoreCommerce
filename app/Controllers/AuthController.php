<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Models\User;
use App\Core\Mail\MailService;

class AuthController
{
    private $mailService;

    public function __construct()
    {
        $this->mailService = new MailService();
    }

    /**
     * Показати форму входу
     */
    public function showLogin()
    {
        View::render('auth/login');
    }

    /**
     * Обробити вхід
     */
    public function login()
    {
        // Перевірити CSRF токен
        if (!Csrf::isValid()) {
            return 'CSRF token validation failed';
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Валідація
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = __('email_and_password_required');
            header('Location: /login');
            exit;
        }

        // Знайти користувача
        $user = User::findByEmail($email);
        
        if (!$user || !User::verifyPassword($password, $user['password'])) {
            $_SESSION['error'] = __('invalid_email_or_password');
            header('Location: /login');
            exit;
        }

        // Перевірити, чи активний користувач
        if (!$user['is_active']) {
            $_SESSION['error'] = __('account_is_inactive');
            header('Location: /login');
            exit;
        }

        $oldSessionId = session_id();
        
        // Встановити сесію
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
        ];

        // Перенести кошик з сесії до користувача
        \App\Models\Cart::migrate($oldSessionId, $user['id']);

        // Якщо користувач вибрав "Запам'ятати мене"
        if (!empty($_POST['remember_me'])) {
            // Генерувати токен для запам'ятовування
            $remember_token = bin2hex(random_bytes(32));
            User::setRememberToken($user['id'], $remember_token);
            
            // Встановити кукі на 30 днів
            setcookie('remember_token', $remember_token, time() + (30 * 24 * 60 * 60), '/');
            setcookie('user_id', $user['id'], time() + (30 * 24 * 60 * 60), '/');
        }

        // Оновити час останнього входу
        User::updateLastLogin($user['id']);
        do_action('auth.success', $user);

        $_SESSION['success'] = __('login_successful');
        header('Location: /');
        exit;
    }

    /**
     * Показати форму реєстрації
     */
    public function showRegister()
    {
        View::render('auth/register');
    }

    /**
     * Обробити реєстрацію
     */
    public function register()
    {
        // Перевірити CSRF токен
        if (!Csrf::isValid()) {
            return 'CSRF token validation failed';
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';

        // Валідація
        $errors = [];

        if (empty($email)) {
            $errors[] = __('email_is_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('invalid_email_format');
        } elseif (User::findByEmail($email)) {
            $errors[] = __('email_already_registered');
        }

        if (empty($password)) {
            $errors[] = __('password_is_required');
        } elseif (strlen($password) < 6) {
            $errors[] = __('password_must_be_at_least_6_characters');
        }

        if ($password !== $password_confirm) {
            $errors[] = __('passwords_do_not_match');
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /register');
            exit;
        }

        // Створити користувача (деактивованим до підтвердження email)
        $token = bin2hex(random_bytes(32));
        $result = User::create([
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'is_active' => 0,
            'email_verified' => 0,
            'password_reset_token' => $token, // Використовуємо це поле тимчасово для верифікації
        ]);

        if (!$result) {
            $_SESSION['error'] = __('registration_failed');
            header('Location: /register');
            exit;
        }

        // Відправити лист підтвердження
        $confirmation_link = "http://" . $_SERVER['HTTP_HOST'] . "/verify-email/" . $token;
        $body = $this->mailService->renderTemplate('registration_confirmation', [
            'first_name' => $first_name,
            'confirmation_link' => $confirmation_link
        ]);
        
        $this->mailService->send($email, 'Підтвердження реєстрації - MySite', $body);

        $_SESSION['success'] = __('registration_successful_check_email');
        header('Location: /login');
        exit;
    }

    /**
     * Верифікація email
     */
    public function verifyEmail($token)
    {
        $user = User::query("SELECT * FROM users WHERE password_reset_token = ? AND email_verified = 0", [$token]);
        
        if (empty($user)) {
            $_SESSION['error'] = __('invalid_verification_token');
            header('Location: /login');
            exit;
        }

        $user = $user[0];
        User::execute("UPDATE users SET is_active = 1, email_verified = 1, email_verified_at = NOW(), password_reset_token = NULL WHERE id = ?", [$user['id']]);

        $_SESSION['success'] = __('email_verified_successfully');
        header('Location: /login');
        exit;
    }

    /**
     * Вихід
     */
    public function logout()
    {
        if (!Csrf::isValid()) {
            http_response_code(419);
            $_SESSION['error'] = 'CSRF token validation failed';
            header('Location: /');
            exit;
        }

        // Видалити токен запам'ятовування з бази даних
        if (!empty($_COOKIE['user_id'])) {
            User::clearRememberToken($_COOKIE['user_id']);
        }
        
        unset($_SESSION['user']);
        
        // Видалити кукі
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
        
        $_SESSION['success'] = __('logout_successful');
        header('Location: /');
        exit;
    }

    /**
     * Показати форму забуття пароля
     */
    public function showForgotPassword()
    {
        View::render('auth/forgot_password');
    }

    /**
     * Обробити запит на відновлення пароля
     */
    public function forgotPassword()
    {
        // Перевірити CSRF токен
        if (!Csrf::isValid()) {
            return 'CSRF token validation failed';
        }

        $email = $_POST['email'] ?? '';

        // Валідація
        if (empty($email)) {
            $_SESSION['error'] = __('email_is_required');
            header('Location: /forgot-password');
            exit;
        }

        // Знайти користувача
        $user = User::findByEmail($email);
        
        if (!$user) {
            // Не розкривати, чи існує користувач
            $_SESSION['success'] = __('if_email_exists_reset_link_sent');
            header('Location: /login');
            exit;
        }

        // Генерувати токен
        $token = bin2hex(random_bytes(32));
        
        // Зберегти токен
        User::setPasswordResetToken($user['id'], $token, 3600); // 1 година

        // Відправити email
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password/" . $token;
        $body = $this->mailService->renderTemplate('password_reset', [
            'first_name' => $user['first_name'] ?? 'Користувач',
            'reset_link' => $reset_link
        ]);
        
        $this->mailService->send($user['email'], 'Відновлення пароля - MySite', $body);

        $_SESSION['success'] = __('password_reset_link_sent_to_email');
        header('Location: /login');
        exit;
    }

    /**
     * Показати форму скидання пароля
     */
    public function showResetPassword($token = null)
    {
        if (!$token) {
            $_SESSION['error'] = __('invalid_reset_token');
            header('Location: /login');
            exit;
        }

        // Перевірити токен
        $user = User::verifyPasswordResetToken($token);
        
        if (!$user) {
            $_SESSION['error'] = __('reset_token_expired_or_invalid');
            header('Location: /login');
            exit;
        }

        View::render('auth/reset_password', [
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Обробити скидання пароля
     */
    public function resetPassword()
    {
        // Перевірити CSRF токен
        if (!Csrf::isValid()) {
            return 'CSRF token validation failed';
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Валідація
        if (empty($token)) {
            $_SESSION['error'] = __('invalid_reset_token');
            header('Location: /login');
            exit;
        }

        // Перевірити токен
        $user = User::verifyPasswordResetToken($token);
        
        if (!$user) {
            $_SESSION['error'] = __('reset_token_expired_or_invalid');
            header('Location: /login');
            exit;
        }

        // Валідація пароля
        if (empty($password)) {
            $_SESSION['error'] = __('password_is_required');
            header('Location: /reset-password/' . $token);
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['error'] = __('password_must_be_at_least_6_characters');
            header('Location: /reset-password/' . $token);
            exit;
        }

        if ($password !== $password_confirm) {
            $_SESSION['error'] = __('passwords_do_not_match');
            header('Location: /reset-password/' . $token);
            exit;
        }

        // Оновити пароль
        User::updatePassword($user['id'], $password);
        
        // Очистити токен
        User::clearPasswordResetToken($user['id']);

        $_SESSION['success'] = __('password_reset_successful');
        header('Location: /login');
        exit;
    }

    /**
     * Показати профіль користувача
     */
    public function showProfile()
    {
        $userId = $this->requireAuth();
        $user = User::findById($userId);

        View::render('auth/profile', [
            'user' => $user,
            'activeTab' => 'edit',
            'seo' => [
                'meta_title' => __('profile'),
            ],
        ]);
    }

    private function requireAuth(): int
    {
        if (empty($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }

        return (int) $_SESSION['user']['id'];
    }

    public function showOrders()
    {
        $userId = $this->requireAuth();
        $user = User::findById($userId);
        $orders = User::query("SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
        $orderIds = array_map(static fn(array $order): int => (int) ($order['id'] ?? 0), $orders);
        $orderIds = array_values(array_filter($orderIds, static fn(int $id): bool => $id > 0));

        $orderItemsByOrderId = [];
        if (!empty($orderIds)) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $items = User::query(
                "SELECT oi.order_id, oi.qty, oi.price, p.name AS product_name
                 FROM order_items oi
                 INNER JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id IN ($placeholders)
                 ORDER BY oi.id ASC",
                $orderIds
            );

            foreach ($items as $item) {
                $orderId = (int) ($item['order_id'] ?? 0);
                if ($orderId <= 0) {
                    continue;
                }
                if (!isset($orderItemsByOrderId[$orderId])) {
                    $orderItemsByOrderId[$orderId] = [];
                }
                $orderItemsByOrderId[$orderId][] = $item;
            }
        }

        $statusLabels = [
            'new' => __('crm_order_status_new'),
            'processing' => __('crm_order_status_processing'),
            'shipped' => __('crm_order_status_shipped'),
            'completed' => __('crm_order_status_completed'),
            'cancelled' => __('crm_order_status_cancelled'),
            'canceled' => __('crm_order_status_cancelled'),
        ];

        foreach ($orders as &$order) {
            $statusCode = (string) ($order['status'] ?? '');
            $order['status_label'] = $statusLabels[$statusCode] ?? $statusCode;
            $order['items'] = $orderItemsByOrderId[(int) ($order['id'] ?? 0)] ?? [];
        }
        unset($order);

        View::render('auth/profile', [
            'user' => $user,
            'orders' => $orders,
            'activeTab' => 'orders',
            'seo' => ['meta_title' => __('profile')],
        ]);
    }

    public function showFavorites()
    {
        $userId = $this->requireAuth();
        $user = User::findById($userId);
        $favorites = User::query("SELECT p.id, p.name, p.slug, p.price, p.image FROM favorites f INNER JOIN 
        products p ON p.id = f.product_id WHERE f.user_id = ? ORDER BY f.created_at DESC", [$userId]);

        View::render('auth/profile', [
            'user' => $user,
            'favorites' => $favorites,
            'activeTab' => 'favorites',
            'seo' => ['meta_title' => __('profile')],
        ]);
    }

    public function showProfileEdit()
    {
        $userId = $this->requireAuth();
        $user = User::findById($userId);

        View::render('auth/profile', [
            'user' => $user,
            'activeTab' => 'edit',
            'seo' => ['meta_title' => __('profile')],
        ]);
    }

    public function updateProfile()
    {
        $userId = $this->requireAuth();

        if (!Csrf::isValid()) {
            http_response_code(419);
            $_SESSION['error'] = __('csrf_token_invalid');
            header('Location: /profile/edit');
            exit;
        }

        $firstName = trim(strip_tags((string) ($_POST['first_name'] ?? '')));
        $lastName = trim(strip_tags((string) ($_POST['last_name'] ?? '')));
        $phone = trim(strip_tags((string) ($_POST['phone'] ?? '')));
        $emailRaw = trim((string) ($_POST['email'] ?? ''));
        $email = filter_var($emailRaw, FILTER_SANITIZE_EMAIL);

        $errors = [];
        if ($firstName === '' || mb_strlen($firstName) > 100) { $errors[] = __('profile_invalid_first_name'); }
        if ($lastName === '' || mb_strlen($lastName) > 100) { $errors[] = __('profile_invalid_last_name'); }
        if (!preg_match('/^[\d\+\(\)\-\s]{10,20}$/', $phone)) { $errors[] = __('profile_invalid_phone'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = __('profile_invalid_email'); }

        $existing = User::findByEmail($email);
        if ($existing && (int) $existing['id'] !== $userId) {
            $errors[] = __('email_already_registered');
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /profile/edit');
            exit;
        }

        User::update($userId, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
        ]);

        $_SESSION['user']['first_name'] = $firstName;
        $_SESSION['user']['last_name'] = $lastName;
        $_SESSION['user']['email'] = $email;

        $_SESSION['success'] = __('profile_updated_successfully');
        header('Location: /profile/edit');
        exit;
    }

}
