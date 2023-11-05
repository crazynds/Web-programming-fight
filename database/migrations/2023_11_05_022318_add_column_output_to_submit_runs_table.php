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
        Schema::table('submit_runs', function (Blueprint $table) {
            // 8kb
            $table->string('output',1024*8)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submit_runs', function (Blueprint $table) {
            $table->dropColumn('output');
        });
    }
};
