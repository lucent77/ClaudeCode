<?php
/**
 * Creodent Dashboard - Application Constants
 */

// Application Info
define('APP_NAME', 'Creodent Analytics');
define('APP_VERSION', '1.0.0');

// Session Configuration
define('SESSION_NAME', 'creodent_session');
define('SESSION_TIMEOUT', 1800); // 30 minutes

// Pagination
define('DEFAULT_PAGE_SIZE', 50);
define('MAX_PAGE_SIZE', 100);

// Date/Time
define('TIMEZONE', 'America/New_York');
define('DATE_FORMAT', 'n/j/Y'); // M/D/YYYY
define('DATETIME_FORMAT', 'n/j/Y g:i A');

// Currency
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_CODE', 'USD');

// Branch Configuration
define('BRANCHES', [
    'ALL' => ['name' => 'All Branches', 'color' => '#3b82f6'],
    'NYC' => ['name' => 'NYC Branch', 'color' => '#f59e0b'],
    'HV' => ['name' => 'HV Branch', 'color' => '#10b981']
]);

// Risk Thresholds
define('RISK_DECLINE_THRESHOLD', 20); // 20% decline triggers risk alert
define('INACTIVE_DAYS_THRESHOLD', 90); // Days without purchase = inactive

// Cache TTL (seconds)
define('CACHE_TTL_SHORT', 300);  // 5 minutes
define('CACHE_TTL_MEDIUM', 900); // 15 minutes
define('CACHE_TTL_LONG', 3600);  // 1 hour
