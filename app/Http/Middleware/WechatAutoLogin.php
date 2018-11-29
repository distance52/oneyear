<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class WechatAutoLogin
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
        $user_id   = Session::get('user_id');
        $wechat_id = Session::get('wechat_id');
        $bindurl   = route('login_bind',['type' => 'weixinmob','_target_path'=>urlencode($request->url())]);
        if($user_id>0){
            //如果session中有用户id没有wechatid
            if($wechat_id<0){
                //数据库中读取wechatid
                $user=DB::table('UserOpenid')->where(['user_id'=>$user_id])->first();
                if($user){
                    Session::put('wechat_id',$user->openid);
                }
                else{
                    return redirect($bindurl);
                }
            }
        }
        else{
            if($wechat_id>0){
                //数据库中查询对应的userid
                $user=DB::table('UserOpenid')->where(['wechat_id'=>$wechat_id])->first();
                if($user){
                    Session::put('user_id',$user->user_id);
                }
                else{
                    return redirect($bindurl);
                }
            }
            else{
                //跳完微信授权页面
                //return Redirect::action("LoginController@getIndex", ["path" => $request->fullUrl()]);
                return redirect($bindurl);
            }
        }
        return $next($request);
    }
}
