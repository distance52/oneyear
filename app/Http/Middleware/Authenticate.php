<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use DB;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        //如果是微信浏览器
        if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
            if (Auth::guard($guard)->guest()) {
                //因授权域的关系只能用主域来授权
                $bindurl=env('APP_URL').'/login/bind/weixinmob?_target_path='.urlencode($request->url());
                return redirect($bindurl);
            }
            else{
                $oUser = \Auth::user();
                $arrPlat = [
                    'admin',
                    'school',
                    'teacher',
                    'student',
                    'member',
                    'fwsmember'
                ];
                // 老师
                if($oUser->plat > 0 && $oUser->plat <= 3  && $oUser->school_id == 0) {
                    return redirect('error')->with(['msg'=>'您的账号尚未关联任何学校，请确认后重新登陆，谢谢！', 'href'=>'']);
                }
                //学生
//                if($oUser->plat == 3) {
//                    $oStudent = $oUser->student->id;
//					$osquad = \App\Models\SquadStruct::where('type',1)->where('struct_id',$oStudent)->first();
//                    if(!$osquad) {
//                        return redirect('error')->with(['msg'=>'您的账号尚未关联任何班级，请确认后重新登陆，谢谢！', 'href'=>'']);
//                    }
//                }
                if($oUser->plat>=4){
                    return redirect(env('APP_PRO').env('APP_SITE').'/'.$arrPlat[$oUser->plat]);
                }
                if(!str_contains($request->root(), $arrPlat[$oUser->plat])) {
                    return redirect('error')->with(['msg'=>'你没有权限登录此平台，将为你进行跳转', 'href'=>env('APP_PRO').$arrPlat[$oUser->plat].'.'.env('APP_SITE')]);
                }
            }
			if(empty(session('mode'))){
				$omode = 'default';
				if(!empty(config('mode')['id'][$oUser->school_id])){
					$omode = config('mode')['id'][$oUser->school_id];
				}
				session(['mode'=>$omode]);
			}
            return $next($request);
        }
        else{
            if (Auth::guard($guard)->guest()) {
                $host=\Request::root();
                $school_suffix=\App\Models\School::pluck('id','host_suffix')->toArray();
                $suffix=array_keys($school_suffix);
                preg_match('/^(http:\/\/){0,1}([^\.]*)[\.]?/',$host,$match);//取最前面的前缀
                $school_id=0;
                if ($request->ajax() || $request->wantsJson()) {
                    return response('Unauthorized.', 401);
                }
                elseif(in_array($match[2],$suffix)){
                    $settings = config('login_bind');
                    $type='weixinweb';
                    $wechat_appid=$settings[$type.'_key'];
                    $school_id=$school_suffix[$match[2]];
                    $school_info=\App\Models\School::where('id',$school_id)->first(['name','email_suffix']);
                    //授权地址
                    $callbackUrl = env('APP_URL').'/login/bind/'.$type.'/callback';
                    $redirect_uri=urlencode($callbackUrl);
                    $student_code_url= env('APP_PRO').'student.'.env('APP_SITE');
                    //学校单独登录页
                    return view('default.auth.login-school',compact('captcha','redirect_uri','wechat_appid','student_code_url','school_info'));
                }
                else {
                    return redirect(env('APP_URL').'/login');
                }
            } else {
                $oUser = \Auth::user();
                $arrPlat = [
                    'admin',
                    'school',
                    'teacher',
                    'student',
                    'member',
                    'fwsmember',
                ];
                // 老师
                if($oUser->plat > 0 && $oUser->plat <= 3 && $oUser->school_id == 0) {
                    return redirect('error')->with(['msg'=>'您的账号尚未关联任何学校，请确认后重新登陆，谢谢！', 'href'=>'']);
                }
                //学生
//                if($oUser->plat == 3) {
//                    $oStudent = $oUser->student->id;
//					$osquad = \App\Models\SquadStruct::where('type',1)->where('struct_id',$oStudent)->first();
//                    if(!$osquad) {
//                        return redirect('error')->with(['msg'=>'您的账号尚未关联任何班级，请确认后重新登陆，谢谢！', 'href'=>'']);
//                    }
//                }
                if($oUser->plat>=4){
                    return redirect(env('APP_PRO').env('APP_SITE').'/'.$arrPlat[$oUser->plat]);
                }
                // 跳转
//                dd(env('APP_PRO').$arrPlat[$oUser->plat].'.'.env('APP_SITE'));
                if(!str_contains($request->root(), $arrPlat[$oUser->plat])) {
                    return redirect('error')->with(['msg'=>'你没有权限登录此平台，将为你进行跳转', 'href'=> env('APP_PRO').$arrPlat[$oUser->plat].'.'.env('APP_SITE')]);
                }
            }
			if(empty(session('mode'))){
				$omode = 'default';
				if(!empty(config('mode')['id'][$oUser->school_id])){
					$omode = config('mode')['id'][$oUser->school_id];
				}
				session(['mode'=>$omode]);
			}
            return $next($request);
        }
    }
}
