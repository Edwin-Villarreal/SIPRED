<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tipo_sensor_id')->constrained('tipos_sensores')->restrictOnDelete();
            $table->foreignId('zona_id')->constrained('zonas')->restrictOnDelete();
            $table->string('codigo', 64)->unique();
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->string('unidad_medida', 32);
            $table->string('modelo', 100)->nullable();
            $table->string('fabricante', 100)->nullable();
            $table->string('estado', 24)->default('sin_conexion')->index();
            $table->unsignedInteger('intervalo_lectura')->default(60);
            $table->decimal('umbral_minimo', 16, 4)->nullable();
            $table->decimal('umbral_maximo', 16, 4)->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->timestamp('ultima_medicion_at')->nullable();
            $table->timestamp('ultima_conexion_at')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['zona_id', 'estado']);
            $table->index(['tipo_sensor_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensores');
    }
};
