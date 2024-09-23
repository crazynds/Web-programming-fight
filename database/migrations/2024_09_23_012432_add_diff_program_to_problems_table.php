<?php

use App\Models\File;
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
        Schema::table('problems', function (Blueprint $table) {
            $table->foreignIdFor(File::class, 'diff_program_id')->nullable()->constrained('files')->onDelete('set null');
            $table->smallInteger('diff_program_language')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(File::class, 'diff_program_id');
            $table->dropColumn('diff_program_language');
        });
    }
};
