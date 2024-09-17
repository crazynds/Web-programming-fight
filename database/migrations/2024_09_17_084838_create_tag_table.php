<?php

use App\Models\Problem;
use App\Models\Tag;
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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');     // Full name
            $table->string('alias');    // Small name lower case

            $table->unsignedSmallInteger('type'); // Problem tag/Event tag/Algorithm tag

        });
        Schema::create('problem_tag', function (Blueprint $table) {
            $table->foreignIdFor(Problem::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Tag::class)->constrained()->onDelete('cascade');

            $table->unique(['tag_id', 'problem_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problem_tag');
        Schema::dropIfExists('tags');
    }
};
