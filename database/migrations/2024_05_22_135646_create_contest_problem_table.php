<?php

use App\Models\Contest;
use App\Models\Problem;
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
        Schema::create('contest_problem', function (Blueprint $table) {
            $table->foreignIdFor(Contest::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Problem::class)->constrained()->onDelete('cascade');

            $table->unique(['contest_id', 'problem_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_problem');
    }
};
