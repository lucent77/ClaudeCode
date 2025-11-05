<?php
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: /public/login.php');
exit;
