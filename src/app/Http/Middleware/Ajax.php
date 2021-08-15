<?php

namespace App\Http\Middleware;

use Closure;
use App;

class Ajax
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
        // 非同期通信でない場合
        if (!$request->ajax()) {
            abort(404);
        }

        return $next($request);
    }
}
