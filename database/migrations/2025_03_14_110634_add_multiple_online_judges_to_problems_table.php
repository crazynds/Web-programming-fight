<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->string('online_judge')->nullable();
            $table->string('online_judge_id')->nullable();
            $table->unsignedBigInteger('vjudge_id')->nullable();

            $table->index('vjudge_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->dropColumn(['online_judge', 'online_judge_id', 'vjudge_id']);
        });
    }
};
