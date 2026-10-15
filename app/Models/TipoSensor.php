<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoSensor extends Model
{
    use HasFactory;
    protected $table = 'tipos_sensores';

    protected $fillable = ['nombre', 'slug', 'descripcion', 'unidad_medida', 'tipo_dato', 'estado'];

    public function sensores(): HasMany
    {
        return $this->hasMany(Sensor::class, 'tipo_sensor_id');
    }
}
