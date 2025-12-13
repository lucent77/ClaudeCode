<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\CocrController;
use App\Http\Controllers\Api\SolidexController;
use App\Http\Controllers\Api\PrintController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\IntakeController;
use App\Http\Controllers\Api\GsyncController;
use App\Http\Controllers\Api\StreamController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware(['auth:api'])->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/due-risk', [DashboardController::class, 'dueRisk']);

    // Intake (from Windows app)
    Route::post('/intake', [IntakeController::class, 'store']);

    // Cases
    Route::get('/cases', [CaseController::class, 'index']);
    Route::get('/cases/{id}', [CaseController::class, 'show']);
    Route::patch('/cases/{id}', [CaseController::class, 'update']);
    Route::post('/cases/{id}/notes', [NoteController::class, 'store']);

    // COCR routes
    Route::prefix('cocr')->middleware(['dept:COCR'])->group(function () {
        Route::get('/{caseId}/meta', [CocrController::class, 'getMeta']);
        Route::patch('/{caseId}/meta', [CocrController::class, 'updateMeta']);
        Route::get('/{caseId}/stages', [CocrController::class, 'getStages']);
        Route::post('/stages/{stageId}/complete', [CocrController::class, 'completeStage']);
        Route::post('/stages/{stageId}/assign', [CocrController::class, 'assignStage']);
    });

    // SOLIDEX routes
    Route::prefix('solidex')->middleware(['dept:SOLIDEX'])->group(function () {
        Route::get('/{caseId}/meta', [SolidexController::class, 'getMeta']);
        Route::patch('/{caseId}/meta', [SolidexController::class, 'updateMeta']);
        Route::get('/{caseId}/teeth', [SolidexController::class, 'getTeeth']);
        Route::post('/{caseId}/teeth', [SolidexController::class, 'createTeeth']);
        Route::get('/tooth/{toothId}/stages', [SolidexController::class, 'getToothStages']);
        Route::post('/tooth/stages/{stageId}/complete', [SolidexController::class, 'completeStage']);
        Route::post('/tooth/stages/{stageId}/assign', [SolidexController::class, 'assignStage']);
        Route::get('/{caseId}/isComplete', [SolidexController::class, 'isComplete']);
    });

    // 3D Print routes
    Route::prefix('print')->middleware(['dept:PRINT'])->group(function () {
        Route::get('/{caseId}/meta', [PrintController::class, 'getMeta']);
        Route::patch('/{caseId}/meta', [PrintController::class, 'updateMeta']);
        Route::get('/{caseId}/stages', [PrintController::class, 'getStages']);
        Route::post('/stages/{stageId}/complete', [PrintController::class, 'completeStage']);
        Route::post('/stages/{stageId}/assign', [PrintController::class, 'assignStage']);
    });

    // Worker task queues
    Route::get('/my/tasks', [TaskController::class, 'myTasks']);

    // Notes
    Route::get('/notes', [NoteController::class, 'index']);
    Route::get('/notes/{id}', [NoteController::class, 'show']);

    // Admin routes
    Route::middleware(['role:ADMIN'])->prefix('admin')->group(function () {
        // Users
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{id}', [UserController::class, 'update']);

        // Lookups
        Route::get('/lookups/{type}', [LookupController::class, 'index']);
        Route::post('/lookups/{type}', [LookupController::class, 'store']);
        Route::patch('/lookups/{type}/{id}', [LookupController::class, 'update']);
        Route::delete('/lookups/{type}/{id}', [LookupController::class, 'destroy']);

        // Stage configs
        Route::get('/stage-configs', [LookupController::class, 'stageConfigs']);
        Route::post('/stage-configs', [LookupController::class, 'createStageConfig']);
        Route::patch('/stage-configs/{id}', [LookupController::class, 'updateStageConfig']);

        // Google Sheets sync
        Route::post('/gsync/run', [GsyncController::class, 'run']);
        Route::get('/gsync/status', [GsyncController::class, 'status']);
        Route::get('/gsync/queue', [GsyncController::class, 'queue']);
    });

    // SSE Stream
    Route::get('/stream', [StreamController::class, 'stream']);
});
