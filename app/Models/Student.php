<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['national_id', 'first_name', 'second_name', 'third_name', 'family_name', 'gender'])]
class Student extends Model
{
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
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
}
