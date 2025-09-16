<?php

// Rotas do sistema normal

use App\Http\Controllers\AdminJudgeSubmissionController;
use App\Http\Controllers\ContestController;
use App\Http\Middleware\PreventAccessDuringContest;
use Illuminate\Support\Facades\Route;

Route::middleware([PreventAccessDuringContest::class, 'auth'])->group(function () {
    Route::get('/contest/{contest}/admin', [ContestController::class, 'admin']);
    Route::post('/contest/{contest}/admin/recomputateScores', [ContestController::class, 'recomputateScores'])
        ->name('recomputateScores');
    Route::put('/contest/{contest}/admin/settings', [ContestController::class, 'settings'])
        ->name('settings');
    Route::post('/contest/{contest}/admin/submission/{submission}/accept', [AdminJudgeSubmissionController::class, 'accept'])
        ->name('submission.accept');
    Route::post('/contest/{contest}/admin/submission/{submission}/rejectWA', [AdminJudgeSubmissionController::class, 'rejectWA'])
        ->name('submission.rejectWA');
    Route::post('/contest/{contest}/admin/submission/{submission}/rejectTL', [AdminJudgeSubmissionController::class, 'rejectTL'])
        ->name('submission.rejectTL');
    Route::post('/contest/{contest}/admin/submission/{submission}/rejectAI', [AdminJudgeSubmissionController::class, 'rejectAI'])
        ->name('submission.rejectAI');
    Route::get('/contest/{contest}/admin/competitor/{competitor}/review', [AdminJudgeSubmissionController::class, 'reviewCompetitor'])
        ->name('competitor.review');
});
