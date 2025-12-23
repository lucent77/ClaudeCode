<?php
/**
 * Application Configuration
 */

return [
    'name' => 'SwissTurn CNC Parser',
    'version' => '1.0.0',
    'debug' => getenv('APP_DEBUG') ?: true,
    'timezone' => 'UTC',

    // Upload settings
    'upload' => [
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => ['nc', 'cnc', 'txt', 'prg', 'mpf'],
        'programs_path' => __DIR__ . '/../uploads/programs/',
        'dwg_path' => __DIR__ . '/../uploads/dwg/',
    ],

    // Parser settings
    'parser' => [
        'max_lines' => 50000,
        'timeout' => 30, // seconds
    ],
];
