<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('submit_runs', 'submissions');
        Schema::rename('submit_run_test_case', 'submission_test_case');
        Schema::rename('competitor_submit_run', 'competitor_submission');
        Schema::table('competitor_submission', function ($table) {
            $table->renameColumn('submit_run_id', 'submission_id');
        });
        Schema::table('submission_test_case', function ($table) {
            $table->renameColumn('submit_run_id', 'submission_id');
        });
        Schema::table('competitor_problem', function ($table) {
            $table->renameColumn('submit_run_id', 'submission_id');
        });
        Schema::table('ranks', function ($table) {
            $table->renameColumn('submit_run_id', 'submission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ranks', function ($table) {
            $table->renameColumn('submission_id', 'submit_run_id');
        });
        Schema::table('competitor_problem', function ($table) {
            $table->renameColumn('submission_id', 'submit_run_id');
        });
        Schema::table('submission_test_case', function ($table) {
            $table->renameColumn('submission_id', 'submit_run_id');
        });
        Schema::table('competitor_submission', function ($table) {
            $table->renameColumn('submission_id', 'submit_run_id');
        });
        Schema::rename('competitor_submission', 'competitor_submit_run');
        Schema::rename('submission_test_case', 'submit_run_test_case');
        Schema::rename('submissions', 'submit_runs');
    }
};
