<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'exam_id', 'question_number', 'recitation_question_id',
    'errors_count', 'warnings_count', 'continuations_count', 'final_score',
])]
class ExamQuestion extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function recitationQuestion(): BelongsTo
    {
        return $this->belongsTo(RecitationQuestion::class);
    }
}
