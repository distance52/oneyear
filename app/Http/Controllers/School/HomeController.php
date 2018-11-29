<?php

namespace App\Http\Controllers\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;
use App\Models\File;
use App\Models\User;
use App\Models\School;
use App\Models\Squad;
use App\Models\Notice;
use Illuminate\Support\Facades\Redis as Redis;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        return view('default.schoolplat.home-v2');
    }

    public function index2() {
        $user=\Auth::user();
        $school_id=$user->school_id;
        // 获取教学方案、资源、用户数
        $schoolKey = "dataStatis:school:{$school_id}:".date('Ymd');
        if(Redis::exists($schoolKey)){
            $count = json_decode(Redis::get($schoolKey),true);
        }else{
            $count = [
                'plans' => Plan::where('school_id',$school_id)->count(),
                'cells' => Cell::where('school_id',$school_id)->count(),
                'modules' => Module::where('school_id',$school_id)->count(),
                'nodes' => Node::where('school_id',$school_id)->count(),
                'files' => File::where('school_id',$school_id)->count(),
                'videos' => File::where('school_id',$school_id)->where('type',1)->count(),
                'pics' => File::where('school_id',$school_id)->where('type',3)->count(),
                'words' => File::where('school_id',$school_id)->where('type',6)->count(),
                'users' => User::where('school_id',$school_id)->count(),
                'teachers' => User::where('school_id',$school_id)->where('plat',2)->count(),
                'students' => User::where('school_id',$school_id)->where('plat',3)->count(),
            ];
        }

        $oUser=User::where('id',$user->id)->first(['name','email','avatar']);
        $oUser->avatar=getAvatar($oUser->avatar);
        
        $noticesKey = "platformNotices";
        if(Redis::exists($noticesKey)){
            $notices = json_decode(Redis::get($noticesKey),true);
        }else{
            $notices = Notice::where('is_show',1)->orderBy('id','desc')->take(5)->get(['id','title','view','send_time','created_at']);
            foreach($notices as $notice)
            {
                $notice->send_time = substr($notice->send_time,0,10);
            }
        }
        $oSchool=School::where('id',$school_id)->first(['server_user_id','start_time','end_time']);
        $server_user=User::where('id',$oSchool->server_user_id)->first(['name','email']);
        if(!empty($server_user)){
            $oSchool->server_user=$server_user?$server_user->name:'客服名字';
            $oSchool->server_email=$server_user?$server_user->email:'客服邮箱';
        }
        else{
            $oSchool->server_user='';
            $oSchool->server_email='';
        }
        $oPlans=Plan::where('school_id',$school_id)->pluck('name');
        $plan_name=implode(',',$oPlans->toArray());
		if(view()->exists(session('mode').'.schoolplat.home')){
			return View(session('mode').'.schoolplat.home', compact('count','oUser','notices','oSchool','plan_name'));
		}else{
			return View('default.schoolplat.home', compact('count','oUser','notices','oSchool','plan_name'));
		}
    }
}
