<?php

namespace App\Enums;

enum SettingValueType: string
{
    case String  = 'string';
    case Int     = 'int';
    case Bool    = 'bool';
    case Decimal = 'decimal';

    public function cast(string $value): mixed
    {
        return match($this) {
            self::Int     => (int) $value,
            self::Bool    => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::Decimal => (float) $value,
            self::String  => $value,
        };
    }
}
