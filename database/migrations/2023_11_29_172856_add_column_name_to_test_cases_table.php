<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->string('name')->nullable(true);
        });
        DB::statement('UPDATE test_cases SET name = id');
        Schema::table('test_cases', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
