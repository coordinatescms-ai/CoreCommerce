<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Database\DB;

class AdminContentController
{
    private function checkAdmin()
    {
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }
 
    public function index()
    {
        $this->checkAdmin();

         $stats = [
            'orders_count' => 0,
            'products_count' => 0,
            'total_sales' => 0
        ];
        
        View::render('admin/content/index', [
           'stats' => $stats,
        ], 'admin');
    }

    public function create()
    {
        $this->checkAdmin();
        View::render('admin/content/create', [], 'admin');
    }

    public function store()
    {
        $this->checkAdmin();

        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $content = $_POST['content'] ?? '';

        // Якщо slug порожній (наприклад, JS вимкнено), видаємо помилку або генеруємо його
        if (empty($slug)) {
            // Проста логіка: якщо slug пустий, можна повернути назад з помилкою
            die("Помилка: Slug не може бути порожнім.");
        }

        // Додатково: перевірка на унікальність slug в базі
        // $existingPage = $db->query("SELECT id FROM pages WHERE slug = ?", [$slug]);
        // if ($existingPage) { die("Ця адреса вже зайнята."); }

        // Збереження в базу...
    }
}
