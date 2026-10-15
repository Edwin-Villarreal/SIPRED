<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zona extends Model
{
    use HasFactory;
    protected $table = 'zonas';

    protected $fillable = [
        'nombre', 'slug', 'descripcion', 'municipio', 'departamento', 'pais',
        'latitud', 'longitud', 'nivel_riesgo', 'estado', 'is_demo',
    ];

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'is_demo' => 'boolean',
        ];
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', 'activa');
    }

    public function scopeDeDemostracion(Builder $query): Builder
    {
        return $query->where('is_demo', true);
    }

    public function sensores(): HasMany
    {
        return $this->hasMany(Sensor::class);
    }

    public function mediciones(): HasManyThrough
    {
        return $this->hasManyThrough(Medicion::class, Sensor::class, 'zona_id', 'sensor_id');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }
}
