<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->unsignedInteger('display_order')->default(0)->after('title');
            $table->index(['talent_show_id', 'display_order']);
        });

        $talentShowIds = DB::table('judges')->distinct()->pluck('talent_show_id');

        foreach ($talentShowIds as $talentShowId) {
            $judges = DB::table('judges')
                ->where('talent_show_id', $talentShowId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($judges as $index => $judgeId) {
                DB::table('judges')
                    ->where('id', $judgeId)
                    ->update(['display_order' => $index + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->dropIndex(['talent_show_id', 'display_order']);
            $table->dropColumn('display_order');
        });
    }
};
