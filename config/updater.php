<?php

return [
    'allow_updates' => true,
    'source' => 'local',
    'current_version' => '1.0.0',
    'version_id' => 1,
    'update_server' => 'https://api.corecommerce.com/v1/update', // Заглушка, користувач змінить на свій
    'local_manifest' => __DIR__ . '/../storage/local_updates/manifest.json',
    'local_package_dir' => __DIR__ . '/../storage/local_updates',
    'backup_dir' => __DIR__ . '/../backups',
    'temp_dir' => __DIR__ . '/../storage/temp/updates',
    'staging_dir' => __DIR__ . '/../storage/temp/updates/staging',
    'exclude_dirs' => [
        'config',
        'public/uploads',
        'resources/themes',
        'storage',
        'backups'
    ],
    'maintenance_file' => __DIR__ . '/../storage/maintenance.flag'
];
