<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_shows', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('venue')->nullable();
            $table->date('event_date')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->unsignedBigInteger('winner_team_id')->nullable();
            $table->boolean('show_live_scores')->default(false);
            $table->boolean('show_ranking')->default(false);
            $table->boolean('winner_revealed')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_shows');
    }
};
