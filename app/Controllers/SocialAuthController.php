<?php

namespace App\Controllers;

use App\Models\Setting;
use App\Models\User;

class SocialAuthController
{
    private function loadSocialConfig(string $provider): array
    {
        $services = require __DIR__ . '/../../config/services.php';
        $providerConfig = $services[$provider] ?? [];

        $clientId = trim((string) Setting::get("{$provider}_client_id", ''));
        $clientSecret = trim((string) Setting::get("{$provider}_client_secret", ''));
        $redirectUrl = trim((string) Setting::get("{$provider}_redirect_url", ''));
        $enabled = trim((string) Setting::get("{$provider}_auth_enabled", ''));

        return [
            'enabled' => $enabled !== '' ? $enabled : (string) ($providerConfig['enabled'] ?? '0'),
            'client_id' => $clientId !== '' ? $clientId : (string) ($providerConfig['client_id'] ?? ''),
            'client_secret' => $clientSecret !== '' ? $clientSecret : (string) ($providerConfig['client_secret'] ?? ''),
            'redirect_url' => $redirectUrl !== '' ? $redirectUrl : (string) ($providerConfig['redirect'] ?? ''),
        ];
    }

    private function socialLoginOrRegister(array $payload): void
    {
        $email = trim((string) ($payload['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Не вдалося отримати email з профілю соціального акаунта.';
            header('Location: /login');
            exit;
        }

        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));

        $user = User::findByEmail($email);
        if (!$user) {
            User::create([
                'email' => $email,
                'password' => bin2hex(random_bytes(16)),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'is_active' => 1,
                'email_verified' => 1,
                'email_verified_at' => date('Y-m-d H:i:s'),
            ]);
            $user = User::findByEmail($email);
        }

        if (!$user) {
            $_SESSION['error'] = 'Не вдалося авторизувати користувача.';
            header('Location: /login');
            exit;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
        ];

        User::updateLastLogin($user['id']);

        $_SESSION['success'] = __('login_successful');
        header('Location: /');
        exit;
    }

    public function redirectToGoogle()
    {
        $config = $this->loadSocialConfig('google');
        if ($config['enabled'] !== '1' || $config['client_id'] === '' || $config['redirect_url'] === '') {
            $_SESSION['error'] = 'Google Login не налаштований або вимкнений.';
            header('Location: /login');
            exit;
        }

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_url'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        exit;
    }

    public function handleGoogleCallback()
    {
        $config = $this->loadSocialConfig('google');
        $code = trim((string) ($_GET['code'] ?? ''));

        if ($config['enabled'] !== '1' || $config['client_id'] === '' || $config['client_secret'] === '' || $config['redirect_url'] === '') {
            $_SESSION['error'] = 'Google Login не налаштований або вимкнений.';
            header('Location: /login');
            exit;
        }

        if ($code === '') {
            $_SESSION['error'] = 'Google не повернув код авторизації.';
            header('Location: /login');
            exit;
        }

        $tokenResponse = @file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'code' => $code,
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $config['redirect_url'],
                    'grant_type' => 'authorization_code',
                ]),
                'ignore_errors' => true,
            ]
        ]));

        $tokenData = json_decode((string) $tokenResponse, true);
        $accessToken = trim((string) ($tokenData['access_token'] ?? ''));
        if ($accessToken === '') {
            $_SESSION['error'] = 'Не вдалося отримати токен Google.';
            header('Location: /login');
            exit;
        }

        $profileResponse = @file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($accessToken));
        $profile = json_decode((string) $profileResponse, true);

        $this->socialLoginOrRegister([
            'email' => $profile['email'] ?? '',
            'first_name' => $profile['given_name'] ?? '',
            'last_name' => $profile['family_name'] ?? '',
        ]);
    }

    public function redirectToFacebook()
    {
        $config = $this->loadSocialConfig('facebook');
        if ($config['enabled'] !== '1' || $config['client_id'] === '' || $config['redirect_url'] === '') {
            $_SESSION['error'] = 'Facebook Login не налаштований або вимкнений.';
            header('Location: /login');
            exit;
        }

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_url'],
            'response_type' => 'code',
            'scope' => 'email,public_profile',
        ]);

        header('Location: https://www.facebook.com/v22.0/dialog/oauth?' . $params);
        exit;
    }

    public function handleFacebookCallback()
    {
        $config = $this->loadSocialConfig('facebook');
        $code = trim((string) ($_GET['code'] ?? ''));

        if ($config['enabled'] !== '1' || $config['client_id'] === '' || $config['client_secret'] === '' || $config['redirect_url'] === '') {
            $_SESSION['error'] = 'Facebook Login не налаштований або вимкнений.';
            header('Location: /login');
            exit;
        }

        if ($code === '') {
            $_SESSION['error'] = 'Facebook не повернув код авторизації.';
            header('Location: /login');
            exit;
        }

        $tokenUrl = 'https://graph.facebook.com/v22.0/oauth/access_token?' . http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_url'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
        ]);

        $tokenResponse = @file_get_contents($tokenUrl);
        $tokenData = json_decode((string) $tokenResponse, true);
        $accessToken = trim((string) ($tokenData['access_token'] ?? ''));

        if ($accessToken === '') {
            $_SESSION['error'] = 'Не вдалося отримати токен Facebook.';
            header('Location: /login');
            exit;
        }

        $profileResponse = @file_get_contents('https://graph.facebook.com/me?' . http_build_query([
            'fields' => 'id,first_name,last_name,email',
            'access_token' => $accessToken,
        ]));
        $profile = json_decode((string) $profileResponse, true);

        $this->socialLoginOrRegister([
            'email' => $profile['email'] ?? '',
            'first_name' => $profile['first_name'] ?? '',
            'last_name' => $profile['last_name'] ?? '',
        ]);
    }
}
