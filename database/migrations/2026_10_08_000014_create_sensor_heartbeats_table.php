<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_heartbeats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensores')->restrictOnDelete();
            $table->string('device_id', 120)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->integer('signal_strength')->nullable();
            $table->decimal('battery_level', 5, 2)->nullable();
            $table->string('firmware_version', 64)->nullable();
            $table->string('status', 24)->default('unknown')->index();
            $table->timestamp('received_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['sensor_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_heartbeats');
    }
};
