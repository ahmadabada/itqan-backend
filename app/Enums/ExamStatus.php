<?php

namespace App\Enums;

enum ExamStatus: string
{
    case InProgress    = 'in_progress';
    case Completed     = 'completed';
    case PendingReview = 'pending_review';
    case Approved      = 'approved';
    case Rejected      = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::InProgress    => 'جارٍ',
            self::Completed     => 'مكتمل',
            self::PendingReview => 'قيد المراجعة',
            self::Approved      => 'معتمد',
            self::Rejected      => 'مرفوض',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Approved || $this === self::Rejected;
    }
}
