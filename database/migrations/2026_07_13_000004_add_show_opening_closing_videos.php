<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->string('opening_video_path')->nullable()->after('showing_team_intro');
            $table->string('closing_video_path')->nullable()->after('opening_video_path');
            $table->boolean('showing_opening_video')->default(false)->after('closing_video_path');
            $table->boolean('showing_closing_video')->default(false)->after('showing_opening_video');
        });
    }

    public function down(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropColumn([
                'opening_video_path',
                'closing_video_path',
                'showing_opening_video',
                'showing_closing_video',
            ]);
        });
    }
};
