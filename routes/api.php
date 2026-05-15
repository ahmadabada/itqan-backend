<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\ReexamPermitController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth — login is public, the rest require a token
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Students (BR-SYNC-01, BR-STD-04)
        Route::get('students', [StudentController::class, 'index']);
        Route::post('students', [StudentController::class, 'store']);

        // Exams (BR-EXAM-08, BR-EXAM-09, BR-CONF-01)
        // /exams/in-progress must come before /exams/{id} to avoid "in-progress" being treated as an id
        Route::post('exams/start', [ExamController::class, 'start']);
        Route::get('exams/in-progress', [ExamController::class, 'inProgress']);
        Route::get('exams/{id}', [ExamController::class, 'show']);
        Route::patch('exams/{id}/progress', [ExamController::class, 'updateProgress']);
        Route::post('exams/{id}/complete', [ExamController::class, 'complete']);

        // Re-exam Permits (BR-REEX-02, BR-REEX-03)
        Route::post('reexam-permits/verify', [ReexamPermitController::class, 'verify']);
        Route::get('reexam-permits/active', [ReexamPermitController::class, 'active']);

        // Sync (BR-SYNC-03, BR-CONF-02~05)
        Route::post('sync/exams', [SyncController::class, 'syncExams']);
        Route::get('sync/status', [SyncController::class, 'status']);
    });
});
