<?php

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
        Schema::create('contests', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class)->nullable()->constrained()->onDelete('set null');
            $table->string('title', 255);
            $table->text('description');

            $table->boolean('is_private');
            $table->string('password', 255)->nullable();
            $table->json('languages');

            $table->timestamp('start_time');
            $table->unsignedInteger('duration');
            $table->unsignedInteger('blind_time');
            $table->unsignedInteger('penality'); // Penalidade por errar

            // Custom Rules
            $table->boolean('parcial_solution')->default(false); // Solução parcial? 0-80%
            /**
             * Solução parcial:
             *  - Somente ganha pontos se acertar pelo menos 20% dos casos
             *  - Ganha uma pontuação máxima de 60% se acertar todos os casos -1
             *  - Ganha uma pontuação de 100% se acertar todos os casos
             */
            $table->boolean('show_wrong_answer')->default(false); // Exibir o output errado em wrong answer?
            $table->boolean('individual')->default(false); // Participação individual
            $table->boolean('time_based_points')->default(false); // Pontuação baseada pelo tempo

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contests');
    }
};
