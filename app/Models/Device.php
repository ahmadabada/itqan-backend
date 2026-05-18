<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['device_uuid', 'fcm_token', 'fcm_platform', 'last_user_id', 'last_seen_at', 'registered_at'])]
class Device extends Model
{
    protected function casts(): array
    {
        return [
            'last_seen_at'  => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    public function lastUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_user_id');
    }
}
