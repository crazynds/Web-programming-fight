<?php

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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Problem::class)->constrained()->onDelete('cascade');

            $table->unsignedSmallInteger('value');
            $table->boolean('computed')->default(false);

            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['problem_id', 'user_id']);
        });
        Schema::table('problems', function (Blueprint $table) {
            $table->unsignedSmallInteger('rating')->default(5);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->dropColumn('rating');
        });
        Schema::dropIfExists('ratings');
    }
};
