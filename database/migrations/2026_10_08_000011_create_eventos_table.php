<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sensor_id')->nullable()->constrained('sensores')->nullOnDelete();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->nullOnDelete();
            $table->foreignId('alerta_id')->nullable()->constrained('alertas')->nullOnDelete();
            $table->string('tipo', 32);
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->string('nivel', 20)->default('informativa')->index();
            $table->string('origen', 20)->default('sistema')->index();
            $table->timestamp('ocurrio_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['sensor_id', 'ocurrio_at']);
            $table->index(['zona_id', 'ocurrio_at']);
            $table->index(['alerta_id', 'ocurrio_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
