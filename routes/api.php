<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth — login is public, the rest require a token
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:api-auth');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Device registration (FCM + last_user_id upsert)
        Route::post('devices/register', [DeviceController::class, 'register']);

        // Student roster — downloaded and cached read-only; the device picks from
        // it and never creates students. (No POST: field creation is forbidden.)
        Route::get('students', [StudentController::class, 'index']);

        // Sync — the device uploads completed offline exams here (student_id +
        // scores). The online exam flow (exams/start|complete|…), the question
        // bank pull, the re-exam permits, and the suggested-students endpoints
        // were all retired on 2026-07-17; their controllers remain but unrouted.
        Route::post('sync/exams', [SyncController::class, 'syncExams']);
        Route::get('sync/status', [SyncController::class, 'status']);
        Route::get('sync/commands', [SyncController::class, 'commands']);
        Route::post('sync/commands/{deviceCommand}/ack', [SyncController::class, 'ackCommand']);
    });
});
