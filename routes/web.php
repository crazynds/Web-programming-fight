<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Contest\ClarificationController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\DiffController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\IOProblemController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ScorerController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TestCaseController;
use App\Http\Controllers\UserController;
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

Route::middleware(['auth'])->group(function () {
    Route::get('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/changeUser', [AuthController::class, 'changeUser'])
        ->name('auth.changeUser');

    // Rotas de dentro do contest
    Route::name('contest.')->prefix('contest/')->group(base_path('routes/contest.php'));
    Route::name('admin.')->group(base_path('routes/admin.php'))->middleware('can:viewPulse');

    // Contest admin
    Route::name('contest.admin.')->group(base_path('routes/contestAdmin.php'));
});

// Rotas do sistema normal
Route::middleware([PreventAccessDuringContest::class])->group(function () {

    Route::middleware('auth')->group(function () {
        Route::resource('team', TeamController::class)
            ->only(['index', 'store', 'create', 'edit', 'update', 'destroy']);
        Route::get('/team/{team}/accept', [TeamController::class, 'accept'])
            ->name('team.accept');
        Route::get('/team/{team}/deny', [TeamController::class, 'deny'])
            ->name('team.deny');
        Route::get('/team/{team}/leave', [TeamController::class, 'leave'])
            ->name('team.leave');

        Route::resource('problem.diff', DiffController::class)
            ->only(['create', 'store', 'destroy'])->middleware('auth');
        Route::resource('problem.scorer', ScorerController::class)
            ->except(['edit', 'update'])->middleware('auth');
        Route::get('/problem_import', [IOProblemController::class, 'import'])
            ->name('problem.import');
        Route::get('/problem_import/sbc', [IOProblemController::class, 'importSbc'])
            ->name('problem.import.sbc');
        Route::post('/problem_upload', [IOProblemController::class, 'upload'])
            ->name('problem.upload');
        Route::post('/problem_upload/sbc', [IOProblemController::class, 'uploadSbc'])
            ->name('problem.upload.sbc');

        Route::get('/problem/{problem}/testcase/create/manual', [TestCaseController::class, 'createManual'])
            ->name('problem.testCase.create.manual');
        Route::post('/problem/{problem}/testcase/create/manual', [TestCaseController::class, 'storeManual'])
            ->name('problem.testCase.store.manual');
        Route::resource('problem.rating', RatingController::class)
            ->only('store');

        Route::resource('contest.clarification', ClarificationController::class)
            ->only(['update', 'destroy']);
    });

    Route::resource('problem', ProblemController::class);
    Route::resource('tag', TagController::class);

    Route::resource('problem.testCase', TestCaseController::class)
        ->except(['update']);

    Route::controller(TestCaseController::class)
        ->name('problem.')->prefix('problem/{problem}')
        ->group(function () {
            Route::get('podium', [ProblemController::class, 'podium'])
                ->name('podium');
            Route::get('public', [ProblemController::class, 'publicChange'])
                ->name('public');
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

    // run routes
    Route::resource('submission', SubmissionController::class)
        ->only(['index', 'store', 'create', 'show']);
    Route::get('/submission/global/live', [SubmissionController::class, 'global'])
        ->name('submission.global');
    Route::get('/submission/{submission}/download', [SubmissionController::class, 'download'])
        ->name('submission.download');

    // user routes
    Route::get('/user/profile', [UserController::class, 'profile'])
        ->name('user.me');
    Route::get('/users', [UserController::class, 'index'])
        ->name('user.index');
    Route::get('/user/profile/{user}', [UserController::class, 'profileUser'])
        ->name('user.profile');

    Route::resource('contest', ContestController::class)
        ->only(['index', 'show', 'store', 'create', 'edit', 'update', 'destroy']);
    Route::post('/contest/{contest}/register', [ContestController::class, 'register'])
        ->name('contest.register')->middleware('auth');
    Route::post('/contest/{contest}/unregister', [ContestController::class, 'unregister'])
        ->name('contest.unregister')->middleware('auth');
    Route::post('/contest/{contest}/enter', [ContestController::class, 'enter'])
        ->name('contest.enter')->middleware('auth');
    Route::get('/contest/{contest}/leaderboard', [ContestController::class, 'leaderboard'])
        ->name('contest.leaderboard');
    Route::get('/contest/{contest}/submissions', [SubmissionController::class, 'global'])
        ->name('contest.submissions')
        ->can('viewSubmissions', 'contest');

    // Forum Routes
    Route::resource('forum', ForumController::class);
});

Route::get('/', function () {
    return redirect()->route('home');
});
