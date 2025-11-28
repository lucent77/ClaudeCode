<?php
/**
 * Settings API
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (!isAjax()) {
    jsonResponse(['error' => 'Invalid request'], 400);
}

Auth::require();

$userId = Auth::id();
$input = json_decode(file_get_contents('php://input'), true);

$action = $input['action'] ?? '';

switch ($action) {
    case 'update_theme':
        $theme = $input['theme'] ?? 'system';

        if (!in_array($theme, SUPPORTED_THEMES)) {
            $theme = 'system';
        }

        User::update($userId, ['theme' => $theme]);
        $_SESSION['theme'] = $theme;

        jsonResponse(['success' => true]);
        break;

    case 'update_language':
        $language = $input['language'] ?? 'ko';

        if (!in_array($language, SUPPORTED_LANGUAGES)) {
            $language = 'ko';
        }

        User::update($userId, ['language' => $language]);
        $_SESSION['language'] = $language;

        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
