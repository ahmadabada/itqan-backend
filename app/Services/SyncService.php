<?php

namespace App\Services;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ReexamPermit;
use App\Models\Student;

class SyncService
{
    public function processExams(array $exams, int $examinerId): array
    {
        // Pre-compute student occurrence counts to detect within-batch duplicates (BR-CONF-02)
        $studentKeys = $this->buildStudentKeys($exams);

        $results = [];
        foreach ($exams as $examData) {
            $results[] = $this->processOne($examData, $examinerId, $studentKeys);
        }

        return $results;
    }

    private function processOne(array $data, int $examinerId, array $studentKeys): array
    {
        // Step 1: Resolve student
        [$student, $manualConflict] = $this->resolveStudent($data);

        // Step 2: Detect conflict reason
        $studentKey     = $this->studentKey($data);
        $conflictReason = $this->detectConflict(
            $data, $student, $studentKeys[$studentKey] ?? 1, $manualConflict
        );

        // Step 3: Consume permit if valid (only when no prior-permit conflict)
        $permitId = null;
        if ($data['reexam_permit_code'] && $conflictReason !== 'existing_approved_no_permit') {
            $permit = ReexamPermit::where('permit_code', $data['reexam_permit_code'])
                ->where('student_id', $student->id)
                ->first();

            if ($permit && $permit->isValid()) {
                $permit->update(['is_used' => true, 'used_at' => now()]);
                $permitId = $permit->id;
            }
        }

        // Step 4: Create exam
        $status      = $conflictReason ? ExamStatus::PendingReview : ExamStatus::Approved;
        $isApproved  = $conflictReason === null;
        $attemptNumber = (Exam::where('student_id', $student->id)->max('attempt_number') ?? 0) + 1;

        $exam = Exam::create([
            'student_id'       => $student->id,
            'examiner_id'      => $examinerId,
            'exam_type'        => $data['exam_type'],
            'source'           => ExamSource::Flutter,
            'status'           => $status,
            'attempt_number'   => $attemptNumber,
            'rulings_score'    => $data['rulings_score'],
            'total_score'      => $data['total_score'],
            'is_approved'      => $isApproved,
            'device_uuid'      => $data['device_uuid'],
            'reexam_permit_id' => $permitId,
            'conflict_reason'  => $conflictReason,
            'started_at'       => $data['started_at'],
            'completed_at'     => $data['completed_at'],
            'synced_at'        => now(),
        ]);

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

        // BR-REEX-08: One approved per student — revoke others when auto-approving
        if ($isApproved) {
            Exam::where('student_id', $student->id)
                ->where('id', '!=', $exam->id)
                ->where('is_approved', true)
                ->update(['is_approved' => false]);
        }

        $result = [
            'local_id'       => $data['local_id'],
            'server_exam_id' => $exam->id,
            'status'         => $exam->status->value,
            'is_approved'    => $exam->is_approved,
        ];

        if ($conflictReason) {
            $result['conflict_reason'] = $conflictReason;
        }

        return $result;
    }

    // Returns [Student, bool $hadNationalIdConflict]
    private function resolveStudent(array $data): array
    {
        if (! $data['is_manually_added_student']) {
            return [Student::findOrFail($data['student_id']), false];
        }

        $manualData = $data['manual_student_data'];
        $existing   = Student::where('national_id', $manualData['national_id'])->first();

        if ($existing) {
            // BR-CONF-05: manual student but national_id already exists
            return [$existing, true];
        }

        $student = Student::create([
            'national_id'  => $manualData['national_id'],
            'first_name'   => $manualData['first_name'],
            'second_name'  => $manualData['second_name'] ?? null,
            'third_name'   => $manualData['third_name'] ?? null,
            'family_name'  => $manualData['family_name'],
        ]);

        return [$student, false];
    }

    private function detectConflict(array $data, Student $student, int $batchCount, bool $manualConflict): ?string
    {
        // BR-CONF-05: manually added student whose national_id already exists in DB
        if ($manualConflict) {
            return 'manual_student_exists';
        }

        // BR-CONF-02: same student appears more than once in this batch (same device offline)
        if ($batchCount > 1) {
            return 'same_student_multiple_offline';
        }

        // BR-CONF-03: student already has a completed exam from a different device
        $otherDeviceExists = Exam::where('student_id', $student->id)
            ->where('device_uuid', '!=', $data['device_uuid'])
            ->whereNotNull('completed_at')
            ->exists();

        if ($otherDeviceExists) {
            return 'same_student_multiple_devices';
        }

        // BR-CONF-04: student has an approved exam and no valid permit was provided
        $hasApproved = Exam::where('student_id', $student->id)->where('is_approved', true)->exists();

        if ($hasApproved) {
            $hasValidPermit = false;

            if ($data['reexam_permit_code']) {
                $permit = ReexamPermit::where('permit_code', $data['reexam_permit_code'])
                    ->where('student_id', $student->id)
                    ->first();
                $hasValidPermit = $permit && $permit->isValid();
            }

            if (! $hasValidPermit) {
                return 'existing_approved_no_permit';
            }
        }

        return null;
    }

    private function studentKey(array $data): string
    {
        if (! $data['is_manually_added_student']) {
            return 'id:' . $data['student_id'];
        }

        return 'nid:' . ($data['manual_student_data']['national_id'] ?? '');
    }

    private function buildStudentKeys(array $exams): array
    {
        $counts = [];
        foreach ($exams as $exam) {
            $key            = $this->studentKey($exam);
            $counts[$key]   = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }
}
