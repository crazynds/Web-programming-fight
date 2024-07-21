<?php

use App\Models\File;
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
        Schema::table('files', function (Blueprint $table) {
            $table->boolean('compacted')->default(false);
            $table->binary('content', 1024 * 16 + 1)->nullable()->change();
        });
        foreach (File::query()->lazy() as $file) {
            $file->compact();
            $file->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (File::query()->lazy() as $file) {
            if ($file->compacted) {
                $file->extract();
                $file->save();
            }
        }
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('compacted');
            $table->string('content', 1024 * 16 + 1)->nullable()->change();
        });
    }
};
