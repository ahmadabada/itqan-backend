<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// RETIRED 2026-07-17 — see MergeService. The merge_operations table still exists
// and holds the historical record, but nothing writes to it any more.
#[Fillable([
    'master_student_id', 'merged_student_ids', 'authoritative_exam_id',
    'pre_merge_snapshot', 'performed_by_admin_id', 'performed_at', 'notes',
    'undone_at', 'undone_by_admin_id', 'undo_notes',
])]
class MergeOperation extends Model
{
    protected function casts(): array
    {
        return [
            'merged_student_ids' => 'array',
            'pre_merge_snapshot' => 'array',
            'performed_at'       => 'datetime',
            'undone_at'          => 'datetime',
        ];
    }

    public function masterStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'master_student_id');
    }

    public function authoritativeExam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'authoritative_exam_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_admin_id');
    }

    public function undoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by_admin_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('undone_at');
    }

    public function scopeUndone(Builder $query): Builder
    {
        return $query->whereNotNull('undone_at');
    }
}
