<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorHeartbeat extends Model
{
    protected $fillable = [
        'sensor_id', 'device_id', 'ip_address', 'signal_strength',
        'battery_level', 'firmware_version', 'status', 'received_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'battery_level' => 'decimal:2',
            'received_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}
