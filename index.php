<?php
session_start();

// Redirect to dashboard if logged in, otherwise to login
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>
