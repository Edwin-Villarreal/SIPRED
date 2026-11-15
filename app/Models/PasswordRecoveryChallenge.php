<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordRecoveryChallenge extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'recovery_code_id',
        'recovery_file_id',
        'token_hash',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recoveryCode(): BelongsTo
    {
        return $this->belongsTo(RecoveryCode::class);
    }

    public function recoveryFile(): BelongsTo
    {
        return $this->belongsTo(RecoveryFile::class);
    }
}