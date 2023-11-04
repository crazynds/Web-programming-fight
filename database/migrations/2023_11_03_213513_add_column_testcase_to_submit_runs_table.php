<?php

use App\Models\Problem;
use App\Models\TestCase;
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
            $table->foreignIdFor(Problem::class);
            $table->foreignIdFor(TestCase::class,'failed_testcase_id')
                ->nullable(true)->onDelete('SET NULL');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submit_runs', function (Blueprint $table) {
            $table->dropForeignIdFor(Problem::class);
            $table->dropForeignIdFor(TestCase::class,'failed_testcase_id');
        });
    }
};
