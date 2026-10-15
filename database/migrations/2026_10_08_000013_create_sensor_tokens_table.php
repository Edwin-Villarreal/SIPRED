<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensores')->restrictOnDelete();
            $table->string('token_hash', 255)->unique();
            $table->string('nombre', 120);
            $table->timestamp('ultimo_uso_at')->nullable();
            $table->timestamp('expira_at')->nullable();
            $table->timestamp('revocado_at')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
            $table->index(['sensor_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_tokens');
    }
};
