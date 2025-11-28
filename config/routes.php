<?php
/**
 * Route Definitions
 *
 * Format: [method, path, controller, action]
 * Path supports {param} placeholders
 */

declare(strict_types=1);

return [
    // Health check
    ['GET', '/health', 'HealthController', 'index'],

    // Public pages
    ['GET', '/', 'HomeController', 'index'],
    ['GET', '/submit', 'SubmitController', 'index'],
    ['POST', '/submit', 'SubmitController', 'store'],
    ['GET', '/thanks/{public_id}', 'SubmitController', 'thanks'],

    // Public posts listing
    ['GET', '/posts', 'PostController', 'index'],
    ['GET', '/posts/{public_id}', 'PostController', 'show'],

    // API endpoints (public)
    ['POST', '/api/posts/{public_id}/votes', 'Api\VoteController', 'store'],
    ['POST', '/api/posts/{public_id}/comments', 'Api\CommentController', 'store'],

    // Authentication
    ['GET', '/auth/login', 'AuthController', 'loginForm'],
    ['POST', '/auth/login', 'AuthController', 'login'],
    ['POST', '/auth/logout', 'AuthController', 'logout'],

    // Admin routes
    ['GET', '/admin', 'Admin\DashboardController', 'index'],
    ['GET', '/admin/queue', 'Admin\QueueController', 'index'],
    ['POST', '/admin/posts/{id}/moderate', 'Admin\QueueController', 'moderate'],
    ['POST', '/admin/posts/{id}/assign', 'Admin\PostController', 'assign'],
    ['POST', '/admin/posts/{id}/status', 'Admin\PostController', 'updateStatus'],
    ['POST', '/admin/posts/{id}/comment', 'Admin\PostController', 'comment'],
    ['GET', '/admin/posts/{id}', 'Admin\PostController', 'show'],
    ['GET', '/admin/export.csv', 'Admin\ExportController', 'csv'],
    ['GET', '/admin/users', 'Admin\UserController', 'index'],
    ['POST', '/admin/users', 'Admin\UserController', 'store'],

    // Digests
    ['GET', '/digests', 'DigestController', 'index'],
    ['GET', '/digests/{id}', 'DigestController', 'show'],
];
