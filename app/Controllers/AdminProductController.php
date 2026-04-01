<?php
namespace App\Controllers;

use App\Models\Product;
use App\Core\View\View;
use App\Middleware\AuthMiddleware;
use App\Core\Database\DB;

class AdminProductController
{
    public function index()
    {
        AuthMiddleware::handle();

        $products = Product::all();

        return View::render('admin.products.index', compact('products'));
    }

    public function create()
    {
        AuthMiddleware::handle();

        return View::render('admin.products.create');
    }

    public function store()
    {
        AuthMiddleware::handle();

        // 🔐 CSRF CHECK
        if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
            die('CSRF token mismatch');
        }

        // 🧹 Валідація (мінімум)
        if (empty($_POST['name']) || empty($_POST['slug']) || empty($_POST['price'])) {
            die('Validation error');
        }

        $image = null;

        // 🖼 Upload image
        if (!empty($_FILES['image']['name'])) {

            // базова безпека
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                die('Invalid image format');
            }

            $image = '/uploads/' . time() . '_' . basename($_FILES['image']['name']);

            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                __DIR__ . '/../../public' . $image
            );
        }

        Product::create([
            'name' => htmlspecialchars($_POST['name']),
            'slug' => htmlspecialchars($_POST['slug']),
            'price' => (float)$_POST['price'],
            'image' => $image
        ]);

        header('Location:/admin/products');
        exit;
    }

    public function delete($id)
    {
        AuthMiddleware::handle();

        // 🔐 CSRF CHECK (ВАЖЛИВО!)
        if (!isset($_GET['csrf']) || $_GET['csrf'] !== $_SESSION['csrf']) {
            die('CSRF token mismatch');
        }

        DB::query("DELETE FROM products WHERE id=?", [$id]);

        header('Location:/admin/products');
        exit;
    }
}