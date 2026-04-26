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
