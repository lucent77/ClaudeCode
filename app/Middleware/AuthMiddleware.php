<?php
/**
 * Authentication Middleware
 *
 * Ensures user is authenticated before accessing protected routes
 */

namespace App\Middleware;

use App\Core\Session;
use App\Core\Router;

class AuthMiddleware
{
    public function handle(): void
    {
        Session::start();

        if (!Session::isLoggedIn()) {
            // Check if this is an AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized'
                ]);
                exit;
            } else {
                Router::redirect('/login');
            }
        }
    }
}
