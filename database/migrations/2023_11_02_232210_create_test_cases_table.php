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
        /**
         *  Dont add on delete cascade in this file because this need to be deleted by laravel
         * because this table is linked with files table, and to delete files this must delete from
         * disk before.
         */

        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Problem::class)->constrained();
            $table->smallInteger("position");

            $table->tinyInteger("type")->default(TestCaseType::FileDiff);

            $table->foreignIdFor(File::class, 'input_file')->constrained('files');
            $table->foreignIdFor(File::class, 'output_file')->constrained('files');

            # If this test case should be used to rank the time
            $table->boolean('rankeable')->default(false);
            $table->boolean('public')->default(false);
            $table->boolean('validated')->default(false);

            $table->index(['problem_id', 'position']);
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
