<?php

namespace App\Services;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Sync philosophy (2026-07-17):
//  • The device never creates students. It carries a roster downloaded from the
//    server and uploads exams against an existing student_id, so duplicate people
//    cannot arise and there is nothing to reconcile afterwards.
//  • Idempotency is keyed on client_request_id (UUID v4). Retrying the same payload
//    after a dropped response returns the existing row instead of inserting again.
//  • A student may sit many exams. Every upload is approved on arrival; which exam
//    counts is AuthoritativeExamResolver's decision, not this class's.
class SyncService
{
    private const MYSQL_DUP_ENTRY = 1062;

    public function __construct(
        private readonly AuthoritativeExamResolver $authoritative,
        private readonly MobileExamRoundResolver $roundResolver,
        private readonly ExamApprovalService $approvalService,
    ) {}

    public function processExams(array $exams, int $examinerId): array
    {
        $results = [];
        foreach ($exams as $examData) {
            $results[] = DB::transaction(fn() => $this->processOne($examData, $examinerId));
        }
        return $results;
    }

    private function processOne(array $data, int $examinerId): array
    {
        // Idempotency check: this exam may already be on the server from a prior retry.
        $existing = Exam::where('client_request_id', $data['client_request_id'])->first();
        if ($existing) {
            return [
                'client_request_id' => $data['client_request_id'],
                'server_exam_id'    => $existing->id,
                'server_student_id' => $existing->student_id,
                'status'            => 'idempotent',
            ];
        }

        $exam = $this->insertExam($data, $examinerId);

        foreach ($data['questions'] as $q) {
            ExamQuestion::create([
                'exam_id'             => $exam->id,
                'question_number'     => $q['question_number'],
                'errors_count'        => $q['errors_count'],
                'warnings_count'      => $q['warnings_count'],
                'continuations_count' => $q['continuations_count'],
                'final_score'         => $q['final_score'],
            ]);
        }

        $this->approvalService->demoteOthersInRound($exam);
        $this->authoritative->refreshFor($exam->student_id);

        return [
            'client_request_id' => $data['client_request_id'],
            'server_exam_id'    => $exam->id,
            'server_student_id' => $exam->student_id,
            'status'            => 'created',
        ];
    }

    private function insertExam(array $data, int $examinerId): Exam
    {
        try {
            return Exam::create([
                'student_id'             => $data['student_id'],
                'examiner_id'            => $examinerId,
                'exam_round_id'          => $this->roundResolver->resolveId(),
                'attempt_number'         => $this->nextAttemptNumber($data['student_id']),
                'parts_count'            => $data['parts_count'],
                'new_memorization_parts' => $data['new_memorization_parts'],
                'rulings_score'          => $data['rulings_score'],
                'total_score'            => $data['total_score'],
                'is_approved'            => true,
                // Provisional: refreshFor() decides the real winner once the row exists.
                'is_authoritative'       => false,
                'status'                 => ExamStatus::Approved,
                'source'                 => ExamSource::Flutter,
                'device_uuid'            => $data['device_uuid'],
                'client_request_id'      => $data['client_request_id'],
                // The Flutter client serializes timestamps as UTC ISO8601 (Z
                // suffix). Convert to the app timezone before persisting so the
                // raw DB string already matches what admins see in the UI.
                'started_at'             => Carbon::parse($data['started_at'])->setTimezone(config('app.timezone')),
                'completed_at'           => Carbon::parse($data['completed_at'])->setTimezone(config('app.timezone')),
                'synced_at'              => now(),
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateEntry($e)) {
                // Either a concurrent retry of this same exam (client_request_id), or
                // two devices racing for the same attempt_number on a shared student.
                $twin = Exam::where('client_request_id', $data['client_request_id'])->first();
                if ($twin) {
                    return $twin;
                }
            }
            throw $e;
        }
    }

    // Students are shared across devices now, so attempt numbers must be derived
    // from what is already on the server rather than assumed.
    private function nextAttemptNumber(int $studentId): int
    {
        return (int) Exam::where('student_id', $studentId)->max('attempt_number') + 1;
    }

    private function isDuplicateEntry(QueryException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === self::MYSQL_DUP_ENTRY;
    }
}
