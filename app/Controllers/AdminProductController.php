<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\ProductAttribute;
use App\Services\SlugHelper;

class AdminProductController
{
    /**
     * Підготувати рядки атрибутів із форми.
     *
     * @return array
     */
    private function collectAttributeRows()
    {
        $names = $_POST['attribute_name'] ?? [];
        $values = $_POST['attribute_value'] ?? [];

        if (!is_array($names) || !is_array($values)) {
            return [];
        }

        $rows = [];
        $maxCount = max(count($names), count($values));

        for ($i = 0; $i < $maxCount; $i++) {
            $name = trim((string) ($names[$i] ?? ''));
            $value = trim((string) ($values[$i] ?? ''));

            if ($name === '' || $value === '') {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'value' => $value
            ];
        }

        return $rows;
    }

    /**
     * Зберегти атрибути товару, створюючи нові атрибути за потреби.
     *
     * @param int $productId
     * @param array $attributeRows
     * @return void
     */
    private function syncProductAttributes($productId, $attributeRows)
    {
        ProductAttribute::deleteAll((int) $productId);

        foreach ($attributeRows as $row) {
            $attribute = Attribute::findByName($row['name']);
            if (!$attribute) {
                $attributeId = Attribute::create([
                    'name' => $row['name'],
                    'type' => 'text',
                    'description' => null,
                    'is_filterable' => 1,
                    'is_visible' => 1,
                    'sort_order' => 0,
                ]);

                if (!$attributeId) {
                    continue;
                }
            } else {
                $attributeId = (int) $attribute['id'];
            }

            ProductAttribute::setValue((int) $productId, (int) $attributeId, $row['value']);
        }
    }

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
        $attributeNameSuggestions = Attribute::getAllNames();

        View::render('admin/products/create', [
            'categories' => $categories,
            'attributeNameSuggestions' => $attributeNameSuggestions
        ], 'admin');
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
        $attributeRows = $this->collectAttributeRows();

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

        $productId = Product::create($data);

        if ($productId) {
            $this->syncProductAttributes((int) $productId, $attributeRows);
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
        $existingAttributes = ProductAttribute::getByProduct($id);
        $attributeNameSuggestions = Attribute::getAllNames();

        View::render('admin/products/edit', [
            'product' => $product,
            'categories' => $categories,
            'existingAttributes' => $existingAttributes,
            'attributeNameSuggestions' => $attributeNameSuggestions,
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
        $attributeRows = $this->collectAttributeRows();
        if ($newImage) {
            $data['image'] = $newImage;
        }

        if (Product::update($id, $data)) {
            $this->syncProductAttributes((int) $id, $attributeRows);
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
