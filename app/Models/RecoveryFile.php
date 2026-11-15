<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecoveryFile extends Model
{
    protected $fillable = [
        'user_id',
        'recovery_id',
        'credential_hash',
        'generated_at',
        'used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}