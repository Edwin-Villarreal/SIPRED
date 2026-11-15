<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoverySetting extends Model
{
    protected $table = 'recovery_settings';

    protected $fillable = [
        'id',
        'recovery_codes_enabled',
        'recovery_file_enabled',
    ];

    protected function casts(): array
    {
        return [
            'recovery_codes_enabled' => 'boolean',
            'recovery_file_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['recovery_codes_enabled' => true, 'recovery_file_enabled' => true],
        );
    }
}