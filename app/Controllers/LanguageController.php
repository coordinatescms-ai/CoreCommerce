<?php

namespace App\Controllers;

use App\Core\Localization\LocalizationManager;

class LanguageController
{
    /**
     * Зміна мови та збереження у сесію та кукі
     *
     * @param string $lang Код мови (зі списку config/languages.php)
     * @return void
     */
    public function change($lang = 'ua')
    {
        // Єдине джерело істини — config/languages.php (через LocalizationManager),
        // а не власний список тут. Раніше цей контролер мав СВІЙ окремий
        // хардкод-масив мов, який легко забути оновити при додаванні нової
        // мови — тепер він завжди узгоджений з рештою сайту автоматично.
        if (!LocalizationManager::isLanguageSupported($lang)) {
            $lang = 'ua';
        }

        LocalizationManager::setLanguage($lang);

        // Редирект на попередню сторінку або на головну
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer);
        exit;
    }
    /**
     * Сумісність із роутом /language/{lang}.
     *
     * @param string $lang
     * @return void
     */
    public function switch($lang = 'ua')
    {
        $this->change($lang);
    }

}
