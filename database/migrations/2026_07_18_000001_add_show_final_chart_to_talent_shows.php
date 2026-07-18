<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->boolean('show_final_chart')->default(false)->after('show_final_overview');
        });
    }

    public function down(): void
    {
        Schema::table('talent_shows', function (Blueprint $table) {
            $table->dropColumn('show_final_chart');
        });
    }
};
