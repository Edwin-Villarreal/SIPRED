<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medicion extends Model
{
    use HasFactory;
    public const ORIGIN_SIMULATOR = 'simulador';

    protected $table = 'mediciones';

    protected $fillable = [
        'sensor_id', 'measurement_id', 'valor', 'unidad', 'calidad_dato',
        'origen', 'medida_at', 'received_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:4',
            'medida_at' => 'datetime',
            'received_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function scopeReales(Builder $query): Builder
    {
        return $query->where('origen', '!=', self::ORIGIN_SIMULATOR);
    }

    public function scopeDeDemostracion(Builder $query): Builder
    {
        return $query->where('origen', self::ORIGIN_SIMULATOR);
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}
