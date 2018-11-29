<?php

namespace App\Http\Controllers\Teacher;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Group;
use App\Models\Squad;
use App\Models\SquadStruct;
use App\Models\Student;
use App\Models\Score;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;
use App\Models\Notice;
use App\Models\NotifyUser;
use Illuminate\Support\Facades\Redis as Redis;
use App\Models\Option;
use App\Services\Plugin\PluginManager;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function welcome()
    {
        $user=\Auth::user();
        $school_id=$user->school_id;
        $teacher_id=Teacher::where('user_id',$user->id)->value('id');
        $squad_ids=Squad::where(['teacher_id'=>$teacher_id,'school_id'=>$school_id])->pluck('id');
        $assistant = \DB::table('squad_structs')
            ->where(['struct_id'=>$teacher_id,'type'=>2])
            ->count();
        // 获取教学方案、资源、用户数
        $count = [
            'plans' => Plan::where('school_id',$school_id)->count(),
            'cells' => Cell::where('school_id',$school_id)->count(),
            'modules' => Module::where('school_id',$school_id)->count(),
            'nodes' => Node::where('school_id',$school_id)->count(),
            'xmz_groups' => Group::wherein('squad_id',$squad_ids)->where('type',0)->count(),
            'ztz_groups' => Group::wherein('squad_id',$squad_ids)->where('type',1)->count(),
            'students' => SquadStruct::wherein('squad_id',$squad_ids)->where('type',1)->count(),
            'squads' => $squad_ids->count(),
        ];
        if(!empty($assistant)){
            $count['squads'] = $count['squads'] + $assistant;
        }
        $oUser=User::where('id',$user->id)->first(['name','email','avatar','mobile']);
        $oUser->avatar=getAvatar($oUser->avatar);
        $notices = Notice::where('is_show',1)->orderBy('id','desc')->take(5)->get(['id','title','view','send_time','created_at']);
        foreach($notices as $notice)
        {
            $notice->send_time = substr($notice->send_time,0,10);
        }
        if (view()->exists(session('mode').'.teacherplat.welcome')){
            return View(session('mode').'.teacherplat.welcome', compact('count','oUser','notices'));
        }else{
            return View('default.teacherplat.welcome', compact('count','oUser','notices'));
        }
    }

    public function index(PluginManager $plugins){
//        $installed = $plugins->getEnabledPlugins();
//        $id =  \Session::get('school_id');
//        $enbled = array();
//        Option::where('sid',$id)->where('option_name','plugins_enabled')->get()->each(function ($enble) use(&$enbled){
//            $arr = array();
//            $arr['created_at'] = $enble->created_at;
//            $arr['updated_at'] = $enble->updated_at;
//            $arr['end_time'] = $enble->end_time;
//            $enbled[$enble->option_value] = $arr;
//        });
//        return view('default.teacherplat.home-v2', compact('installed','id','enbled'));
        return view('default.teacherplat.home-v2');
    }

    public function index2()
    {
        $user=\Auth::user();
        $school_id=$user->school_id;
        $teacher_id=Teacher::where('user_id',$user->id)->value('id');
        $squad_ids=Squad::where(['teacher_id'=>$teacher_id,'school_id'=>$school_id])->pluck('id');
//        $assistant = \DB::table('squad_structs')
//            ->where(['struct_id'=>$teacher_id,'type'=>2])
//            ->count();
        // 获取教学方案、资源、用户数

        $schoolKey = "dataStatis:teacher:{$user->id}:".date('Ymd');
        if(Redis::exists($schoolKey)){
            $count = json_decode(Redis::get($schoolKey),true);
        }else{
            $count = [
                'plans' => Plan::where('school_id',$school_id)->count(),
                'cells' => Cell::where('school_id',$school_id)->count(),
                'modules' => Module::where('school_id',$school_id)->count(),
                'nodes' => Node::where('school_id',$school_id)->count(),
                'xmz_groups' => Group::wherein('squad_id',$squad_ids)->where('type',0)->count(),
                'ztz_groups' => Group::wherein('squad_id',$squad_ids)->where('type',1)->count(),
                'students' => SquadStruct::wherein('squad_id',$squad_ids)->where('type',1)->count(),
                'squads' => $squad_ids->count(),
                'homework'=>Score::where('teacher_id',$teacher_id)->where("student_status",">","0")->where("teacher_status","=","0")->count(),
            ];
        }

//        if(!empty($assistant)){
//             $count['squads'] = $count['squads'] + $assistant;
//        }
        $oUser=User::where('id',$user->id)->first(['name','email','avatar','mobile']);
        $oUser->avatar=getAvatar($oUser->avatar);
        $noticees = Notice::where('is_show',1)->count();

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

        //消息
        $notifys = NotifyUser::where('user_id',$user->id)->with('notify');
        $notifys->where('is_scan','=',0);
        $num['notify'] = $notifys->count();
        $notifys = $notifys->orderBy('notify_id','desc')->paginate(10);
        foreach($notifys as $val){
            if(isset($val->notify) && $val->notify->user_id>0){
                $val->sender_name=User::where('id',$val->notify->user_id)->value('name');
                $val->avatar=User::where('id',$val->notify->user_id)->value('avatar');
                $val->avatar=getAvatar($val->avatar);
                $val->title=$val->notify->title;
                $val->addtime=$val->notify->send_time;
            }
            else{
                $val->sender_name='';
                $val->title='';
                $val->addtime='';
            }
            unset($val->notify);
        }
        if (view()->exists(session('mode').'.teacherplat.home')){
            return View(session('mode').'.teacherplat.home', compact('count','oUser','notices','num','notifys','noticees'));
        }else{
            return View('default.teacherplat.home', compact('count','oUser','notices','num','notifys','noticees'));
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
