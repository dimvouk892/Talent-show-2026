<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->boolean('is_final_voter')->default(false)->after('is_active');
        });

        Schema::table('talent_shows', function (Blueprint $table) {
            $table->boolean('final_vote_open')->default(false)->after('show_live_scores');
            $table->timestamp('final_vote_submitted_at')->nullable()->after('final_vote_open');
        });
    }

    public function down(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->dropColumn('is_final_voter');
        });

        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropColumn(['final_vote_open', 'final_vote_submitted_at']);
        });
    }
};
