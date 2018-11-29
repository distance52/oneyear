<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Services\OAuthClient\OAuthClientFactory;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\Models\UserBind;

class LoginBindController extends Controller
{
    public function __construct(){
        $this->userServiceClass=new UserServiceController();
    }

    /**
     * 登录并设置绑定
     * @param $user
     */
    protected  function authenticateUser($user){
        \Auth::loginUsingId($user->id);//登录并设置session
    }

    /**
     * 获取请求地址并跳转
     * @param $request
     * @return string
     */
    protected function getTargetPath($request,$user=null)
    {
        if ($request->input('goto')) {
            $targetPath = $request->input('goto');
        } else if ($request->getSession()->has('_target_path')) {
            $targetPath = $request->getSession()->get('_target_path');
        }
//        else if($request->headers->has('Referer')) {
//            $targetPath = $request->headers->get('Referer');//laravel的headers中没有referer
//        }
        else{
            if(is_null($user)){
                $targetPath='/';
            }
            else{
                $arrPlat = ['admin','school','teacher','student'];
                if(!str_contains($request->root(), $arrPlat[$user->plat])) {
                    $targetPath=env('APP_PRO').$arrPlat[$user->plat].'.'.env('APP_SITE');
                }
            }
        }
        return $targetPath;
    }

    protected function getBlacklist()
    {
        return array('/logout');
    }

    /**
     * 跳转至授权地址
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request,$type='')
    {

        if ($request->has('_target_path')) {
            $targetPath = $request->input('_target_path');
            if (!in_array($targetPath, $this->getBlacklist())) {
                $request->getSession()->put('_target_path', $targetPath);
            }
        }
        $client      = $this->createOAuthClient($type);
        $callbackUrl = route('login_bind_callback', ['type' => $type]);
        $url = $client->getAuthorizeUrl($callbackUrl);
        return redirect($url);
    }



    /**
     * 授权回调地址
     * @param Request $request
     * @param $type
     * @return mixed
     * 流程：
     *                  获取TOKEN
     *                  ↓       ↘
     *         已经登录             未登录
     *            ↓                   ↓
     *        切换登录          检查是否存在绑定记录
     *                        ↓           ↓           ↘
     *                存在一条记录       存在多条记录    不存在记录
     *                ↓     ↘               ↓              ↘
     *          学生用户     其他用户    跳转用户选择用户页    跳转至绑定界面
     *              ↓           ↘     
     *          登陆成功     选择用户登录                   
     *
     */
    public function callback(Request $request,$type='')
    {
        $code   =   $request->input('code');
        $callbackUrl = route('login_bind_callback', ['type' => $type]);
        $token       = $this->createOAuthClient($type)->getAccessToken($code, $callbackUrl);
        if (empty($token) || empty($token['userId'])) {
            return redirect("/error2")->withInput()->withErrors(['msg' => '授权失败，请关注公众号或尝试重新登录平台']);
        }

        if (\Auth::check()) {
            $request->getSession()->put('oauth_token', $token);
            $bindurl = route('login_bind_change', ['type' => $type]);
            return redirect($bindurl);
        }else{
            $bind        = $this->userServiceClass->getUserBindByTypeAndFromIdV2($type, $token['userId']);//查询绑定表是否存在绑定记录
            $request->getSession()->put('oauth_token', $token);
            $count = count($bind);
            if ($count == 1){
                //存在绑定记录
                $bind = isset($bind[0]) ? $bind[0]->toId : 0;
                $user = User::whereid($bind)->first();
                if (empty($user)) {
                    return back()->withInput()->withErrors(['msg' => '绑定的用户不存在，请重新绑定。',]);
                }
                $targetPath=$this->getTargetPath($request,$user);
                if($type=='weixinmob'){
                    //设置微信登录token
                    Session::put('user_id',$user->id);
                    Session::put('wechat_id',$token['openid']);
                    $this->userServiceClass->bindUidOpenid($user->id, $token['openid']);//绑定表便于推送，因为通过PC端oauth登录的token经测试无法发送消息
                }
                if($type=='weixinmob' && $user->plat != 3){
                    $data =[] ;
                    $data[$user->id]['name'] = $user->name;
                    $data[$user->id]['email'] = $user->email;
                    $data[$user->id]['avatar'] = getAvatar($user->avatar);
                    $data[$user->id]['plat'] = $user->plat;
                    Session::put('login_select',$data);
                    return view('default.login.select', compact('data'));
                }
                $this->authenticateUser($user);
                return redirect($targetPath);
                
//            if(config('ucenter.ucenter_enabled')){
//            }
                //如下需要登录并设置session
                /*
                $this->authenticateUser($user);
                //第三方登录，比方说ucenter登录
                if ($this->getAuthService()->hasPartnerAuth()) {
                    return $this->redirect($this->generateUrl('partner_login', array('goto' => $this->getTargetPath($request))));
                } else {
                    $goto = $this->getTargetPath($request);
                    return $this->redirect($goto);
                }
                */
            }elseif ($count > 1){
                $data = [];
                $bind->each(function ($bind ,$i) use(&$data){
                    $oUser = User::whereid($bind->toId)->first();
                    if ($oUser){
//                    $data[$i]['id'] = $oUser->id;
                        $data[$oUser->id]['name'] = $oUser->name;
                        $data[$oUser->id]['email'] = $oUser->email;
                        $data[$oUser->id]['avatar'] = getAvatar($oUser->avatar);
                        $data[$oUser->id]['plat'] = $oUser->plat;

                    }
                });
                Session::put('login_select',$data);
                return view('default.login.select', compact('data'));
            }else{
                //本系统都是绑定已存在的用户，因此都提供给绑定旧账户功能，不绑定并注册新用户
                if($type=='weixinmob'){
                    $bindurl = route('login_bind_weixin');
                }
                else{
                    $bindurl = route('login_bind_change', ['type' => $type]);
                }
                return redirect($bindurl);
            }
        }

    }

    /**
     * 微信手机端绑定已有帐号视图页面
     * @param Request $request
     * @return mixed
     */
    public function weixinIndex(Request $request)
    {
        $token = $request->getSession()->get('oauth_token');
        if (empty($token)) {
            return redirect('error')->withErrors(['msg'=>'页面已过期，请重新登录！','href'=>'/login']);
            //return redirect()->back()->withInput()->withErrors('页面已过期，请重新登录！');
        }
		if (view()->exists(session('mode').'.login.bind-weixin')){
			return View(session('mode').'.login.bind-weixin');
		}else{
			return view('default.login.bind-weixin');
		}
    }

    /**
     * 绑定新帐号除微信端以外的视图页面
     * @param Request $request
     * @param $type
     * @return mixed
     */
    public function choose(Request $request, $type)
    {
        $token       = $request->getSession()->get('oauth_token');
        $client      = $this->createOAuthClient($type);
        $clientMetas = OAuthClientFactory::clients();
        $clientMeta  = $clientMetas[$type];

        try {
            $oauthUser         = $client->getUserInfo($token);
            $oauthUser['name'] = preg_replace('/[^\x{4e00}-\x{9fa5}a-zA-z0-9_.]+/u', '', $oauthUser['name']);
            $oauthUser['name'] = str_replace(array('-'), array('_'), $oauthUser['name']);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if ($message == 'unaudited') {
                $message = '抱歉！暂时无法通过第三方帐号登录。原因：'.$clientMeta['name'].'登录连接的审核还未通过。';
            } else {
                $message = '抱歉！暂时无法通过第三方帐号登录。原因：'.$message;
            }
            return back()->withInput()->withErrors(['msg' => $message,]);
        }

        $name = $this->mateName($type);
        return view('default/login/bind-choose', [
            'oauthUser' => $oauthUser,
            'type' => $type,
            'name'           => $name,
        ]);
    }

    /**
     * 进行绑定已有帐号页面
     * @param Request $request
     * @param $type
     * @return mixed
     */
    public function changeToExist(Request $request, $type)
    {

        $token = $request->getSession()->get('oauth_token');
        if (empty($token)) {
            return redirect('error')->withErrors(['msg'=>'页面已过期，请重新登录！','href'=>'/login']);
            //return redirect()->back()->withInput()->withErrors('页面已过期，请重新登录！');
        }
        $client    = $this->createOAuthClient($type);
        $oauthUser = $client->getUserInfo($token);
        $name      = $this->mateExistName($type);
        //登录状态
        if (\Auth::check()) {
            $user = \Auth::user();
            $oBind =UserBind::where(['type'=>'weixin','toId'=>$user->id ])->value('id');
            //判断登录账户有没有绑定过，没有绑定过为授权绑定微信号，绑定过为添加新的账户绑定账号
            if ($oBind){
                return view('default.login.bind-choose-exist', compact('oauthUser','type','name'));
            }else{
                return view('default.login.bind', compact('oauthUser','type','name','user'));
            }
        }else{
            return view('default.login.bind-choose-exist', compact('oauthUser','type','name'));
        }

    }

    /**
     * 除微信手机端外，处理绑定其他已有帐号
     * @param Request $request
     * @param $type
     * @return mixed
     */
    public function exist(Request $request, $type)
    {
        $token     = $request->getSession()->get('oauth_token');
        $client    = $this->createOAuthClient($type);
        $oauthUser = $client->getUserInfo($token);
        $data      = $request->all();
        $count = $this->userServiceClass->getUserBindCount($type, $oauthUser['id']);
        $message   = '';
        if($count >=5){
            $message="操作失败，该第三方账号在本平台绑定账号数量已经超过5个。";
            return back()->withInput()->withErrors([ "custom-msg"=> $message ]);
        }
        if (!isset($data['type'])) {
            if (\Auth::check()){
                $user = \Auth::user();
                if ($this->userServiceClass->getUserBindByTypeAndUserId($type, $user->id)) {
                    $message="帐号已经绑定了平台的帐号，如需重新绑定，请先到账户设置中取消绑定！";
                    return back()->withInput()->withErrors([ "custom-msg"=> $message ]);
                }
                $this->userServiceClass->bindUser($type, $oauthUser['id'], $user->id, $token);
//                if($user->plat == 3){
//                    //只有学生才能收到微信推送
//                    $this->userServiceClass->bindUidOpenid($user->id, $token['openid']);//绑定表便于推送，因为通过PC端oauth登录的token经测试无法发送消息
//                }
                $this->authenticateUser($user);
                $targetPath=$this->getTargetPath($request,$user);
                return redirect($targetPath);//跳转到首页
            }
        }else{
            $v = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);
            if ($v->fails()){
                return back()->withInput()->withErrors([$v->errors()]);
            }
            $user=User::whereemail($data['email'])->first();
            if (empty($user)) {
                $message="用户不存在";
            }
            elseif (!password_verify($data['password'],$user->password)) {
                $message="密码不正确，请重试！";
            }
            elseif ($this->userServiceClass->getUserBindByTypeAndUserId($type, $user->id)) {
                $message="帐号已经绑定了平台的帐号，如需重新绑定，请先到账户设置中取消绑定！";
            }elseif ($user->plat == 3){
                if ($this->userServiceClass->getStudentBindCount($type, $oauthUser['id'])){
                    $message="操作失败，每个第三方账号只能绑定一个平台的学生身份账号";
                }
            }
            if($message){
                return back()->withInput()->withErrors([ "custom-msg"=> $message ]);
            }
            //执行绑定
            $this->userServiceClass->bindUser($type, $oauthUser['id'], $user->id, $token);
//            if($user->plat == 3){
//                //只有学生才能收到微信推送
//                $this->userServiceClass->bindUidOpenid($user->id, $token['openid']);//绑定表便于推送，因为通过PC端oauth登录的token经测试无法发送消息
//            }
            $this->authenticateUser($user);
            $targetPath=$this->getTargetPath($request,$user);
            return redirect($targetPath);//跳转到首页
        }
        
    }

    /**
     * 微信端处理绑定已有帐号
     * @param Request $request
     * @return mixed
     */
    public function existBind(Request $request)
    {
        $token     = $request->getSession()->get('oauth_token');
        if(empty($token)){
            return back()->withInput()->withErrors([ "custom-msg"=> '未获取到token信息' ]);
        }
        if(!isset($token['openid'])){
            return back()->withInput()->withErrors([ "custom-msg"=> '未获取到openid' ]);
        }
        $type      = 'weixinmob';
        $client    = $this->createOAuthClient($type);
        $oauthUser = $client->getUserInfo($token);
        $data      = $request->all();
        $message = '';
        //获取登录的帐号并解除绑定
        /*
        $olduser   = $this->getCurrentUser();
        $userBinds = $this->userServiceClass->unBindUserByTypeAndToId($type, $olduser->id);
         **/
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($v->fails()){
            return back()->withInput()->withErrors([$v->errors()]);
        }
        $user=User::whereemail($data['email'])->first();
        if (empty($user)) {
            $message="用户不存在";
        }
        elseif (!password_verify($data['password'],$user->password)) {
            $message="密码不正确，请重试！";
        }elseif ($this->userServiceClass->getUserBindByTypeAndUserId($type, $user->id)) {
            $message="帐号已经绑定了该第三方网站的其他帐号，如需重新绑定，请先到账户设置中取消绑定！";
        } else {
            //执行绑定
            $this->userServiceClass->bindUser($type, $oauthUser['id'], $user->id, $token);//绑定帐号
            if($user->plat == 3){
                //只有学生才能收到微信推送
                $this->userServiceClass->bindUidOpenid($user->id, $token['openid']);//绑定表便于推送，因为通过PC端oauth登录的token经测试无法发送消息
            }
            //设置微信登录token
            Session::put('user_id',$user->id);
            Session::put('wechat_id',$token['openid']);
            //$this->authenticateUser($user);
        }
        if($message){
            //return response()->json($msg)->setStatusCode(422);
            return back()->withInput()->withErrors([ "custom-msg"=> $message ]);
        }
        else{
            $this->authenticateUser($user);
            $targetPath=$this->getTargetPath($request,$user);
            return redirect($targetPath);//跳转到首页
        }
    }

    /**
     * 处理用户选择需要登录的账号
     * @param $user
     * @return url
     */
    public function select(Request $request){
        $data = [];
        $data = Session::get('login_select');
        $id = $request->user;
        if (!array_key_exists($id, $data)){
            Session::forget('login_select');
            return redirect("/");//跳转到首页
        }
        $arrPlat = ['admin','school','teacher','student'];
        if(!str_contains($request->root(), $arrPlat[$data[$id]['plat']])) {
            $targetPath=env('APP_PRO').$arrPlat[$data[$id]['plat']].'.'.env('APP_SITE');
        }
//        $targetPath=$this->getTargetPath($request,$user);
        \Auth::loginUsingId($id);//登录并设置session
        Session::forget('login_select');
        return redirect($targetPath);
    }

    /**
     * 判断绑定参数是否开启
     * @param $type
     * @return mixed
     */
    protected function createOAuthClient($type)
    {
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

    /**
     * 绑定新帐号处理页面
     * @param Request $request
     * @param $type
     * @return mixed
     */
    public function newSet(Request $request, $type){
        return false;
    }

    protected function mateName($type)
    {
        switch ($type) {
            case 'weixinweb':
                return '微信创建新帐号';
                break;
            case 'weixinmob':
                return '微信创建新帐号';
                break;
            case 'weibo':
                return '微博创建新帐号';
                break;
            case 'qq':
                return 'QQ创建新帐号';
                break;
            case 'renren':
                return '人人创建新帐号';
                break;
            default:
                return '';
        }
    }

    protected function mateExistName($type)
    {
        switch ($type) {
            case 'weixinweb':
                return '微信绑定已有帐号';
                break;
            case 'weixinmob':
                return '微信绑定已有帐号';
                break;
            case 'weibo':
                return '微博绑定已有帐号';
                break;
            case 'qq':
                return 'QQ绑定已有帐号';
                break;
            case 'renren':
                return '人人绑定已有帐号';
                break;
            default:
                return '';
        }
    }
}
