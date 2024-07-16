<?php

use App\Models\Competitor;
use App\Models\Contest;
use App\Models\Problem;
use App\Models\User;
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
        Schema::create('contest_clatifications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contest::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Competitor::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Problem::class)->nullable()->constrained()->onDelete('cascade');

            $table->text('question');
            $table->text('answer')->nullable();
            $table->boolean('public')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_clatifications');
    }
};
