<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// Pre-staged candidate students uploaded by the admin via Excel. Read-only
// from the examiner side and used purely as an autocomplete source when
// starting a new exam — the actual exam record copies the data, it does not
// reference this row.
#[Fillable([
    'national_id', 'first_name', 'second_name', 'third_name', 'family_name',
    'student_zone', 'gender', 'is_recite_before',
])]
class SuggestedStudent extends Model
{
    protected function casts(): array
    {
        return [
            'gender'           => Gender::class,
            'is_recite_before' => 'boolean',
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

    // BR-SS-1: every read endpoint scopes by the authenticated examiner's gender.
    // Centralised here so a controller can never forget to apply it.
    public function scopeForExaminer(Builder $query, User $examiner): Builder
    {
        return $query->where('gender', $examiner->gender);
    }
}
