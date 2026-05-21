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
// they are written only through the admin merge flow.
#[Fillable([
    'student_id', 'examiner_id', 'exam_type', 'selected_groups', 'attempt_number',
    'rulings_score', 'total_score', 'is_approved', 'is_authoritative',
    'status', 'source', 'device_uuid', 'client_request_id', 'reexam_permit_id',
    'conflict_reason', 'started_at', 'completed_at', 'synced_at',
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

    // Returns exams belonging to the master student PLUS exams of any student
    // merged into that master. Use this anywhere the merged identity should be
    // presented as a single person (admin student detail, public result query).
    public function scopeForMasterStudent(Builder $query, int $masterId): Builder
    {
        return $query->where(fn (Builder $inner) => $inner
            ->where('student_id', $masterId)
            ->orWhereIn('student_id', fn ($sub) => $sub
                ->from('students')
                ->select('id')
                ->where('master_id', $masterId)
                ->whereNull('deleted_at')
            ));
    }
}
