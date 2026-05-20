<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\RecitationQuestionController;
use App\Http\Controllers\Api\V1\ReexamPermitController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SuggestedStudentController;
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

        // Question bank — pulled once on first launch, cached locally
        Route::get('recitation-questions', [RecitationQuestionController::class, 'index']);

        // Students (BR-SYNC-01, BR-STD-04)
        Route::get('students', [StudentController::class, 'index']);
        Route::post('students', [StudentController::class, 'store']);

        // Exams — /in-progress before /{exam} to prevent "in-progress" being bound as model id
        Route::post('exams/preview-questions', [ExamController::class, 'previewQuestions']);
        Route::post('exams/start', [ExamController::class, 'start']);
        Route::get('exams/in-progress', [ExamController::class, 'inProgress']);
        Route::get('exams/{exam}', [ExamController::class, 'show']);
        Route::patch('exams/{exam}/progress', [ExamController::class, 'updateProgress']);
        Route::post('exams/{exam}/complete', [ExamController::class, 'complete']);

        // Re-exam Permits (BR-REEX-02, BR-REEX-03)
        Route::post('reexam-permits/verify', [ReexamPermitController::class, 'verify']);
        Route::get('reexam-permits/active', [ReexamPermitController::class, 'active']);

        // Suggested students. Web examiner reads gender-scoped lists from
        // /suggested-students (index/search). Mobile uses /sync to pull every
        // row (with gender) because devices are shared across examiners — the
        // local SQLite query then filters by the logged-in examiner's gender.
        Route::get('suggested-students/sync',     [SuggestedStudentController::class, 'sync']);
        Route::get('suggested-students',          [SuggestedStudentController::class, 'index']);
        Route::get('suggested-students/search',   [SuggestedStudentController::class, 'search']);
        Route::get('suggested-students/{suggestedStudent}', [SuggestedStudentController::class, 'show'])->whereNumber('suggestedStudent');

        // Sync — record-per-exam uploads + admin command channel
        Route::post('sync/exams', [SyncController::class, 'syncExams']);
        Route::get('sync/status', [SyncController::class, 'status']);
        Route::get('sync/commands', [SyncController::class, 'commands']);
        Route::post('sync/commands/{deviceCommand}/ack', [SyncController::class, 'ackCommand']);
    });
});
