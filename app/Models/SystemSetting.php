<?php

namespace App\Models;

use App\Enums\SettingValueType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'value_type', 'description'])]
class SystemSetting extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'value_type' => SettingValueType::class,
            'updated_at' => 'datetime',
        ];
    }

    public function typedValue(): mixed
    {
        return $this->value_type->cast($this->value);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->typedValue() : $default;
    }
}
