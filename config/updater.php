<?php

return [
    'allow_updates' => true,
    'current_version' => '1.0.0',
    'version_id' => 1,
    'update_server' => 'https://api.corecommerce.com/v1/update', // Заглушка, користувач змінить на свій
    'backup_dir' => __DIR__ . '/../backups',
    'temp_dir' => __DIR__ . '/../storage/temp/updates',
    'exclude_dirs' => [
        'config',
        'public/uploads',
        'resources/themes',
        'storage',
        'backups'
    ],
    'maintenance_file' => __DIR__ . '/../storage/maintenance.flag'
];
