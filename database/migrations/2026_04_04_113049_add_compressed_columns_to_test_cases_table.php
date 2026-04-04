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
        Schema::table('test_cases', function (Blueprint $table) {
            $table->boolean('is_input_compressed')->default(false)->after('input_file');
            $table->boolean('is_output_compressed')->default(false)->after('output_file');
        });
    }

    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropColumn(['is_input_compressed', 'is_output_compressed']);
        });
    }
};
