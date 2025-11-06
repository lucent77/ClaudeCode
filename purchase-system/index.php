<?php
/**
 * Index/Home Page
 * Purchase Management System
 */

session_start();

// Redirect to dashboard if logged in, otherwise to login page
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header("Location: /purchase-system/dashboard.php");
} else {
    header("Location: /purchase-system/login.php");
}
exit();
?>
