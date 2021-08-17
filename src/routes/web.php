<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckController;

// Route::group(['middleware' => ['can:superAdmin']], function () {
    Route::get('/check', [CheckController::class, 'index'])->name('post.index');
// });

// Route::get('/check/{post}', [CheckController::class, 'show'])->name('post.show');

// Route::group(['middleware' => ['can:postCreate']], function () {
//     Route::get('/check/{post}/store', [CheckController::class, 'store'])->name('post.store');
//     Route::get('/check/{post}/update', [CheckController::class, 'update'])->name('post.update');
// });

// Route::group(['middleware' => ['can:postDestroy,post']], function () {
//     Route::get('/check/{post}/destroy', [CheckController::class, 'destroy'])->name('post.destroy');
// });

// API以外はindexを返すようにして、VueRouterで制御
Route::get('/{any?}', function () {
    return view('index');
})->where('any', '.+');
