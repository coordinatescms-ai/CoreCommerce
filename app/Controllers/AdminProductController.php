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
    private const PRODUCT_FORM_FLASH_KEY = 'product_form_old';

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
        $formData = $this->consumeProductFormFlash();
        $selectedCategoryId = (int) ($formData['category_id'] ?? 0);
        $allowedAttributes = [];
        if ($selectedCategoryId > 0) {
            $allowedAttributes = $this->enrichAllowedAttributes(
                Category::getAllowedAttributes($selectedCategoryId)
            );
        }

        View::render('admin/products/create', [
            'categories' => $categories,
            'allowedAttributes' => $allowedAttributes,
            'formData' => $formData,
            'attributeRows' => $formData['attributes'] ?? [],
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

        $attributeRows = $this->collectAttributeRows();
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => !empty($_POST['slug']) ? trim($_POST['slug']) : SlugHelper::getUnique($_POST['name'], 'product'),
            'price' => trim((string) ($_POST['price'] ?? '')),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'meta_title' => trim((string) ($_POST['meta_title'] ?? '')),
            'meta_description' => trim((string) ($_POST['meta_description'] ?? ''))
        ];
        $validationError = $this->validateProductPayload($data);
        if ($validationError !== null) {
            $this->flashProductFormData($data, $attributeRows);
            $_SESSION['error'] = $validationError;
            header('Location: /admin/products/create');
            exit;
        }

        $data['price'] = (float) str_replace(',', '.', (string) $data['price']);
        $image = $this->handleImageUpload();
        $data['image'] = $image;

        $productId = Product::create($data);

        if ($productId) {
            if (!$this->validateAttributesForCategory($data['category_id'], $attributeRows)) {
                Product::delete((int) $productId);
                $this->flashProductFormData($data, $attributeRows);
                $_SESSION['error'] = 'Неможливо зберегти характеристики: обрано атрибути, які не дозволені для категорії товару.';
                header('Location: /admin/products/create');
                exit;
            }

            $this->syncProductAttributes((int) $productId, $attributeRows);
            unset($_SESSION[self::PRODUCT_FORM_FLASH_KEY]);
            $_SESSION['success'] = 'Товар успішно додано!';
            header('Location: /admin/products');
        } else {
            $this->flashProductFormData($data, $attributeRows);
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
        $formData = $this->consumeProductFormFlash();
        if (!empty($formData)) {
            $product['name'] = $formData['name'] ?? $product['name'];
            $product['slug'] = $formData['slug'] ?? $product['slug'];
            $product['price'] = $formData['price'] ?? $product['price'];
            $product['category_id'] = $formData['category_id'] ?? $product['category_id'];
            $product['description'] = $formData['description'] ?? $product['description'];
            $product['meta_title'] = $formData['meta_title'] ?? $product['meta_title'];
            $product['meta_description'] = $formData['meta_description'] ?? $product['meta_description'];
        }

        $existingAttributes = !empty($formData['attributes'])
            ? $formData['attributes']
            : ProductAttribute::getByProduct($id);
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
            'price' => trim((string) ($_POST['price'] ?? '')),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'meta_title' => trim((string) ($_POST['meta_title'] ?? '')),
            'meta_description' => trim((string) ($_POST['meta_description'] ?? ''))
        ];

        $attributeRows = $this->collectAttributeRows();
        $validationError = $this->validateProductPayload($data);
        if ($validationError !== null) {
            $this->flashProductFormData($data, $attributeRows);
            $_SESSION['error'] = $validationError;
            header('Location: /admin/products/edit/' . $id);
            exit;
        }

        $data['price'] = (float) str_replace(',', '.', (string) $data['price']);
        $newImage = $this->handleImageUpload();
        if ($newImage) {
            $data['image'] = $newImage;
        }

        if (Product::update($id, $data)) {
            if (!$this->validateAttributesForCategory($data['category_id'], $attributeRows)) {
                $this->flashProductFormData($data, $attributeRows);
                $_SESSION['error'] = 'Неможливо зберегти характеристики: обрано атрибути, які не дозволені для категорії товару.';
                header('Location: /admin/products/edit/' . $id);
                exit;
            }

            $this->syncProductAttributes((int) $id, $attributeRows);
            unset($_SESSION[self::PRODUCT_FORM_FLASH_KEY]);
            $_SESSION['success'] = 'Товар успішно оновлено!';
            header('Location: /admin/products');
        } else {
            $this->flashProductFormData($data, $attributeRows);
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

    /**
     * @param array $data
     * @return string|null
     */
    private function validateProductPayload(array $data)
    {
        if (trim((string) ($data['name'] ?? '')) === '') {
            return 'Поле "Назва товару" є обов’язковим.';
        }

        $priceValue = str_replace(',', '.', (string) ($data['price'] ?? ''));
        if ($priceValue === '' || !is_numeric($priceValue)) {
            return 'Поле "Ціна" повинно містити коректне число.';
        }

        if ((float) $priceValue <= 0) {
            return 'Поле "Ціна" повинно бути більше 0.';
        }

        if (trim((string) ($data['description'] ?? '')) === '') {
            return 'Поле "Опис товару" є обов’язковим.';
        }

        if ((int) ($data['category_id'] ?? 0) <= 0) {
            return 'Потрібно обрати категорію товару.';
        }

        return null;
    }

    /**
     * @param array $data
     * @param array $attributeRows
     * @return void
     */
    private function flashProductFormData(array $data, array $attributeRows)
    {
        $_SESSION[self::PRODUCT_FORM_FLASH_KEY] = [
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'price' => (string) ($data['price'] ?? ''),
            'category_id' => (int) ($data['category_id'] ?? 0),
            'description' => (string) ($data['description'] ?? ''),
            'meta_title' => (string) ($data['meta_title'] ?? ''),
            'meta_description' => (string) ($data['meta_description'] ?? ''),
            'attributes' => array_values($attributeRows),
        ];
    }

    /**
     * @return array
     */
    private function consumeProductFormFlash()
    {
        $data = $_SESSION[self::PRODUCT_FORM_FLASH_KEY] ?? [];
        unset($_SESSION[self::PRODUCT_FORM_FLASH_KEY]);

        return is_array($data) ? $data : [];
    }
}
