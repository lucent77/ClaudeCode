<?php
/**
 * Creodent Dashboard - Database Configuration
 *
 * Configure your Hostinger MySQL credentials here
 */

return [
    // NYC Branch Database (Primary - contains central tables)
    'nyc' => [
        'host' => 'localhost',
        'database' => 'creodent_nyc',
        'username' => 'your_username',
        'password' => 'your_password',
        'charset' => 'utf8mb4',
        'port' => 3306,
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true
        ]
    ],

    // HV Branch Database
    'hv' => [
        'host' => 'localhost',
        'database' => 'creodent_hv',
        'username' => 'your_username',
        'password' => 'your_password',
        'charset' => 'utf8mb4',
        'port' => 3306,
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true
        ]
    ]
];
