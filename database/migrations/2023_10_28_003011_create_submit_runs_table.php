<?php

use App\Enums\SubmitResult;
use App\Enums\SubmitStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\File;
use App\Models\Problem;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('submit_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();

            $table->foreignIdFor(Problem::class)->constrained();
            $table->foreignIdFor(File::class)->nullable()->constrained();
            $table->smallInteger('language');

            $table->tinyInteger('status')->default(SubmitResult::NoResult);
            $table->tinyInteger('result')->default(SubmitStatus::Submitted);
            $table->dateTime('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submit_runs');
    }
};
