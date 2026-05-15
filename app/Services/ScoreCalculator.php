<?php

namespace App\Services;

use App\Models\SystemSetting;

class ScoreCalculator
{
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

    // BR-EXAM-07: passing = total >= passing_score setting
    public static function isPassing(float $score): bool
    {
        $passingScore = (int) SystemSetting::get('passing_score', 60);
        return $score >= $passingScore;
    }
}
