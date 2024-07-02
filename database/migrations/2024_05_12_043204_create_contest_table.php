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

            $table->boolean('is_private');
            $table->string('password', 255)->nullable();

            $table->timestamp('start_time');
            $table->unsignedInteger('duration');
            $table->unsignedInteger('blind_time');
            $table->unsignedInteger('penalty'); // Penalidade por errar

            // Custom Rules
            $table->boolean('parcial_solution')->default(false); // Solução parcial? 0-80%
            /**
             * Solução parcial:
             *  - Somente ganha pontos se acertar pelo menos 20% dos casos
             *  - Ganha uma pontuação máxima de 60% se acertar todos os casos -1
             *  - Ganha uma pontuação de 100% se acertar todos os casos
             */
            $table->boolean('show_wrong_answer')->default(false); // Exibir o output errado em wrong answer?

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
