<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Models\Category;
use App\Services\SlugHelper;

class AdminCategoryController
{
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

        $data = [
            'name' => $_POST['name'],
            'slug' => !empty($_POST['slug']) ? $_POST['slug'] : SlugHelper::getUnique($_POST['name'], 'category'),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            'description' => $_POST['description'] ?? '',
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? ''
        ];

        if (Category::create($data)) {
            $_SESSION['success'] = __('admin_category_created');
            header('Location: /admin/categories');
        } else {
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

        $data = [
            'name' => $_POST['name'],
            'slug' => $_POST['slug'],
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            'description' => $_POST['description'] ?? '',
            'meta_title' => $_POST['meta_title'] ?? '',
            'meta_description' => $_POST['meta_description'] ?? ''
        ];

        if (Category::update($id, $data)) {
            $_SESSION['success'] = __('admin_category_updated');
            header('Location: /admin/categories');
        } else {
            $_SESSION['error'] = __('admin_category_update_error');
            header('Location: /admin/categories/edit/' . $id);
        }
        exit;
    }

    public function delete($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        if (Category::delete($id)) {
            $_SESSION['success'] = __('admin_category_deleted');
        } else {
            $_SESSION['error'] = __('admin_category_delete_error');
        }
        header('Location: /admin/categories');
        exit;
    }
}
