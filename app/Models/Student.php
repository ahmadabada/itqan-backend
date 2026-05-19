<?php

namespace App\Models;

use App\Enums\ExamSource;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// master_id, merged_at, merged_by_admin_id are intentionally NOT fillable —
// they are written only through the admin merge flow, never via mass assignment.
#[Fillable([
    'national_id', 'first_name', 'second_name', 'third_name', 'family_name', 'gender',
    'created_via', 'created_by_user_id', 'device_uuid', 'client_request_id',
    'is_recite_before', 'student_zone',
])]
class Student extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'gender'             => Gender::class,
            'created_via'        => ExamSource::class,
            'merged_at'          => 'datetime',
            'is_recite_before'   => 'boolean',
        ];
    }

    public function fullName(): string
    {
        return implode(' ', array_filter([
            $this->first_name,
            $this->second_name,
            $this->third_name,
            $this->family_name,
        ]));
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function reexamPermits(): HasMany
    {
        return $this->hasMany(ReexamPermit::class);
    }

    public function approvedExam(): ?Exam
    {
        return $this->exams()->where('is_approved', true)->first();
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'master_id');
    }

    public function mergedRecords(): HasMany
    {
        return $this->hasMany(Student::class, 'master_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by_admin_id');
    }

    public function scopeNotMerged(Builder $query): Builder
    {
        return $query->whereNull('master_id');
    }

    public function scopeMerged(Builder $query): Builder
    {
        return $query->whereNotNull('master_id');
    }
}
