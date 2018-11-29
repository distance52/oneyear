<?php

namespace App\Http\Controllers\Api;

use App\Models\StudentPoint;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\User;
class SmsController extends Controller
{
    private $user_id=0;
    public function __construct(){
        $oUser = \Auth::user();
        if($oUser){
            $this->user_id=$oUser->id;
        }
    }

    public function sendCode(Request $request){
        /*
         *
         本来准备用session设置某个IP每天能获取验证码条数的，失败了
        $ip_day=$request->getClientIp().date('Y-m-d');
        $ip_day=md5($ip_day);
        $ip_day='sesskey';
        $ip_time=0;
        //var_dump($request->session()->all());

        if(empty($request->session()->get($ip_day))){
            echo '124';
            $request->session()->put($ip_day,1);
            var_dump($request->session()->all());
        }
        else{
            $ip_time=$request->session()->get($ip_day);
            $request_time=intval($ip_time)+1;
            $request->session()->put($ip_day,$request_time);
        }
        if($ip_time>100){
            $msg = [
                "custom-msg"=> ["IP请求验证码请求过于频繁"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        */
        $mobile=$request->input('mobile','');
        $code=mt_rand(100000,999999);
        //校验校验码
        $key='user_'.$this->user_id.'_mobile'.$mobile.'_code';
        $s_code=$request->session()->get($key);
        if(!empty($s_code) && (time()-$s_code['time'])<60){
            $msg = [
                "custom-msg"=> ["验证码请求过于频繁"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $request->session()->put($key, array('code'=>$code,'time'=>time()));
        //$oUser=User::where('id',$this->user_id)->first();
        $data=array(
            'code'=>$code,
            'product'=>'用户'
        );
        $smsclass=new \App\Http\Controllers\NoticeSend\SmsNoticeController();
        $return=$smsclass->send($mobile,'SMS_8130859',$data);
        $return=json_decode($return,true);
        if (!empty($return) && $return['success']) {
            return response()->json(null);
        }
        else{
            $msg = [
                "custom-msg"=> ["验证码发送失败"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function bindMobile(Request $request){
        $mobile=$request->input("mobile");
        $code=$request->input("code");
        if($mobile==''){
            $msg = [
                "custom-msg"=> ["手机号不能为空"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        //校验校验码
        $key='user_'.$this->user_id.'_mobile'.$mobile.'_code';
        $s_code=$request->getSession()->get($key);
        if(empty($s_code)|| !is_array($s_code)){
            $msg = [
                "custom-msg"=> ["验证码还未发送"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if($code!=$s_code['code']){
            $msg = [
                "custom-msg"=> ["验证码错误"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $user=User::where('mobile',$mobile)->first();
        if(!empty($user) && $user->id!=$this->user_id){
            $msg = [
                "custom-msg"=> ["该手机号已被其他用户绑定"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $oUser=User::where('id',$this->user_id)->first();
        $oUser->mobile=$mobile;
        $oUser->save();
        $model=new StudentPoint();
        $model->setPoints('bind_phone',$this->user_id);//绑定手机增加积分
        return response()->json(null);
    }
}
