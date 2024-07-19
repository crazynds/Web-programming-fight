<?php

use App\Models\Problem;
use App\Models\Scorer;
use App\Models\SubmitRun;
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
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Problem::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Scorer::class)->constrained('scorers')->onDelete('cascade');
            $table->foreignIdFor(SubmitRun::class)->nullable()->constrained()->onDelete('set null');

            $table->unsignedSmallInteger('language');
            $table->string('category');
            $table->double('value')->default(0);
            $table->string('reference');

            $table->unique(['problem_id', 'category', 'user_id', 'language']);
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
