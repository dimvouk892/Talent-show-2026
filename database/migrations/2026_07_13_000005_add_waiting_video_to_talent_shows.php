<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->string('waiting_video_path')->nullable()->after('closing_video_path');
            $table->boolean('showing_waiting_video')->default(false)->after('showing_closing_video');
        });
    }

    public function down(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropColumn(['waiting_video_path', 'showing_waiting_video']);
        });
    }
};
