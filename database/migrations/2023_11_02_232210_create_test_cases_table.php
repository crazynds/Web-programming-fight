<?php

use App\Enums\TestCaseType;
use App\Models\File;
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
        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Problem::class);
            $table->smallInteger("position");

            $table->smallInteger("type")->default(TestCaseType::FileDiff);

            $table->foreignIdFor(File::class,'input_file');
            $table->foreignIdFor(File::class,'output_file');

            # If this test case should be used to rank the time
            $table->boolean('rankeable')->default(false);
            $table->boolean('public')->default(false);
            $table->boolean('validated')->default(false);

            $table->index(['problem_id','position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_cases');
    }
};
