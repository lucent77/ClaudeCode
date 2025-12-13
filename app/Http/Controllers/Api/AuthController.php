<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'Invalid email or password',
            ], 401);
        }

        if (!$user->active) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'Your account has been deactivated',
            ], 401);
        }

        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token, $user);
    }

    public function me(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'dept' => $user->dept,
            'initials' => $user->initials,
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = auth()->refresh();
        $user = auth()->user();

        return $this->respondWithToken($token, $user);
    }

    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'dept' => $user->dept,
                'initials' => $user->initials,
            ],
        ]);
    }
}
