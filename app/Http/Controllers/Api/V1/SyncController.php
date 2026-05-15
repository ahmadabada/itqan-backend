<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncExamsRequest;
use App\Models\Exam;
use App\Models\ReexamPermit;
use App\Models\Student;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(private readonly SyncService $syncService) {}

    // POST /sync/exams — BR-SYNC-03: Flutter uploads all offline exams on reconnect
    public function syncExams(SyncExamsRequest $request): JsonResponse
    {
        $results = $this->syncService->processExams(
            $request->validated()['exams'],
            $request->user()->id,
        );

        return response()->json(['results' => $results]);
    }

    // GET /sync/status
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'pending_reviews_count' => Exam::where('status', ExamStatus::PendingReview)->count(),
            'server_time'           => now()->toISOString(),
            'students_last_updated' => Student::max('updated_at'),
            'permits_last_updated'  => ReexamPermit::max('created_at'),
        ]);
    }
}
