<?php

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
            $table->dateTime('updated_at')->useCurrent();
            $table->index(['updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submit_runs', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
            $table->dropColumn('updated_at');
        });
    }
};
