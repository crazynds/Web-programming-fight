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
Route::resource('problem.testcase', TestCaseController::class)
    ->except(['show','edit','update']);


Route::get('/', function(){
    return redirect()->route('run.create');
});
