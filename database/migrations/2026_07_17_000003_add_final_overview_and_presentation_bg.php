<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->boolean('show_final_overview')->default(false)->after('podium_reveal_step');
            $table->string('presentation_bg_path')->nullable()->after('show_final_overview');
            $table->string('presentation_bg_type', 16)->nullable()->after('presentation_bg_path');
        });
    }

    public function down(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropColumn([
                'show_final_overview',
                'presentation_bg_path',
                'presentation_bg_type',
            ]);
        });
    }
};
