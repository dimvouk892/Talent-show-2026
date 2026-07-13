<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judge_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->string('session_token_hash')->unique();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent_hash')->nullable();
            $table->timestamp('last_activity_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['judge_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judge_sessions');
    }
};
