<?php

use App\Models\SubmitRun;
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
        Schema::create('submit_run_test_case', function (Blueprint $table) {
            $table->foreignIdFor(SubmitRun::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(TestCase::class)->constrained()->onDelete('cascade');
            $table->tinyInteger('result');
            $table->primary(['test_case_id','submit_run_id']);
            $table->index(['test_case_id','result']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submit_run_test_case');
    }
};
