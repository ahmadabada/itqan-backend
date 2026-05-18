<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'device_uuid', 'command_type', 'payload', 'issued_by_admin_id', 'issued_at',
    'status', 'acknowledged_at', 'completed_at', 'failure_reason',
])]
class DeviceCommand extends Model
{
    public const STATUS_PENDING      = 'pending';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_COMPLETED    = 'completed';
    public const STATUS_FAILED       = 'failed';

    public const TYPE_WIPE_DATA = 'wipe_data';

    protected function casts(): array
    {
        return [
            'payload'         => 'array',
            'issued_at'       => 'datetime',
            'acknowledged_at' => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_admin_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForDevice(Builder $query, string $deviceUuid): Builder
    {
        return $query->where('device_uuid', $deviceUuid);
    }
}
