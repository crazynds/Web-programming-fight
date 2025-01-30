<?php

use App\Http\Controllers\BackupController;
use Illuminate\Support\Facades\Route;


// Rotas do contest



Route::get('/files', function () {
    return view('vendor.filemanager.index');
});

Route::get('/backup',[BackupController::class,'index']);