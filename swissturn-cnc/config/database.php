<?php
/**
 * Database Configuration
 *
 * Configure your MySQL database connection here.
 * For Hostinger, update these values from your hosting panel.
 */

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'database' => getenv('DB_NAME') ?: 'swissturn_cnc',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'port' => getenv('DB_PORT') ?: 3306,
];
