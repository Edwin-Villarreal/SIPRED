<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evento extends Model
{
    use HasFactory;
    protected $table = 'eventos';

    protected $fillable = [
        'usuario_id', 'sensor_id', 'zona_id', 'alerta_id', 'tipo', 'titulo',
        'descripcion', 'nivel', 'origen', 'ocurrio_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'ocurrio_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    public function alerta(): BelongsTo
    {
        return $this->belongsTo(Alerta::class);
    }
}
