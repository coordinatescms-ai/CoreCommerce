<?php

namespace App\Core\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../../config/mail.php';
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
