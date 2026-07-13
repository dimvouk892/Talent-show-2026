<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->string('waiting_image_path')->nullable()->after('waiting_video_path');
            $table->boolean('showing_waiting_image')->default(false)->after('showing_waiting_video');
        });
    }

    public function down(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropColumn(['waiting_image_path', 'showing_waiting_image']);
        });
    }
};
