<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Models\User;

class AdminUserController
{
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

        View::render('admin/users/edit', [
            'user' => $user,
            'roles' => $roles,
        ], 'admin');
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
