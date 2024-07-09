<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\IOProblemController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\ScorerController;
use App\Http\Controllers\SubmitRunController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TestCaseController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AccessOnlyDuringContest;
use App\Http\Middleware\PreventAccessDuringContest;
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

Route::name('contest.')->prefix('contest/')->middleware('auth')->group(base_path('routes/contest.php'));

// Rotas do sistema como um todo
Route::middleware(['auth', PreventAccessDuringContest::class])->group(function () {
    Route::resource('problem', ProblemController::class);
    Route::resource('problem.testCase', TestCaseController::class)
        ->except(['edit', 'update']);
    Route::resource('problem.scorer', ScorerController::class)
        ->except(['edit', 'update']);

    Route::get('/problem_import', [IOProblemController::class, 'import'])
        ->name('problem.import');
    Route::post('/problem_upload', [IOProblemController::class, 'upload'])
        ->name('problem.upload');

    Route::controller(TestCaseController::class)
        ->name('problem.')->prefix('problem/{problem}')
        ->group(function () {
            Route::get('public', [ProblemController::class, 'publicChange'])
                ->name('public');
            Route::get('download', [ProblemController::class, 'download'])
                ->name('download');
            Route::get('podium', [ProblemController::class, 'podium'])
                ->name('podium');
            Route::get('download', [IOProblemController::class, 'download'])
                ->name('download');


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


            Route::get('scorer/all/reavaliate', [ScorerController::class, 'reavaliate'])
                ->name('scorer.reavaliate');
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
        ->only(['index', 'show', 'store', 'create', 'edit', 'update', 'destroy']);
    Route::post('/contest/{contest}/join', [ContestController::class, 'join'])
        ->name('contest.join');
    Route::post('/constes/{contest}/enter', [ContestController::class, 'enter'])
        ->name('contest.enter');
    Route::get('/constes/{contest}/leaderboard', [ContestController::class, 'leaderboard'])
        ->name('contest.leaderboard');
});

Route::get('/', function () {
    return redirect()->route('home');
});
