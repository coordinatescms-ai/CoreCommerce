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
     * Додати до дозволених атрибутів опції для select-типів.
     *
     * @param array $allowedAttributes
     * @return array
     */
    private function enrichAllowedAttributes(array $allowedAttributes)
    {
        foreach ($allowedAttributes as &$attribute) {
            $type = (string) ($attribute['type'] ?? '');
            if (in_array($type, [Attribute::TYPE_SELECT, 'multiselect', 'color'], true)) {
                $attribute['options'] = array_values(array_map(function ($option) {
                    return [
                        'id' => (int) ($option['id'] ?? 0),
                        'name' => (string) ($option['name'] ?? ''),
                        'value' => (string) ($option['value'] ?? ''),
                    ];
                }, Attribute::getOptions((int) ($attribute['id'] ?? 0))));
            } else {
                $attribute['options'] = [];
            }
        }
        unset($attribute);

        return $allowedAttributes;
    }

    /**
     * Підготувати рядки атрибутів із форми.
     *
     * @return array
     */
    private function collectAttributeRows()
    {
        $attributeIds = $_POST['attribute_id'] ?? [];
        $values = $_POST['attribute_value'] ?? [];

        if (!is_array($attributeIds) || !is_array($values)) {
            return [];
        }

        $rows = [];
        $maxCount = max(count($attributeIds), count($values));

        for ($i = 0; $i < $maxCount; $i++) {
            $attributeId = (int) ($attributeIds[$i] ?? 0);
            $value = trim((string) ($values[$i] ?? ''));

            if ($attributeId <= 0 || $value === '') {
                continue;
            }

            $rows[] = [
                'attribute_id' => $attributeId,
                'value' => $value
            ];
        }

        return $rows;
    }

    /**
     * Нормалізувати значення атрибута згідно з його типом.
     *
     * @param array $attribute
     * @param string $value
     * @return string|null
     */
    private function normalizeValueByType($attribute, $value)
    {
        $type = (string) ($attribute['type'] ?? Attribute::TYPE_TEXT);
        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        if ($type === Attribute::TYPE_RANGE) {
            $normalized = str_replace(',', '.', $trimmed);
            if (!is_numeric($normalized)) {
                return null;
            }
            return (string) (float) $normalized;
        }

        return $trimmed;
    }

    /**
     * Перевірити, чи дозволені атрибути для обраної категорії.
     *
     * @param int|null $categoryId
     * @param array $attributeRows
     * @return bool
     */
    private function validateAttributesForCategory($categoryId, $attributeRows)
    {
        if (empty($attributeRows)) {
            return true;
        }

        $categoryId = (int) $categoryId;
        if ($categoryId <= 0) {
            return false;
        }

        $allowedIds = Category::getAllowedAttributeIds($categoryId);
        if (empty($allowedIds)) {
            return false;
        }

        $allowedLookup = array_fill_keys($allowedIds, true);
        foreach ($attributeRows as $row) {
            $attributeId = (int) ($row['attribute_id'] ?? 0);
            if ($attributeId <= 0 || !isset($allowedLookup[$attributeId])) {
                return false;
            }
        }

        return true;
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
            $attributeId = (int) ($row['attribute_id'] ?? 0);
            $attribute = $attributeId > 0 ? Attribute::findById($attributeId) : null;
            if (!$attribute) {
                continue;
            }

            $normalizedValue = $this->normalizeValueByType($attribute, (string) ($row['value'] ?? ''));
            if ($normalizedValue === null) {
                continue;
            }

            $optionId = null;
            if (($attribute['type'] ?? '') === Attribute::TYPE_SELECT) {
                $option = Attribute::findOptionByValue((int) $attributeId, $normalizedValue);
                if (!$option) {
                    $optionId = Attribute::createOption((int) $attributeId, [
                        'name' => $normalizedValue,
                        'value' => $normalizedValue,
                        'sort_order' => 0,
                    ]);
                } else {
                    $optionId = (int) $option['id'];
                }

                if (!$optionId) {
                    $optionId = null;
                }
            }

            ProductAttribute::setValue((int) $productId, (int) $attributeId, $normalizedValue, $optionId);
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

        View::render('admin/products/create', [
            'categories' => $categories,
            'allowedAttributes' => []
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
            if (!$this->validateAttributesForCategory($data['category_id'], $attributeRows)) {
                Product::delete((int) $productId);
                $_SESSION['error'] = 'Неможливо зберегти характеристики: обрано атрибути, які не дозволені для категорії товару.';
                header('Location: /admin/products/create');
                exit;
            }

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
        $allowedAttributes = [];
        if (!empty($product['category_id'])) {
            $allowedAttributes = $this->enrichAllowedAttributes(
                Category::getAllowedAttributes((int) $product['category_id'])
            );
        }

        View::render('admin/products/edit', [
            'product' => $product,
            'categories' => $categories,
            'existingAttributes' => $existingAttributes,
            'allowedAttributes' => $allowedAttributes,
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
            if (!$this->validateAttributesForCategory($data['category_id'], $attributeRows)) {
                $_SESSION['error'] = 'Неможливо зберегти характеристики: обрано атрибути, які не дозволені для категорії товару.';
                header('Location: /admin/products/edit/' . $id);
                exit;
            }

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

    /**
     * Повернути список дозволених атрибутів для категорії (AJAX).
     *
     * @param int $categoryId
     * @return void
     */
    public function allowedAttributes($categoryId)
    {
        $this->checkAdmin();

        header('Content-Type: application/json; charset=utf-8');

        $categoryId = (int) $categoryId;
        if ($categoryId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Некоректний ID категорії.',
                'attributes' => []
            ]);
            return;
        }

        $category = Category::findById($categoryId);
        if (!$category) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Категорію не знайдено.',
                'attributes' => []
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'attributes' => $this->enrichAllowedAttributes(Category::getAllowedAttributes($categoryId))
        ]);
    }
}
