<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Cases
    Route::get('/cases', [CaseController::class, 'index']);
    Route::post('/cases', [CaseController::class, 'store']);
    Route::get('/cases/statistics', [CaseController::class, 'statistics']);
    Route::get('/cases/{id}', [CaseController::class, 'show']);
    Route::put('/cases/{id}', [CaseController::class, 'update']);
    Route::delete('/cases/{id}', [CaseController::class, 'destroy']);
    Route::get('/cases/{id}/activity', [CaseController::class, 'activityLogs']);

    // Attachments
    Route::get('/cases/{caseId}/attachments', [AttachmentController::class, 'index']);
    Route::post('/cases/{caseId}/attachments', [AttachmentController::class, 'store']);
    Route::get('/cases/{caseId}/attachments/{id}', [AttachmentController::class, 'show']);
    Route::put('/cases/{caseId}/attachments/{id}', [AttachmentController::class, 'update']);
    Route::delete('/cases/{caseId}/attachments/{id}', [AttachmentController::class, 'destroy']);
    Route::get('/cases/{caseId}/attachments/{id}/download', [AttachmentController::class, 'download']);

    // Attachment proxy for Slack files
    Route::get('/attachments/{id}/proxy', [AttachmentController::class, 'proxy'])->name('api.attachments.proxy');

    // Sync
    Route::post('/sync/trigger', [SyncController::class, 'sync']);
    Route::get('/sync/logs', [SyncController::class, 'logs']);
    Route::get('/sync/logs/{id}', [SyncController::class, 'showLog']);
    Route::get('/sync/test', [SyncController::class, 'testConnection']);
    Route::get('/sync/statistics', [SyncController::class, 'statistics']);

    // Users (Admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
});

// Webhook endpoint (if needed in future)
Route::post('/webhooks/slack', function () {
    // Handle Slack webhooks
    return response()->json(['status' => 'received']);
});
