<?php

namespace App\Services;

use App\Enums\ExamStatus;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\MergeOperation;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

// RETIRED 2026-07-17 — NOT WIRED UP AND NOT RUNNABLE.
//
// Students are now created only on the server with a UNIQUE national_id, so
// duplicates never arise and there is nothing to merge. The admin/merges routes
// are unregistered; nothing calls this class.
//
// This is dead code, not dormant code: every method below touches
// students.master_id / merged_at / merged_by_admin_id, columns that no longer
// exist. Calling any of them throws. Reviving the flow means restoring those
// columns first — do not simply re-register the routes.
//
// Original design (kept for context):
//  • Mobile uploads create record-per-exam, so duplicates by national_id are
//    expected. After the exam period the admin selects duplicates and merges
//    them into a single "master" student.
//  • The whole pre-merge state is captured in MergeOperation.pre_merge_snapshot
//    so the operation can be undone cleanly.
//  • Exactly one exam across the merged students is flagged is_authoritative;
//    the others stay around (is_authoritative=false) for audit + undo.
class MergeService
{
    public function merge(
        array $studentIds,
        int $masterId,
        ?int $authoritativeExamId,
        ?array $masterDataOverride,
        int $adminUserId,
        ?string $notes = null,
    ): MergeOperation {
        if (! in_array($masterId, $studentIds, true)) {
            throw new InvalidArgumentException('masterId must be one of the studentIds being merged.');
        }
        if (count($studentIds) < 2) {
            throw new InvalidArgumentException('At least two students are required to merge.');
        }

        return DB::transaction(function () use (
            $studentIds, $masterId, $authoritativeExamId, $masterDataOverride, $adminUserId, $notes
        ) {
            $students = Student::whereIn('id', $studentIds)->lockForUpdate()->get();
            if ($students->count() !== count($studentIds)) {
                throw new InvalidArgumentException('One or more students not found.');
            }

            $exams = Exam::whereIn('student_id', $studentIds)->lockForUpdate()->get();

            if ($authoritativeExamId !== null && ! $exams->contains('id', $authoritativeExamId)) {
                throw new InvalidArgumentException('authoritativeExamId must belong to one of the merged students.');
            }

            $snapshot = [
                'students' => $students->map(fn($s) => $s->only([
                    'id', 'national_id', 'first_name', 'second_name', 'third_name',
                    'family_name', 'gender', 'master_id', 'merged_at', 'merged_by_admin_id',
                ]))->all(),
                'exams' => $exams->map(fn($e) => $e->only([
                    'id', 'status', 'is_authoritative', 'is_approved',
                    'authoritative_decision_by', 'authoritative_decision_at',
                ]))->all(),
            ];

            // Mark non-master students as merged into the master.
            $now = now();
            Student::whereIn('id', $studentIds)
                ->where('id', '!=', $masterId)
                ->update([
                    'master_id'          => $masterId,
                    'merged_at'          => $now,
                    'merged_by_admin_id' => $adminUserId,
                ]);

            // Apply admin-supplied overrides to the master row (name and national_id).
            if ($masterDataOverride) {
                $master = $students->firstWhere('id', $masterId);
                $allowed = array_intersect_key($masterDataOverride, array_flip([
                    'national_id', 'first_name', 'second_name', 'third_name', 'family_name',
                ]));
                if (! empty($allowed)) {
                    $master->update($allowed);
                }
            }

            // One master = one canonical exam. Everything in the merged set is
            // set to Excluded first, then the chosen one is promoted to Approved.
            // The legacy is_approved / is_authoritative columns are mirrored for
            // backward-compat with any external readers still on the old schema.
            Exam::whereIn('student_id', $studentIds)->update([
                'status'           => ExamStatus::Excluded,
                'is_authoritative' => false,
                'is_approved'      => false,
            ]);

            if ($authoritativeExamId !== null) {
                Exam::where('id', $authoritativeExamId)->update([
                    'status'                     => ExamStatus::Approved,
                    'is_authoritative'           => true,
                    'is_approved'                => true,
                    'authoritative_decision_by'  => $adminUserId,
                    'authoritative_decision_at'  => $now,
                ]);
            }

            $operation = MergeOperation::create([
                'master_student_id'        => $masterId,
                'merged_student_ids'       => array_values(array_diff($studentIds, [$masterId])),
                'authoritative_exam_id'    => $authoritativeExamId,
                'pre_merge_snapshot'       => $snapshot,
                'performed_by_admin_id'    => $adminUserId,
                'performed_at'             => $now,
                'notes'                    => $notes,
            ]);

            AuditLog::create([
                'user_id'     => $adminUserId,
                'action'      => 'merge_students',
                'target_type' => 'merge_operation',
                'target_id'   => $operation->id,
                'new_values'  => [
                    'master_student_id'     => $masterId,
                    'merged_student_ids'    => $operation->merged_student_ids,
                    'authoritative_exam_id' => $authoritativeExamId,
                ],
            ]);

            return $operation;
        });
    }

    public function undo(int $mergeOperationId, int $adminUserId, ?string $notes = null): MergeOperation
    {
        return DB::transaction(function () use ($mergeOperationId, $adminUserId, $notes) {
            $operation = MergeOperation::lockForUpdate()->findOrFail($mergeOperationId);

            if ($operation->undone_at !== null) {
                throw new InvalidArgumentException('This merge has already been undone.');
            }

            $snapshot = $operation->pre_merge_snapshot;

            foreach ($snapshot['students'] ?? [] as $row) {
                Student::where('id', $row['id'])->update([
                    'national_id'        => $row['national_id'],
                    'first_name'         => $row['first_name'],
                    'second_name'        => $row['second_name'],
                    'third_name'         => $row['third_name'],
                    'family_name'        => $row['family_name'],
                    'master_id'          => $row['master_id'],
                    'merged_at'          => $row['merged_at'],
                    'merged_by_admin_id' => $row['merged_by_admin_id'],
                ]);
            }

            foreach ($snapshot['exams'] ?? [] as $row) {
                $current = Exam::find($row['id']);
                Exam::where('id', $row['id'])->update([
                    // Snapshot fields may be absent on older merges; fall back to
                    // the current value so undo never clobbers a missing key.
                    'status'                     => $row['status'] ?? $current?->status?->value,
                    'is_authoritative'           => $row['is_authoritative'],
                    'is_approved'                => $row['is_approved'] ?? $current?->is_approved,
                    'authoritative_decision_by'  => $row['authoritative_decision_by'],
                    'authoritative_decision_at'  => $row['authoritative_decision_at'],
                ]);
            }

            $operation->forceFill([
                'undone_at'          => now(),
                'undone_by_admin_id' => $adminUserId,
                'undo_notes'         => $notes,
            ])->save();

            AuditLog::create([
                'user_id'     => $adminUserId,
                'action'      => 'undo_merge',
                'target_type' => 'merge_operation',
                'target_id'   => $operation->id,
            ]);

            return $operation;
        });
    }
}
