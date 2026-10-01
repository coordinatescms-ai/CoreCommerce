<?php

/**
 * Конфігурація системи оновлень CoreCommerce.
 *
 * Перемикач source:
 *   'local'  — пакет береться з папки storage/local_updates/ на сервері.
 *              Використовуйте для ручного розгортання або тестування.
 *   'remote' — пакет завантажується автоматично з update_server.
 *              Потребує коректного update_server і api_key.
 */

return [

    // ── Дозвіл оновлень ────────────────────────────────────────────────────
    // false = кнопка оновлення прихована, всі ендпоінти повертають 403
    'allow_updates' => true,

    // ── Джерело оновлень: 'local' або 'remote' ────────────────────────────
    'source' => 'remote',

    // ── Поточна версія рушія ──────────────────────────────────────────────
    'current_version' => '1.0.0',
    'version_id'      => 1,

    // ── Remote-сервер (використовується лише при source = 'remote') ───────
    'update_server' => 'https://corecommerce.website/v1/update',

    // API-ключ для авторизації на update-сервері (необов'язково)
    'api_key' => '',

    // Таймаут HTTP-запиту до сервера (секунди)
    'remote_timeout' => 15,

    // ── Локальні шляхи ────────────────────────────────────────────────────
    'local_manifest'    => __DIR__ . '/../storage/local_updates/manifest.json',
    'local_package_dir' => __DIR__ . '/../storage/local_updates',

    // ── Системні шляхи (не змінювати без потреби) ────────────────────────
    'backup_dir'       => __DIR__ . '/../backups',
    'temp_dir'         => __DIR__ . '/../storage/temp/updates',
    'staging_dir'      => __DIR__ . '/../storage/temp/updates/staging',
    'maintenance_file' => __DIR__ . '/../storage/maintenance.flag',
    'rollback_marker'  => __DIR__ . '/../storage/temp/updates/rollback.json',

    // ── Виключення з backup і оновлення ──────────────────────────────────
    // Ці папки НЕ перезаписуються при оновленні і НЕ включаються в backup
    'exclude_dirs' => [
        'config',
        'public/uploads',
        'resources/themes',
        'storage',
        'backups',
    ],

    // ── Авто-перевірка нових версій ──────────────────────────────────────
    // Перевіряється при вході в адмінку (не частіше ніж раз на N годин)
    'auto_check_enabled'       => true,
    'auto_check_interval_hours' => 24,
];
