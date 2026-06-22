<?php

namespace App\Core\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\Setting;

class MailService
{
    private $config;

    public function __construct()
    {
        $config = require __DIR__ . '/../../../config/mail.php';
        $this->config = [
            'host' => (string) Setting::get('smtp_host', $config['host'] ?? ''),
            'port' => (int) Setting::get('smtp_port', (string) ($config['port'] ?? 587)),
            'username' => (string) Setting::get('smtp_username', $config['username'] ?? ''),
            'password' => (string) Setting::get('smtp_pass', $config['password'] ?? ''),
            'encryption' => (string) Setting::get('smtp_encryption', $config['encryption'] ?? 'tls'),
            'from_email' => (string) Setting::get('smtp_from_email', $config['from_email'] ?? ''),
            'from_name' => (string) Setting::get('smtp_from_name', $config['from_name'] ?? ''),
        ];
    }

    /**
     * Відправити HTML лист
     * 
     * @param string $to Кому
     * @param string $subject Тема
     * @param string $body Тіло листа (HTML)
     * @return bool
     */
    /**
     * Сконфігурувати PHPMailer з поточних налаштувань.
     */
    private function configure(PHPMailer $mail): void
    {
        $encryption = $this->config['encryption'];

        $mail->isSMTP();
        $mail->Host     = $this->config['host'];
        $mail->Port     = (int) $this->config['port'];
        $mail->CharSet  = 'UTF-8';

        if ($encryption === '' || $encryption === null || $encryption === 'none') {
            // Без шифрування — Mailpit, локальний SMTP, порт 25
            $mail->SMTPAuth   = false;
            $mail->SMTPSecure = '';
        } else {
            // tls або ssl
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = $encryption;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
        }

        // Таймаут з'єднання — щоб сторінка не зависала при недосяжному SMTP
        $mail->Timeout    = 10;

        // Дозволяємо самопідписані сертифікати (поширено на локальних SMTP)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom($this->config['from_email'], $this->config['from_name']);
    }

    /**
     * Відправити HTML лист.
     *
     * @param string $to      Кому
     * @param string $subject Тема
     * @param string $body    Тіло листа (HTML)
     */
    public function send(string $to, string $subject, string $body): bool
    {
        return $this->sendWithDiagnostics($to, $subject, $body)['success'];
    }

    /**
     * Відправити лист і повернути детальну інформацію про результат.
     * Використовується для тестової відправки в адмін-панелі.
     *
     * @return array{success: bool, error: string}
     */
    public function sendWithDiagnostics(string $to, string $subject, string $body): array
    {
        $mail = new PHPMailer(true);

        try {
            $this->configure($mail);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return ['success' => true, 'error' => ''];
        } catch (\Throwable $e) {
            $detail = $mail->ErrorInfo ?: $e->getMessage();
            error_log('MailService error: ' . $detail);
            return ['success' => false, 'error' => $detail];
        }
    }

    /**
     * Повернути поточну конфігурацію (без пароля) для відображення в адмінці.
     */
    public function getConfigSummary(): array
    {
        return [
            'host'       => $this->config['host'],
            'port'       => $this->config['port'],
            'username'   => $this->config['username'],
            'encryption' => $this->config['encryption'],
            'from_email' => $this->config['from_email'],
            'from_name'  => $this->config['from_name'],
        ];
    }

    /**
     * Рендеринг шаблону листа
     * 
     * @param string $template Назва файлу шаблону
     * @param array $data Дані для шаблону
     * @return string
     */
    public function renderTemplate($template, $data = [])
    {
        extract($data);
        ob_start();
        include __DIR__ . "/../../../resources/views/mail/{$template}.php";
        return ob_get_clean();
    }
}
