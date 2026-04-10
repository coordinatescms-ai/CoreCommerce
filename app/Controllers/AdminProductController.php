<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductImage;
use App\Services\SlugHelper;
use App\Services\ImageManager;

class AdminProductController
{
    private const PRODUCT_FORM_FLASH_KEY = 'product_form_old';

    private const MAX_GALLERY_IMAGES = 5;
    private const MAX_IMAGE_SIZE_BYTES = 5242880; // 5MB

    private function validateCsrfOrAbort()
    {
        $sessionToken = $_SESSION['csrf'] ?? '';
        $requestToken = $_POST['csrf'] ?? '';

        if (!is_string($sessionToken) || !is_string($requestToken) || $sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
            die('CSRF validation failed');
        }
    }

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
        $selectableFlags = $_POST['attribute_is_selectable'] ?? [];

        if (!is_array($attributeIds) || !is_array($values) || !is_array($selectableFlags)) {
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
                'value' => $value,
                'is_selectable' => (int) ($selectableFlags[$i] ?? 0) === 1
            ];
        }

        return $rows;
    }

    /**
     * Перевірити заповнення пари "характеристика-значення".
     *
     * @return string|null
     */
    private function validateAttributePairs()
    {
        $attributeIds = $_POST['attribute_id'] ?? [];
        $values = $_POST['attribute_value'] ?? [];

        if (!is_array($attributeIds) || !is_array($values)) {
            return null;
        }

        $maxCount = max(count($attributeIds), count($values));
        for ($i = 0; $i < $maxCount; $i++) {
            $attributeId = (int) ($attributeIds[$i] ?? 0);
            $value = trim((string) ($values[$i] ?? ''));

            if ($attributeId > 0 && $value === '') {
                return 'Для обраної характеристики потрібно заповнити поле "Значення".';
            }

            if ($attributeId <= 0 && $value !== '') {
                return 'Вказано значення без характеристики. Будь ласка, оберіть характеристику.';
            }
        }

        return null;
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
        ProductAttributeValue::deleteAll((int) $productId);

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
            $attributeType = (string) ($attribute['type'] ?? '');
            if (in_array($attributeType, [Attribute::TYPE_SELECT, 'multiselect', 'color'], true)) {
                $optionId = $this->resolveAttributeOptionId((int) $attributeId, $normalizedValue);
            }

            $isSelectable = !empty($row['is_selectable']);

            ProductAttribute::setValue((int) $productId, (int) $attributeId, $normalizedValue, $optionId);
            ProductAttributeValue::addValue((int) $productId, (int) $attributeId, $normalizedValue, $isSelectable);
        }
    }


    /**
     * Транслітерувати значення опції до латиниці для збереження у attribute_options.value.
     *
     * @param string $value
     * @return string
     */
    private function transliterateOptionValue($value)
    {
        $value = trim((string) $value);
        if ($value == '') {
            return '';
        }

        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g', 'д' => 'd', 'е' => 'e', 'є' => 'ye', 'ж' => 'zh', 'з' => 'z',
            'и' => 'y', 'і' => 'i', 'ї' => 'yi', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p',
            'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ь' => '', 'ю' => 'yu', 'я' => 'ya', 'ы' => 'y', 'э' => 'e', 'ъ' => '',
        ];

        $lowered = mb_strtolower($value, 'UTF-8');
        $transliterated = strtr($lowered, $map);
        $transliterated = preg_replace('/[^a-z0-9\s\-_.]/', '-', $transliterated);
        $transliterated = preg_replace('/\s+/', '-', $transliterated);
        $transliterated = preg_replace('/-+/', '-', $transliterated);

        return trim($transliterated, '-');
    }

    /**
     * Повернути ID опції атрибута (створює нову опцію, якщо введено нове значення).
     *
     * @param int $attributeId
     * @param string $displayValue
     * @return int|null
     */
    private function resolveAttributeOptionId($attributeId, $displayValue)
    {
        $displayValue = trim((string) $displayValue);
        if ($displayValue === '') {
            return null;
        }

        $existing = Attribute::findOptionByValue((int) $attributeId, $displayValue);
        if ($existing) {
            return (int) ($existing['id'] ?? 0) ?: null;
        }

        $optionValue = $this->transliterateOptionValue($displayValue);
        if ($optionValue !== '') {
            $existingByTranslit = Attribute::findOptionByValue((int) $attributeId, $optionValue);
            if ($existingByTranslit) {
                return (int) ($existingByTranslit['id'] ?? 0) ?: null;
            }
        }

        if ($optionValue === '') {
            $optionValue = 'option-' . time();
        }

        $optionId = Attribute::createOption((int) $attributeId, [
            'name' => $displayValue,
            'value' => $optionValue,
            'sort_order' => 0,
        ]);

        return $optionId ? (int) $optionId : null;
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
            'galleryLimit' => $this->getGalleryImagesLimit(),
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

        $galleryImages = ProductImage::getByProduct((int) $id);

        View::render('admin/products/show', ['product' => $product, 'galleryImages' => $galleryImages], 'admin');
    }

    private function getGalleryImagesLimit(): int
    {
        return self::MAX_GALLERY_IMAGES;
    }

    private function normalizeFilesInput(string $fieldName): array
    {
        if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'] ?? null)) {
            return [];
        }

        $normalized = [];
        $names = $_FILES[$fieldName]['name'];
        foreach ($names as $index => $name) {
            $normalized[] = [
                'name' => (string) ($name ?? ''),
                'type' => (string) ($_FILES[$fieldName]['type'][$index] ?? ''),
                'tmp_name' => (string) ($_FILES[$fieldName]['tmp_name'][$index] ?? ''),
                'error' => (int) ($_FILES[$fieldName]['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($_FILES[$fieldName]['size'][$index] ?? 0),
            ];
        }

        return $normalized;
    }

    private function validateImageFile(array $file, int $maxSizeBytes): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'Помилка завантаження файлу: ' . ($file['name'] ?? 'невідомий файл') . '.';
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxSizeBytes) {
            return 'Максимальний розмір одного зображення — 5MB.';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, (string) ($file['tmp_name'] ?? '')) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        if (!is_string($mime) || !in_array($mime, $allowedMime, true)) {
            return 'Дозволені формати зображень: jpg, jpeg, png, webp.';
        }

        return null;
    }

    private function uploadImageFile(array $file): ?string
    {
        $imageManager = new ImageManager();

        try {
            $paths = $imageManager->processUploadedProductImage($file, [
                'thumb_width' => (int) get_setting('media_thumb_width', 200),
                'medium_width' => (int) get_setting('media_medium_width', 800),
                'quality' => (int) get_setting('media_quality', 82),
                'auto_webp' => (int) get_setting('media_auto_webp', 1),
                'apply_watermark' => (int) get_setting('media_apply_watermark', 0),
                'watermark_path' => (string) get_setting('media_watermark_path', ''),
                'watermark_position' => (string) get_setting('media_watermark_position', 'bottom-right'),
            ]);

            return $paths['original'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function uploadGalleryImages(int $productId, int $alreadyStoredCount = 0): array
    {
        $files = $this->normalizeFilesInput('images');
        $files = array_values(array_filter($files, static function (array $file) {
            return ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        }));

        if (empty($files)) {
            return ['paths' => [], 'error' => null];
        }

        $limit = $this->getGalleryImagesLimit();
        if ($alreadyStoredCount + count($files) > $limit) {
            return ['paths' => [], 'error' => 'Можна завантажити максимум ' . $limit . ' фото для одного товару.'];
        }

        foreach ($files as $file) {
            $validationError = $this->validateImageFile($file, self::MAX_IMAGE_SIZE_BYTES);
            if ($validationError !== null) {
                return ['paths' => [], 'error' => $validationError];
            }
        }

        $uploadedPaths = [];
        foreach ($files as $file) {
            $path = $this->uploadImageFile($file);
            if ($path === null) {
                foreach ($uploadedPaths as $uploadedPath) {
                    $absolute = __DIR__ . '/../../public' . $uploadedPath;
                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }
                }

                return ['paths' => [], 'error' => 'Не вдалося зберегти одне із зображень.'];
            }
            $uploadedPaths[] = $path;
        }

        $sortOrder = ProductImage::getNextSortOrder($productId);
        foreach ($uploadedPaths as $path) {
            ProductImage::createForProduct($productId, $path, $sortOrder++);
        }

        return ['paths' => $uploadedPaths, 'error' => null];
    }

    private function removeImageFileByPath(?string $imagePath): void
    {
        if (empty($imagePath) || !is_string($imagePath)) {
            return;
        }

        $absolutePath = __DIR__ . '/../../public' . $imagePath;
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        if (strpos($imagePath, '/uploads/products/gallery/original/') === 0) {
            $basename = pathinfo($imagePath, PATHINFO_FILENAME);
            foreach (['medium', 'thumb'] as $variant) {
                $pattern = __DIR__ . '/../../public/uploads/products/gallery/' . $variant . '/' . $basename . '.*';
                foreach (glob($pattern) ?: [] as $variantFile) {
                    if (is_file($variantFile)) {
                        @unlink($variantFile);
                    }
                }
            }
        }
    }

    private function resolvePrimaryImageFromGallery(int $productId): ?string
    {
        $gallery = ProductImage::getByProduct($productId);
        if (!empty($gallery[0]['image_path'])) {
            return (string) $gallery[0]['image_path'];
        }

        return null;
    }

    public function store()
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

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
        $attributePairError = $this->validateAttributePairs();
        if ($attributePairError !== null) {
            $this->flashProductFormData($data, $attributeRows);
            $_SESSION['error'] = $attributePairError;
            header('Location: /admin/products/create');
            exit;
        }

        $validationError = $this->validateProductPayload($data);
        if ($validationError !== null) {
            $this->flashProductFormData($data, $attributeRows);
            $_SESSION['error'] = $validationError;
            header('Location: /admin/products/create');
            exit;
        }

        $data['price'] = (float) str_replace(',', '.', (string) $data['price']);
        $data['image'] = null;

        $productId = Product::create($data);

        if ($productId) {
            if (!$this->validateAttributesForCategory($data['category_id'], $attributeRows)) {
                Product::delete((int) $productId);
                $this->flashProductFormData($data, $attributeRows);
                $_SESSION['error'] = 'Неможливо зберегти характеристики: обрано атрибути, які не дозволені для категорії товару.';
                header('Location: /admin/products/create');
                exit;
            }

            $uploadResult = $this->uploadGalleryImages((int) $productId);
            if ($uploadResult['error'] !== null) {
                Product::delete((int) $productId);
                $this->flashProductFormData($data, $attributeRows);
                $_SESSION['error'] = $uploadResult['error'];
                header('Location: /admin/products/create');
                exit;
            }

            $this->syncProductAttributes((int) $productId, $attributeRows);

            $primaryImage = $this->resolvePrimaryImageFromGallery((int) $productId);
            if ($primaryImage !== null) {
                Product::update((int) $productId, ['image' => $primaryImage]);
            }

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
            : ProductAttributeValue::getByProduct($id);
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
            'galleryImages' => ProductImage::getByProduct((int) $id),
            'galleryLimit' => $this->getGalleryImagesLimit(),
        ], 'admin');
    }

    public function update($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

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
        $attributePairError = $this->validateAttributePairs();
        if ($attributePairError !== null) {
            $this->flashProductFormData($data, $attributeRows);
            $_SESSION['error'] = $attributePairError;
            header('Location: /admin/products/edit/' . $id);
            exit;
        }

        $validationError = $this->validateProductPayload($data);
        if ($validationError !== null) {
            $this->flashProductFormData($data, $attributeRows);
            $_SESSION['error'] = $validationError;
            header('Location: /admin/products/edit/' . $id);
            exit;
        }

        $data['price'] = (float) str_replace(',', '.', (string) $data['price']);

        $existingGallery = ProductImage::getByProduct((int) $id);
        $existingById = [];
        foreach ($existingGallery as $image) {
            $existingById[(int) ($image['id'] ?? 0)] = $image;
        }

        $deleteImageIds = $_POST['delete_gallery_image_ids'] ?? [];
        if (!is_array($deleteImageIds)) {
            $deleteImageIds = [];
        }

        $deleteImageIds = array_values(array_unique(array_filter(array_map('intval', $deleteImageIds), static function ($imageId) use ($existingById) {
            return $imageId > 0 && isset($existingById[$imageId]);
        })));

        $remainingCount = max(0, count($existingGallery) - count($deleteImageIds));
        $uploadResult = $this->uploadGalleryImages((int) $id, $remainingCount);
        if ($uploadResult['error'] !== null) {
            $this->flashProductFormData($data, $attributeRows);
            $_SESSION['error'] = $uploadResult['error'];
            header('Location: /admin/products/edit/' . $id);
            exit;
        }

        if (Product::update($id, $data)) {
            if (!$this->validateAttributesForCategory($data['category_id'], $attributeRows)) {
                $this->flashProductFormData($data, $attributeRows);
                $_SESSION['error'] = 'Неможливо зберегти характеристики: обрано атрибути, які не дозволені для категорії товару.';
                header('Location: /admin/products/edit/' . $id);
                exit;
            }

            foreach ($deleteImageIds as $imageId) {
                $image = $existingById[$imageId] ?? null;
                if (!$image) {
                    continue;
                }

                ProductImage::deleteById($imageId);
                $this->removeImageFileByPath((string) ($image['image_path'] ?? ''));
            }

            $this->syncProductAttributes((int) $id, $attributeRows);

            $primaryImage = $this->resolvePrimaryImageFromGallery((int) $id);
            Product::update((int) $id, ['image' => $primaryImage]);

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

    public function setMainImage($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $imageId = (int) ($_POST['main_gallery_image_id'] ?? 0);
        $image = ProductImage::findById($imageId);
        if (!$image || (int) ($image['product_id'] ?? 0) !== (int) $id) {
            $_SESSION['error'] = 'Не вдалося знайти обране фото.';
            header('Location: /admin/products/edit/' . (int) $id);
            exit;
        }

        Product::update((int) $id, ['image' => (string) $image['image_path']]);
        $_SESSION['success'] = 'Головне фото оновлено.';
        header('Location: /admin/products/edit/' . (int) $id);
        exit;
    }

    public function delete($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $galleryImages = ProductImage::getByProduct((int) $id);
        foreach ($galleryImages as $galleryImage) {
            $this->removeImageFileByPath((string) ($galleryImage['image_path'] ?? ''));
        }
        ProductImage::deleteByProduct((int) $id);

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
