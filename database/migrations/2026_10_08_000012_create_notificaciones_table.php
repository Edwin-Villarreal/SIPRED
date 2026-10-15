<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('alerta_id')->nullable()->constrained('alertas')->nullOnDelete();
            $table->string('tipo', 40);
            $table->string('titulo', 180);
            $table->text('mensaje');
            $table->timestamp('leida_at')->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['usuario_id', 'leida_at']);
            $table->index(['alerta_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
