<?php

namespace App\Enums;

// Simplified lifecycle (2026-05-19):
//   InProgress → Approved | Excluded
//
// An exam is either:
//   • InProgress — examiner started it but hasn't completed yet
//   • Approved   — completed and counts as THE result for the student
//   • Excluded   — completed but not counted (superseded by a merge, rejected
//                  by an admin, or otherwise set aside)
//
// The legacy is_approved / is_authoritative columns remain in the DB for
// backward-compat but the source of truth is now `status` alone.
enum ExamStatus: string
{
    case InProgress = 'in_progress';
    case Approved   = 'approved';
    case Excluded   = 'excluded';

    public function label(): string
    {
        return match($this) {
            self::InProgress => 'جارٍ',
            self::Approved   => 'معتمد',
            self::Excluded   => 'مستبعد',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Approved || $this === self::Excluded;
    }
}
