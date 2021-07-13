<?php

use Illuminate\Support\Facades\Route;

// API以外はindexを返すようにして、VueRouterで制御
Route::get('/{any?}', function () {
    return view('index');
})->where('any', '.+');
