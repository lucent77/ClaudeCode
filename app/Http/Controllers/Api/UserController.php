<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->has('role')) {
            $query->byRole($request->role);
        }

        if ($request->has('dept')) {
            $query->byDept($request->dept);
        }

        if ($request->has('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->q}%")
                  ->orWhere('email', 'LIKE', "%{$request->q}%");
            });
        }

        $users = $query->orderBy('name')->get();

        return $this->successResponse([
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'dept' => $user->dept,
                    'initials' => $user->initials,
                    'active' => $user->active,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:ADMIN,LEAD,WORKER',
            'dept' => 'required|in:COCR,SOLIDEX,PRINT,FD,MULTI',
            'initials' => 'required|string|max:10',
            'active' => 'sometimes|boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'dept' => $user->dept,
                'initials' => $user->initials,
                'active' => $user->active,
            ],
        ], 'User created successfully', 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:ADMIN,LEAD,WORKER',
            'dept' => 'sometimes|in:COCR,SOLIDEX,PRINT,FD,MULTI',
            'initials' => 'sometimes|string|max:10',
            'active' => 'sometimes|boolean',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'dept' => $user->dept,
                'initials' => $user->initials,
                'active' => $user->active,
            ],
        ]);
    }
}
