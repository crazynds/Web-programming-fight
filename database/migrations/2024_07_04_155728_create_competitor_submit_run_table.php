<?php

use App\Models\Competitor;
use App\Models\SubmitRun;
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
        Schema::create('competitor_submit_run', function (Blueprint $table) {
            $table->foreignIdFor(Competitor::class)->constrained()->onDelete('CASCADE');
            $table->foreignIdFor(SubmitRun::class)->constrained()->onDelete('CASCADE');

            $table->primary(['competitor_id', 'submit_run_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_submit_run');
    }
};
