<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Models\Category;
use App\Services\SlugHelper;

class AdminCategoryController
{
    // 1 МБ — за вимогою: тільки перевірка розширення + ліміт розміру.
    // Додатково звіряємо реальний MIME-тип файлу (finfo) — захист від файлів,
    // яким просто підмінили розширення (класична вразливість аплоаду).
    private const CATEGORY_IMAGE_MAX_SIZE_BYTES = 1048576; // 1 MB
    private const CATEGORY_IMAGE_ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    private const CATEGORY_IMAGE_ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const CATEGORY_IMAGE_UPLOAD_DIR = __DIR__ . '/../../public/uploads/categories';
    private const CATEGORY_IMAGE_PUBLIC_PREFIX = '/uploads/categories/';

    private function validateCsrfOrAbort()
    {
        Csrf::abortIfInvalid();
    }

    private function checkAdmin()
    {
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Перевірка файлу зображення категорії.
     * Повертає null, якщо все гаразд (або файл просто не завантажували — поле не обовʼязкове).
     */
    private function validateCategoryImage(?array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE || empty($file['tmp_name'])) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return __('category_image_upload_error');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::CATEGORY_IMAGE_MAX_SIZE_BYTES) {
            return __('category_image_max_size');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, self::CATEGORY_IMAGE_ALLOWED_EXT, true)) {
            return __('category_image_invalid_format');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, (string) $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!is_string($mime) || !in_array($mime, self::CATEGORY_IMAGE_ALLOWED_MIME, true)) {
            return __('category_image_invalid_format');
        }

        return null;
    }

    /**
     * Переносить перевірений файл у public/uploads/categories з випадковим ім'ям
     * (не довіряємо оригінальній назві файла від користувача).
     * Повертає публічний шлях (для БД) або null у разі помилки збереження.
     */
    private function uploadCategoryImage(array $file): ?string
    {
        if (!is_dir(self::CATEGORY_IMAGE_UPLOAD_DIR)
            && !mkdir(self::CATEGORY_IMAGE_UPLOAD_DIR, 0755, true)
            && !is_dir(self::CATEGORY_IMAGE_UPLOAD_DIR)
        ) {
            return null;
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $filename  = 'category_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $target    = self::CATEGORY_IMAGE_UPLOAD_DIR . '/' . $filename;

        if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $target)) {
            return null;
        }

        return self::CATEGORY_IMAGE_PUBLIC_PREFIX . $filename;
    }

    /**
     * Видаляє файл зображення категорії з диска (при заміні/видаленні/відкаті).
     */
    private function deleteCategoryImageFile(?string $publicPath): void
    {
        if (empty($publicPath)) {
            return;
        }
        $absolute = __DIR__ . '/../../public' . $publicPath;
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    public function index()
    {
        $this->checkAdmin();
        $categories = Category::getFlatTree();
        View::render('admin/categories/index', ['categories' => $categories], 'admin');
    }

    public function create()
    {
        $this->checkAdmin();
        $categories = Category::all();
        View::render('admin/categories/create', ['categories' => $categories], 'admin');
    }

    public function store()
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $imageError = $this->validateCategoryImage($_FILES['image'] ?? null);
        if ($imageError !== null) {
            $_SESSION['error'] = $imageError;
            header('Location: /admin/categories/create');
            exit;
        }

        $data = [
            'name' => $_POST['name'],
            'slug' => !empty($_POST['slug']) ? $_POST['slug'] : SlugHelper::getUnique($_POST['name'], 'category'),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            'description' => $_POST['description'] ?? '',
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? ''
        ];

        $uploadedImage = null;
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadedImage = $this->uploadCategoryImage($_FILES['image']);
            if ($uploadedImage === null) {
                $_SESSION['error'] = __('category_image_save_error');
                header('Location: /admin/categories/create');
                exit;
            }
            $data['image'] = $uploadedImage;
        }

        if (Category::create($data)) {
            $_SESSION['success'] = __('admin_category_created');
            header('Location: /admin/categories');
        } else {
            // Категорію не створено — прибираємо щойно завантажений файл, щоб не лишався "сирітський"
            $this->deleteCategoryImageFile($uploadedImage);
            $_SESSION['error'] = __('admin_category_create_error');
            header('Location: /admin/categories/create');
        }
        exit;
    }

    public function edit($id)
    {
        $this->checkAdmin();
        $category = Category::findById($id);
        if (!$category) {
            header('Location: /admin/categories');
            exit;
        }
        $categories = Category::all();
        View::render('admin/categories/edit', [
            'category' => $category,
            'categories' => $categories
        ], 'admin');
    }

    public function update($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $existing = Category::findById((int) $id);
        if (!$existing) {
            header('Location: /admin/categories');
            exit;
        }

        $imageError = $this->validateCategoryImage($_FILES['image'] ?? null);
        if ($imageError !== null) {
            $_SESSION['error'] = $imageError;
            header('Location: /admin/categories/edit/' . $id);
            exit;
        }

        $data = [
            'name' => $_POST['name'],
            'slug' => $_POST['slug'],
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            'description' => $_POST['description'] ?? '',
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? ''
        ];

        $oldImage      = $existing['image'] ?? null;
        $uploadedImage = null;
        $hasNewUpload  = ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($hasNewUpload) {
            $uploadedImage = $this->uploadCategoryImage($_FILES['image']);
            if ($uploadedImage === null) {
                $_SESSION['error'] = __('category_image_save_error');
                header('Location: /admin/categories/edit/' . $id);
                exit;
            }
            $data['image'] = $uploadedImage;
        } elseif (!empty($_POST['remove_image'])) {
            $data['image'] = null;
        }

        if (Category::update($id, $data)) {
            // Стару картинку прибираємо з диска лише якщо вона дійсно замінена/видалена
            if (($hasNewUpload || array_key_exists('image', $data)) && !empty($oldImage)) {
                $this->deleteCategoryImageFile($oldImage);
            }
            $_SESSION['success'] = __('admin_category_updated');
            header('Location: /admin/categories');
        } else {
            // Оновлення не вдалося — прибираємо щойно завантажений файл
            $this->deleteCategoryImageFile($uploadedImage);
            $_SESSION['error'] = __('admin_category_update_error');
            header('Location: /admin/categories/edit/' . $id);
        }
        exit;
    }

    public function delete($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $category = Category::findById((int) $id);

        if (Category::delete($id)) {
            $this->deleteCategoryImageFile($category['image'] ?? null);
            $_SESSION['success'] = __('admin_category_deleted');
        } else {
            $_SESSION['error'] = __('admin_category_delete_error');
        }
        header('Location: /admin/categories');
        exit;
    }
}
