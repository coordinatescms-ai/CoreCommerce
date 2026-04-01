<?php

namespace App\Controllers;

class LanguageController
{
    /**
     * Зміна мови та збереження у сесію та кукі
     * 
     * @param string $lang Код мови (ua, en)
     * @return void
     */
    public function change($lang = 'ua')
    {
        // Список підтримуваних мов
        $supported_languages = ['ua', 'en'];
        
        // Перевірка, чи мова підтримується
        if (!in_array($lang, $supported_languages)) {
            $lang = 'ua'; // За замовчуванням українська
        }
        
        // Збереження мови у сесію
        $_SESSION['lang'] = $lang;
        
        // Збереження мови у кукі на 1 рік
        setcookie('lang', $lang, time() + (365 * 24 * 60 * 60), '/', '', false, true);
        
        // Редирект на попередню сторінку або на головну
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer);
        exit;
    }
}
