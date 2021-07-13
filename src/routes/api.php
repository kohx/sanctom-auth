<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);


Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/test', function () {
        return 'test';
    });
});
