<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('recovery_codes_enabled')->default(true);
            $table->boolean('recovery_file_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('recovery_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash', 255);
            $table->timestamp('generated_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'used_at', 'revoked_at'], 'recovery_codes_available_idx');
        });

        Schema::create('recovery_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('recovery_id')->unique();
            $table->string('credential_hash', 255);
            $table->timestamp('generated_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'used_at', 'revoked_at'], 'recovery_files_available_idx');
        });

        Schema::create('password_recovery_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('method', 16);
            $table->foreignId('recovery_code_id')->nullable()->constrained('recovery_codes')->cascadeOnDelete();
            $table->foreignId('recovery_file_id')->nullable()->constrained('recovery_files')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'method', 'consumed_at'], 'recovery_challenges_user_method_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_recovery_challenges');
        Schema::dropIfExists('recovery_files');
        Schema::dropIfExists('recovery_codes');
        Schema::dropIfExists('recovery_settings');
    }
};