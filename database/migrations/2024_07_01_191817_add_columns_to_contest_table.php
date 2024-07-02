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
        Schema::table('contests', function (Blueprint $table) {
            $table->boolean('individual')->default(false); // Participação individual
            $table->boolean('time_based_points')->default(false); // Pontuação baseada pelo tempo

            $table->string('languages', 255)->defualt('[]');
            $table->text('description');

            $table->dropColumn('penalty'); // Penalidade estava escrito errado
            $table->unsignedInteger('penality'); // Penalidade por errar
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->dropColumn(['individual', 'time_based_points', 'languages', 'description']);

            $table->dropColumn('penality'); // Penalidade estava escrito errado
            $table->unsignedInteger('penalty'); // Penalidade por errar
        });
    }
};
