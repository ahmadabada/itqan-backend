<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CompleteExamRequest;
use App\Http\Requests\Api\StartExamRequest;
use App\Http\Requests\Api\UpdateProgressRequest;
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
    public function start(StartExamRequest $request): JsonResponse
    {
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

        $exam = Exam::create([
            'student_id'     => $studentId,
            'examiner_id'    => $request->user()->id,
            'exam_type'      => $request->exam_type,
            'source'         => ExamSource::Flutter,
            'status'         => ExamStatus::InProgress,
            'attempt_number' => $attemptNumber,
            'is_approved'    => false,
            'device_uuid'    => $request->header('X-Device-UUID'),
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

    // PATCH /exams/{exam}/progress — BR-EXAM-08: auto-save from Flutter
    public function updateProgress(UpdateProgressRequest $request, Exam $exam): JsonResponse
    {
        abort_if(
            $exam->examiner_id !== $request->user()->id || $exam->status !== ExamStatus::InProgress,
            403
        );

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

    // POST /exams/{exam}/complete
    public function complete(CompleteExamRequest $request, Exam $exam): JsonResponse
    {
        abort_if(
            $exam->examiner_id !== $request->user()->id || $exam->status !== ExamStatus::InProgress,
            403
        );

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
            'status'        => ExamStatus::Approved,
            'rulings_score' => $request->rulings_score,
            'total_score'   => $totalScore,
            'is_passed'     => $isPassing,
            'is_approved'   => true,
            'completed_at'  => now(),
        ]);

        // BR-REEX-08: One approved per student
        Exam::where('student_id', $exam->student_id)
            ->where('id', '!=', $exam->id)
            ->where('is_approved', true)
            ->update(['is_approved' => false]);

        return response()->json(['exam' => new ExamResource($exam->fresh())]);
    }

    // GET /exams/{exam}
    public function show(Exam $exam): JsonResponse
    {
        return response()->json(['exam' => new ExamResource($exam->load(['student', 'examiner', 'questions']))]);
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
