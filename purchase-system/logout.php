<?php
/**
 * Logout Script
 * Purchase Management System
 */

session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: /purchase-system/login.php");
exit();
?>
