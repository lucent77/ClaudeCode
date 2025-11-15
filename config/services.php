<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'slack' => [
        // Slack Bot Token (xoxb- prefix)
        'bot_token' => env('SLACK_BOT_TOKEN', 'xoxb-YOUR-BOT-TOKEN-HERE'),

        // Slack Workspace ID
        'workspace_id' => env('SLACK_WORKSPACE_ID', 'T-YOUR-WORKSPACE-ID'),

        // Canvas ID (Canvas with embedded List)
        'canvas_id' => env('SLACK_CANVAS_ID', 'F-YOUR-CANVAS-ID'),

        // Canvas share URL (backup)
        'canvas_share_url' => env('SLACK_CANVAS_URL', 'https://your-workspace.slack.com/docs/...'),

        // Slack API Base URL
        'api_base_url' => 'https://slack.com/api',

        // File storage strategy: 'slack_url', 'local_download', 'hybrid'
        'file_storage_strategy' => 'hybrid',

        // Maximum file size (50MB)
        'max_file_size' => 52428800,
    ],

    'sync' => [
        'enabled' => true,
        'interval_minutes' => 30,
    ],

];
