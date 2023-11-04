<?php

use App\Http\Controllers\ProblemController;
use App\Http\Controllers\SubmitRunController;
use App\Http\Controllers\TestCaseController;
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

Route::resource('run', SubmitRunController::class)
    ->only(['index','store','create','show']);

Route::resource('problem', ProblemController::class);
Route::resource('problem.testCase', TestCaseController::class)
    ->except(['edit','update']);
Route::get('/problem/{problem}/testCase/{testCase}/input',[TestCaseController::class,'downloadInput'])
    ->name('problem.testCase.input');
Route::get('/problem/{problem}/testCase/{testCase}/output',[TestCaseController::class,'downloadOutput'])
    ->name('problem.testCase.output');
Route::get('/problem/{problem}/testCase/{testCase}/up',[TestCaseController::class,'up'])
    ->name('problem.testCase.up');
Route::get('/problem/{problem}/testCase/{testCase}/down',[TestCaseController::class,'down'])
    ->name('problem.testCase.down');


Route::get('/', function(){
    return redirect()->route('run.create');
});
