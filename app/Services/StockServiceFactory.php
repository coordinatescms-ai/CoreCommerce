<?php

namespace App\Services;

class StockServiceFactory
{
    public static function make(): StockServiceInterface
    {
        $services = require __DIR__ . '/../../config/services.php';
        $source = strtolower((string) ($services['stock']['source'] ?? 'local'));

        return new LocalStockService();
    }
}

