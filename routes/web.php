<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\SubmitRunController;
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

 
Route::get('/home',function(){
    return view('pages.home');
})->name('home');


// auth routes
Route::get('/auth/{provider}/redirect', [AuthController::class,'redirect'])->name('auth.login');
Route::get('/auth/{provider}/callback',[AuthController::class,'callback'])->name('auth.callback');
Route::get('/auth/logout',[AuthController::class,'logout'])->middleware('auth')->name('auth.logout');



Route::middleware('auth')->group(function(){
    // problem routes
    // TODO: fazer o problema estar vinculado a um usuario
    // TODO: somente os donos podem editar seus problemas e etc..
    Route::resource('problem', ProblemController::class);
    Route::resource('problem.testCase', TestCaseController::class)
        ->except(['edit','update']);
    Route::controller(TestCaseController::class)
        ->name('problem.')->prefix('problem/{problem}')
        ->group(function(){
        Route::get('testCase/{testCase}/input','downloadInput')
            ->name('testCase.input');
        Route::get('testCase/{testCase}/output','downloadOutput')
            ->name('testCase.output');
        Route::get('testCase/{testCase}/up','up')
            ->name('testCase.up');
        Route::get('testCase/{testCase}/down','down')
            ->name('testCase.down');
        Route::get('testCase/{testCase}/public','publicChange')
            ->name('testCase.edit.public');
    });

    // Todo fazer polices para submissions, somente o dono pode gerenciar
    // run routes
    Route::resource('run', SubmitRunController::class)
        ->only(['index','store','create','show']);
    Route::get('/run/{submitRun}/rejudge',[SubmitRunController::class,'rejudge'])
        ->name('run.rejudge');
    Route::get('/run/global/live',[SubmitRunController::class,'global'])
        ->name('run.global');
    Route::get('/run/{submitRun}/show',[SubmitRunController::class,'show'])
        ->name('run.show');
    Route::get('/run/{submitRun}/download',[SubmitRunController::class,'download'])
        ->name('run.download');

    // user routes
    Route::get('/user/profile',[UserController::class,'profile'])
        ->name('user.me');
    Route::get('/user/profile/{user}',[UserController::class,'profileUser'])
        ->name('user.profile');

});



Route::get('/', function(){
    return redirect()->route('home');
});
