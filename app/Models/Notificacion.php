<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id', 'alerta_id', 'tipo', 'titulo', 'mensaje',
        'leida_at', 'enviada_at', 'estado', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'leida_at' => 'datetime',
            'enviada_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function alerta(): BelongsTo
    {
        return $this->belongsTo(Alerta::class);
    }
}
