<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ReexamPermit;
use App\Services\ScoreCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    // POST /exams/start
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'student_id'          => ['required', 'integer', 'exists:students,id'],
            'exam_type'           => ['required', 'in:full_quran,half_quran'],
            'reexam_permit_code'  => ['nullable', 'string'],
        ]);

        $studentId = $request->student_id;

        // BR-REEX-01: Check if student has approved exam
        $hasApproved = Exam::where('student_id', $studentId)->where('is_approved', true)->exists();

        if ($hasApproved) {
            $permit = ReexamPermit::where('permit_code', $request->reexam_permit_code)
                ->where('student_id', $studentId)
                ->first();

            if (! $permit || ! $permit->isValid()) {
                return response()->json([
                    'error'   => 'reexam_permit_required',
                    'message' => 'هذا الطالب لديه اختبار معتمد، يرجى تقديم إذن إعادة.',
                ], 403);
            }

            $permit->update(['is_used' => true, 'used_at' => now()]);
        }

        $attemptNumber = (Exam::where('student_id', $studentId)->max('attempt_number') ?? 0) + 1;
        $deviceUuid    = $request->header('X-Device-UUID');

        $exam = Exam::create([
            'student_id'     => $studentId,
            'examiner_id'    => $request->user()->id,
            'exam_type'      => $request->exam_type,
            'source'         => ExamSource::Flutter,
            'status'         => ExamStatus::InProgress,
            'attempt_number' => $attemptNumber,
            'is_approved'    => false,
            'device_uuid'    => $deviceUuid,
            'started_at'     => now(),
        ]);

        foreach ([1, 2, 3] as $qNum) {
            ExamQuestion::create([
                'exam_id'             => $exam->id,
                'question_number'     => $qNum,
                'errors_count'        => 0,
                'warnings_count'      => 0,
                'continuations_count' => 0,
                'final_score'         => config('exam.score_per_question', 30),
            ]);
        }

        return response()->json(['exam' => new ExamResource($exam)], 201);
    }

    // PATCH /exams/{id}/progress — BR-EXAM-08: auto-save from Flutter
    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'questions'                           => ['required', 'array'],
            'questions.*.question_number'         => ['required', 'integer', 'between:1,3'],
            'questions.*.errors_count'            => ['required', 'integer', 'min:0'],
            'questions.*.warnings_count'          => ['required', 'integer', 'min:0'],
            'questions.*.continuations_count'     => ['required', 'integer', 'min:0'],
            'questions.*.final_score'             => ['required', 'numeric', 'min:0', 'max:30'],
        ]);

        $exam = Exam::where('id', $id)
            ->where('examiner_id', $request->user()->id)
            ->where('status', ExamStatus::InProgress)
            ->firstOrFail();

        foreach ($request->questions as $q) {
            ExamQuestion::where('exam_id', $exam->id)
                ->where('question_number', $q['question_number'])
                ->update([
                    'errors_count'        => $q['errors_count'],
                    'warnings_count'      => $q['warnings_count'],
                    'continuations_count' => $q['continuations_count'],
                    'final_score'         => $q['final_score'],
                ]);
        }

        return response()->json([
            'exam_id'  => $exam->id,
            'status'   => $exam->status->value,
            'saved_at' => now()->toISOString(),
        ]);
    }

    // POST /exams/{id}/complete
    public function complete(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'questions'                       => ['required', 'array', 'size:3'],
            'questions.*.question_number'     => ['required', 'integer', 'between:1,3'],
            'questions.*.errors_count'        => ['required', 'integer', 'min:0'],
            'questions.*.warnings_count'      => ['required', 'integer', 'min:0'],
            'questions.*.continuations_count' => ['required', 'integer', 'min:0'],
            'questions.*.final_score'         => ['required', 'numeric', 'min:0', 'max:30'],
            'rulings_score'                   => ['required', 'numeric', 'min:0', 'max:10'],
        ]);

        $exam = Exam::where('id', $id)
            ->where('examiner_id', $request->user()->id)
            ->where('status', ExamStatus::InProgress)
            ->firstOrFail();

        foreach ($request->questions as $q) {
            ExamQuestion::where('exam_id', $exam->id)
                ->where('question_number', $q['question_number'])
                ->update([
                    'errors_count'        => $q['errors_count'],
                    'warnings_count'      => $q['warnings_count'],
                    'continuations_count' => $q['continuations_count'],
                    'final_score'         => $q['final_score'],
                ]);
        }

        $questionsForCalc = array_map(fn($q) => [
            'errors_count'        => $q['errors_count'],
            'warnings_count'      => $q['warnings_count'],
            'continuations_count' => $q['continuations_count'],
        ], $request->questions);

        $totalScore = ScoreCalculator::totalScore($questionsForCalc, $request->rulings_score);
        $isPassing  = ScoreCalculator::isPassing($totalScore);

        // BR-CONF-01: Auto-approve if no conflict
        $exam->update([
            'status'       => ExamStatus::Approved,
            'rulings_score' => $request->rulings_score,
            'total_score'  => $totalScore,
            'is_passed'    => $isPassing,
            'is_approved'  => true,
            'completed_at' => now(),
        ]);

        // BR-REEX-08: One approved per student
        Exam::where('student_id', $exam->student_id)
            ->where('id', '!=', $exam->id)
            ->where('is_approved', true)
            ->update(['is_approved' => false]);

        return response()->json(['exam' => new ExamResource($exam)]);
    }

    // GET /exams/{id}
    public function show(Request $request, int $id): JsonResponse
    {
        $exam = Exam::with(['student', 'examiner', 'questions'])->findOrFail($id);

        return response()->json(['exam' => new ExamResource($exam)]);
    }

    // GET /exams/in-progress — BR-EXAM-09
    public function inProgress(Request $request): JsonResponse
    {
        $exams = Exam::where('examiner_id', $request->user()->id)
            ->where('status', ExamStatus::InProgress)
            ->with(['student', 'questions'])
            ->latest()
            ->get();

        return response()->json(['exams' => ExamResource::collection($exams)]);
    }
}
