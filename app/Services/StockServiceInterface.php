<?php

namespace App\Services;

interface StockServiceInterface
{
    public function getQuantity(string $sku): int;

    public function getAvailableQuantity(string $sku): int;

    public function addStock(string $sku, int $qty, string $comment = ''): bool;

    public function removeStock(string $sku, int $qty, string $comment = ''): bool;

    public function reserve(string $sku, int $qty): bool;

    public function releaseReserve(string $sku, int $qty): bool;

    public function syncStock(array $skuQtyMap): int;
}

