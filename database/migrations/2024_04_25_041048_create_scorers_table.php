<?php

use App\Models\File;
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
        Schema::create('scorers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Problem::class)->constrained();
            $table->foreignIdFor(File::class)->constrained();
            $table->foreignIdFor(File::class, 'input_id')->constrained('files');
            $table->smallInteger('language');

            $table->unsignedInteger('time_limit');
            $table->unsignedInteger('memory_limit');

            $table->string('name');
            $table->string('categories')->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scorers');
    }
};
