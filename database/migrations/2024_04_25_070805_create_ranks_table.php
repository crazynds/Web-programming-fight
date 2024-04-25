<?php

use App\Models\Problem;
use App\Models\SubmitRun;
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
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Problem::class)->constrained();
            $table->foreignIdFor(SubmitRun::class)->constrained();

            $table->string('category');
            $table->double('value');

            $table->unique(['problem_id', 'category', 'submit_run_id']);
            $table->index(['problem_id', 'category', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranks');
    }
};
