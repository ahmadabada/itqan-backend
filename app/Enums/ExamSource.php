<?php

namespace App\Enums;

enum ExamSource: string
{
    case Web     = 'web';
    case Flutter = 'flutter';

    public function label(): string
    {
        return match($this) {
            self::Web     => 'ويب',
            self::Flutter => 'تطبيق موبايل',
        };
    }
}
