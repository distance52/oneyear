<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Squad;
use App\Models\UserBind;
use App\Models\Notify;
use App\Models\Specialty;
use App\Models\NotifyTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use UcClient;
use Ucenter;
use Illuminate\Support\Facades\Bus;
use AliyunOSS;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\StudentGroupScore;


use App\Models\Student;
use App\Models\GroupStudent;
use App\Models\StudentFinalScore;
use App\Models\StudentPoint;
use App\Models\Score;
use App\Models\Exampaper;
class TestController extends Controller
{
    protected $wechatclass;
    public function __construct()
    {
       // $this->middleware('auth');
    }

    public function search($arr,$searchval){
        $top=count($arr)-1;
        $low=0;
        while($low<=$top){
            $mid=floor(($top+$low)/2);//取整进一
            if($arr[$mid]==$searchval){
                return $mid;
            }
            elseif($arr[$mid]>$searchval){
                //如果说中间的值比搜索的值大，证明我们要到前半部分去搜索，因此top为中间的-1
                $top=$mid-1;
            }
            else{
                //如果说中间的值比搜索的值小，证明我们要到后半部分去搜索，因此low为中间的+1
                $low=$mid+1;
            }
        }
        return -1;//未查找到
    }

    private function _staticMyScore($group_id,$student_info){
        //统计我的项目组/专题组成绩
        $count=DB::select('select exampaper_id,count(*) as num from student_group_scores where group_id='.$group_id.' and to_user_id='.$student_info->user_id.' group by exampaper_id');//每个评分项目给我打分【分配积分】人的人数
        $score=DB::select('select exampaper_id,sum(score) as score from student_group_scores where group_id='.$group_id.' and to_user_id='.$student_info->user_id.' group by exampaper_id');//每个评分项目给我打分【分配积分】和值
        $totalScore=0;
        $totalCount=count($count);//整个打分的次数
        $student_score=0;
        if(count($count)>0){
            foreach($count as $key=>$val){
                //这次评分的
                $this_score=$score[$key]->score;
                //echo $this_score.'<br>';
                $per_score=($val->num==0)?0:round($this_score/$val->num);
                $totalScore=$totalScore+$per_score;
            }
            $student_score=($totalCount==0)?0:round($totalScore/$totalCount);
        }
        return $student_score;
    }

    /**
     * curdtest
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function test(Request $request){
//        $ss = User::where('plat',2)->orderBy('id','desc')->take(5)->with(array('school'=>function($query){
//            $query->select('id','name');
//        }))->get(['id','name']);
        $ss = User::where('plat',2)->orderBy('id','desc')->take(5)->with('school:id,name')->get();
        dd($ss);
//        $ss = User::where('plat',2)->orderBy('id','desc')->take(5)->has('school',function ($q){
//            $q->select('id','name');
//        })->get(['*']);
        return view('default.test');
        $school_id = \Session::get('school_id');
        dd($school_id);
        $result = Ucenter::uc_get_user('long');
        var_dump($result);
        //var_dump(Ucenter::uc_user_register('testxiangli126','123456','xiangli5@host.edu.cn'));
        //var_dump(Ucenter::uc_user_synlogin(64574));
        //var_dump(Ucenter::uc_user_synlogout(64574));
        var_dump(Ucenter::uc_user_checkname('testxiangli1261'));
        var_dump(Ucenter::uc_user_checkemail('xiangli11110@host.edu.cn'));
        //var_dump(Ucenter::uc_user_register('testxiangli12611','123456','xiangli11110@host.edu.cn'));
        exit;
        Session::set('_target_path',null);
       var_dump($request->getSession()->all());
        $request->session()->forget('_target_path');
        exit;
        //$users=array('allAdmin','squad|2|all','squad|5|all','2','1','4');
        $users=array('25');
        $squad_regix = '/^squad\|(\d+)\|all$/i';
        $user_id=2;
        $school_id=User::where('id',$user_id)->value('school_id');
        $user_ids=array();
        $squad_ids=array();
        $title='站内通知测试测试';
        $content='貌似测试邮件会被打回去啊！是怎么回事呢';
        $send_time=time();
        $send_method=3;
        if($send_method==3){
            //$template_content=NotifyTemplate::where('template','email')->value('example');
            //$content=str_replace('{内容}',$content,$template_content);
        }
        else{
            $content=$request->input('content','');
        }
        $oNotify = Notify::create([
            'title' => $title,
            'send_time' => $send_time,
            'template_id' => 0,
            'send_method' => $send_method,
            'content' => $content,
            'url' => '',
            'user_id' => $user_id
        ]);
        $oNotify->id = Notify::where('user_id', $user_id)->orderBy('id','desc')->take(1)->value('id');
        foreach($users as $val){
            if($val=='allAdmin'){
                $user=User::where('plat',0)->pluck('id')->toArray();//所有系统管理员
                empty($user) || $user_ids=array_merge($user_ids,$user);
            }
            elseif($val=='AllSchoolAdmin'){
                $user=User::where('plat',1)->pluck('id')->toArray();//所有学校管理员
                empty($user) || $user_ids=array_merge($user_ids,$user);
            }
            elseif($val=='AllTeacher'){
                $user=User::where('plat',2)->pluck('id')->toArray();//系统所有老师
                empty($user) || $user_ids=array_merge($user_ids,$user);
            }
            elseif($val=='AllSchoolTeacher'){
                $user=User::where(['plat'=>2,'school_id'=>$school_id])->pluck('id')->toArray();//所有老师
                empty($user) || $user_ids=array_merge($user_ids,$user);
            }
            elseif(preg_match($squad_regix,$val,$matches)){
                $squad_id=Squad::where(['id'=>$matches[1]])->first(['id']);//所有老师
                empty($squad_id) || array_push($squad_ids,$squad_id->id);
            }
            elseif(preg_match('/^(\d+)$/',$val,$matches)){
                array_push($user_ids,$matches[1]);
            }
        }
        if(!empty($user_ids)) {
            $oNotify->users()->sync($user_ids);
        }
        if(!empty($squad_ids)) {
            $oNotify->squads()->sync($squad_ids);
        }
        dd($user_ids);
        $oSpecialty = new Specialty();
        //dd(Specialty::where('id', 3)->value('path'));
        //dd(Specialty::where('id',3)->first(['id','pid','path']));
        //dd(Specialty::where('pid',0)->get(['id','pid','path']));
        //dd(Specialty::where('pid',0)->pluck('id'));
        dd(Specialty::where('pid',0)->take(1)->get());
        //dd(DB::update('update users set name="neo2" where id=1200'));
        dd($oSpecialty->getPid(8));
        dd(User::orderBy('id','desc')->take(1)->value('id'));
        exit;
        $result = Ucenter::uc_get_user('long');
        var_dump($result);
        var_dump(Ucenter::uc_user_register('testxiangli126','123456','xiangli5@host.edu.cn'));
        //var_dump(Ucenter::uc_user_synlogin(64574));
        //var_dump(Ucenter::uc_user_synlogout(64574));
        exit;
        var_dump(Ucenter::uc_user_checkemail('xiangli3@host.edu.cn'));
        // 设置Bucket
        AliyunOSS::setBucket('ledao');
        // 获取资源请求URL
        $abc=AliyunOSS::getUrl('1346','2016-04-10 10:00:00','ledao');
        dd($abc);
        $oNotify=Notify::whereid(3)->first();
        $oUsers = $oNotify->receiveUsers();
        $oUsers->map(function($oUser) use ($oNotify) {
            \Mail::queue('emails.tpl', ['msg'=>$oNotify->content], function ($message) use ($oUser,$oNotify) {
                $message->to($oUser->email, $name = $oUser->name);
                $message->subject($oNotify->title);
            });
        });
        exit;
        /*
        Notify::find(1)->users()->each(function($oSchool) use (&$ids) {
            var_dump($oSchool->id);
        });
        //$oNotify=Notify::whereid(4)->first();
        //$oUsers=$oNotify->users()->pluck('users.id')->toArray();//list方法只取数据表中的某列,，获取多态中该通知中发送给的所有用户的用户Id
        */

        //$oUsers=$oNotify->squads()->pluck('squads.id')->toArray();//list方法只取数据表中的某列，获取多态中该通知中发送给所有班级的id
        //dd($oUsers);
        //dd($oNotify->receiveUsersID());
        //发送微信
        $oNotify=Notify::whereid(1)->first();
        $openids=array();
        $oNotify->receiveUsers()->each(function($oUser) use(&$openids){
            $obj=$oUser;
            //var_dump($obj->email);l
            //var_dump($obj->openid->openid);
            $openids = array_merge($openids, $obj->openid()->pluck('openid')->toArray());
        });
        var_dump($openids);
        //发送短信
        $oNotify=Notify::whereid(2)->first();
        $phones=array();
        $oNotify->receiveUsers()->each(function($oUser) use(&$phones){
            $obj=$oUser;
            //var_dump($obj->email);l
            //var_dump($obj->openid->openid);
            $phones = array_merge($phones, $obj->student()->pluck('phone')->toArray());
        });
        var_dump($phones);
        exit;
        //方法在vendor/vergil-lai/uc-client/src/client.php
        //使用Facade
        //$abc=UcClient::getUser('long');
        //var_dump(UcClient::userSyncLogin(1));
        //$abc=UcClient::userRegister('testxiangli123','123456','xiangli@host.edu.cn');
        $result = Ucenter::uc_get_user('long');
        var_dump($result);
        //var_dump(Ucenter::uc_user_register('testxiangli125','123456','xiangli3@host.edu.cn'));
        var_dump(Ucenter::uc_user_synlogin(64574));
        var_dump(Ucenter::uc_user_synlogout(64574));
        var_dump(Ucenter::uc_user_checkemail('xiangli3@host.edu.cn'));
        exit;
        //dd($abc);
        //查询
        //$userModel = new User();
        $info1=User::where('id', 1)->get();
        $info2=User::find(1);
        $info3=User::whereid(1)->first();
        $info4=User::where('id', 1)->first();
        $info5 = User::where(['id'=>1,'email'=>'admin@163.com'])->first();
        $info6 = User::whereid(1)->first();
        $info7 = User::where(['id'=>1])->first();
        $info8 = User::where(['id'=>1])->first()->toArray();//注意toArray对象为null会报错，因此一般不这样用
        $info9 = UserBind::where(['fromId'=>1])->get()->toArray();//注意toArray对象为null会报错，因此一般不这样用
        var_dump($info9);
        //var_dump($info6);
       // var_dump($info7);
        //var_dump($info1);
        //var_dump($info2->email);//string(13) "admin@163.com"
        //var_dump($info3->email);//string(13) "admin@163.com"
        //var_dump($info3);//string(13) "admin@163.com"
        //var_dump($info4->email);//string(13) "admin@163.com"
        //var_dump($info5->email);//string(13) "admin@163.com"
        /*
        foreach ($info1 as $user)
        {
            var_dump($user->email);
        }
        */
        //新增
        $ret=UserBind::insert(array(
            'type'        => 1,
            'fromId'      => 1,
            'toId'        => 2,
            'token'       => empty($token['token']) ? '' : $token['token'],
            'createdTime' => time(),
            'expiredTime' => empty($token['expiredTime']) ? 0 : $token['expiredTime']
        ));
        //var_dump($ret);//返回布尔型真假
        //$ret=UserBind::whereid(1)->delete();//返回int 1成功 0失败
        //var_dump($ret);
    }

    /*
     * $openid 公共号中用户的openid  如: o643RvjJLXHL5eDY6oFvEM1ezULQ
     * $template 模板id 如: 2ogKpQKADvxa9Np14E58TEUNABHDHyx0AAJ9RjB5Ehc
     * $data 数据结构
     * $url 连接地址
     * $id 公共号编号 默认1
     * */
    public function testpage(\App\Http\Controllers\NoticeSend\WechatNoticeController $wechatclass){
        $wechatclass->getToken();
        exit;
        /**帐号密码校验测试**/
        /*
        $userlist=User::get();
        foreach($userlist as $val){
            echo $val->email;
            var_dump(password_verify('123456',$val->password));
            var_dump(password_verify('111111',$val->password));
            echo '<br>';
        }
        //var_dump($userlist);
        exit;
        */
        /*
        $arr=array('openid'=>123);
        $abc=$arr['unionid'];
        var_dump($arr);
        var_dump($abc);
        exit;
        */
        /*
        //$user=User::whereemail($data['email'])->first();
        if (empty($user)) {
            $message="用户不存在";
        }
        elseif (!password_verify($data['password'],$user->password)) {
            $message="密码不正确，请重试！";
        }
/*
        return view('login/test', [
            'oauthUser' => '134',
            'type' => 'weixinweb',
            'name' => 'xiaoshu',
        ]);
        exit;
*/
        /*
        $bac2=$wechatclass->wx_get_token();
        dd($bac2);
        */
        $data = array(
            'first'=>array(
                'value'=>'作业提醒',
                'color'=>'#173177'
            ),
            'name'=>array(
                'value'=>'小丸子',
                'color'=>'#173177'
            ),
            'subject'=>array(
                'value'=>'语文',
                'color'=>'#173177'
            ),
            'content'=>array(
                'value'=>'预习第5课的内容，学习书写本课生字',
                'color'=>'#173177'
            ),
            'remark'=>array(
                'value'=>'若非您本人操作，请立即联系在线客服。',
                'color'=>'#173177'
            )
        );
        //$ret = $wechatclass->send('omUKbtw8lt_lhHXht3LSps-ZltJQ','B6t2pY2-O089r4kLAGq5pQSNnYlk1OA_VtCWaq6Dp6w',$data,'http://baidu.com');
        //$ret = $wechatclass->send('obSMMv-GJvbwq401S8kL9khKPco0','vbmxD9V73kSu9UXdk7VrM3Z9slqnwTTAgEiqIyX7qFQ',$data,'http://baidu.com');
        $ret = $wechatclass->send('oL5R9wZdm-uio8lrCgCQGkov76hU','vbmxD9V73kSu9UXdk7VrM3Z9slqnwTTAgEiqIyX7qFQ',$data,'http://baidu.com');
        dd($ret);
        /*
        $user=User::whereemail('ledao@163.com')->first();
        if (empty($user)) {
            $message="用户不存在";
        }
        var_dump($user->password);
        var_dump(password_verify('ledao',$user->password));
        exit;
        $a= password_hash('123456',PASSWORD_DEFAULT);
        $b= password_hash('123456',PASSWORD_DEFAULT);
        echo $a;
        echo '<br>';
        echo $b;
        var_dump(password_verify('123456',$a));
        var_dump(password_verify('123456',$b));
*/
    }

    public function testsend(\App\Http\Controllers\NoticeSend\SmsNoticeController $send){
        $template='SMS_5905289';
        $data = array(
            'code'=>12544,
            'product'=>'双创教育222',
            'item'=>'4545',
        );
        $setting=array(
            'key'=>23325702,
            'secret'=>'57ae2296384276d09ee1a5709baa1629'
        );
        $send->setConfig($setting);
        $ret=$send->send('13080603915,15623071791',$template,$data);
        var_dump($ret);
        /*
        preg_match_all('/href=\"(.*?)\"/',$html,$matchs);
       // print_r($matchs);
        $array=array();
        foreach($matchs[1] as $val){
            if(!in_array($val,$array)){
                array_push($array,$val);
                echo $val.'<br>';
            }
        }
        */
    }

    public function testpost(Request $request) {
        $type='weixinweb';
        $token=123;
        $oauthUser=array('id'=>123);
        $data      = $request->all();
        $message = '';
        var_dump($data['password']);
        exit;
        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($v->fails()){
            /*
            $msg = ["custom-msg"=> [$v->errors()],];
            return response()->json($msg)->setStatusCode(422);
            */
            return back()->withInput()->withErrors([ 'msg' => $v->errors(),]);
        }
        $user=User::whereemail($data['email'])->first();
        if (empty($user)) {
            $message="用户不存在";
        } elseif ($user['password']!=bcrypt($data['password'])) {
            $message="密码不正确，请重试！";
        } elseif ($this->userServiceClass->getUserBindByTypeAndUserId($type, $user->id)) {
            $message="帐号已经绑定了该第三方网站的其他帐号，如需重新绑定，请先到账户设置中取消绑定！";
        } else {
            //执行绑定
            $this->userServiceClass->bindUser($type, $oauthUser['id'], $user->id, $token);
            $this->authenticateUser($user);
        }
        if($message){
            return back()->withInput()->withErrors([ 'msg' => $v->errors(),]);
            $msg = [
                "custom-msg"=> [$message],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        else{
            return response()->json(null);
        }
    }

     public function testnotify(){
         $squad_id=4;
         $send_time=date('Y-m-d H:i:s',time());
         $first='消息提醒';
         $keyword1='单元名称';
         $keyword2='模块名称';
         $keyword3='2015年7月5日 21:00';
         $keyword4='2015年7月12日 21:00';
         $keyword5='100分';
         $remark='';
         $url='http://www.baidu.com';
         //作业发布通知微信提醒，接受squad_id,first,keyword1,keyword2,keyword3,keyword4,keyword5,url,remark参数，除first,remark参数外，其余必填，请注意参数顺序，
         //$return=\App\Http\Controllers\Api\WechatNotifyController::homework_publish($squad_id,$first,$remark,$url,$send_time,$keyword1,$keyword2,$keyword3,$keyword4,$keyword5);

//        //作业催交提醒，接受squad_id,first,keyword1,keyword2,remark,url参数，除first,remark参数外，其余必填
//         $return=\App\Http\Controllers\Api\WechatNotifyController::homework_cuijiao($squad_id,$first,$remark,$url,$send_time,$keyword1,$keyword2);
//        //作业批阅提醒，接受squad_id,first,keyword1,keyword2,keyword3,remark,url参数，除first,remark参数外，其余必填
//         $return=\App\Http\Controllers\Api\WechatNotifyController::homework_piyue($squad_id,$first,$remark,$url,$send_time,$keyword1,$keyword2,$keyword3,$keyword4,$keyword5);
//        //预习通知，接受squad_id,first,keyword1,keyword2,keyword3,keyword4,keyword5,remark,url参数，除first,remark,keyword4,keyword5参数外，其余必填
//         $return=\App\Http\Controllers\Api\WechatNotifyController::yuxi($squad_id,$first,$remark,$url,$send_time,$keyword1,$keyword2,$keyword3,$keyword4,$keyword5);
//        //课堂评分提醒，接受squad_id,first,keyword1,keyword2,keyword3,keyword4,keyword5,remark,url参数，除first,remark,keyword5参数外参数外，其余必填
//         $return=\App\Http\Controllers\Api\WechatNotifyController::ketang_pingfen($squad_id,$first,$remark,$url,$send_time,$keyword1,$keyword2,$keyword3,$keyword4,$keyword5);
//        //评分分配提醒，接受squad_id,first,keyword1,keyword2,remark参数，除first,remark,keyword1,keyword2参数外，其余必填
         $return=\App\Http\Controllers\Api\WechatNotifyController::score_pingfen($squad_id,$first,$remark,$url,$send_time,$keyword1,$keyword2);
         print_r($return);
     }
}
