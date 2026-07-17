<?php

namespace App\Services;

use App\Enums\ExamStatus;
use App\Models\Exam;
use Illuminate\Support\Facades\DB;

// A student may sit any number of exams. Exactly one of them counts, and this
// class is the only place that decides which.
//
// The rule: the most recently *taken* exam wins — unless an admin has pinned one,
// in which case the admin's choice stands until they unpin it.
//
// "Most recently taken" is ordered by completed_at, not by arrival. Offline exams
// reach the server out of order — a tablet that syncs a day late would otherwise
// override a newer exam that arrived first.
//
// Excluded exams are never candidates. If every exam is excluded, the student is
// left with no authoritative exam at all, which is the honest outcome.
class AuthoritativeExamResolver
{
    // Recompute which of a student's exams is authoritative. Safe to call after
    // any change to their exams — insert, status change, delete, or restore.
    public function refreshFor(int $studentId): ?Exam
    {
        return DB::transaction(function () use ($studentId) {
            $winner = $this->pinnedFor($studentId) ?? $this->newestFor($studentId);

            Exam::where('student_id', $studentId)
                ->when($winner, fn($q) => $q->whereKeyNot($winner->id))
                ->where('is_authoritative', true)
                ->update(['is_authoritative' => false]);

            if ($winner && ! $winner->is_authoritative) {
                $winner->forceFill(['is_authoritative' => true])->save();
            }

            return $winner;
        });
    }

    // Pin an exam as the student's result, overriding the newest-wins default.
    public function pin(Exam $exam, int $adminId): Exam
    {
        if ($exam->status !== ExamStatus::Approved) {
            throw new \InvalidArgumentException('Only an approved exam can be pinned as authoritative.');
        }

        return DB::transaction(function () use ($exam, $adminId) {
            // Clear any previous pin for this student — only one may be pinned.
            Exam::where('student_id', $exam->student_id)
                ->whereKeyNot($exam->id)
                ->whereNotNull('authoritative_decision_by')
                ->update([
                    'authoritative_decision_by' => null,
                    'authoritative_decision_at' => null,
                ]);

            $exam->forceFill([
                'authoritative_decision_by' => $adminId,
                'authoritative_decision_at' => now(),
            ])->save();

            $this->refreshFor($exam->student_id);

            return $exam->refresh();
        });
    }

    // Drop the admin's pin and fall back to newest-wins.
    public function unpin(Exam $exam): ?Exam
    {
        return DB::transaction(function () use ($exam) {
            $exam->forceFill([
                'authoritative_decision_by' => null,
                'authoritative_decision_at' => null,
            ])->save();

            return $this->refreshFor($exam->student_id);
        });
    }

    private function pinnedFor(int $studentId): ?Exam
    {
        return Exam::where('student_id', $studentId)
            ->where('status', ExamStatus::Approved)
            ->whereNotNull('authoritative_decision_by')
            ->latest('authoritative_decision_at')
            ->first();
    }

    private function newestFor(int $studentId): ?Exam
    {
        return Exam::where('student_id', $studentId)
            ->where('status', ExamStatus::Approved)
            // completed_at ties (same second, two devices) fall back to id so the
            // winner is deterministic rather than whatever MySQL returns first.
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();
    }
}
