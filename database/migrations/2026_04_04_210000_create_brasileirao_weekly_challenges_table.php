<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brasileirao_weekly_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->constrained()->cascadeOnDelete();
            $table->enum('league', ['bixos', 'veteranos', 'all']);
            $table->enum('ranking_type', ['cpu_time', 'memory_peak', 'submission_time'])->default('submission_time');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brasileirao_weekly_challenges');
    }
};
