<?php

namespace App\Enums;

// BR-EXAM-10: Recitation questions are partitioned into 6 groups across the Quran.
enum QuestionGroup: int
{
    case Group1 = 1;
    case Group2 = 2;
    case Group3 = 3;
    case Group4 = 4;
    case Group5 = 5;
    case Group6 = 6;

    public function shortLabel(): string
    {
        return 'م' . $this->value;
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::Group1 => 'المجموعة الأولى',
            self::Group2 => 'المجموعة الثانية',
            self::Group3 => 'المجموعة الثالثة',
            self::Group4 => 'المجموعة الرابعة',
            self::Group5 => 'المجموعة الخامسة',
            self::Group6 => 'المجموعة السادسة',
        };
    }

    // Juz range covered by this group (used for UI hints and audit, not for picking).
    public function juzRange(): array
    {
        return match ($this) {
            self::Group1 => [1, 5],
            self::Group2 => [6, 10],
            self::Group3 => [11, 15],
            self::Group4 => [16, 20],
            self::Group5 => [21, 25],
            self::Group6 => [26, 30],
        };
    }

    // Surah range covered by this group — shown on the group selection cards.
    /** @return array{0: string, 1: string} [first_surah, last_surah] */
    public function surahRange(): array
    {
        return match ($this) {
            self::Group1 => ['الفاتحة', 'النساء'],
            self::Group2 => ['المائدة', 'التوبة'],
            self::Group3 => ['يونس',    'الكهف'],
            self::Group4 => ['مريم',    'العنكبوت'],
            self::Group5 => ['الروم',   'الجاثية'],
            self::Group6 => ['الأحقاف', 'الناس'],
        };
    }

    // BR-EXAM-10: Pairs used by full_quran picker — one question per pair.
    public static function fullQuranPairs(): array
    {
        return [
            [self::Group1, self::Group2],
            [self::Group3, self::Group4],
            [self::Group5, self::Group6],
        ];
    }

    public static function values(): array
    {
        return array_map(fn($c) => $c->value, self::cases());
    }
}
