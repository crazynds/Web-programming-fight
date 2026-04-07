<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brasileirao_newsletters', function (Blueprint $table) {
            $table->string('audience', 20)->default('all')->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('brasileirao_newsletters', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
