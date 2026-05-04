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
    public function send($to, $subject, $body)
    {
        $mail = new PHPMailer(true);

        try {
            // Налаштування сервера
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->Port       = $this->config['port'];
            $mail->CharSet    = 'UTF-8';

            // Отримувачі
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($to);

            // Вміст
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return true;
        } catch (Exception $e) {
            // У реальному додатку тут було б логування помилки
            // error_log("Mail Error: {$mail->ErrorInfo}");
            return false;
        }
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
