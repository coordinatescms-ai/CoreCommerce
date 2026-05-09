<?php

namespace App\Services;

use App\Core\Database\DB;

class LocalStockService implements StockServiceInterface
{
    private function ensureStockRow(string $sku): void
    {
        DB::query('INSERT IGNORE INTO product_stocks (sku, quantity, reserved, updated_at) VALUES (?, 0, 0, NOW())', [$sku]);
    }

    private function log(string $sku, string $type, int $qty, string $comment = ''): void
    {
        DB::query('INSERT INTO inventory_log (sku, event_type, qty, comment, created_at) VALUES (?, ?, ?, ?, NOW())', [$sku, $type, $qty, $comment]);
    }

    public function getQuantity(string $sku): int
    {
        $row = DB::query('SELECT quantity FROM product_stocks WHERE sku = ? LIMIT 1', [$sku])->fetch();
        return (int) ($row['quantity'] ?? 0);
    }

    public function getAvailableQuantity(string $sku): int
    {
        $row = DB::query('SELECT quantity, reserved FROM product_stocks WHERE sku = ? LIMIT 1', [$sku])->fetch();
        return max(0, (int) ($row['quantity'] ?? 0) - (int) ($row['reserved'] ?? 0));
    }

    public function addStock(string $sku, int $qty, string $comment = ''): bool
    {
        if ($qty <= 0) {
            return false;
        }
        $this->ensureStockRow($sku);
        DB::query('UPDATE product_stocks SET quantity = quantity + ?, updated_at = NOW() WHERE sku = ?', [$qty, $sku]);
        $this->log($sku, 'add', $qty, $comment);
        return true;
    }

    public function removeStock(string $sku, int $qty, string $comment = ''): bool
    {
        if ($qty <= 0) {
            return false;
        }
        $this->ensureStockRow($sku);
        $stmt = DB::query('UPDATE product_stocks SET quantity = quantity - ?, updated_at = NOW() WHERE sku = ? AND quantity >= ?', [$qty, $sku, $qty]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }
        $this->log($sku, 'remove', $qty, $comment);
        return true;
    }

    public function reserve(string $sku, int $qty): bool
    {
        if ($qty <= 0) {
            return false;
        }
        $this->ensureStockRow($sku);
        $stmt = DB::query('UPDATE product_stocks SET reserved = reserved + ?, updated_at = NOW() WHERE sku = ? AND (quantity - reserved) >= ?', [$qty, $sku, $qty]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }
        $this->log($sku, 'reserve', $qty, 'Автоматичне резервування');
        return true;
    }

    public function releaseReserve(string $sku, int $qty): bool
    {
        if ($qty <= 0) {
            return false;
        }
        $this->ensureStockRow($sku);
        $stmt = DB::query('UPDATE product_stocks SET reserved = reserved - ?, updated_at = NOW() WHERE sku = ? AND reserved >= ?', [$qty, $sku, $qty]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }
        $this->log($sku, 'release', $qty, 'Зняття резерву');
        return true;
    }

    public function syncStock(array $skuQtyMap): int
    {
        $updated = 0;
        foreach ($skuQtyMap as $sku => $qty) {
            $sku = trim((string) $sku);
            $qty = max(0, (int) $qty);
            if ($sku === '') {
                continue;
            }
            $this->ensureStockRow($sku);
            DB::query('UPDATE product_stocks SET quantity = ?, updated_at = NOW() WHERE sku = ?', [$qty, $sku]);
            $this->log($sku, 'sync', $qty, 'External sync');
            $updated++;
        }
        return $updated;
    }
}

