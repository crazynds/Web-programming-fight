<?php

use App\Http\Controllers\Contest\ClarificationController;
use App\Http\Controllers\Contest\CompetitorController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\SubmitRunController;
use App\Http\Middleware\AccessOnlyDuringContest;
use Illuminate\Support\Facades\Route;


// Rotas do contest


Route::middleware(AccessOnlyDuringContest::class)->group(function () {
    Route::resource('problem', ProblemController::class)
        ->only(['index', 'show']);

    Route::resource('submitRun', SubmitRunController::class)
        ->only(['index', 'store', 'create', 'show']);
    Route::get('/submitRun/global/live', [SubmitRunController::class, 'global'])
        ->name('submitRun.global');
    Route::get('/submitRun/{submitRun}/download', [SubmitRunController::class, 'download'])
        ->name('submitRun.download');


    Route::resource('clarification', ClarificationController::class)
        ->only(['store']);
});

Route::resource('competitor', CompetitorController::class)
    ->only(['index']);
Route::get('competitor/leaderboard', [CompetitorController::class, 'leaderboard'])
    ->name('competitor.leaderboard');


Route::get('/leave', [ContestController::class, 'leave'])
    ->name('leave');
