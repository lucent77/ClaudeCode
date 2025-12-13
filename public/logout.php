<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/../includes/bootstrap.php';

auth()->logout();
flash('success', 'You have been successfully logged out.');
redirect(url('/login.php'));
