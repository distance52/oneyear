<?php

namespace App\Http\Middleware;

use Closure;

class ResetConfig
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

        $json_datas = $arr_datas = null;
        // email
        $file_path = storage_path('app/email.data');
        if(file_exists($file_path)) {
            $json_datas = file_get_contents($file_path);
            $json_datas && $arr_datas = json_decode($json_datas, true);
            $arr_datas && config(['mail' => array_merge(config('mail'), $arr_datas)]);
        }
        $json_datas = $arr_datas = null;
        // msg
        $file_path = storage_path('app/msg.data');
        if(file_exists($file_path)) {
            $json_datas = file_get_contents($file_path);
            $json_datas && $arr_datas = json_decode($json_datas, true);
            $arr_datas && config(['msg' => $arr_datas]);
        }
        $json_datas = $arr_datas = null;
        // basic
        $file_path = storage_path('app/basic.data');
        if(file_exists($file_path)) {
            $json_datas = file_get_contents($file_path);
            $json_datas && $arr_datas = json_decode($json_datas, true);
            $arr_datas && config(['basic' => $arr_datas]);
        }
        $json_datas = $arr_datas = null;
        // oss
        $file_path = storage_path('app/alioss.data');
        if(file_exists($file_path)) {
            $json_datas = file_get_contents($file_path);
            $json_datas && $arr_datas = json_decode($json_datas, true);
            if($arr_datas) {
                $arr_datas['domain'] = config('alioss.outer_url');
                config(['alioss' => array_merge(config('alioss'), $arr_datas)]);
                $oss = config('filesystems.disks.oss');
                $oss['access_id'] = isset($arr_datas['access_key_id'])? $arr_datas['access_key_id']:'';
                $oss['access_key'] = isset($arr_datas['access_key_secret'])? $arr_datas['access_key_secret']:'';
                $oss['endpoint'] = $arr_datas['endpoint'];
                $oss['bucket'] = $arr_datas['bucket'];
                config(['filesystems.disks.oss'=>$oss]);
            }
        }
        //第三方登录
        $json_datas = $arr_datas = null;
        $file_path = storage_path('app/login_bind.data');
        if(file_exists($file_path)) {
            $json_datas = file_get_contents($file_path);
            $json_datas && $arr_datas = json_decode($json_datas, true);
            $arr_datas && config(['login_bind' => $arr_datas]);
        }
        //ucenter设置
        $json_datas = $arr_datas = null;
        $file_path = storage_path('app/ucenter.data');
        if(file_exists($file_path)) {
            $json_datas = file_get_contents($file_path);
            $json_datas && $arr_datas = json_decode($json_datas, true);
            $arr_datas && config(['ucenter' => array_merge(config('ucenter'), $arr_datas)]);
        }
		//模板设置
        $json_datas = $arr_datas = null;
        $file_path = storage_path('app/mode.data');
        if(file_exists($file_path)) {
            $json_datas = file_get_contents($file_path);
            $json_datas && $arr_datas = json_decode($json_datas, true);
            $arr_datas && config(['mode' => $arr_datas]);
        }
        return $next($request);
    }
}
