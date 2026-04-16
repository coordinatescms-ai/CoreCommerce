<?php

namespace App\Core\Http;

class Csrf
{
    public static function token(): string
    {
        return is_string($_SESSION['csrf'] ?? null) ? $_SESSION['csrf'] : '';
    }

    public static function requestToken(string $field = 'csrf'): string
    {
        $token = $_POST[$field] ?? '';
        return is_string($token) ? $token : '';
    }

    public static function isValid(string $field = 'csrf'): bool
    {
        $sessionToken = self::token();
        $requestToken = self::requestToken($field);

        return $sessionToken !== '' && $requestToken !== '' && hash_equals($sessionToken, $requestToken);
    }

    public static function abortIfInvalid(string $message = 'CSRF validation failed', int $statusCode = 419): void
    {
        if (self::isValid()) {
            return;
        }

        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        die($message);
    }
}
