<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\ScorerController;
use App\Http\Controllers\SubmitRunController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TestCaseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/home', function () {
    return view('pages.home');
})->name('home');


// auth routes
Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirect'])->name('auth.login');
Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])->name('auth.callback');
Route::get('/auth/logout', [AuthController::class, 'logout'])->middleware('auth')->name('auth.logout');



Route::middleware('auth')->group(function () {
    // problem routes
    // TODO: fazer o problema estar vinculado a um usuario
    // TODO: somente os donos podem editar seus problemas e etc..
    Route::resource('problem', ProblemController::class);
    Route::resource('problem.testCase', TestCaseController::class)
        ->except(['edit', 'update']);
    Route::resource('problem.scorer', ScorerController::class)
        ->except(['edit', 'update']);
    Route::get('/problem/{problem}/scorer/all/reavaliate', [ScorerController::class, 'reavaliate'])
        ->name('problem.scorer.reavaliate');

    Route::get('/problem/{problem}/public', [ProblemController::class, 'publicChange'])
        ->name('problem.public');
    Route::get('/problem/{problem}/download', [ProblemController::class, 'download'])
        ->name('problem.download');
    Route::get('/problem/{problem}/podium', [ProblemController::class, 'podium'])
        ->name('problem.podium');
    Route::controller(TestCaseController::class)
        ->name('problem.')->prefix('problem/{problem}')
        ->group(function () {
            Route::get('testCase/{testCase}/input', 'downloadInput')
                ->name('testCase.input');
            Route::get('testCase/{testCase}/output', 'downloadOutput')
                ->name('testCase.output');
            Route::get('testCase/{testCase}/up', 'up')
                ->name('testCase.up');
            Route::get('testCase/{testCase}/down', 'down')
                ->name('testCase.down');
            Route::get('testCase/{testCase}/public', 'publicChange')
                ->name('testCase.edit.public');
        });

    // Todo fazer polices para submissions, somente o dono pode gerenciar
    // run routes
    Route::resource('submitRun', SubmitRunController::class)
        ->only(['index', 'store', 'create', 'show']);
    Route::get('/submitRun/{submitRun}/rejudge', [SubmitRunController::class, 'rejudge'])
        ->name('submitRun.rejudge');
    Route::get('/submitRun/global/live', [SubmitRunController::class, 'global'])
        ->name('submitRun.global');
    Route::get('/submitRun/{submitRun}/download', [SubmitRunController::class, 'download'])
        ->name('submitRun.download');

    // user routes
    Route::get('/user/profile', [UserController::class, 'profile'])
        ->name('user.me');
    Route::get('/users', [UserController::class, 'index'])
        ->name('user.index');
    Route::get('/user/profile/{user}', [UserController::class, 'profileUser'])
        ->name('user.profile');



    Route::resource('team', TeamController::class)
        ->only(['index', 'store', 'create', 'edit', 'update', 'destroy']);
    Route::get('/team/{team}/accept', [TeamController::class, 'accept'])
        ->name('team.accept');
    Route::get('/team/{team}/deny', [TeamController::class, 'deny'])
        ->name('team.deny');
    Route::get('/team/{team}/leave', [TeamController::class, 'leave'])
        ->name('team.leave');


    Route::resource('contest', ContestController::class)
        ->only(['index', 'store', 'create', 'edit', 'update', 'destroy']);
});



Route::get('/', function () {
    return redirect()->route('home');
});
