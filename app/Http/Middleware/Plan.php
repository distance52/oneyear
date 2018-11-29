<?php

namespace App\Http\Middleware;


use Illuminate\Routing\UrlGenerator;
use Closure;

class Plan
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

        $oUser = \Auth::user();
        // 获取用户的权限
        if($oUser->plat === 3) {
			return redirect('error')->with(['msg'=>'你没有权限操作！', 'href'=>app(UrlGenerator::class)->previous()]);
        }
        return $next($request);
    }
}
