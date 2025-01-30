<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\SubmitRunController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });



Route::middleware('auth:web')->group(function(){
    Route::get('/run/{submitRun}/code',[SubmitRunController::class,'getCode'])
        ->name('submitRun.code');
    Route::get('/run/{submitRun}/result',[SubmitRunController::class,'result'])
        ->name('submitRun.result');
    Route::get('/submitRun/{submitRun}/rejudge', [SubmitRunController::class, 'rejudge'])
        ->name('submitRun.rejudge');


    Route::middleware('can:viewPulse')->group(function () {
        Route::get('/backup',[BackupController::class,'start'])
            ->name('backup.start');
        Route::get('/backup/download',[BackupController::class,'download'])
            ->name('backup.download');
        Route::get('/backup/now',[BackupController::class,'backupNow'])
            ->name('backup.now');
        Route::post('/backup/upload',[BackupController::class,'upload'])
            ->name('backup.upload');
    });

});