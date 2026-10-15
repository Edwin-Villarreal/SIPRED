<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sensor_id')->nullable()->constrained('sensores')->nullOnDelete();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->nullOnDelete();
            $table->string('tipo_alerta', 40);
            $table->string('nivel', 20)->index();
            $table->string('titulo', 180);
            $table->text('mensaje');
            $table->decimal('valor_detectado', 16, 4)->nullable();
            $table->decimal('umbral', 16, 4)->nullable();
            $table->string('unidad', 32)->nullable();
            $table->string('estado', 20)->default('activa')->index();
            $table->string('origen', 20)->default('sistema')->index();
            $table->timestamp('fecha_hora_inicio')->useCurrent();
            $table->timestamp('fecha_hora_fin')->nullable();
            $table->foreignId('reconocida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconocida_at')->nullable();
            $table->foreignId('resuelta_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resuelta_at')->nullable();
            $table->timestamps();
            $table->index(['estado', 'nivel']);
            $table->index(['sensor_id', 'fecha_hora_inicio']);
            $table->index(['zona_id', 'fecha_hora_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
