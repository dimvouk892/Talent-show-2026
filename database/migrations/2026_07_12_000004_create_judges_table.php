<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_show_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('access_token_hash')->nullable()->unique();
            $table->timestamp('token_generated_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_access_at')->nullable();
            $table->timestamps();

            $table->index(['talent_show_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judges');
    }
};
