<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorToken extends Model
{
    public const STATUS_ACTIVE = 'activo';

    public const STATUS_REVOKED = 'revocado';

    protected $fillable = [
        'sensor_id', 'token_hash', 'nombre', 'ultimo_uso_at',
        'expira_at', 'revocado_at', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_uso_at' => 'datetime',
            'expira_at' => 'datetime',
            'revocado_at' => 'datetime',
        ];
    }

    public static function hashPlainTextToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    public function matchesPlainTextToken(string $plainTextToken): bool
    {
        if ($this->estado !== self::STATUS_ACTIVE || $this->revocado_at !== null) {
            return false;
        }

        if ($this->expira_at !== null && $this->expira_at->isPast()) {
            return false;
        }

        return hash_equals($this->token_hash, self::hashPlainTextToken($plainTextToken));
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}
