<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sensor extends Model
{
    use HasFactory;
    public const STATUS_ACTIVE = 'activo';

    public const STATUS_INACTIVE = 'inactivo';

    public const STATUS_MAINTENANCE = 'mantenimiento';

    public const STATUS_DISCONNECTED = 'sin_conexion';

    protected $table = 'sensores';

    protected $fillable = [
        'tipo_sensor_id', 'zona_id', 'codigo', 'nombre', 'descripcion', 'unidad_medida',
        'modelo', 'fabricante', 'estado', 'intervalo_lectura', 'umbral_minimo',
        'umbral_maximo', 'fecha_instalacion', 'ultima_medicion_at', 'ultima_conexion_at',
        'is_demo',
    ];

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
            'ultima_medicion_at' => 'datetime',
            'ultima_conexion_at' => 'datetime',
            'umbral_minimo' => 'decimal:4',
            'umbral_maximo' => 'decimal:4',
            'is_demo' => 'boolean',
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', self::STATUS_ACTIVE);
    }

    public function scopeReales(Builder $query): Builder
    {
        return $query->where('is_demo', false);
    }

    public function scopeDeDemostracion(Builder $query): Builder
    {
        return $query->where('is_demo', true);
    }

    public function tipoSensor(): BelongsTo
    {
        return $this->belongsTo(TipoSensor::class, 'tipo_sensor_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    public function mediciones(): HasMany
    {
        return $this->hasMany(Medicion::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(SensorToken::class);
    }

    public function heartbeats(): HasMany
    {
        return $this->hasMany(SensorHeartbeat::class);
    }
}
