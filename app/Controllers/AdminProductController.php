<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Models\Product;
use App\Models\Category;
use App\Services\SlugHelper;

class AdminProductController
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
        $products = Product::allWithCategory();
        View::render('admin/products/index', ['products' => $products], 'admin');
    }

    public function create()
    {
        $this->checkAdmin();
        $categories = Category::getFlatTree();
        View::render('admin/products/create', ['categories' => $categories], 'admin');
    }

    public function show($id)
    {
        $this->checkAdmin();

        $product = Product::findWithCategoryById($id);
        if (!$product) {
            header('Location: /admin/products');
            exit;
        }

        View::render('admin/products/show', ['product' => $product], 'admin');
    }

    private function handleImageUpload()
    {
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                return null;
            }

            $uploadDir = __DIR__ . '/../../public/uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destination = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                return '/uploads/products/' . $filename;
            }
        }

        return null;
    }

    public function store()
    {
        $this->checkAdmin();

        if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
            die('CSRF validation failed');
        }

        $image = $this->handleImageUpload();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => !empty($_POST['slug']) ? trim($_POST['slug']) : SlugHelper::getUnique($_POST['name'], 'product'),
            'price' => (float)($_POST['price'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'description' => $_POST['description'] ?? '',
            'image' => $image,
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? ''
        ];

        if (Product::create($data)) {
            $_SESSION['success'] = 'Товар успішно додано!';
            header('Location: /admin/products');
        } else {
            $_SESSION['error'] = 'Помилка при додаванні товару. Перевірте унікальність slug.';
            header('Location: /admin/products/create');
        }

        exit;
    }

    public function edit($id)
    {
        $this->checkAdmin();

        $product = Product::findById($id);
        if (!$product) {
            header('Location: /admin/products');
            exit;
        }

        $categories = Category::getFlatTree();

        View::render('admin/products/edit', [
            'product' => $product,
            'categories' => $categories,
        ], 'admin');
    }

    public function update($id)
    {
        $this->checkAdmin();

        if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
            die('CSRF validation failed');
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'price' => (float)($_POST['price'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'description' => $_POST['description'] ?? '',
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? ''
        ];

        $newImage = $this->handleImageUpload();
        if ($newImage) {
            $data['image'] = $newImage;
        }

        if (Product::update($id, $data)) {
            $_SESSION['success'] = 'Товар успішно оновлено!';
            header('Location: /admin/products');
        } else {
            $_SESSION['error'] = 'Помилка при оновленні товару. Перевірте унікальність slug.';
            header('Location: /admin/products/edit/' . $id);
        }

        exit;
    }

    public function delete($id)
    {
        $this->checkAdmin();

        if (empty($_GET['csrf']) || $_GET['csrf'] !== $_SESSION['csrf']) {
            die('CSRF validation failed');
        }

        if (Product::delete($id)) {
            $_SESSION['success'] = 'Товар видалено!';
        } else {
            $_SESSION['error'] = 'Помилка при видаленні.';
        }

        header('Location: /admin/products');
        exit;
    }
}
