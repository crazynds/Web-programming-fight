<?php

use App\Http\Controllers\Contest\CompetitorController;
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
});

Route::resource('competitor', CompetitorController::class)
    ->only(['index']);
