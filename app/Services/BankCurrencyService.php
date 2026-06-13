<?php

namespace App\Services;

class BankCurrencyService
{
    private const NBU_API_URL = 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?valcode=%s&json';

    /**
     * Отримати поточний курс валюти відносно UAH через API НБУ.
     *
     * @param  string $code    Код валюти: USD, EUR тощо
     * @param  string $apiKey  Ключ доступу до API НБУ (зберігається в settings.currency_source)
     * @return float           Курс до гривні
     * @throws \RuntimeException  Якщо запит або розбір даних невдалий
     */
    public function fetchRate(string $code, string $apiKey = ''): float
    {
        $code = strtoupper(trim($code));

        if ($code === 'UAH') {
            return 1.0;
        }

        $url = sprintf(self::NBU_API_URL, urlencode($code));

        $headers = "Accept: application/json\r\n";
        if ($apiKey !== '') {
            $headers .= "Authorization: Bearer {$apiKey}\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 10,
                'ignore_errors' => true,
                'header'        => $headers,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException(
                "Не вдалося з'єднатися з API НБУ для валюти «{$code}»."
            );
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'API НБУ повернув некоректний JSON: ' . json_last_error_msg()
            );
        }

        if (empty($data) || !isset($data[0]['rate'])) {
            throw new \RuntimeException(
                "API НБУ не повернув курс для валюти «{$code}». Перевірте код валюти або API-ключ."
            );
        }

        $rate = (float) $data[0]['rate'];

        if ($rate <= 0) {
            throw new \RuntimeException(
                "API НБУ повернув некоректний курс ({$rate}) для «{$code}»."
            );
        }

        return $rate;
    }
}
