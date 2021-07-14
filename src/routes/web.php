<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckController;

Route::get('/check', [CheckController::class, 'index']);

// API以外はindexを返すようにして、VueRouterで制御
Route::get('/{any?}', function () {
    return view('index');
})->where('any', '.+');
