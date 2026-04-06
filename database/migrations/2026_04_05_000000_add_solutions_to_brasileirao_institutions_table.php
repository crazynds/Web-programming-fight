<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brasileirao_institutions', function (Blueprint $table) {
            $table->unsignedInteger('solutions')->default(0)->after('points');
        });

        Schema::table('brasileirao_weekly_challenges', function (Blueprint $table) {
            $table->timestamp('scored_at')->nullable()->after('end_at');
        });
    }

    public function down(): void
    {
        Schema::table('brasileirao_institutions', function (Blueprint $table) {
            $table->dropColumn('solutions');
        });

        Schema::table('brasileirao_weekly_challenges', function (Blueprint $table) {
            $table->dropColumn('scored_at');
        });
    }
};
