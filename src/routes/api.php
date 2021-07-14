<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);
Route::post('/register', [RegisterController::class, 'register']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('/user', function (Request $request) {
        return response()->json([
            'message' => 'Logged in',
            'user' => $request->user(),
        ], 200);
    });

    Route::get('/test', function () {
        return response()->json([
            'message' => 'Authenticated',
        ], 200);
    });
});
