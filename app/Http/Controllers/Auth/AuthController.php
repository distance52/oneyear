<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\Controller;
use App\Models\SqlLog;
use App\Models\User;
use App\Models\PasswordReset;


class AuthController extends Controller {

    // 登录操作
    public function login(Request $request) {
        //
        if(\Auth::check()) {
//            dd($request->session()->all());
            return redirect()->intended('/');
        } else {

            $failTimes = $request->session()->get('user.failTimes', []);
            if((count($failTimes)>3 && (time()-$failTimes[count($failTimes)-3])<300) || ($request->session()->get('errors') && $request->session()->get('errors')->has('captcha'))) {
                $oCaptcha = new CaptchaController();
                $captcha = $oCaptcha->imgSrc('userCaptcha');
            }
			if (view()->exists(session('mode').'.auth.login')){
				return View(session('mode').'.auth.login', compact('captcha'));
			}else{
				return View('default.auth.login', compact('captcha'));
			}
        }
    }

    /**
     * TODO ： CMP login page
     **/
    public function cmp(Request $request) {
        //
        if(\Auth::check()) {
            return redirect()->intended('/');
        } else {
            $failTimes = $request->session()->get('user.failTimes', []);
            if((count($failTimes)>3 && (time()-$failTimes[count($failTimes)-3])<300) || ($request->session()->get('errors') && $request->session()->get('errors')->has('captcha'))) {
                $oCaptcha = new CaptchaController();
                $captcha = $oCaptcha->imgSrc('userCaptcha');
            }
            if (view()->exists(session('mode').'.auth.cmp')){
                return View(session('mode').'.auth.cmp', compact('captcha'));
            }else{
                return View('default.auth.cmp', compact('captcha'));
            }
        }
    }


    //
    public function postLogin(Requests\UserRequest $request) {
        $email = trim($request->input('email'));
//        if(!preg_match("/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i",$email)){
//            return back()->withInput()->withErrors([
//                'msg' => '请输入正确账号',
//            ]);
//        }
        $email_suffix=$request->input('email_suffix');
        //兼容学生端用学号不带后缀登录
        if(strpos($email, '@') ==false && $email_suffix!=''){
            $email=$email.'@'.$email_suffix;
        }
        if (\Auth::attempt(['email' => $email, 'password' => $request->input('password')],  $request->input('remember'))) {
            // 写入日志
            $oLoginLog = new SqlLog;
            $oUser = \Auth::user();
            $oLoginLog->user_id = $oUser->id;
            $oLoginLog->school_id = $oUser->school_id;
            $oLoginLog->role_id = $oUser->role_id;
            $oLoginLog->name = $oUser->name;
            $oLoginLog->plat = $oUser->plat;
            $oLoginLog->ip = $request->getClientIp();
            $oLoginLog->type = 'login';
            $oLoginLog->save();
            // 登录成功
            $redirect = $request->input('redirect',0);
            if( $redirect) {
                return redirect($redirect);
            }else {
                switch ($oUser->plat) {
                    case 1:
                        $url = env('APP_PRO').'school.'.env('APP_SITE');
                        return redirect($url);
                        break;
                    case 2:
                        $url = env('APP_PRO').'teacher.'.env('APP_SITE');
                        return redirect($url);
                        break;
                    case 3:
                        $url = env('APP_PRO').'student.'.env('APP_SITE');
                        return redirect($url);
                        break;
                    case 4:
                        $url = env('APP_URL').'/member';
                        return redirect($url);
                        break;
                    case 5:
                        $url = env('APP_URL').'/fwsmember';
                        return redirect($url);
                        break;
                    default:
                        $url = env('APP_PRO').'admin.'.env('APP_SITE');
                        return redirect($url);
                        break;
                }
            }
        } else {
            $failTimes = array_slice($request->session()->get('user.failTimes', []), -3);
            $failTimes[] = time();
            $request->session()->put('user.failTimes', $failTimes);
            return back()
                ->withInput()
                ->withErrors([
                    'msg' => '帐号或密码错误',
                ]);
        }

    }

    /*
     * 学生单独登陆
     */
    public function postStuLogin(Request $request) {
        $email=$request->input('email');
        $email_suffix=$request->input('email_suffix');
        //兼容学生端用学号不带后缀登录
        if(strpos($email, '@') ==false && $email_suffix!=''){
            $email=$email.'@'.$email_suffix;
        }
        if (\Auth::attempt(['email' => $email, 'password' => $request->input('password')],  $request->input('remember'))) {
            // 写入日志
            $oLoginLog = new SqlLog;
            $oUser = \Auth::user();
            $oLoginLog->user_id = $oUser->id;
            $oLoginLog->school_id = $oUser->school_id;
            $oLoginLog->role_id = $oUser->role_id;
            $oLoginLog->name = $oUser->name;
            $oLoginLog->plat = $oUser->plat;
            $oLoginLog->ip = $request->getClientIp();
            $oLoginLog->type = 'login';
            $oLoginLog->save();
            // 登录成功
            if($oUser->plat==3){
                
                $url = env('APP_PRO').'student.'.env('APP_SITE');
                return redirect($url);
            }
            else{
                return back()->withInput()->withErrors([
                    'msg' => '不是学生账号不允许登录',
                ]);
            }
        } else {
            $failTimes = array_slice($request->session()->get('user.failTimes', []), -3);
            $failTimes[] = time();
            $request->session()->put('user.failTimes', $failTimes);
            return back()->withInput()->withErrors([
                    'msg' => '帐号或密码错误',
                ]);
        }

    }

//    public function register(){
//        if(\Auth::check()) {
//            return redirect()->intended('/');
//        }
//        $oCaptcha = new CaptchaController();
//        $captcha = $oCaptcha->imgSrc('userCaptcha');
//        return view('default.auth.register', compact('captcha'));
//    }

//    public function postRegister(Request $request){
//
//    }

    public function logout() {
        if(\Auth::check()) {
            $oUser = \Auth::user();
            $oLoginLog = new SqlLog;
            $oLoginLog->user_id = $oUser->id;
            $oLoginLog->school_id = $oUser->school_id;
            $oLoginLog->role_id = $oUser->role_id;
            $oLoginLog->name = $oUser->name;
            $oLoginLog->plat = $oUser->plat;
            $oLoginLog->type = 'logout';
            $oLoginLog->save();
            \Auth::logout();
        }
		session()->forget("mode");
        return redirect(env('APP_URL').'/login');
    }

    // 忘记密码
    public function getForget() {
		if (view()->exists(session('mode').'.auth.forget')){
			return View(session('mode').'.auth.forget');
		}else{
			return View('default.auth.forget');
		}
    }

    // 修改密码
    public function postForget(Request $request) {
        $validator = \Validator::make($request->all(),
            [
                'email'=>'sometimes|email|exists:users,email|required_without:mobile',
                'password'=>'required',
                'code'=>'required',
                'mobile'=>'sometimes|numeric|exists:users,mobile|required_without:email'
            ],
            [
                'email.required'=>'邮箱帐号不能为空',
                'email.email'=>'邮箱格式不正确',
                'email.exists'=>'邮箱帐号不存在',
                'email.required_without'=>'邮箱不得为空',
                'password.required'=>'密码不能为空',
                'code.required'=>'验证码不能为空',
                'mobile.required'=>'手机号不能为空',
                'mobile.numeric'=>'手机号码格式错误',
                'mobile.exists'=>'手机号不存在',
                'mobile.required_without'=>'手机号不得为空'
            ]
        );
        if($validator->fails()) {
            $msg = [
                'custom-msg'=>[$validator->errors()->first()]
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        //对于一个手机号多个账号，修改为先验证手机号码
        $mobile = $request->input('mobile','');
        if($mobile) {
            $oPasswordReset = PasswordReset::where('mobile',$mobile)
                ->where('token',$request->input('code'))->where('status',1)->first();
        } elseif($request->input('email','')) {
            $oPasswordReset = PasswordReset::where('email',$request->input('email'))
                ->where('token',$request->input('code'))->where('status',1)->first();
        } else {
            return response()->json(['custom-msg'=>['邮箱和手机号不能都为空']])->setStatusCode(422);
        }

//        if($request->input('email','')) {
//            $oPasswordReset = PasswordReset::where('email',$request->input('email'))
//            ->where('token',$request->input('code'))->where('status',1)->first();
//        } elseif($request->input('mobile','')) {
//            $oPasswordReset = PasswordReset::where('mobile',$request->input('mobile'))
//            ->where('token',$request->input('code'))->where('status',1)->first();
//        } else {
//            return response()->json(['custom-msg'=>['邮箱和手机号不能都为空']])->setStatusCode(422);
//        }
        //
        if(!$oPasswordReset) {
            return response()->json(['custom-msg'=>['验证码错误']])->setStatusCode(422);
        }
        //
        if($mobile) {
            $count = User::where('mobile',$mobile)->count();
            if($count > 1){
                if(empty($request->input('email',''))){
                    return response()->json(['custom-msg'=>['由于该手机号对应多个账号，请填写登录邮箱']])->setStatusCode(422);
                }
                $oUser = User::where('email', $request->input('email'))->where('mobile', $mobile)->first();
                if(!$oUser){
                    return response()->json(['custom-msg'=>['账号信息错误']])->setStatusCode(422);
                }
            }else{
                $oUser = User::where('mobile', $request->input('mobile'))->first();
            }

        } elseif($request->input('email','')) {
            $oUser = User::where('email', $request->input('email'))->first();
        }
        $oUser->password = bcrypt($request->input('password'));
        $oUser->save();
        \Auth::login($oUser);
        //

        $oPasswordReset->status = 0;
        $oPasswordReset->save();

        switch ($oUser->plat) {
            case 1:
                $url = env('APP_PRO').'school.'.env('APP_SITE');
                // return redirect($url);
                break;
            case 2:
                $url = env('APP_PRO').'teacher.'.env('APP_SITE');
                // return redirect($url);
                break;
            case 3:
                $url = env('APP_PRO').'student.'.env('APP_SITE');
                // return redirect($url);
                break;
            case 4:
                $url = env('APP_PRO').'member';
                // return redirect($url);
                break;
            case 5:
                $url = env('APP_PRO').'fwsmember';
                // return redirect($url);
                break;
            default:
                $url = env('APP_PRO').'admin.'.env('APP_SITE');
                // return redirect($url);
                break;
        }
        return response()->json(['location'=>$url]);
    }

    // api
    public function postEmailCheck(Request $request) {

        $validator = \Validator::make($request->all(),
            [
                'email'=>'required|email|exists:users,email',
            ],
            [
                'email.required'=>'邮箱帐号不能为空',
                'email.email'=>'邮箱格式不正确',
                'email.exists'=>'邮箱帐号不存在'
            ]
        );
        if($validator->fails()) {
            $msg = [
                'custom-msg'=>[$validator->errors()->first('email')]
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        // 发送验证邮件
        $oPasswordReset = new PasswordReset();
        $oPasswordReset->email = $request->input('email');
        $oPasswordReset->token = str_random(6);
        $oPasswordReset->status = 1;
        $oPasswordReset->save();
        //
        \Mail::send('default.auth.emails.password', ['code' => $oPasswordReset->token ], function ($m) use ($oPasswordReset) {
            $m->from(config('mail.from.address'), config('mail.from.name'));
            $m->to($oPasswordReset->email, '用户')->subject('邮箱验证');
        });
        //
        return response()->json(null);

    }

    public function postMobileCheck(Request $request) {
        $validator = \Validator::make($request->all(),
            [
                'mobile'=>'required|numeric|exists:users,mobile',
            ],
            [
                'mobile.required'=>'手机号不能为空',
                'mobile.numeric'=>'手机号码格式错误',
                'mobile.exists'=>'手机号不存在',
            ]
        );
        //验证
        if($validator->fails()) {
            $data = array('state'=>false , 'data' =>'', 'custom-msg'=>[$validator->errors()->first('mobile')]);
            return response()->json($data)->setStatusCode(422);
        }
        // 发送手机验证码
        $oPasswordReset = new PasswordReset();
        $oPasswordReset->mobile = $request->input('mobile');
        $oPasswordReset->token = mt_rand(100000,999999);
        $oPasswordReset->status = 1;
        $oPasswordReset->save();
        // 发送手机验证码
        $data=array(
            'code'=>$oPasswordReset->token,
            'product'=>'用户'
        );
        $smsclass=new \App\Http\Controllers\NoticeSend\SmsNoticeController();
        $return=$smsclass->send($oPasswordReset->mobile,'SMS_8130859',$data);
        $return=json_decode($return,true);
        if (!empty($return) && $return['success']) {
            $count = User::where('mobile',$oPasswordReset->mobile)->count();
            $data = array('state'=>true , 'data' =>$count, 'custom-msg' => '');
            return response()->json($data);
        }else{
            $data = array('state'=>false , 'data' =>'', 'custom-msg'=>["验证码发送失败"]);
            return response()->json($data)->setStatusCode(422);
        }
    }

    public function postMobileRegister(Request $request) {
        $validator = \Validator::make($request->all(),
            [
                // 'mobile'=>'required|numeric|exists:users,mobile',
                'mobile'=>'required|numeric',
                'captcha'=>'required',
            ],
            [
                'mobile.required'=>'手机号不能为空',
                'mobile.numeric'=>'手机号码格式错误',
                // 'mobile.exists'=>'手机号不存在'
                'captcha.required'=>'验证码不能为空',
            ]
        );
        if($validator->fails()) {
            $data = array('state'=>false , 'data' =>'', 'custom-msg'=>[$validator->errors()->first()]);
            return response()->json($data)->setStatusCode(422);
        }
        // 发送手机验证码
        $oPasswordReset = new PasswordReset();
        $oPasswordReset->mobile = $request->input('mobile');
        $oPasswordReset->token = mt_rand(100000,999999);
        $oPasswordReset->status = 3;
        $oPasswordReset->save();
        // 发送手机验证码
        $data=array(
            'code'=>$oPasswordReset->token,
            'product'=>'用户'
        );
        $smsclass=new \App\Http\Controllers\NoticeSend\SmsNoticeController();
        $return=$smsclass->send($oPasswordReset->mobile,'SMS_8130859',$data);
        $return=json_decode($return,true);
        if (!empty($return) && $return['success']) {
            $count = User::where('mobile',$oPasswordReset->mobile)->count();
            $data = array('state'=>true , 'data' =>$count, 'custom-msg' => '');
            return response()->json($data);
        }else{
            $data = array('state'=>false , 'data' =>'', 'custom-msg'=>["验证码发送失败"]);
            return response()->json($data)->setStatusCode(422);
        }
    }


    public function qrCode(){
        $http = new NoticeHttp;
        $token = get_access_token();

        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$token";

        $qrid = 'user-'.rand(1, 4294967295);
        $key = "wx:qrcode:{$qrid}";
        if (Redis::exists($key)) {
            return response()->json(array('status'=>false , 'msg'=> 'key exist' ,'data'=>null ));
        }
        Redis::set($key , '');
        Redis::expire($key ,120);
        $parmas = array('expire_seconds'=>120 , "action_name"=>"QR_STR_SCENE" ,"action_info"=> array('scene'=> array('scene_str'=>$qrid) ));
        $s = $http->postRequest($url,json_encode( $parmas));

        $result = json_decode($s,true);
        \Session::put('qr_id',$qrid);
        \Session::save();
        if(isset($result['ticket'])){
            return response()->json(array('status'=>true , 'msg'=> 'ok' ,'data'=>array('userId'=>$qrid , 'ticket'=> $result['ticket'] ) ));
        }else{
            return response()->json(array('status'=>false , 'msg'=> 'err' ,'data'=>null ));
        }

        // $s =file_get_contents("https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$result['ticket']}");
        // header('Content-type: image/jpeg');
        // echo $s;

        // $http = new NoticeHttp;
        // $token = get_access_token();
        // $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$token";
        // $parmas = '{"button":[{"type":"view",  "name":"双创教研","url":"https://sc.cnczxy.com/"},{"name":"更多系统","sub_button":[{"type":"view","name":"自主学习","url":"http://edu.cnczxy.com/"},{"type":"view","name":"沙盘模拟","url":"https://sp.cnczxy.com/"}]}]}';

        // $s = $http->postRequest($url,$parmas);
        // dump($s );

    }

    public function getUserScan($id ,Request $request){


        $qid = \Session::get('qr_id');
        $key = "wx:qrcode:{$qid}";
        if (!Redis::exists($key)) {
            return response()->json(array('status'=>false ,'msg'=> 'not found' ,'data'=>null  ));
        }
        $data = Redis::get($key);
        if($data){
            $data = json_decode($data ,true);

            $uoid = UserOpenid::where('openid',$data['openid'])->first();
            if($uoid){
                //存在执行登录操作,有问题
                \Auth::loginUsingId($uoid->user_id);//登录并设置session
                \Session::forget('qr_id');
                return response()->json(array('status'=>true , 'msg'=> 'ok' ,'data'=> array('method'=> 'login' , 'id' => $uoid->id) ));
            }else{

                return response()->json(array('status'=>true , 'msg'=> 'ok' ,'data'=> array('method'=> 'register' , 'id' => $qid) ));
            }

        }else{
            return response()->json(array('status'=>true , 'msg'=> 'ok' ,'data'=>null ));
        }
    }

    public function school(Request $request){
        $name = $request->input('name',0);
        if(!$name){
            return response()->json(array('status'=>false ,'msg'=>'请输入学校名称','data'=>null));
        }
        $data = [];
        $obj = School::where("name","like","%$name%")->take(5)->get();
        $obj->each(function ($obj,$i) use(&$data){
            $data[$i]['id'] = $obj->id;
            $data[$i]['name'] = $obj->name;
        });
        return response()->json(array('status'=>true ,'msg'=>'ok','data'=>$data));

    }

//    public function squad($id,Request $request){
//        $name = $request->input('name',0);
//        if(!$name){
//            return response()->json(array('status'=>false ,'msg'=>'请输入班级名称','data'=>null));
//        }
//        $data = [];
//        $obj = Squad::where('school_id',$id)->where("name","like","%$name%")->take(5)->get();
//        $obj->each(function ($obj,$i) use(&$data){
//            $data[$i]['id'] = $obj->id;
//            $data[$i]['name'] = $obj->name;
//        });
//        return response()->json(array('status'=>true ,'msg'=>'ok','data'=>$data));
//
//    }




}
