<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Plugin\PluginManager;
//use DB;
use Illuminate\Support\Facades\Redis;
use App\Models\Option;
use Illuminate\Routing\UrlGenerator;

class PluginPermission
{
    public function handle($request, Closure $next ,$name){
        $school_id = \Session::get('school_id');
        if($school_id){
            $key = "pluginEnabled:{$school_id}";
            if(Redis::exists($key)){

                $enbled = Redis::hgetall($key);
            }else{

                $enbled = array();
                Option::where('sid',$school_id)->where('option_name','plugins_enabled')->get()->each(function ($enble) use(&$enbled ,$key ){
                    $enbled[$enble->option_value] = $enble->end_time;
                    Redis::hMset($key,$enbled);
                    Redis::expire($key,3600*24*7);
                });
            }
            if( !empty($enbled)  && isset($enbled[$name])){

                if( $enbled[$name] != 0 && time() > $enbled[$name] ){
                    return redirect('error')->with(['msg'=>"应用模块服务已到期（error:{$name}_{$school_id}）", 'href'=>'/']);

//                    abort(404 , "应用模块服务已到期（error:{$name}_{$school_id}）");
                }
            }else{

                return redirect('error')->with(['msg'=>"找不到应用模块（error:{$name}_{$school_id}）", 'href'=>'/']);

//                abort(404 , "找不到应用模块（error:{$name}_{$school_id}）");
            }
        }
        return $next($request);
    }
}