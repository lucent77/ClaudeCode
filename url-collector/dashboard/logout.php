<?php
/**
 * URL Collector - Dashboard Logout
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config/config.php';

require_once APP_ROOT . '/lib/security.php';
require_once APP_ROOT . '/lib/util.php';

Security::init($config);
Util::init($config);
Security::startSession();

// Log the logout
if (Security::isLoggedIn()) {
    Util::logInfo('Admin logged out from IP: ' . Security::getClientIp());
}

// Clear session
Security::setLoggedIn(false);
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
