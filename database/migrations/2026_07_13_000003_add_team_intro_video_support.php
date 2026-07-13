<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('photo_path');
        });

        Schema::table('talent_shows', function (Blueprint $table) {
            $table->boolean('showing_team_intro')->default(false)->after('show_live_scores');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('video_path');
        });

        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropColumn('showing_team_intro');
        });
    }
};
