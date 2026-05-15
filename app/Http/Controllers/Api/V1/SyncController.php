<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReexamPermit;
use App\Models\Student;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(private readonly SyncService $syncService) {}

    // POST /sync/exams — BR-SYNC-03: Flutter uploads all offline exams on reconnect
    public function syncExams(Request $request): JsonResponse
    {
        $request->validate([
            'exams'                                   => ['required', 'array', 'min:1'],
            'exams.*.local_id'                        => ['required', 'string'],
            'exams.*.student_id'                      => ['nullable', 'integer'],
            'exams.*.student_national_id'             => ['nullable', 'string'],
            'exams.*.is_manually_added_student'       => ['required', 'boolean'],
            'exams.*.exam_type'                       => ['required', 'in:full_quran,half_quran'],
            'exams.*.rulings_score'                   => ['required', 'numeric', 'min:0', 'max:10'],
            'exams.*.total_score'                     => ['required', 'numeric', 'min:0', 'max:100'],
            'exams.*.is_passed'                       => ['required', 'boolean'],
            'exams.*.started_at'                      => ['required', 'date'],
            'exams.*.completed_at'                    => ['required', 'date'],
            'exams.*.device_uuid'                     => ['required', 'string'],
            'exams.*.reexam_permit_code'              => ['nullable', 'string'],
            'exams.*.questions'                       => ['required', 'array', 'size:3'],
            'exams.*.questions.*.question_number'     => ['required', 'integer', 'between:1,3'],
            'exams.*.questions.*.errors_count'        => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.warnings_count'      => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.continuations_count' => ['required', 'integer', 'min:0'],
            'exams.*.questions.*.final_score'         => ['required', 'numeric', 'min:0', 'max:30'],
            'exams.*.manual_student_data'             => ['nullable', 'array'],
        ]);

        $results = $this->syncService->processExams(
            $request->exams,
            $request->user()->id,
        );

        return response()->json(['results' => $results]);
    }

    // GET /sync/status
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'pending_reviews_count' => \App\Models\Exam::where('status', \App\Enums\ExamStatus::PendingReview)->count(),
            'server_time'           => now()->toISOString(),
            'students_last_updated' => Student::max('updated_at'),
            'permits_last_updated'  => ReexamPermit::max('created_at'),
        ]);
    }
}
