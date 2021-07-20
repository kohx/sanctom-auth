<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // https://laravel.com/docs/8.x/validation#validating-passwords
        Password::defaults(function () {
            // 最低8文字
            return Password::min(8)
                // 大文字と小文字のアルファベットを含むこと
                ->mixedCase()
                // 1文字以上の数字を含むこと
                ->numbers()
                // 1文字以上の記号を含むこと
                ->symbols();
        });
    }
}
