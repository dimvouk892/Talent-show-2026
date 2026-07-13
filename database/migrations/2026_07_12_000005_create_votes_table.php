<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_show_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->timestamp('submitted_at');
            $table->boolean('is_admin_edited')->default(false);
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->text('edit_reason')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'judge_id']);
            $table->index(['talent_show_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
