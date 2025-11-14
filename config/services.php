<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'bot_token' => env('SLACK_BOT_TOKEN'),
        'workspace_id' => env('SLACK_WORKSPACE_ID'),
        'canvas_id' => env('SLACK_CANVAS_ID'),
        'api_base_url' => env('SLACK_API_BASE_URL', 'https://slack.com/api'),
        'file_storage_strategy' => env('FILE_STORAGE_STRATEGY', 'hybrid'),
        'max_file_size' => env('MAX_FILE_SIZE', 52428800), // 50MB
    ],

    'sync' => [
        'enabled' => env('SYNC_ENABLED', true),
        'interval_minutes' => env('SYNC_INTERVAL_MINUTES', 30),
    ],

];
