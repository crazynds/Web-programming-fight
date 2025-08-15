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
            $table->dropForeign(['diff_program_id']);

            $table->foreign('diff_program_id')->references('id')->on('files');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->dropForeign(['diff_program_id']);

            $table->foreign('diff_program_id')->references('id')->on('files')->onDelete('cascade');
        });
    }
};
