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

        $pageModel = new \App\Models\Page();
        $pages = $pageModel->getAll(); // Отримуємо всі сторінки з бази

        View::render('admin/content/index', [
            'pages' => $pages,
        ],      'admin');
    }

    public function create()
    {
        $this->checkAdmin();
        View::render('admin/content/create', [], 'admin');
    }

    public function store()
    {
        $this->checkAdmin();

        $data = [
            'title'     => trim($_POST['title'] ?? ''),
            'slug'      => trim($_POST['slug'] ?? ''),
            'content'   => $_POST['content'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0), // Додаємо цей рядок
            'meta_title'       => trim($_POST['meta_title'] ?? ''),       // SEO заголовок
            'meta_description' => trim($_POST['meta_description'] ?? ''), // SEO опис
        ];

        // Валідація
        if (empty($data['title']) || empty($data['slug'])) {
            die("Заповніть назву та посилання.");
        }

        $pageModel = new \App\Models\Page();

        // Перевірка на дублікат URL
        if (!$pageModel->isSlugUnique($data['slug'])) {
            die("Сторінка з таким посиланням вже існує.");
        }

        if ($pageModel->create($data)) {
            header('Location: /admin/content?success=1');
            exit;
        }
    }
    public function delete($id)
    {
        $this->checkAdmin();
    
        $pageModel = new \App\Models\Page();
        $pageModel->delete($id);

        header('Location: /admin/content?deleted=1');
        exit;
    }

    public function edit($id)
    {
        $this->checkAdmin();
    
        $pageModel = new \App\Models\Page();
        $page = $pageModel->getById($id);

        if (!$page) {
            die("Сторінку не знайдено");
        }

        View::render('admin/content/edit', [
            'page' => $page
        ], 'admin');
    }

    public function update($id)
    {
        $this->checkAdmin();

        $data = [
            'title'     => trim($_POST['title'] ?? ''),
            'slug'      => trim($_POST['slug'] ?? ''),
            'content'   => $_POST['content'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0), // Додаємо цей рядок
            'meta_title'       => trim($_POST['meta_title'] ?? ''),       // SEO заголовок
            'meta_description' => trim($_POST['meta_description'] ?? ''), // SEO опис
        ];

        $pageModel = new \App\Models\Page();
    
        // Оновлюємо через модель (метод update я давав вище)
        if ($pageModel->update($id, $data)) {
            header('Location: /admin/content?updated=1');
        exit;
        }
    }

public function uploadImage()
{
    $this->checkAdmin();
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['error' => 'Недопустимий тип файлу']);
            exit;
        }

        // Шлях від кореня сервера (public)
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/pages/';
        
        // Створюємо папку, якщо її нема
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid('img_', true) . '.' . $ext;
        $fullPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // Повертаємо посилання, яке браузер зможе відкрити
            echo json_encode(['url' => '/uploads/pages/' . $filename]);
            exit;
        } else {
            echo json_encode(['error' => 'Не вдалося перемістити файл. Перевірте права папки.']);
            exit;
        }
    }
    
    echo json_encode(['error' => 'Файл не отримано або помилка завантаження']);
    exit;
}
}
