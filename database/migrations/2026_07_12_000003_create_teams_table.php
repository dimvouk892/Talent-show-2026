<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_show_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('pending');
            $table->boolean('is_active')->default(true);
            $table->timestamp('scoring_completed_at')->nullable();
            $table->timestamp('score_revealed_at')->nullable();
            $table->timestamps();

            $table->index(['talent_show_id', 'display_order']);
            $table->index(['talent_show_id', 'status']);
        });

        Schema::table('talent_shows', function (Blueprint $table) {
            $table->foreign('current_team_id')->references('id')->on('teams')->nullOnDelete();
            $table->foreign('winner_team_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropForeign(['current_team_id']);
            $table->dropForeign(['winner_team_id']);
        });

        Schema::dropIfExists('teams');
    }
};
