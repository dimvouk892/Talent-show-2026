<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropColumn([
                'showing_team_intro',
                'opening_video_path',
                'closing_video_path',
                'waiting_video_path',
                'waiting_image_path',
                'showing_opening_video',
                'showing_closing_video',
                'showing_waiting_video',
                'showing_waiting_image',
            ]);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'video_path']);
        });
    }

    public function down(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->boolean('showing_team_intro')->default(false);
            $table->string('opening_video_path')->nullable();
            $table->string('closing_video_path')->nullable();
            $table->string('waiting_video_path')->nullable();
            $table->string('waiting_image_path')->nullable();
            $table->boolean('showing_opening_video')->default(false);
            $table->boolean('showing_closing_video')->default(false);
            $table->boolean('showing_waiting_video')->default(false);
            $table->boolean('showing_waiting_image')->default(false);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
            $table->string('video_path')->nullable();
        });
    }
};
