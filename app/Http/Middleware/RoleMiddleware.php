<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'Authentication required',
            ], 401);
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'You do not have permission to access this resource',
            ], 403);
        }

        return $next($request);
    }
}
