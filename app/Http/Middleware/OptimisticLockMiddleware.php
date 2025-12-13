<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptimisticLockMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for PATCH and PUT requests
        if (!in_array($request->method(), ['PATCH', 'PUT'])) {
            return $next($request);
        }

        // Check for If-Match-Version header
        $version = $request->header('If-Match-Version');

        if ($version === null && $request->has('version')) {
            $version = $request->input('version');
        }

        if ($version !== null) {
            $request->merge(['_expected_version' => (int) $version]);
        }

        return $next($request);
    }
}
