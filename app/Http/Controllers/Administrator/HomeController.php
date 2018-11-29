<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
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

class HomeController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // 获取教学方案、资源、用户数
        $adminKey = "dataStatis:admin:".date('Ymd');
        if(Redis::exists($adminKey)){
            $arr = json_decode(Redis::get($adminKey),true);
            $count = $arr['count'];
            $news = $arr['news'];
        }else{
            $count = [
                'plans' => Plan::count(),
                'cells' => Cell::count(),
                'modules' => Module::count(),
                'nodes' => Node::count(),
                'files' => File::count(),
                'videos' => File::where('type',1)->count(),
                'pics' => File::where('type',3)->count(),
                'words' => File::where('type',6)->count(),
                'users' => User::count(),
                'teachers' => User::where('plat',2)->count(),
                'students' => User::where('plat',3)->count(),
                'schools' => School::count(),
            ];
            $news = [
                'teachers' => User::where('plat',2)->orderBy('id','desc')->take(5)->with('school')->get(),
                'num1' => User::where('plat',2)->with('school')->count(),
                'students' => User::where('plat',3)->orderBy('id','desc')->take(5)->with('school')->get(),
                'num2' => User::where('plat',3)->with('school')->count(),
                'squads' => Squad::orderBy('id','desc')->take(5)->with('school')->get(),
                'num3' => Squad::with('school')->count(),
            ];
        }
//        dd($news['squads']);
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
       
        //
        
		if (view()->exists(session('mode').'.adminPlat.home')){
			return View(session('mode').'.adminPlat.home', compact('count','news','notices'));
		}else{
			return View('default.adminPlat.home', compact('count','news','notices'));
		}
    }
}
