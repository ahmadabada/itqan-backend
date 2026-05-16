<?php

namespace App\Models;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Services\ScoreCalculator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_id', 'examiner_id', 'exam_type', 'attempt_number',
    'rulings_score', 'total_score', 'is_approved',
    'status', 'source', 'device_uuid', 'reexam_permit_id',
    'conflict_reason', 'started_at', 'completed_at', 'synced_at',
])]
class Exam extends Model
{
    protected function casts(): array
    {
        return [
            'exam_type'      => ExamType::class,
            'status'         => ExamStatus::class,
            'source'         => ExamSource::class,
            'rulings_score'  => 'decimal:2',
            'total_score'    => 'decimal:2',
            'is_approved'    => 'boolean',
            'started_at'     => 'datetime',
            'completed_at'   => 'datetime',
            'synced_at'      => 'datetime',
        ];
    }

    // Computed live from the current passing_score setting — never stored.
    // Why: when admin raises the threshold, historical exams must reflect the new rule.
    public function getIsPassedAttribute(): ?bool
    {
        if ($this->total_score === null) {
            return null;
        }
        return ScoreCalculator::isPassing((float) $this->total_score);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function reexamPermit(): BelongsTo
    {
        return $this->belongsTo(ReexamPermit::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('question_number');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
