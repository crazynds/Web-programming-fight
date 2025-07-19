<?php

use App\Models\Competitor;
use App\Models\Problem;
use App\Models\Submission;
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
        Schema::create('competitor_problem', function (Blueprint $table) {
            $table->foreignIdFor(Problem::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Competitor::class)->constrained()->onDelete('cascade');

            $table->foreignIdFor(Submission::class, 'submit_run_id')->nullable()->constrained('submit_runs')->onDelete('set null');

            $table->integer('penality');
            $table->integer('score'); // Total after the calculations

            $table->primary(['competitor_id', 'problem_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_problem');
    }
};
