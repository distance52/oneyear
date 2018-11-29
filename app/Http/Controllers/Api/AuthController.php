<?php

namespace App\Http\Controllers\Api;

use App\Models\School;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis as Redis;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserBind;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Services\OAuthClient\OAuthClientFactory;

class AuthController extends Controller {

    public function getToken(Request $request) {
    }

    public function auth(Request $request){
        $code   =   $request->input('code');
        $client = $this->createOAuthClient('program');
        $apiData = $client->getUserInfo($code);
        $data = json_decode($apiData, true);
        if(!isset($data['errcode'])){
            //暂时遗留一个问题就是openid，每个应用不一致，然而可以获取到unionid，但是平台学生都是使用openid，没有使用unionid，是学生添加到unionid（但是学生不能登录web平台），还是为openid表加入新的字段而解决问题呢？
            return response()->json(array('state'=>true , 'msg' => 'ok' , 'data'=>''));
        }else{
            return response()->json(array('state'=>false , 'msg' => 'error' , 'data'=>''));
        }
    }

    public function search(Request $request){
        $name   =   $request->input('name');
        $page   =   $request->input('page',1);
        if(empty($name)){
            return response()->json(array('state'=>false , 'msg' => 'no data' , 'data'=>null));
        }
        $pagesize = 10;
        $oObjs = School::where('name', 'like', '%'.trim($name).'%');
//        $totalRows = $oObjs->count();
//        $pages = ceil($totalRows/$pagesize);
//        $offset= ($page-1)*$pagesize;
//        $oObjs->orderBy('id','desc')->skip(1)->take(2)->get();
        $oObjs->offset(1)->limit(5)->get();
        $data = [];
        $oObjs->each(function ($oSchool,$i)use(&$data){
            $data[$i]['name'] = $oSchool->name;
            $data[$i]['short_name'] = $oSchool->short_name;
            $data[$i]['email_suffix'] = $oSchool->email_suffix;
            $data[$i]['logo'] = $oSchool->logo ? getAvatar($oSchool->logo) : 'https://sc.cnczxy.com/images/default-school.png';
        });
        if($data){
            return response()->json(array('state'=>true , 'msg' => 'ok' , 'data'=>$data));
        }else{
            return response()->json(array('state'=>false , 'msg' => 'no data' , 'data'=>null));
        }

    }

    public function getSchoolAddress(Request $request){
        $data = $request->only("lng","lat");
        foreach ($data as $v){
            if (empty($v)){
                return response()->json(array('state'=>false , 'msg' => 'error' , 'data'=>null));
            }
        }

        $range = 0.1;
        $lng = $data['lng'];
        $lat = $data['lat'];
        $ret = [];
        $results = \DB::select("select `name`,`short_name`,`email_suffix`,`logo` from schools where latitude>?-? and latitude<?+? and longitude>?-? and longitude <?+? order by abs(longitude -?)+abs(latitude -?) limit 10", [$lat,$range,$lat,$range,$lng,$range,$lng,$range,$lng,$lat]);
        foreach ($results as $k => $v){
            $ret[$k]['name'] = $v->name;
            $ret[$k]['short_name'] = $v->short_name;
            $ret[$k]['email_suffix'] = $v->email_suffix;
            $ret[$k]['logo'] = $v->logo ? getAvatar($v->logo) : 'https://sc.cnczxy.com/images/default-school.png';
        }
        return response()->json(array('state'=>true , 'msg' => 'ok' , 'data'=>$ret));

    }

    public function login(Request $request){

        $data      = $request->all();
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($v->fails()){
            return response()->json(array('state'=>false , 'msg' => $v->errors() , 'data'=>null));
        }
        $user = User::whereEmail($data['email'])->first();
        $message = '';
        if (empty($user)) {
            $message="用户不存在";
        } elseif (!password_verify($data['password'],$user->password)) {
            $message="密码不正确，请重试！";
        } else {
            Session::put('user_id',$user->id);
        }
        if($message){
            return response()->json(array('state'=>false , 'msg' => $message , 'data'=>null));
        }
        Session::put('source','mini-program');
        return response()->json(array('state'=>true , 'msg' => 'ok' , 'data'=>null));
    }

    public function message(Request $request){
        $count = UserBind::where(['type'=>'weixin','fromId'=>'o9qS8v88888888888888888888'])->whereHas('user',function ($query){
            $query->where('plat',3);
        })->count();
        dd($count);
//        $request->session()->flush();
//        response('Hello World', 200)
//            ->header('xxxxxx', 'text/plain');
//        dd($request->header());
        \Auth::logout();
        \Auth::loginUsingId(2212);
        return redirect(env('APP_PRO').'student.'.env('APP_SITE'));

    }


    /**
     * 登录并设置绑定
     * @param $user
     */
    protected  function authenticateUser($user){
        \Auth::loginUsingId($user->id);//登录并设置session
    }

    protected function createOAuthClient($type){
        if($type==''){
            throw new \RuntimeException('类型未传递');
        }
        //$settings = response()->json(config('login_bind'));
        $settings = config('login_bind');
        if (empty($settings)) {
            throw new \RuntimeException('第三方登录系统参数尚未配置，请先配置。');
        }

        if (empty($settings) || !isset($settings[$type.'_enabled']) || empty($settings[$type.'_key']) || empty($settings[$type.'_secret'])) {
            throw new \RuntimeException("第三方登录({$type})系统参数尚未配置，请先配置。");
        }

        if (!$settings[$type.'_enabled']) {
            throw new \RuntimeException("第三方登录({$type})未开启");
        }

        $config = array('key' => $settings[$type.'_key'], 'secret' => $settings[$type.'_secret']);
        $client = OAuthClientFactory::create($type, $config);

        return $client;
    }

    public function postRequest($url, $params){
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_URL, $url );
        // curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE );

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

}