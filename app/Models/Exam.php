<?php

namespace App\Models;

use App\Enums\ExamSource;
use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Services\ScoreCalculator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// authoritative_decision_by / authoritative_decision_at are intentionally NOT fillable —
// they are written only when an admin pins an exam, never via mass assignment.
#[Fillable([
    'student_id', 'examiner_id', 'exam_round_id', 'exam_type', 'selected_groups', 'attempt_number',
    'parts_count', 'new_memorization_parts',
    'rulings_score', 'total_score', 'is_approved', 'is_authoritative',
    'status', 'source', 'device_uuid', 'client_request_id', 'reexam_permit_id',
    'started_at', 'completed_at', 'synced_at',
])]
class Exam extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'exam_type'                  => ExamType::class,
            'status'                     => ExamStatus::class,
            'source'                     => ExamSource::class,
            'selected_groups'            => 'array',
            // Parts may be fractional (e.g. 5.5 أجزاء), capped at 30.00.
            'parts_count'                => 'float',
            'new_memorization_parts'     => 'float',
            'rulings_score'              => 'decimal:2',
            'total_score'                => 'decimal:2',
            'is_approved'                => 'boolean',
            'is_authoritative'           => 'boolean',
            'started_at'                 => 'datetime',
            'completed_at'               => 'datetime',
            'synced_at'                  => 'datetime',
            'authoritative_decision_at'  => 'datetime',
        ];
    }

    // Computed live from the current passing_score_{gender} setting — never stored.
    // Why: when admin raises the threshold, historical exams must reflect the new rule.
    // Callers must eager-load `student:id,gender` to avoid N+1 in list views.
    public function getIsPassedAttribute(): ?bool
    {
        if ($this->total_score === null) {
            return null;
        }
        return ScoreCalculator::isPassing((float) $this->total_score, $this->student?->gender);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(ExamRound::class, 'exam_round_id');
    }

    public function reexamPermit(): BelongsTo
    {
        return $this->belongsTo(ReexamPermit::class);
    }

    public function authoritativeDecisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authoritative_decision_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('question_number');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function scopeAuthoritative(Builder $query): Builder
    {
        return $query->where('is_authoritative', true);
    }
}
