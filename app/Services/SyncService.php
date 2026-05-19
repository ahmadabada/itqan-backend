<?php

namespace App\Services;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// Offline-first sync philosophy:
//  • Every offline exam creates its own student row (record-per-exam). The server
//    never tries to deduplicate by national_id — the admin merges duplicates after
//    the exam period via the merge UI.
//  • Idempotency is keyed on client_request_id (UUID v4). Retrying the same payload
//    after a dropped response returns the existing row instead of inserting again.
//  • Uploads are auto-approved and authoritative; merging is the only place where
//    is_authoritative or is_approved gets flipped, and only by an admin.
class SyncService
{
    private const MYSQL_DUP_ENTRY = 1062;

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

        $student = $this->upsertStudent($data['student'], $examinerId, $data['device_uuid']);

        $exam = $this->insertExam($data, $examinerId, $student->id);

        foreach ($data['questions'] as $q) {
            ExamQuestion::create([
                'exam_id'                => $exam->id,
                'question_number'        => $q['question_number'],
                'recitation_question_id' => $q['recitation_question_id'] ?? null,
                'errors_count'           => $q['errors_count'],
                'warnings_count'         => $q['warnings_count'],
                'continuations_count'    => $q['continuations_count'],
                'final_score'            => $q['final_score'],
            ]);
        }

        return [
            'client_request_id' => $data['client_request_id'],
            'server_exam_id'    => $exam->id,
            'server_student_id' => $student->id,
            'status'            => 'created',
        ];
    }

    private function upsertStudent(array $data, int $examinerId, string $deviceUuid): Student
    {
        $existing = Student::where('client_request_id', $data['client_request_id'])->first();
        if ($existing) {
            return $existing;
        }

        try {
            return Student::create([
                'national_id'        => $data['national_id'],
                'first_name'         => $data['first_name'],
                'second_name'        => $data['second_name'] ?? null,
                'third_name'         => $data['third_name'] ?? null,
                'family_name'        => $data['family_name'],
                'gender'             => $data['gender'],
                // Tolerate older mobile builds that don't yet send these fields —
                // the validation rule is `required` for new clients, but defensively
                // null-coalesce here so a missing key never explodes mid-transaction.
                'is_recite_before'   => $data['is_recite_before'] ?? false,
                'student_zone'       => $data['student_zone'] ?? null,
                'created_via'        => ExamSource::Flutter->value,
                'created_by_user_id' => $examinerId,
                'device_uuid'        => $deviceUuid,
                'client_request_id'  => $data['client_request_id'],
            ]);
        } catch (QueryException $e) {
            // Race: a concurrent request inserted first. Return that row.
            if ($this->isDuplicateEntry($e)) {
                return Student::where('client_request_id', $data['client_request_id'])->firstOrFail();
            }
            throw $e;
        }
    }

    private function insertExam(array $data, int $examinerId, int $studentId): Exam
    {
        try {
            return Exam::create([
                'student_id'         => $studentId,
                'examiner_id'        => $examinerId,
                'exam_type'          => $data['exam_type'],
                'selected_groups'    => $data['selected_groups'] ?? null,
                'attempt_number'     => 1,
                'rulings_score'      => $data['rulings_score'],
                'total_score'        => $data['total_score'],
                'is_approved'        => true,
                'is_authoritative'   => true,
                'status'             => ExamStatus::Approved,
                'source'             => ExamSource::Flutter,
                'device_uuid'        => $data['device_uuid'],
                'client_request_id'  => $data['client_request_id'],
                'started_at'         => $data['started_at'],
                'completed_at'       => $data['completed_at'],
                'synced_at'          => now(),
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateEntry($e)) {
                return Exam::where('client_request_id', $data['client_request_id'])->firstOrFail();
            }
            throw $e;
        }
    }

    private function isDuplicateEntry(QueryException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === self::MYSQL_DUP_ENTRY;
    }
}
