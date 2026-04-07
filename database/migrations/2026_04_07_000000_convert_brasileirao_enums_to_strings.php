<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brasileirao_weekly_challenges', function (Blueprint $table) {
            $table->string('league')->change();
            $table->string('ranking_type')->default('submission_time')->change();
        });
    }

    public function down(): void
    {
        Schema::table('brasileirao_weekly_challenges', function (Blueprint $table) {
            $table->enum('league', ['bixos', 'veteranos', 'all'])->change();
            $table->enum('ranking_type', ['cpu_time', 'memory_peak', 'submission_time'])->default('submission_time')->change();
        });
    }
};
