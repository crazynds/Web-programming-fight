<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brasileirao_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('brasileirao_institutions')->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brasileirao_members');
    }
};
