<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Models\Attribute;
use App\Models\Category;

class AdminAttributeController
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

    private function getPostCategoryIds()
    {
        $categoryIds = $_POST['category_ids'] ?? [];
        if (!is_array($categoryIds)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $categoryIds), function ($id) {
            return $id > 0;
        })));
    }

    private function parseOptionLines()
    {
        $raw = trim((string) ($_POST['options_text'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);
        if (!$lines) {
            return [];
        }

        $options = [];
        foreach ($lines as $line) {
            $value = trim((string) $line);
            if ($value === '') {
                continue;
            }

            $key = mb_strtolower($value, 'UTF-8');
            if (isset($options[$key])) {
                continue;
            }

            $options[$key] = $value;
        }

        return array_values($options);
    }

    private function syncOptionsByType($attributeId, $type)
    {
        $attributeId = (int) $attributeId;
        $type = Attribute::normalizeTypeForStorage($type);

        if ($type !== Attribute::TYPE_SELECT) {
            Attribute::deleteAllOptions($attributeId);
            return;
        }

        $options = $this->parseOptionLines();
        Attribute::deleteAllOptions($attributeId);

        foreach ($options as $index => $optionValue) {
            Attribute::createOption($attributeId, [
                'name' => $optionValue,
                'value' => $optionValue,
                'sort_order' => $index + 1,
            ]);
        }
    }

    public function index()
    {
        $this->checkAdmin();

        View::render('admin/attributes/index', [
            'attributes' => Attribute::allForAdmin(),
        ], 'admin');
    }

    public function create()
    {
        $this->checkAdmin();

        View::render('admin/attributes/create', [
            'categories' => Category::getFlatTree(),
            'attributeTypes' => [
                'text' => 'Текст',
                'number' => 'Число',
                'select' => 'Список (select)',
            ],
        ], 'admin');
    }

    public function store()
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['error'] = 'Назва атрибута обовʼязкова.';
            header('Location: /admin/attributes/create');
            exit;
        }

        $attributeId = Attribute::create([
            'name' => $name,
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'type' => Attribute::normalizeTypeForStorage($_POST['type'] ?? Attribute::TYPE_TEXT),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_filterable' => !empty($_POST['is_filterable']) ? 1 : 0,
            'is_visible' => !empty($_POST['is_visible']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);

        if (!$attributeId) {
            $_SESSION['error'] = 'Помилка при створенні атрибута.';
            header('Location: /admin/attributes/create');
            exit;
        }

        Attribute::syncCategories($attributeId, $this->getPostCategoryIds());
        $this->syncOptionsByType($attributeId, $_POST['type'] ?? Attribute::TYPE_TEXT);

        $_SESSION['success'] = 'Атрибут успішно створено.';
        header('Location: /admin/attributes');
        exit;
    }

    public function edit($id)
    {
        $this->checkAdmin();

        $attribute = Attribute::findById((int) $id);
        if (!$attribute) {
            header('Location: /admin/attributes');
            exit;
        }

        $displayType = ($attribute['type'] ?? '') === Attribute::TYPE_RANGE ? 'number' : ($attribute['type'] ?? Attribute::TYPE_TEXT);
        $options = Attribute::getOptions((int) $id);

        View::render('admin/attributes/edit', [
            'attribute' => $attribute,
            'displayType' => $displayType,
            'categories' => Category::getFlatTree(),
            'assignedCategoryIds' => Attribute::getAssignedCategoryIds((int) $id),
            'attributeTypes' => [
                'text' => 'Текст',
                'number' => 'Число',
                'select' => 'Список (select)',
            ],
            'optionsText' => implode("\n", array_map(function ($option) {
                return $option['name'] ?? '';
            }, $options)),
        ], 'admin');
    }

    public function update($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        $id = (int) $id;
        if (!Attribute::findById($id)) {
            $_SESSION['error'] = 'Атрибут не знайдено.';
            header('Location: /admin/attributes');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['error'] = 'Назва атрибута обовʼязкова.';
            header('Location: /admin/attributes/edit/' . $id);
            exit;
        }

        $result = Attribute::update($id, [
            'name' => $name,
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'type' => Attribute::normalizeTypeForStorage($_POST['type'] ?? Attribute::TYPE_TEXT),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_filterable' => !empty($_POST['is_filterable']) ? 1 : 0,
            'is_visible' => !empty($_POST['is_visible']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);

        if (!$result) {
            $_SESSION['error'] = 'Помилка при оновленні атрибута.';
            header('Location: /admin/attributes/edit/' . $id);
            exit;
        }

        Attribute::syncCategories($id, $this->getPostCategoryIds());
        $this->syncOptionsByType($id, $_POST['type'] ?? Attribute::TYPE_TEXT);

        $_SESSION['success'] = 'Атрибут успішно оновлено.';
        header('Location: /admin/attributes');
        exit;
    }

    public function delete($id)
    {
        $this->checkAdmin();
        $this->validateCsrfOrAbort();

        if (Attribute::delete((int) $id)) {
            $_SESSION['success'] = 'Атрибут видалено.';
        } else {
            $_SESSION['error'] = 'Не вдалося видалити атрибут.';
        }

        header('Location: /admin/attributes');
        exit;
    }
}
