<?php

namespace App\Http\Middleware;

use Closure;

class WechatApi
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
        if (!empty ($request->input('echostr')) && !empty ( $request->input('signature')) && !empty ($request->input('nonce'))) {
            $signature = $request->input('signature');
            $timestamp = $request->input('timestamp');
            $nonce = $request->input('nonce');
            $tmpArr = array (
                'school',
                $timestamp,
                $nonce
            );
            sort ($tmpArr,SORT_STRING );
            $tmpStr = sha1(implode($tmpArr));
            if ($tmpStr == $signature) {
                echo $request->input('echostr');
            }
            exit ();
        }
        return $next($request);
    }
}
