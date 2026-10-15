<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alerta extends Model
{
    use HasFactory;
    public const STATUS_ACTIVE = 'activa';

    public const STATUS_ACKNOWLEDGED = 'reconocida';

    public const STATUS_RESOLVED = 'resuelta';

    public const STATUS_CANCELLED = 'cancelada';

    public const LEVEL_INFORMATIONAL = 'informativa';

    public const LEVEL_LOW = 'baja';

    public const LEVEL_MEDIUM = 'media';

    public const LEVEL_HIGH = 'alta';

    public const LEVEL_CRITICAL = 'critica';

    public const ORIGIN_SIMULATOR = 'simulador';

    protected $table = 'alertas';

    protected $fillable = [
        'sensor_id', 'zona_id', 'tipo_alerta', 'nivel', 'titulo', 'mensaje',
        'valor_detectado', 'umbral', 'unidad', 'estado', 'origen',
        'fecha_hora_inicio', 'fecha_hora_fin', 'reconocida_por', 'reconocida_at',
        'resuelta_por', 'resuelta_at',
    ];

    protected function casts(): array
    {
        return [
            'valor_detectado' => 'decimal:4',
            'umbral' => 'decimal:4',
            'fecha_hora_inicio' => 'datetime',
            'fecha_hora_fin' => 'datetime',
            'reconocida_at' => 'datetime',
            'resuelta_at' => 'datetime',
        ];
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->whereIn('estado', [self::STATUS_ACTIVE, self::STATUS_ACKNOWLEDGED]);
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

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    public function reconocidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconocida_por');
    }

    public function resueltaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelta_por');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }
}
