<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
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
        $crmData = $this->buildMockCrmData($user);

        View::render('admin/users/edit', [
            'user' => $user,
            'roles' => $roles,
            'crmData' => $crmData,
            'cartRefreshSeconds' => self::CRM_CART_REFRESH_SECONDS,
        ], 'admin');
    }

    private function buildMockCrmData(array $user): array
    {
        $fullName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
        $registeredAt = (string) ($user['created_at'] ?? '');

        return [
            'profile' => [
                'full_name' => $fullName !== '' ? $fullName : __('crm_unknown_name'),
                'registered_at' => $registeredAt,
            ],
            'locations' => [
                'primary' => __('crm_mock_primary_address'),
                'additional' => [
                    __('crm_mock_additional_address_1'),
                    __('crm_mock_additional_address_2'),
                ],
            ],
            'group' => 'regular',
            'security' => [
                'is_blocked' => ((int) ($user['is_active'] ?? 1)) === 0,
            ],
            'stats' => [
                'orders_count' => 12,
                'ltv' => 28750.40,
                'average_check' => 2395.87,
                'last_order_at' => '2026-04-21 18:40:00',
            ],
            'orders' => [
                ['id' => 1408, 'date' => '2026-04-21', 'total' => 4899.00, 'status' => __('crm_order_status_completed')],
                ['id' => 1382, 'date' => '2026-04-09', 'total' => 1650.00, 'status' => __('crm_order_status_shipped')],
                ['id' => 1337, 'date' => '2026-03-30', 'total' => 923.00, 'status' => __('crm_order_status_processing')],
            ],
            'live_cart' => [
                ['product' => __('crm_mock_product_1'), 'qty' => 1, 'price' => 2199.00],
                ['product' => __('crm_mock_product_2'), 'qty' => 2, 'price' => 349.00],
            ],
            'wishlist' => [
                __('crm_mock_wishlist_1'),
                __('crm_mock_wishlist_2'),
                __('crm_mock_wishlist_3'),
            ],
            'activity_log' => [
                __('crm_mock_activity_login'),
                __('crm_mock_activity_viewed'),
                __('crm_mock_activity_cart_add'),
            ],
            'bonus' => [
                'balance' => 420,
            ],
            'subscriptions' => [
                'marketing_email' => true,
                'marketing_sms' => false,
            ],
        ];
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

        $updateData = [
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'role_id' => $roleId,
        ];

        User::update($userId, $updateData);

        if ($password !== '') {
            if (mb_strlen($password) < 8) {
                $_SESSION['error'] = 'Пароль повинен містити щонайменше 8 символів.';
                header('Location: /admin/users/edit/' . $userId);
                exit;
            }
            User::updatePassword($userId, $password);
        }

        $_SESSION['success'] = 'Дані користувача оновлено.';
        header('Location: /admin/users');
        exit;
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
