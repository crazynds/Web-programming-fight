<?php

use App\Models\Contest;
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
        Schema::table('submit_runs', function (Blueprint $table) {
            $table->foreignIdFor(Contest::class)->nullable()->constrained()->onDelete('SET NULL');
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submit_runs', function (Blueprint $table) {
            $table->dropForeignIdFor(Contest::class);
            $table->dropColumn('contest_id');

            //
        });
    }
};
