<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Models\User;

class AuthController
{
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
        if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
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

        // Встановити сесію
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
        ];

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
        if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
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

        // Створити користувача
        $result = User::create([
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
        ]);

        if (!$result) {
            $_SESSION['error'] = __('registration_failed');
            header('Location: /register');
            exit;
        }

        $_SESSION['success'] = __('registration_successful_please_login');
        header('Location: /login');
        exit;
    }

    /**
     * Вихід
     */
    public function logout()
    {
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
        if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
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

        // Відправити email (для тестування просто показуємо посилання)
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_user_id'] = $user['id'];
        $_SESSION['success'] = __('password_reset_link_sent_to_email');
        
        // У реальному додатку тут було б відправлення email
        // sendResetEmail($user['email'], $token);

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
        if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
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
        if (empty($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $user = User::findById($_SESSION['user']['id']);
        View::render('auth/profile', ['user' => $user]);
    }
}
