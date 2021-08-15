<?php

namespace App\Http\Middleware;

use Closure;
use App;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // sessionのlang
        $session_lang = $request->session()->get('language');

        // sessionのlangがない場合はデフォルトをセット
        $lang = is_null($session_lang) ? config('app.fallback_locale') : $session_lang;


        // 言語が変わる場合
        if (App::getLocale() !== $lang) {
            // 言語をセット
            App::setLocale($lang);
        }

        return $next($request);
    }
}
