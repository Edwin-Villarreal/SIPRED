<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 120);
            $table->string('slug', 140)->unique();
            $table->text('descripcion')->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('pais', 100)->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('nivel_riesgo', 20)->default('bajo')->index();
            $table->string('estado', 20)->default('activa')->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas');
    }
};
