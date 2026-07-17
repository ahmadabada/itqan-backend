<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Halaqah;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'national_id', 'first_name', 'second_name', 'third_name', 'family_name', 'gender',
    'halaqah', 'created_by_user_id',
])]
class Student extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'gender'  => Gender::class,
            'halaqah' => Halaqah::class,
        ];
    }

    // Cascade soft-delete and restore to the student's exams, exam_questions,
    // and reexam_permits. Children are stamped with the student's deleted_at
    // value so `restoring` can pair them by exact timestamp — exams deleted
    // independently later carry a different timestamp and stay deleted.
    protected static function booted(): void
    {
        static::deleted(function (Student $student) {
            if ($student->isForceDeleting()) {
                return;
            }
            $deletedAt = $student->deleted_at;

            DB::table('exam_questions')
                ->whereIn('exam_id', fn ($q) => $q
                    ->from('exams')
                    ->select('id')
                    ->where('student_id', $student->id))
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $deletedAt]);

            DB::table('exams')
                ->where('student_id', $student->id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $deletedAt]);

            DB::table('reexam_permits')
                ->where('student_id', $student->id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $deletedAt]);
        });

        static::restoring(function (Student $student) {
            $deletedAt = $student->deleted_at;
            if ($deletedAt === null) {
                return;
            }

            DB::table('exam_questions')
                ->whereIn('exam_id', fn ($q) => $q
                    ->from('exams')
                    ->select('id')
                    ->where('student_id', $student->id))
                ->where('deleted_at', $deletedAt)
                ->update(['deleted_at' => null]);

            DB::table('exams')
                ->where('student_id', $student->id)
                ->where('deleted_at', $deletedAt)
                ->update(['deleted_at' => null]);

            DB::table('reexam_permits')
                ->where('student_id', $student->id)
                ->where('deleted_at', $deletedAt)
                ->update(['deleted_at' => null]);
        });
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
