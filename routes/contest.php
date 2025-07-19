<?php

use App\Http\Controllers\Contest\ClarificationController;
use App\Http\Controllers\Contest\CompetitorController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SubmissionController;
use App\Http\Middleware\AccessOnlyDuringContest;
use Illuminate\Support\Facades\Route;

// Rotas do contest

Route::middleware(AccessOnlyDuringContest::class)->group(function () {
    Route::resource('problem', ProblemController::class)
        ->only(['index', 'show']);

    Route::resource('problem.rating', RatingController::class)
        ->only('store');

    Route::resource('submission', SubmissionController::class)
        ->only(['index', 'store', 'create', 'show']);
    Route::get('/submission/global/live', [SubmissionController::class, 'global'])
        ->name('submission.global');
    Route::get('/submission/{submission}/download', [SubmissionController::class, 'download'])
        ->name('submission.download');

    Route::resource('clarification', ClarificationController::class)
        ->only(['store']);
});

Route::resource('competitor', CompetitorController::class)
    ->only(['index']);
Route::get('competitor/leaderboard', [CompetitorController::class, 'leaderboard'])
    ->name('competitor.leaderboard');

Route::get('/leave', [ContestController::class, 'leave'])
    ->name('leave');
