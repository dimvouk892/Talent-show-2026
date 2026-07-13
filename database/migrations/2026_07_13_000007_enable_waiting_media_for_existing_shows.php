<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('talent_shows')
            ->whereNotNull('waiting_video_path')
            ->update(['showing_waiting_video' => true, 'showing_waiting_image' => false]);

        DB::table('talent_shows')
            ->whereNotNull('waiting_image_path')
            ->whereNull('waiting_video_path')
            ->update(['showing_waiting_image' => true, 'showing_waiting_video' => false]);
    }

    public function down(): void
    {
        // No rollback — flags are derived from uploaded media.
    }
};
