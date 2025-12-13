<?php
/**
 * CAD/CAM Workflow System - Main Entry Point
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Redirect to appropriate page based on auth status
if (!auth()->isLoggedIn()) {
    redirect(url('/login.php'));
}

// Redirect based on role
if (auth()->isSuperAdmin()) {
    redirect(url('/admin/dashboard.php'));
} elseif (auth()->isDepartmentManager()) {
    $dept = strtolower(str_replace('_', '-', auth()->userDepartment()));
    redirect(url("/department/{$dept}/dashboard.php"));
} else {
    redirect(url('/operator/tasks.php'));
}
