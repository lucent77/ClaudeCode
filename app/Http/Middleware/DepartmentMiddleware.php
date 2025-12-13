<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentMiddleware
{
    public function handle(Request $request, Closure $next, ...$depts): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'Authentication required',
            ], 401);
        }

        // Admins can access all departments
        if ($user->isAdmin()) {
            return $next($request);
        }

        // MULTI users can access all departments
        if ($user->dept === 'MULTI') {
            return $next($request);
        }

        if (empty($depts)) {
            return $next($request);
        }

        if (!in_array($user->dept, $depts)) {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'You do not have access to this department',
            ], 403);
        }

        return $next($request);
    }
}
