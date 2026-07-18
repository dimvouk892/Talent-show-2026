<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->boolean('show_scoreboard')->default(false)->after('show_final_chart');
        });
    }

    public function down(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropColumn('show_scoreboard');
        });
    }
};
