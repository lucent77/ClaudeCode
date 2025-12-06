<?php
/**
 * Creodent Dashboard - Logout Handler
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Response.php';

use Creodent\Core\{Auth, Response};

Auth::logout();
Response::redirect('index.php');
