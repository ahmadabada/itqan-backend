<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin      = 'admin';
    case Examiner   = 'examiner';

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin => 'سوبر أدمن',
            self::Admin      => 'أدمن',
            self::Examiner   => 'مختبر',
        };
    }

    public function canManageUsers(): bool
    {
        return $this === self::SuperAdmin || $this === self::Admin;
    }
}
