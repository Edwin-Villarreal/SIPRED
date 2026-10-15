<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensores')->restrictOnDelete();
            $table->string('measurement_id', 191)->unique();
            $table->decimal('valor', 16, 4);
            $table->string('unidad', 32);
            $table->string('calidad_dato', 16)->default('unknown')->index();
            $table->string('origen', 16)->default('sistema')->index();
            $table->timestamp('medida_at')->index();
            $table->timestamp('received_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['sensor_id', 'medida_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediciones');
    }
};
