<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vote_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vote_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('old_score');
            $table->unsignedTinyInteger('new_score');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_revisions');
    }
};
