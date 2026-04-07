<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brasileirao_newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('body');
            $table->unsignedInteger('total')->default(0);
            $table->timestamps();
        });

        Schema::create('brasileirao_newsletter_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_id')->constrained('brasileirao_newsletters')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['newsletter_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brasileirao_newsletter_sends');
        Schema::dropIfExists('brasileirao_newsletters');
    }
};
