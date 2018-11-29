<?php

namespace App\Http\Controllers\Student;

use App\Models\PlanImGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Student;
use App\Models\NotifyUser;
use App\Models\MessagePeople;
use App\Models\SqlLog;
use App\Models\StudentPoint;
use App\Models\Score;
use App\Models\NodeSquad;
use App\Models\Teaching\Node;
use App\Models\Info;
use App\Models\School;
use App\Models\SquadStruct;
use App\Models\StudentFinalScore;
use Illuminate\Routing\UrlGenerator;
use App\Models\Notify;
use App\Models\UserBind;
use Illuminate\Support\Facades\Redis as Redis;
use GatewayClient\Gateway as Gateway;

class MyProfileController extends BaseController
{

    //账户管理首页
    public function index(Request $request,$squad_id){
        //Session::set('user_id',null);
        $oUser=User::where('id',$this->user_id)->first();
        $oStudent=Student::where('user_id',$this->user_id)->first();
        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(in_array($squad_id,$squads)){
            //$notify_count = NotifyUser::where('user_id',$this->user_id)->count();//总消息数
           // $notify_unread_count = NotifyUser::where('user_id',$this->user_id)->where('is_scan',0)->count();//总消息数
            $notify_count = NotifyUser::where('user_id',$this->student_id)->count();
            $notify_unread_count = NotifyUser::where('user_id',$this->student_id)->where('is_scan',1)->count();
            $oObj=array();
            $oObj['id']=$oUser->id;
            $oObj['name']=$oUser->name;
            $oObj['email']=$oUser->email;
            $oObj['mobile']=$oUser->mobile;
            $oObj['avatar']=getAvatar($oUser->avatar);
            $oObj['sno']=isset($oStudent->sno)?$oStudent->sno:'';
            $oObj['notify_count']=$notify_count;
            $oObj['notify_unread_count']=$notify_unread_count;
            //统计日常考评成绩
            $common_score=StudentPoint::where('student_id',$oStudent->id)->sum('score');
            //统计作业成绩
            $rank=School::where('id',$this->school_id)->value('score_rank');
            //统计这个班所有学生和国内的每一项的成绩
            $scores=StudentFinalScore::where('student_id',$this->student_id)->get();
            foreach($scores as $v){
                $score[$v['student_id'].'_'.$v['type']]=$v['score'];
            }
            //将各积分的比率转换为小数
            $rank=json_decode($rank,true);
            foreach($rank as &$v){
                $v=0.01*$v;
            }
            for($i=1;$i<=6;$i++){
                $oObj['score'.$i]=isset($score[$this->student_id.'_'.$i])?$score[$this->student_id.'_'.$i]:0;
            }
            if (view()->exists(session('mode').'.studentPlat.my.indexs')){
                return View(session('mode').'.studentPlat.my.indexs',compact('oObj','squad_id'));
            }else{
                return View('default.studentPlat.my.indexs',compact('oObj','squad_id'));
            }
        }else{
            return redirect('error')->with(['msg'=>'非法操作', 'href'=>app(UrlGenerator::class)->previous()]);
        }
    }
    public function indexs(Request $request){
        //Session::set('user_id',null);
        //dd($request->getSession()->all());
        $oUser=User::where('id',$this->user_id)->first();
        $oStudent=Student::where('user_id',$this->user_id)->first();
        //$notify_count = NotifyUser::where('user_id',$this->user_id)->count();//总消息数
	   // $notify_unread_count = NotifyUser::where('user_id',$this->user_id)->where('is_scan',0)->count();//总消息数
		$notify_count = NotifyUser::where('user_id',$this->student_id)->count();
		$notify_unread_count = NotifyUser::where('user_id',$this->student_id)->where('is_scan',1)->count();
        $oObj=array();
        $oObj['id']=$oUser->id;
        $oObj['name']=$oUser->name;
        $oObj['email']=$oUser->email;
        $oObj['mobile']=$oUser->mobile;
        $oObj['avatar']=getAvatar($oUser->avatar);
        $oObj['sno']=isset($oStudent->sno)?$oStudent->sno:'';
        $oObj['notify_count']=$notify_count;
        $oObj['notify_unread_count']=$notify_unread_count;
        //统计日常考评成绩
        $common_score=StudentPoint::where('student_id',$oStudent->id)->sum('score');
        //统计作业成绩
        $rank=School::where('id',$this->school_id)->value('score_rank');
        //统计这个班所有学生和国内的每一项的成绩
        $scores=StudentFinalScore::where('student_id',$this->student_id)->get();
        foreach($scores as $v){
            $score[$v['student_id'].'_'.$v['type']]=$v['score'];
        }
        //将各积分的比率转换为小数
        $rank=json_decode($rank,true);
        foreach($rank as &$v){
            $v=0.01*$v;
        }
        for($i=1;$i<=6;$i++){
            $oObj['score'.$i]=isset($score[$this->student_id.'_'.$i])?$score[$this->student_id.'_'.$i]:0;
        }
        if (view()->exists(session('mode').'.studentPlat.my.index')){
            return View(session('mode').'.studentPlat.my.index',compact('oObj'));
        }else{
            return View('default.studentPlat.my.index',compact('oObj'));
        }
    }


    //修改个人资料
    public function edit(){
        $oUser=User::where('id',$this->user_id)->first();
        $oStudent=Student::where('user_id',$this->user_id)->first();
        $oUser->avatar=getAvatar($oUser->avatar);
		if (view()->exists(session('mode').'.studentPlat.my.edit')){
			return View(session('mode').'.studentPlat.my.edit',compact('oUser','oStudent'));
		}else{
			return View('default.studentPlat.my.edit',compact('oUser','oStudent'));
		}
    }

    //修改个人资料提交
    public function editPost(Request $request){
        $oUser=User::where('id',$this->user_id)->first();
        $oStudent=Student::where('user_id',$this->user_id)->first();
        if ($request->hasFile('userphoto')) {
            if ($request->file('userphoto')->isValid()){
                $file = $request->file('userphoto');
                $file_name = time().str_random(6).$file->getClientOriginalName();
                \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                if(\Storage::disk('oss')->exists($file_name)) {
                    $oUser->avatar = $file_name;
                } else {
                    return back()->withInput()->withErrors(['msg' => '头像上传失败',]);
                }
            } else {
                return back()->withInput()->withErrors(['msg' => '头像上传失败',]);
            }
        }

        $oUser->name = $request->input('name');
        $oUser->save();
        $oStudent->academy=$request->input('academy');
        $oStudent->dept=$request->input('dept');
        $oStudent->major=$request->input('major');
        $oStudent->save();
        return redirect('/my/profiles');
    }

    //修改绑定手机
    public function changeMobile(Request $request){
		if (view()->exists(session('mode').'.studentPlat.my.changemobile')){
			return View(session('mode').'.studentPlat.my.changemobile');
		}else{
			return View('default.studentPlat.my.changemobile');
		}
    }
	
	//取消微信绑定
    public function cancelBind(Request $request){
        $oUser= \Auth::user();
        $user_id = $oUser->id;

        $bindinfo = UserBind::where(['toId'=>$user_id,'type'=>'weixin'])->first();
        $bind =  empty($bindinfo) ?  0 : 1;
        if($bind){
            $oBind = UserBind::where(['fromId'=>$bindinfo->fromId])->get();
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
		if (view()->exists(session('mode').'.studentPlat.my.cancelbind')){
			return View(session('mode').'.studentPlat.my.cancelbind',compact('bind','bind_list'));
		}else{
			return View('default.studentPlat.my.cancelbind',compact('bind','bind_list'));
		}
    }
    //修改密码
    public function changePwd(Request $request){
		if (view()->exists(session('mode').'.studentPlat.my.changepwd')){
			return View(session('mode').'.studentPlat.my.changepwd');
		}else{
			return View('default.studentPlat.my.changepwd');
		}
    }

    //修改密码
    public function doChangePwd(Request $request){
        $oUser=User::where('id',$this->user_id)->first();
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

    //上传头像
    public function uploadAvatar(Request $request){
        $oUser=User::where('id',$this->user_id)->first();
        if ($request->hasFile('avatar')) {
            if ($request->file('avatar')->isValid()){
                $file = $request->file('avatar');
                $file_name = time().str_random(6).$file->getClientOriginalName();
                \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                if(\Storage::disk('oss')->exists($file_name)) {
                    $oUser->avatar = $file_name;
                    $oUser->save();
                    $avatar= \AliyunOSS::getUrl($oUser->avatar, $expire = new \DateTime("+1 day"), $bucket = config('filesystems.disks.oss.bucket'));
                    return response()->json(array('avatar'=>$avatar));
                } else {
                    $msg = [
                        "custom-msg"=> ["头像保存失败"],
                    ];
                    return response()->json($msg)->setStatusCode(422);
                }
            } else {
                $msg = [
                    "custom-msg"=> ["头像保存失败"],
                ];
                return response()->json($msg)->setStatusCode(422);
            }
        }
        else{

        }
    }

    //我的疑问
    public function myFaq(){
        
        $model=new NodeQa();
        $oObjs=$model->where(['user_id'=>$this->user_id,'parent_id'=>0])->paginate(10);
        foreach($oObjs as &$val){
            $val->replyCount=$model->replyCount($val->id);
        }
        return response()->json($oObjs);
    }

    //我的回答
    public function myReply($id){
        $model=new NodeQa();
        $oObjs=$model->where(['parent_id'=>$id,'user_id'=>$this->user_id,'is_black'=>0])->paginate(10);
        return response()->json($oObjs);
    }

    public function logoutWechat(Request $request){
	if($request->session()->has('user_id','wechat_id')) {
        Session::put('user_id',null);
        Session::put('wechat_id',null);
	}
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
        return redirect(env('APP_URL').'/login');
    }

    // 我的预习
    public function preview($squad_id) {
        // 获取我的列表
        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(in_array($squad_id,$squads)){
                $oNodeSquad = NodeSquad
                ::where('squad_id', $squad_id)
                ->where('type',3)
                ->orderBy('id','desc')
                ->get()
                ->map(function ($item, $key) {
                    $a=$item->node = Node::find($item->node_id);
                    $b= $item->info = Info::find($a->info_id);
                    $item->score = 0;
                      switch ($b->type) {
                        case '1':
                            $item->score = 2;
                            break;
                        case '3':
                            $item->score = 10;
                            break;
                        case '4':
                            $item->score = 5;
                            break;
                    }
                  
                    $item->sign =  $b->sign;
                    return $item;
                });
            if (view()->exists(session('mode').'.studentPlat.preview.index')){
                return View(session('mode').'.studentPlat.preview.index',compact('oNodeSquad','squad_id'));
            }else{
                return View('default.studentPlat.preview.index',compact('oNodeSquad','squad_id'));
            }
        }else{
            return redirect('error')->with(['msg'=>'非法操作', 'href'=>app(UrlGenerator::class)->previous()]);
        }
        
    }
	
        // 我的预习
    public function previews() {
        // 获取我的列表
        $oUser = \Auth::user();
        // dd($oUser);
        $oSquad = Student::where('user_id',$oUser->id)->pluck('squad_id');
        // $oSquad = $oUser->student->squads;
        // dd($oSquad);
        $oNodeSquad = NodeSquad
            ::where('squad_id', $oSquad)
            ->where('type',3)
            ->orderBy('id','desc')
            ->get()
            ->map(function ($item, $key) {
                $a=$item->node = Node::find($item->node_id);
                $b= $item->info = Info::find($a->info_id);
                $item->score = 0;
                if($b){
                    switch ($b->type) {
                    case '1':
                        $item->score = 2;
                        break;
                    case '3':
                        $item->score = 10;
                        break;
                    case '4':
                        $item->score = 5;
                        break;
                    }
                    $item->sign =  $b->sign;
                }
                return $item;
            });
        if (view()->exists(session('mode').'.studentPlat.preview.index')){
            return View(session('mode').'.studentPlat.preview.index',compact('oNodeSquad'));
        }else{
            return View('default.studentPlat.preview.index',compact('oNodeSquad'));
        }
    }

     //我的签到
    public function signs($squad_id) {
        // 获取我的列表
        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
       $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(in_array($squad_id,$squads)){
            $codelist = \DB::table('unique_code')->where('squad_id',$squad_id)->where('type','1')->pluck('time');
            $oObjs1 = \DB::table('sign_log')->where('student_id',$oStudent->id)->where('squad_id',$squad_id)->orderBy('time','desc')->get();
            $oObjs2 = [];
            foreach($codelist as $code)
            {
                $studentlist = [];
                $studentlist = \DB::table('sign_log')->where('time','>',$code)
                                                ->where('time','<',strtotime( '120 Minute',$code))
                                                ->pluck('student_id')->toArray();
                if(!in_array($oStudent->id,$studentlist)){
                    $oObjs2[] = $code;
                }
            }
            if (view()->exists(session('mode').'.studentPlat.scan.list')){
                return View(session('mode').'.studentPlat.scan.list',compact("oObjs1","oObjs2",'squad_id'));
            }else{
                return View('default.studentPlat.scan.list',compact("oObjs1","oObjs2",'squad_id'));
            }
        }else{
            return redirect('error')->with(['msg'=>'非法操作', 'href'=>app(UrlGenerator::class)->previous()]); 
        }
		//$squad_id = Student::where('user_id',$oUser->id)->pluck('squad_id');
		
    }

	public function sign($code)
	{
        $oUser = \Auth::user();
        $oObj = \DB::table('unique_code')->where('code',$code)->orderBy('time','desc')->first();
        if (!$oObj){
            return back()->withErrors(['msg' => '非法参数']);
        }

        $oStudent = Student::where('user_id',$oUser->id)->first();
        $group_id = $oObj->squad_id;
        $oGroup = PlanImGroup::whereId($group_id)->first();

        if($oGroup->id != $group_id){
            return back()->withErrors(['msg' => '非本班级学生']);
        }

        $key = "group_sign:{$group_id}";
        $time = time();
        $s = Redis::exists($key);
        if(!$s){
            return back()->withErrors(['msg' => '请联系教师发起签到']);
//            $list = [];
//            $list[$oStudent->id] =  $time;
//            Redis::set($key,json_encode($list));
        }
        $lists = json_decode(Redis::get($key),true);
        if(!in_array($oStudent->id, $lists )){
            return back()->withErrors(['msg' => '你已经签过了']);
        }
        if(strtotime( '-6 Minute') > $oObj->time){
            return back()->withErrors(['msg' => '请求超时']);
        }else{

            $data['time'] = $time;
            $data['student_id'] = $oStudent->id;
            $data['squad_id'] =  $group_id;
            // $data['group_id'] = $group_id;
            \DB::table('sign_log')->insert($data);

//            unset($lists[$oUser->id]);
            foreach ($lists as $k =>$v){
                if ($v == $oStudent->id){
                    array_splice($lists, $k,1);
                    break;
                }
            }

            Redis::set($key,json_encode($lists));
            Redis::expire($key, 3600*12);

            $tid = $oGroup->master_id;
            $message = array(
                'type'     => 'group_sign',
                'to'    => $group_id ,
                'from'  => $oStudent->user_id,
                'timestamp'=> date("H:i:s",time()),
            );
            Gateway::sendToUid($tid,json_encode($message));

            return redirect('/course/study/scan')->withErrors(['msg' => '签到成功',]);
        }
//		$oUser = \Auth::user();
//		$oObj = \DB::table('unique_code')->where('code',$code)->where('type',1)->orderBy('time','desc')->first();
//		$oStuent = \DB::table('students')->where('user_id',$oUser->id)->first();
//		if($oStuent->squad_id == $oObj->squad_id){
//			$student_log = \DB::table('sign_log')->where('squad_id',$oObj->squad_id)
//												 ->where('student_id',$oStudent->id)
//												 ->where('time','>',$oObj->time)
//												 ->first();
//			if($student_log){
//				return back()->withErrors(['msg' => '你已经签过了',]);
//			}else{
//				if(strtotime( '-6 Minute') > $oObj->time){
//					return back()->withErrors(['msg' => '请求超时',]);
//				}else{
//
//					$data['time'] = time();
//					$data['student_id'] = $oStudent->id;
//					$data['squad_id'] = $oObj->squad_id;
//					\DB::table('sign_log')->insert($data);
//					return back()->withErrors(['msg' => '签到成功',]);
//				}
//			}
//
//		}else{
//			return back()->withErrors(['msg' => '你不是我们班的',]);
//		}
    }


    //我的投票
    public function votes() {
        // 获取我的列表
        $oUser = \Auth::user();

        $votelist = \DB::table('ext_wj_result')->where('user_id',$oUser->id)->pluck('wq_id'); 

        $oObjs1 = \DB::table('ext_wj_examp')->whereIN('id',$votelist)->get();
        // dd($oObjs1);
        // $oObjs2 = \DB::table('ext_wj_examp')->whereIN('wq_id',$votelist)->orderby("created_at","desc")->get();
        // $oObjs2 = [];
        // foreach($codelist as $code)
        // {
        //     $studentlist = [];
        //     $studentlist = \DB::table('ext_wj_result')->where('time','>',$code)
        //                                     ->where('time','<',strtotime( '120 Minute',$code))
        //                                     ->pluck('student_id');
        //     if(!in_array($oStudent->user_id,$studentlist)){
        //         $oObjs2[] = $code;
        //     }
        // }
        if (view()->exists(session('mode').'.studentPlat.vote.list')){
            return View(session('mode').'.studentPlat.vote.list',compact("oObjs1"));
        }else{
            return View('default.studentPlat.vote.list',compact("oObjs1"));
        }
    }


}
