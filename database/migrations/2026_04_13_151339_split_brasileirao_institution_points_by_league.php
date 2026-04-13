<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brasileirao_institutions', function (Blueprint $table) {
            $table->unsignedInteger('bixos_points')->default(0)->after('points');
            $table->unsignedInteger('bixos_solutions')->default(0)->after('bixos_points');
            $table->unsignedInteger('veterans_points')->default(0)->after('bixos_solutions');
            $table->unsignedInteger('veterans_solutions')->default(0)->after('veterans_points');
        });

        Schema::table('brasileirao_institutions', function (Blueprint $table) {
            $table->dropColumn(['points', 'solutions']);
        });
    }

    public function down(): void
    {
        Schema::table('brasileirao_institutions', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('id');
            $table->unsignedInteger('solutions')->default(0)->after('points');
        });

        Schema::table('brasileirao_institutions', function (Blueprint $table) {
            $table->dropColumn(['bixos_points', 'bixos_solutions', 'veterans_points', 'veterans_solutions']);
        });
    }
};
