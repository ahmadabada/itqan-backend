<?php

namespace App\Services;

use App\Enums\Gender;
use App\Models\SystemSetting;

class ScoreCalculator
{
    // Keyed by gender string ('male'|'female'); avoids re-reading SystemSetting
    // during list views (Exam::is_passed accessor fires for every row).
    /** @var array<string, int> */
    private static array $cachedPassingScores = [];

    // BR-EXAM-02: question score = max(0, 30 - deductions)
    public static function questionScore(int $errors, int $warnings, int $continuations): float
    {
        $deductions = ($errors * config('exam.deductions.error', 2))
                    + ($warnings * config('exam.deductions.warning', 1))
                    + ($continuations * config('exam.deductions.continuation', 0.5));

        return max(0, config('exam.score_per_question', 30) - $deductions);
    }

    // BR-EXAM-01: total = Q1 + Q2 + Q3 + rulings
    public static function totalScore(array $questions, float $rulingsScore): float
    {
        $total = 0.0;
        foreach ($questions as $q) {
            $total += static::questionScore(
                $q['errors_count'],
                $q['warnings_count'],
                $q['continuations_count'],
            );
        }
        return round($total + $rulingsScore, 2);
    }

    // BR-EXAM-07: passing = total >= passing_score for the student's gender
    // (evaluated live, not frozen). Falls back to legacy passing_score, then 60.
    public static function isPassing(float $score, Gender|string|null $gender): bool
    {
        return $score >= static::passingScore($gender);
    }

    public static function passingScore(Gender|string|null $gender): int
    {
        $key = static::normalizeGender($gender);
        return static::$cachedPassingScores[$key] ??= static::resolvePassingScore($key);
    }

    // Call after admin updates any passing-score setting to invalidate the
    // per-request cache for both genders.
    public static function clearPassingScoreCache(): void
    {
        static::$cachedPassingScores = [];
    }

    // null/unknown gender → use the legacy single threshold. Lets callers that
    // can't resolve the student's gender (rare) still get a sensible answer.
    private static function normalizeGender(Gender|string|null $gender): string
    {
        if ($gender instanceof Gender) {
            return $gender->value;
        }
        if ($gender === 'male' || $gender === 'female') {
            return $gender;
        }
        return 'unknown';
    }

    private static function resolvePassingScore(string $genderKey): int
    {
        $legacy = (int) SystemSetting::get('passing_score', 60);

        if ($genderKey === 'male' || $genderKey === 'female') {
            $value = SystemSetting::get("passing_score_{$genderKey}", null);
            if ($value !== null && $value !== '') {
                return (int) $value;
            }
        }
        return $legacy;
    }
}
