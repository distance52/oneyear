<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class IsLogin
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
        if(!\Auth::check()) {
            \Log::info('未登录执行：'. $request->fullUrl());
            $msg = [
              "custom-msg"=> ["没有登录，请刷新页面重试"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        return $next($request);
    }
}
