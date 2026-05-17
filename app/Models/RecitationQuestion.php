<?php

namespace App\Models;

use App\Enums\QuestionGroup;
use App\Support\Surah;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'question_number', 'group_number',
    'start_surah', 'start_ayah', 'start_page',
    'end_surah', 'end_ayah', 'end_page',
    'is_active',
])]
class RecitationQuestion extends Model
{
    protected function casts(): array
    {
        return [
            'group_number' => QuestionGroup::class,
            'is_active'    => 'boolean',
        ];
    }

    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeInGroups(Builder $q, array $groupNumbers): Builder
    {
        return $q->whereIn('group_number', $groupNumbers);
    }

    public function startSurahName(): ?string
    {
        return Surah::nameFor((int) $this->start_surah);
    }

    public function endSurahName(): ?string
    {
        return Surah::nameFor((int) $this->end_surah);
    }
}
