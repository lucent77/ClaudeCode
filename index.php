<?php
/**
 * Index Page
 * Redirects to appropriate page based on authentication status
 */

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>
