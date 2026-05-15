<?php

namespace App\Enums;

enum ExamType: string
{
    case FullQuran = 'full_quran';
    case HalfQuran = 'half_quran';

    public function label(): string
    {
        return match($this) {
            self::FullQuran => 'كامل القرآن',
            self::HalfQuran => 'نصف القرآن',
        };
    }
}
