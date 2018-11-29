<?php

namespace App\Http\Controllers\Usercenter;

use App\Models\UserOpenid;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\NotifyUser;
use App\Models\User;
use App\Models\SqlLog;
use App\Models\Teacher;
use App\Models\UserBind;
use App\Models\Student;
use App\Models\PasswordReset;
use App\Models\SquadStruct;
use App\Models\School;
use App\Models\ProjectTutor;
use App\Models\Provider;
use Illuminate\Support\Facades\Redis as Redis;

use App\Http\Controllers\CaptchaController;

class MyprofileController extends Controller
{
    private $oUser=null;
    public function __construct()
    {
        $this->oUser = \Auth::user();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $oUser=$this->oUser;
        $oLog=SqlLog::where(['user_id'=>$oUser->id,'type'=>'login'])
            ->orderBy('id','desc')
            ->limit(2)
            ->get();
          
        $oUser->avatar=getAvatar($oUser->avatar);
        if(isset($oLog[1])){
            $oUser->ip=$oLog[1]->ip;
            $oUser->created_at=$oLog[1]->created_at;
        }
        $user_bind = UserBind::where('toId',$oUser->id)->first();
        $is_weixin_bind=empty($user_bind)?0:1;
        if($is_weixin_bind){
            $oBind = UserBind::where(['fromId'=>$user_bind->fromId])->get();
            $bind_list = [];
            $plat = ['管理员','学校管理员','老师','学生'];
            $oBind->each(function ($oBind,$i) use(&$bind_list,$plat){
                $oUser = User::whereId($oBind->toId)->first();
                $bind_list[$i]['id'] = $oBind->toId;
                $bind_list[$i]['name'] = $oUser->name;
                $bind_list[$i]['email'] = $oUser->email;
                $bind_list[$i]['plat'] = $plat[$oUser->plat];
                $bind_list[$i]['avatar'] = getAvatar($oUser->avatar);
                $bind_list[$i]['type'] = $oBind->type == 'weixin' ? '微信' : '其他';
            });
        }else{
            $bind_list =  '';
        }
        $oTeacher='';
        if($oUser->plat==2){
            $oTeacher=Teacher::where('user_id',$oUser->id)->first();
        }
        $sex = [1=>'男',2=>'女'];
        $oUser->birthday= substr($oUser->birthday,0,11);
         if($oUser->birthday == '0000-00-00 '){
            $oUser->birthday='';
        }
		if (view()->exists(session('mode').'.usercenter.myprofile.list')){
			return View(session('mode').'.usercenter.myprofile.list', compact('oUser','sex','oTeacher','is_weixin_bind','bind_list'));
		}else{
			return View('default.usercenter.myprofile.list', compact('oUser','sex','oTeacher','is_weixin_bind','bind_list'));
		}
    }

    public function saveExtend(Request $request){



        $oUser=\Auth::user();
        $oUser->name=$request->input('name');
        $oUser->sex=$request->input('sex');
        $oUser->birthday=$request->input('birthday');
       
        $oUser->weixin=$request->input('weixin');
            // $this->validate($request,[
            //         'sex'=>'required';
            //         'weixin'=>'required|regex:^[a-zA-Z\d_]{5,}$',
            //     ],[
            //         'sex.required' =>'性别不能为空',
            //         'weixin.required' =>'微信不能为空',
            //         'weixin.regex' =>'微信号格式不正确',
            //     ]);
        $oUser->save();

          if($oUser->plat==2){
            $oTeacher=Teacher::where('user_id',$oUser->id)->first();
            $oTeacher->qq=$request->input('qq');
            $oTeacher->dept=$request->input('dept');
            $oTeacher->speciality=$request->input('speciality');
            $oTeacher->desc=$request->input('desc');
            $oTeacher->save();
        }
        return response()->json(array(1=>'ture'));
    }

    //修改密码
    public function doChangePwd(Request $request){
        $oUser=$this->oUser;
        $user_id = $oUser->id;
        $oldPassword=$request->input('oldPassword','');
        $newPassword=$request->input('newPassword','');
        $newPasswordConfirm=$request->input('newPasswordConfirm','');
        $messages = [
            'oldPassword.required' => '原密码不能为空',
            'newPassword.required' => '新密码不能为空',
            'newPasswordConfirm.required' => '确认密码不能为空',
            'newPasswordConfirm.same' => '两次密码输入不一致',
        ];
        $v = Validator::make($request->all(), [
            'oldPassword' => 'required',
            'newPassword' => 'required',
            'newPasswordConfirm' => 'required|same:newPassword',
        ],$messages);
        if ($v->fails()){
            $msg = [
                "custom-msg"=> [$v->errors()->first()],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if(!password_verify($oldPassword,$oUser->password)){
            $msg = [
                "custom-msg"=> ['原密码输入错误'],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $oUser->password=bcrypt($newPassword);
        $oUser->save();
        return response()->json(null);
    }

    /*
     * 解除微信绑定
     */
    public function cancelBind(){
        $oUser=$this->oUser;
        $user_id = $oUser->id;
        $bindinfo=UserBind::where(['toId'=>$user_id,'type'=>'weixin'])->first();
        if(empty($bindinfo)){
            $msg = [
                "custom-msg"=> ['该帐号还没有绑定微信'],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        UserBind::where('id',$bindinfo->id)->delete();
        UserOpenid::where('user_id',$user_id)->delete();
        return response()->json(null);
    }

    //用户注册页面
    public function setProfile(Request $request){

        $qid = \Session::get('qr_id');
        if($qid) {
            //处理微信扫码注册
            $key = "wx:qrcode:{$qid}";
            if (!Redis::exists($key)) {
                abort(404,'抱歉验证已经失效，请重新注册');
            }
            $data = Redis::get($key);
            if($data){
                $data = json_decode($data ,true);
                // $failTimes = $request->session()->get('user.failTimes', []);
                // if((count($failTimes)>3 && (time()-$failTimes[count($failTimes)-3])<300) || ($request->session()->get('errors') && $request->session()->get('errors')->has('captcha'))) {
                $oCaptcha = new CaptchaController();
                $captcha = $oCaptcha->imgSrc('userCaptcha');
                // }
                return view('default.usercenter.myprofile.info', compact('captcha','data'));

            }else{
                abort(404,'抱歉验证已经失效，请重新注册');
            }
        }else{
            //处理访问页面注册

            $oCaptcha = new CaptchaController();
            $captcha = $oCaptcha->imgSrc('userCaptcha');
            $data =  [];
            // }
            return view('default.usercenter.myprofile.info', compact('captcha','data'));
        }
//        if(!$qid) {
//            return redirect('/login');
//        }

    }

    //通过班级邀请二维码注册
    public function setUserProfile($qid , Request $request){

        $key = "wx:qrcode:squadUser-{$qid}";
        if (!Redis::exists($key)) {
            abort(404,'抱歉验证已经失效，请重新注册');
        }
        $data = Redis::get($key);
        if($data){
            $data = json_decode($data ,true);
            $squad_id = isset($data['squad_id']) ? $data['squad_id'] :0 ;
            // $school_id =  0;
            $oSquad = 0;
            if($squad_id){
                $oSquad = Squad::where('id',$squad_id)->first();
                // $school_id = $oSquad->school_id;
            }

            // $failTimes = $request->session()->get('user.failTimes', []);
            // if((count($failTimes)>3 && (time()-$failTimes[count($failTimes)-3])<300) || ($request->session()->get('errors') && $request->session()->get('errors')->has('captcha'))) {
            $oCaptcha = new CaptchaController();
            $captcha = $oCaptcha->imgSrc('userCaptcha');
            // }
            return view('default.usercenter.myprofile.info', compact('captcha','data','qid','squad_id','oSquad'));

        }else{
            abort(404,'抱歉验证已经失效，请重新注册');
        }
    }


    //注册用户
    public function saveProfile(Request $request){

        $validator = \Validator::make($request->all(),
            [
                'mobile'=>'required|numeric',
                'school_id'=>'numeric',
                'name'=>'required',
                'password'=>'required',
                'code'=>'required',
                // 'sno'=>'numeric|between:6,12',
                // 'squad_id'=>'numeric',
            ],
            [
                'mobile.required'=>'手机号码不能为空',
                'mobile.numeric'=>'手机号码格式不正确',
                'school_id.required'=>'学校不能为空',
                'school_id.numeric'=>'学校格式不正确',
                'name.required'=>'姓名不能为空',
                'password.required'=>'密码不能为空',
                'code.required'=>'验证码不能为空',
                // 'sno.numeric'=>'学号格式不正确',
                // 'sno.between'=>'学号格式不正确',
                // 'squad_id.numeric'=>'班级格式不正确',
            ]
        );
        if($validator->fails()) {
            return back()->withInput()->withErrors([ 'msg' => $validator->errors()->first(),'plat'=>$request->plat]);
        }

        $mobile = $request->input('mobile',0);
        $oPasswordReset = PasswordReset::where('mobile',$mobile)
            ->where('token',$request->input('code'))->where('status',3)->first();
        if(!$oPasswordReset) {
            return back()->withInput()->withErrors([ 'msg' => '手机验证码错误','plat'=>$request->plat]);
        }


        $school_id = $request->input('school_id',0);
        $name = $request->input('name',0);
        $mobile = $request->input('mobile',0);
        $sno = $request->input('sno','');
        $squad_id = $request->input('squad_id',0);
        $plat = $request->input('plat',3);
        $qid = '';
        $key = '';
        if(\Session::get('qr_id')){
            $qid = \Session::get('qr_id');
            $key = "wx:qrcode:{$qid}";
        }
        if($request->input('qid',0)){
            $qid = $request->input('qid');
            $key = "wx:qrcode:squadUser-{$qid}";
        }

//        if($qid) {
////            return redirect('/login');
//        }

//        if (!Redis::exists($key)) {
//            abort(404,'抱歉验证已经失效，请重新注册');
//        }
//        $wxData = Redis::get($key);
//        if($wxData){
//            $wxData = json_decode($wxData ,true);

            //////

//            $suffix = School::where('id',$school_id)->first(['email_suffix','host_suffix']);
//            if($suffix->email_suffix==''){
//                return back()->withInput()->withErrors([ 'msg' => '该学校的登录邮箱后缀还未设置',]);
//            }
//            if($suffix->host_suffix==''){
//                return back()->withInput()->withInput()->withErrors([ 'msg' => '该学校的二级域名前缀还未设置',]);
//            }

            if($plat == 2){

                return back()->withInput()->withErrors([ 'msg' => '用户权限还没有开放注册' ]);

                $check = $this->checkSuffix($school_id);
                if( !$check['status'] ) {
                    return back()->withInput()->withErrors([ 'msg' => $check['data'] ]);
                }

                $suffix = School::where('id',$school_id)->first(['email_suffix','host_suffix']);
                $email = trim($mobile).'@'.$suffix->email_suffix;

                $oUser = new User();
                $data=array(
                    'name'=> $name,
                    'email'=> $email,
                    'mobile'=> $mobile,
//                    'password'=> substr(trim($mobile) ,-6),
                    'password'=> $request->input('password'),
                    'plat'=>2,
                    'school_id'=> $school_id
                );
                $newUser = $oUser->createUser($data);
                //未通过验证或创建失败直接报错
                if(!$newUser['status']){
                    return back()->withInput()->withErrors([ 'msg' => $newUser['info'],]);
                }
                $newUser = $newUser['data'];
                if($newUser){
                    $data=array(
                        'user_id'=>$newUser->id,
                        'school_id'=>$request->input('school_id',0),
                        'name'=>$request->input('name',''),
                        'dept'=>$request->input('dept',''),
                        'speciality'=>$request->input('speciality',''),
                        'email'=>$request->input('contact_email',''),
                        'qq'=>$request->input('qq',''),
                        'desc'=>'',
                    );
                    Teacher::createTeacher($data);
                    //注册成功，将openid加入，进行登录
                    if(Redis::exists($key)) {
                        $wxData = json_decode(Redis::get($key) ,true);
                        $this->setWechatData($wxData, $newUser );
                        \Session::forget('qr_id');
                    }
                    $this->authenticateUser($newUser);
                    return redirect('/');
                }else{
                    return back()->withInput()->withErrors([ 'msg' => '创建用户失败',]);
                }

            }elseif ($plat == 3){

                return back()->withInput()->withErrors([ 'msg' => '用户权限还没有开放注册' ]);

                $check = $this->checkSuffix($school_id);
                if( !$check['status'] ) {
                    return back()->withInput()->withErrors([ 'msg' => $check['data'] ]);
                }

                $suffix = School::where('id',$school_id)->first(['email_suffix','host_suffix']);
                $oUser = new User();

                $sno_regix='/^[0-9]{6,}$/';//学号只能纯数字并大于6位
                if($sno =='' || !preg_match($sno_regix,$sno)){
                    return back()->withInput()->withErrors([ 'msg' => '学号不能为空且只能为纯数字并且大于6为',]);
                }

                $email = $sno.'@'.$suffix->email_suffix;

                $stu = Student::where(['sno'=>$sno,'school_id'=>$school_id])->first();
                if(!empty($stu)){
                    return back()->withInput()->withErrors([ 'msg' => '同一所学校的学号不能重复',]);
                }
                $username = $suffix->host_suffix.$sno;//用户名为学校前缀+学号
                $data=array(
                    'name'=> $name,
                    'email'=> $email,
                    'username'=> $username,
//                    'password'=> substr(trim($sno) ,-6),
                    'password'=> $request->input('password'),
                    'sno'=> $sno ,
                    'plat'=> 3,
                    'school_id'=> $school_id
                );
                if($mobile){
                    $data['mobile']=$mobile;
                }
                $newUser=$oUser->createUser($data);
                //未通过验证或创建失败直接报错
                if(!$newUser['status']){
                    return back()->withInput()->withErrors([ 'msg' => $newUser['info'],]);
                }
                $newUser=$newUser['data'];
                if($newUser){
                    $data=array(
                        'school_id'=>$school_id,
                        //'squad_id'=>$request->input('squad_id',0),
                        'sno'=> $sno,
                        'user_id'=>$newUser->id,
                        'name'=> $name,
                        'academy'=>$request->input('academy',0),
                        'dept'=>$request->input('dept',0),
                        'major'=>$request->input('major',0),
                        'year'=>$request->input('year',0),
                        'qq'=>$request->input('qq',''),
                        'phone'=>$request->input('phone',''),
                        'desc'=>'',
                    );
                    $new_student = Student::createStudent($data);
                    if($new_student && $squad_id){
                        $squadStruct = new SquadStruct;
                        $squadStruct->squad_id = $squad_id;
                        $squadStruct->struct_id = $new_student;
                        $squadStruct->type = 1;
                        $squadStruct->save();
                    }
                    //注册成功，将openid加入，进行登录
                    if(Redis::exists($key)) {
                        $wxData = json_decode(Redis::get($key) ,true);
                        $this->setWechatData($wxData, $newUser );
                        \Session::forget('qr_id');
                    }
                    $this->authenticateUser($newUser);
                    return redirect('/');
                }else{
                    return back()->withInput()->withErrors([ 'msg' => '创建用户失败',]);
                }

            }elseif ($plat == 4){
                $email = $request->input('email',0);
                $oUser = new User();
                $data=array(
                    'name'=> $name,
                    'username'=> 'user_' . substr(trim($mobile) ,-6),
                    'email'=> $email,
                    'mobile'=> $mobile,
//                    'password'=> substr(trim($mobile) ,-6),
                    'password'=> $request->input('password'),
                    'plat'=>$plat,
                    'school_id'=> 0
                );
                $newUser = $oUser->createUser($data);
                //未通过验证或创建失败直接报错
                if(!$newUser['status']){
                    return back()->withInput()->withErrors([ 'msg' => $newUser['info'],'plat'=>$plat]);
                }
                $newUser = $newUser['data'];
                if($newUser){
                    ProjectTutor::firstOrCreate(['user_id'=>$newUser->id,'email'=>$email]);

                    //注册成功，将openid加入，进行登录
                    if(Redis::exists($key)) {
                        $wxData = json_decode(Redis::get($key) ,true);
                        $this->setWechatData($wxData, $newUser );
                        \Session::forget('qr_id');
                    }
                    $this->authenticateUser($newUser);
                    return redirect(env('APP_URL').'/member')->send();
                }else{
                    return back()->withInput()->withErrors([ 'msg' => '创建用户失败','plat'=>$plat]);
                }
            }else if($plat == 5){
                $email = $request->input('email',0);

                $oUser = new User();
                $data=array(
                    'name'=> $name,
                    'username'=> 'user_' . substr(trim($mobile) ,-6),
                    'email'=> $email,
                    'mobile'=> $mobile,
//                    'password'=> substr(trim($mobile) ,-6),
                    'password'=> $request->input('password'),
                    'plat'=>$plat,
                    'school_id'=> 0
                );
                $newUser = $oUser->createUser($data);
                //未通过验证或创建失败直接报错
                if(!$newUser['status']){
                    return back()->withInput()->withErrors([ 'msg' => $newUser['info'],'plat'=>$plat]);
                }
                $newUser = $newUser['data'];
                if($newUser){
                    Provider::firstOrCreate(['user_id'=>$newUser->id,'email'=>$email,'name'=>$name]);

                    //注册成功，将openid加入，进行登录
                    if(Redis::exists($key)) {
                        $wxData = json_decode(Redis::get($key) ,true);
                        $this->setWechatData($wxData, $newUser );
                        \Session::forget('qr_id');
                    }
                    $this->authenticateUser($newUser);
                    return redirect(env('APP_URL').'/fwsmember')->send();
                }else{
                    return back()->withInput()->withErrors([ 'msg' => '创建用户失败','plat'=>$plat]);
                }
            }
            else{
                return back()->withInput()->withErrors([ 'msg' => '请选择正确的用户类型','plat'=>$plat]);
            }

    }

    private function checkSuffix($school_id){

        $suffix = School::where('id',$school_id)->first(['email_suffix','host_suffix']);
            if($suffix->email_suffix==''){
                return array('status' => false  , 'data' => '该学校的登录邮箱后缀还未设置');
//                return back()->withInput()->withErrors([ 'msg' => '该学校的登录邮箱后缀还未设置',]);
            }
            if($suffix->host_suffix==''){
                return array('status' => false  , 'data' => '该学校的二级域名前缀还未设置');
//                return back()->withInput()->withInput()->withErrors([ 'msg' => '该学校的二级域名前缀还未设置',]);
            }

        return true;
    }


    private function setWechatData($data,$user){
        if(isset($data['openid'])){
            $obj = new UserOpenid;
            $obj->user_id = $user->id;
            $obj->openid = $data['openid'];
            $obj->save();
        }
        if(isset($data['unionid'])){
            $obj = new UserBind;
            $obj->type = 'weixin';
            $obj->fromId = $data['unionid'];
            $obj->toId = $user->id;
            // $obj->token = '';
            // $obj->refreshToken = $data['openid'];
            $obj->expiredTime = 7200;
            $obj->createdTime = time();
            $obj->save();
        }
    }

    private  function authenticateUser($user){
        \Auth::loginUsingId($user->id);//登录并设置session
    }


}
