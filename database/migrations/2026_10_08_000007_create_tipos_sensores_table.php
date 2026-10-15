<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_sensores', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->string('unidad_medida', 32);
            $table->string('tipo_dato', 32)->default('decimal');
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_sensores');
    }
};
