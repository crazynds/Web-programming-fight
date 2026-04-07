<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brasileirao_newsletters', function (Blueprint $table) {
            $table->string('status', 20)->default('queued')->after('audience');
        });
    }

    public function down(): void
    {
        Schema::table('brasileirao_newsletters', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
