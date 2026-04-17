<?php

namespace App\Controllers;

use App\Core\View\View;

class AdminOrderController
{
    private function checkAdmin(): void
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void
    {
        $this->checkAdmin();

        $kanbanColumns = [
            'new' => 'Новий',
            'confirmed' => 'Підтверджено',
            'processing' => 'Комплектується',
            'shipped' => 'Відправлено',
        ];

        // Тимчасові демонстраційні картки для Кроку 1.
        $demoCards = [
            ['id' => 1001, 'customer' => 'Іван Петренко', 'total' => 1240.50, 'status' => 'new'],
            ['id' => 1002, 'customer' => 'Марія Шевченко', 'total' => 875.00, 'status' => 'confirmed'],
            ['id' => 1003, 'customer' => 'Олег Іваненко', 'total' => 2450.99, 'status' => 'processing'],
            ['id' => 1004, 'customer' => 'Анна Бондар', 'total' => 630.00, 'status' => 'shipped'],
        ];

        View::render('admin/orders/index', [
            'kanbanColumns' => $kanbanColumns,
            'demoCards' => $demoCards,
        ], 'admin');
    }
}
