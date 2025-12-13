<?php

/**
 * Laravel - Hostinger Entry Point
 *
 * This file serves as the entry point for Hostinger shared hosting
 * where the document root cannot be changed to the public folder.
 *
 * For security, it's recommended to configure the web server
 * to point to the /public directory if possible.
 */

// Set the public path to this directory
$publicPath = __DIR__.'/public';

// Ensure public directory exists
if (!is_dir($publicPath)) {
    die('Public directory not found. Please ensure the application is properly installed.');
}

// Change to the public directory
chdir($publicPath);

// Include the public index.php
require_once $publicPath.'/index.php';
